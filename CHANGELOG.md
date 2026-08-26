# Eliminaciones

- Remoción de módulo de chat interno obsoleto.
- Remoción de módulo de presentaciones de capacitación obsoleto.
    
# Sin publicar

## CÓMO ACTUALIZAR — LEER ANTES DE FUSIONAR

Esta versión **renormaliza los finales de línea de todo el repositorio**: 1.126 archivos,
**cero cambio de contenido** (verificado con `git diff --ignore-cr-at-eol`, que sale vacío).
El motivo está en el commit `0ac751b9`.

**Si fusionas sin más, cada archivo que tu despliegue haya tocado dará conflicto.** No por
el código: por los finales de línea.

```bash
git merge -X renormalize <rama-del-framework>
```

o, si prefieres dejarlo puesto de una vez:

```bash
git config merge.renormalize true
```

**Comprobado en una fusión de prueba**, no supuesto: un despliegue con cambios propios sobre
archivos afectados da **2 conflictos sin la opción y 0 con ella**, y **conserva sus cambios
locales** en ambos archivos.

Y una vez fusionado, para que la arqueología no se pierda:

```bash
git config blame.ignoreRevsFile .git-blame-ignore-revs
```

Sin eso, `OrganizationMapper.php` y `PublicationsController.php` atribuyen **todas** sus
líneas al commit de renormalización — comprobado: de 1 commit distinto en 600 líneas se pasa
a la historia real al activarlo.

## Corregido — el enlace delegado se sustituía dejando un instante sin enlace

`ServerStatics::createDynamicSymlink()` borraba el enlace y lo volvía a crear. **Entre las dos
llamadas la ruta no existía**, y una petición que cayera dentro de esa ventana recibía un **404**;
además, un `getSymbolicLink()` concurrente devolvía `null` y la vista reenviaba a PHP, que repetía
la operación. No se reproduce en local, y este framework se clona.

Ahora el enlace se crea con nombre temporal y se publica con `rename()`, que es **atómico**: la
ruta apunta al enlace viejo o al nuevo, **nunca a nada**.

De paso, dos defectos que salieron al reescribirlo:

- **Un enlace roto no se reparaba nunca.** `file_exists()` sigue el enlace, así que uno apuntando a
  la nada parecía ausente; luego `symlink()` fallaba porque la ruta sí estaba ocupada, y se quedaba
  rota indefinidamente.
- **La `umask` se quedaba en `0`** cuando el recurso no existía: ese `return` estaba fuera del
  `try`, así que no pasaba por el `finally` que la restauraba.

El apartado a `.backup` de un archivo real que ocupe esa ruta **se queda, y ahora hace más falta**:
medido, `rename()` de un enlace encima de un archivo real lo sustituye y **el contenido se pierde
sin rastro**.

Puerta nueva: `bin/cli unit-tests:core/symlink-no-window`.

## Herramientas — `verify-integrity` gana una decimoséptima comprobación: las versiones de los paquetes

Compara la versión **instalada** de cada paquete `piecesphp/*` en `src/composer.lock` con la
**última etiquetada** en su repositorio hermano, y lo dice cuando difieren.

**No falla**: una etiqueta preparada y todavía sin empujar es un estado legítimo. Lo que no puede
es ser invisible — `v3.8.1` de `piecesphp/database` estuvo etiquetada y sin instalar durante un
bloque entero sin que nada lo dijera. Lo único que sí falla es que **no se pueda leer
`composer.lock`**, porque entonces la comprobación no miró nada.

Las etiquetas se ordenan **por versión y no alfabéticamente**, que es donde `v3.10.0` contra
`v3.9.0` se equivoca. Si el paquete no está clonado al lado, lo dice: no aprueba en silencio.

## Herramientas — `statics/server-delegated/` queda declarado como estado volátil

Servir un estático de módulo **crea un enlace simbólico** en `src/statics/server-delegated/`. Es
deliberado: los assets de cada módulo se sirven desde una ruta estable en vez de las rutas
entreveradas de cada módulo, y el enlace se crea **al servir y no al desplegar** porque un enlace
creado en el despliegue deja fuera cualquier asset que aparezca después.

Medido antes de declararlo: sobre 33 recursos sin enlace, la primera pasada crea 32 y **la segunda
crea cero**; y dos recorridos completos de 205 rutas con sus assets no crean ni rehacen ninguno,
porque en cuanto el enlace existe la vista emite su URL y lo sirve el servidor web sin pasar por
PHP.

**Cuidado si mides con esto**: sobre la ruta PHP la escritura **no es condicional** —`unlink` más
`symlink` en cada petición—, así que «una vez por recurso» describe lo que hace la aplicación, no
lo que hace el código.

**Y dos cosas anotadas sin arreglar**: el directorio **crece sin límite** —nada poda los enlaces de
un módulo retirado— y, si en esa ruta hay un archivo real en vez de un enlace, se **renombra a
`.backup` en silencio** y nadie limpia esos `.backup` jamás.

La regla 3 de `files/dev/volatile-state.json` decía «LA LISTA SOLO PUEDE ENCOGER». Ahora **encoge
siempre que se pueda**, y para crecer la entrada nueva tiene que traer la medición, el propósito
escrito y la fecha del hallazgo que la motivó.

## Documentación — la memoria de un agente es una caché del registro

`CLAUDE.md` gana una novena regla: lo que un agente guarde en su memoria persistente **solo puede
ser algo que ya viva en `.agents/context/`**, más el puntero a su sección. Si la memoria contiene
algo que el registro no tiene, son dos verdades sin puerta entre ellas, y eso **es el hallazgo**:
se resuelve subiéndolo al registro.

## Herramientas — los recorredores no pedían ni un `.css` ni un `.js`

`bin/walk-routes` dice en su encabezado que pide «TODOS los assets que aparecen en las páginas
visitadas». **No lo hacía**: su extractor solo aceptaba comillas dobles, y los ayudantes de assets
del framework emiten comillas simples. Sobre `/admin/`: 73 enlaces entre comillas dobles con
**cero** `.css`/`.js`, y 48 entre comillas simples con **los 48**.

Arreglado en los dos recorredores. Y `bin/walk-attribute` **pide ahora los estáticos de cada
vista antes de fotografiar**, porque servir un estático escribe:
`ServerStatics::createDynamicSymlink()` hace `mkdir`, `unlink` y `symlink` al servir. Con el
extractor arreglado aparecieron **61 escrituras** que la foto no veía.

Se añaden `--no-assets` y `--skipped`.

**Aviso para quien mida con estas herramientas**: el comparador de fotos **no ve que un enlace
simbólico se recree**, porque `mtime` y el hash siguen el enlace hasta el destino, que no cambia.
Solo se ve el enlace que no existía. Y `bin/cli db-restore` restaura la base, **no el árbol**: una
escritura de archivo que ya ocurrió no vuelve a ocurrir.

## Herramientas — las vistas de formulario vuelven a la pasada de recorrido

`files/dev/forbidden-routes.json` vetaba las 34 rutas `-forms-add` / `-forms-edit`. **Son GET y no
escriben** —el propio archivo lo reconocía—, y son justo donde vive el defecto que la pasada
busca: abrir un formulario que crea una fila. **Veto derogado el 2026-08-26.**

De paso, dos falsos positivos del patrón `/actions`: `actions-logs-admin-list` y
`actions-logs-admin-datatables`, que son un listado de puro leer.

Quitar los patrones no bastaba —la comparación es por subcadena y `-add` casa con `-forms-add`—,
así que el archivo gana un bloque **`allow`** que gana sobre `patterns`, con la razón escrita en
cada entrada. `bin/cli verify-integrity` falla si una excepción libera una ruta que no sea GET, si
no libera ninguna, o si no declara su razón.

## Herramientas — `bin/walk-attribute` dice su propia cobertura

**Una ruta que responde 4xx o 5xx no llegó al código que podría escribir**, así que su «sin
cambios» no significa nada. Hasta ahora se contaba junto a las que sí se ejercitaron, y el
recorrido informaba en verde. Sobre el mismo universo, el antes y el después:

```
antes:  186 rutas pedidas, 1 escriben, 0 diferencias NO declaradas          (salida 0)
ahora:  186 rutas pedidas, 136 EJERCITADAS, 50 con error
        1 escriben, 0 diferencias NO declaradas
        166 omitidas antes de pedir, con su razón                           (salida 1)
```

La pasada sale con **código 1** también por cobertura parcial, no solo por diferencias sin
declarar, y `--skipped` lista las omitidas con su motivo.

## Corregido — dos caminos de LECTURA devolvían 500

**Los endpoints de DataTables fallaban ante cualquier petición sin los parámetros que envía
DataTables.** Un enlace pegado en el navegador, un monitor o un rastreador recibían un 500:
`generateHaving(): Argument #2 ($columns) must be of type array, null given`. Eran **23 de las 24
rutas** de este tipo.

El valor por defecto de `columns` era `null` y el parámetro exige `array`. Ahora es `[]`, que es
lo correcto: **`columns` solo se lee cuando hay término de búsqueda**, así que ausente y vacío
significan lo mismo. **Para DataTables no cambia nada** —siempre manda el parámetro—; lo que
cambia es que la ruta deja de reventar sin él.

`locations-points-datatables` sigue devolviendo 404 sin cabecera `X-Requested-With`: es el único
de los 24 que se defiende, y es deliberado.

**El formulario de países abortaba si algún país no tenía región.** `CountryMapper` usaba ese
nombre nulo como índice de array; en PHP 8.4+ eso es una deprecación, y en este proyecto las
deprecaciones abortan la petición. Afectaba a alta **y** edición de países. Ahora se descarta la
fila sin nombre.

Los dos quedan fijados por `bin/cli unit-tests:core/read-paths-survive`.

## AVISO PARA DESPLIEGUES EXISTENTES — tus copias no traían las rutinas almacenadas

**Un volcado hecho con `bin/cli db-backup` de una versión anterior a esta no incluye
`DROP FUNCTION IF EXISTS` antes de crear las rutinas.** Las tablas sí iban con `DROP`+`CREATE`;
las funciones y procedimientos no.

**Qué pasa al restaurarlo:**

- Sobre una base que **ya tiene** esas rutinas, el `CREATE FUNCTION` falla con
  `1304 … already exists` y la rutina se queda **como estaba antes**, no como decía la copia.
- Sobre una base **nueva** entra bien la primera vez, pero la copia **no es reaplicable**: la
  segunda restauración falla.

**Y `bin/cli db-restore` no sabía leer esos bloques.** Partía el archivo por `;` sin entender
`DELIMITER`, así que el cuerpo de cada rutina —que lleva `;` dentro— llegaba a MySQL troceado.
En un volcado de este proyecto eran **18 sentencias fallidas de 130**.

**Qué hacer si tienes copias antiguas:** restáuralas con el cliente de MySQL, que sí entiende
`DELIMITER`, y vuelve a generar la copia con esta versión.

```bash
mysql -u <usuario> -p <base> < dumps/<archivo>.sql
```

**Desde esta versión** el volcado trae el `DROP` de las rutinas, la restauración entiende
`DELIMITER`, cadenas, identificadores y comentarios, y **`unit-tests:core/db-restore` restaura un
volcado producido por `db-backup`** —no uno fabricado por la propia prueba— comprobando que las
rutinas, las tablas y las vistas llegan.

`db-restore` **ignora y anuncia** `USE`, `CREATE DATABASE` y `DROP DATABASE`: eligen la base por
su cuenta y anularían el parámetro `database=`.

## AVISO PARA DESPLIEGUES EXISTENTES — TUS COPIAS DE SEGURIDAD NO RESTAURAN

**Si tienes copias hechas con `bin/cli db-backup` de una versión anterior a esta, no sirven
tal cual: restaurarlas deja a TODOS los usuarios sin poder entrar.** No es una sospecha; está
medido de punta a punta —volcado, restaurado en una base de usar y tirar, intento de login—.

**Por qué**: la exportación cifraba la columna `password` y **nada la descifraba al restaurar**,
así que en la base restaurada `password_verify()` recibe un hash cifrado y devuelve `false`
siempre.

### LOS DATOS NO ESTÁN PERDIDOS. Así se recuperan

El cifrado es reversible con la clave literal que se usaba. Comprobado: devuelve el hash
`$2y$…` exacto, byte a byte.

```php
$hashReal = PiecesPHP\Core\BaseHashEncryption::decrypt($valorDelVolcado, 'ENCRYPTION_KEY');
```

**Procedimiento:**

1. Restaura la copia como siempre: `mysql -u <usuario> -p <base> < dumps/<archivo>.sql`
2. Recorre `pcsphp_users` y sustituye cada `password` por su `decrypt(...)` con esa clave.
   Se puede hacer también sobre el `.sql` antes de cargarlo, si prefieres no tocar la base.
3. Comprueba con un usuario que conozcas: `password_verify('<su contraseña>', $hashReal)`
   tiene que devolver `true`.

**Las copias hechas desde esta versión no necesitan nada de esto**, y
`bin/cli unit-tests:core/db-backup-round-trip` comprueba el viaje entero en cada ejecución
—exportar, restaurar en una base de usar y tirar, y entrar— para que no vuelva a pasar.

## AVISO PARA DESPLIEGUES EXISTENTES — los emoji NO se están guardando (arreglado en el paquete)

**Medido, no supuesto**: un emoji escrito por el ORM en una columna de texto se guarda como
`?`. `HEX()` sobre lo guardado devuelve `3F` donde PHP mandó `F09F9880`, y `save()` devuelve
`true` mientras ocurre.

La causa no está en las tablas —**189 de 193 columnas de texto ya son `utf8mb4`**— sino en la
conexión: `piecesphp/database` ejecuta `SET CHARACTER SET`, que fija `character_set_client` y
`character_set_results` pero deja `character_set_connection` en el juego **de la base de
datos** (`utf8mb3`). `SET NAMES` es el que fija los tres. El `charset = 'utf8mb4'` de
`config/database.php` es correcto y queda anulado ahí.

**Arreglado en `piecesphp/database` v3.2.1**, que cambia esa sentencia por `SET NAMES`.
Comprobado de punta a punta escribiendo por el ORM: los bytes vuelven idénticos.

**Antes de actualizar el paquete, mide tu propia base.** Con la conexión en `utf8mb4`, todo
camino que hoy escriba **bytes que no son UTF-8** en una columna de texto —imágenes crudas, por
ejemplo— pasa de corromperse en silencio a **fallar con el error 1366**. Es mejor
comportamiento, pero es un cambio de comportamiento.

```bash
bin/cli scan-invalid-utf8     # qué columnas de texto tienen hoy valores no-UTF-8
```

Si sale vacío, actualiza sin más. **En la base de desarrollo de este repositorio sale vacío,
pero eso no dice nada de la tuya: son 89 filas en total y 22 de las 36 tablas están vacías.**

**Y recomendado aparte, para instalaciones existentes**, porque la base se creó sin juego
explícito y quedó en `utf8mb3`:

```sql
ALTER DATABASE `tu_base` CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
```

Con v3.2.1 puesto no hace falta —`SET NAMES` manda sobre el valor por defecto—, pero sin él
cualquier cosa que lea ese valor por defecto sigue heredando `utf8mb3`. Ver T28bis y T37 en
`.agents/context/18-siguientes-ventanas.md`.

> **TERCERA PATA, cerrada en v3.5.0.** Faltaba una: `SchemeCreator` emitía
> `DEFAULT CHARSET=utf8` **escrito a fuego**, así que **una tabla recién generada nacía en
> `utf8mb3`** por mucho que la conexión fuera `utf8mb4`. Las tres patas —conexión, valor por
> defecto de la base y DDL generado— eran **independientes**, y arreglar una no arreglaba las
> otras. Las tablas ya creadas no cambian.

## AVISO PARA DESPLIEGUES EXISTENTES

**Versiones afectadas: todas hasta la `v7.1.0` incluida.** El framework es una plantilla que
se clona, así que este defecto **existe en cualquier despliegue anterior a esta entrada**.
No se puede corregir a distancia; quien actualice debe saber qué está corrigiendo.

**Qué es:** un **defecto de diseño con escritura no autenticada acotada.** Comprobar
credenciales en el login insertaba filas en `pcsphp_users_otp_secrets`, generando de paso un
secreto TOTP, sin que nadie se hubiera autenticado. La causa es que
`UserDataPackage::__construct()` llamaba a un buscador que creaba el registro al leerlo.

**Qué NO es:** no hay toma de cuentas. El secreto pregenerado **nunca llega a ser una
credencial viva**, porque activar el 2FA lo regenera. Tampoco hay crecimiento sin límite: como
mucho una fila por usuario y método.

**Qué implica en la práctica:**

- Filas y secretos TOTP creados para cuentas que nunca pidieron 2FA.
- Una primitiva de escritura alcanzable sin autenticar, acotada pero real.
- Un canal de enumeración de usuarios **débil**: el estado de la base cambia solo si el
  nombre existe. En la mayoría de despliegues estará tapado, porque una rutina de relleno que
  corría en cada petición ya había creado todas las filas.

**Al actualizar:** las filas existentes son inertes y **no hace falta purgarlas**. Vaciar la
columna `secret` de las filas `TOTP` con `twoAuthFactor = 'DISABLED'` es higiene opcional, de
prioridad baja. **Las filas `ONE_USE_CODE` no se tocan**: pueden sostener códigos vigentes;
mirar `maxDate` antes de nada.

**Antes de actualizar**, `bin/cli scan-invalid-utf8` avisa de otra cosa que sí puede romper:
desde esta versión `json_encode()` lanza en vez de devolver `false`, así que un texto con
UTF-8 inválido en base de datos pasa de servir un dato ligeramente mal a cortar la petición.

## Cambios que rompen compatibilidad — `mapbox` se fija por versión exacta y `v2.6.0` se retira

El alias `mapbox-v3.4.0` estaba fijado a `^3.4.0`: **el nombre fingía fijar y el acento hacía lo
contrario**. Hoy resolvía a 3.19.0 —lo desplegado— mientras `node_modules` ya traía 3.21.0, y el
contenido se movía solo en cada `npm install` sin que nada lo dijera.

- `mapbox-v3.4.0` (`^3.4.0`) pasa a **`mapbox-v3.19.0` (`3.19.0`, exacta)**, y la carpeta
  `statics/plugins/mapbox/v3.4.0/` a **`v3.19.0/`**. Se fija en **lo que ya estaba desplegado**:
  no cambia ni un byte de lo que se sirve. Subir a 3.21.0 es otra decisión.
- `mapbox-geocoder-v2.3.0` pasa a versión exacta. No había derivado, pero podía.
- **`mapbox-v2.6.0` se retira entera** —carpeta y alias—: no la referenciaba nadie, y eran 1 MB
  versionados.

**Después de actualizar hace falta `npm install`**: los archivos de bloqueo aún nombran el alias
viejo y `bin/node/copyDependencies.php` avisa —ahora sale con código 1— si no encuentra el origen.

Y ese guion **ya no copia callando**: compara `sha1` antes de sobrescribir y dice qué va a cambiar.
Un cambio de contenido ahí es un cambio de librería, y el mensaje del commit tiene que decirlo.

## Corregido — un guardado sin cambios ya no reabre un rechazo en NINGÚN módulo

El arreglo anterior hacía que `updated` solo saltara si la base decía que cambió una fila, y eso
**no bastaba en tres de los cuatro tipos con aprobaciones**: sus mappers sellan `updatedAt` y
`modifiedBy` en cada guardado, así que la fila cambiaba de verdad.

Ahora el escuchador pregunta **qué** campos cambiaron y los compara con los que el manejador
declara como sellos de auditoría. **La declaración la exige la interfaz**: un manejador nuevo que
no la haga no compila, mientras que una lista central se quedaría corta en silencio.

**El escuchador conserva su intención**: reabrir un rechazo al editar sigue pasando, y hay una
comprobación que lo demuestra.

**Requiere `piecesphp/database` >= 3.8.0.** Con una versión anterior se mantiene la conducta
anterior y la suite `unit-tests:core/updated-event` **falla** para decirlo.

## Herramientas — cada suite declara qué hace fuera de sí misma

Correr las puertas hacía una petición a un servicio de terceros, y lo único que impedía que
mandara correos era que una suite no llevaba el prefijo `core/`. Eso no es una guarda: es un
accidente de nombre.

Ahora cada suite declara sus efectos con `setEffects()` —`network`, `email`, `database`, `files`,
o `none`—. `bin/cli gates` **no corre** las que salen a la red o mandan correo salvo que se pida
con `with=external`, y **una suite sin declarar no se corre y cuenta como fallo**: el estado por
defecto es «no sé qué hace esto».

De paso, dos suites salen del limbo: `core/database-exporter` y `core/helpers-directories` ya
imprimen su veredicto. La segunda además **devolvía éxito pase lo que pase** mientras imprimía
`[FALLÓ]` por pantalla.

## Herramientas — el análisis estático pasa a mirar el repositorio entero

`paths` apuntaba a `src/app` y `src/index.php`: **802 de los 835 archivos PHP versionados**. Ahora
apunta a la raíz, y lo que queda fuera son **7 exclusiones declaradas con su razón** en
`files/dev/phpstan-universe.json` —todas de código de terceros o regenerado—. **El código nuevo
nace analizado**; antes nacía invisible.

La cifra de referencia sube de **859 a 889**, y **no es una regresión**: los 859 del universo viejo
no se mueven ni en un error, y los 30 nuevos vienen de 5 de los 11 archivos que entran. El
trinquete gana un tercer término para poder declararlo —`+ N destapados`— sin que una regresión
pueda disfrazarse de lo mismo.

**Las cifras de PHPStan anteriores a esta versión no son comparables con las siguientes**: no
cuentan sobre el mismo conjunto de archivos.

## Herramientas — `verify-integrity` gana una decimotercera comprobación

El análisis estático mira `src/app` y `src/index.php`: **802 de los 835 archivos PHP versionados**.
De los 33 restantes, 22 son `src/adminer` —de terceros, excluido a propósito en el neon— y **11 no
estaban declarados en ninguna parte**, entre ellos las herramientas de `bin/` y el corredor de
tareas de Composer.

Un archivo fuera del árbol analizado no sube la cifra de errores: **la deja igual de verde midiendo
menos**. Ahora `files/dev/phpstan-universe.json` declara qué queda fuera y por qué, y la
comprobación falla si aparece PHP en un sitio que nadie declaró.

## Corregido — guardar sin cambiar nada reabría un elemento rechazado

El evento `updated` se despachaba cuando `parent::update()` devolvía `true`, y eso significa «la
sentencia corrió», no «cambió una fila». Consecuencia: abrir un elemento **rechazado**, guardarlo
sin tocar nada y verlo volver a la cola de aprobación como pendiente.

Ahora `updated` solo se despacha si la base dice que cambió algo. **El escuchador no se toca**:
reabrir un rechazo al editar es intención, y se conserva —hay una comprobación que lo demuestra,
no solo que no lo rompe—.

> **Lo que este arreglo NO cura, y conviene saberlo**: un mapper que sella `updatedAt` en su
> propio `update()` **cambia la fila él mismo**, así que para él «guardar sin tocar nada» sigue
> siendo un cambio real. Son 3 de los 4 tipos con aprobaciones y 16 mappers en total. Medido, no
> supuesto.

**Requiere `piecesphp/database` >= 3.7.0.** Con una versión anterior se mantiene la conducta de
siempre y la suite `unit-tests:core/updated-event` **falla** para decirlo: no se omite en silencio.

## Dependencias — `piecesphp/database` sube a v3.6.0

Trae `SchemeCreator::createScript()` y `dropScript()`, el charset `utf8mb4` configurable, el
arreglo de los campos nulables y las excepciones tipadas con el nombre del campo. Con ellas
`bin/cli scheme-create`, `bin/cli scheme-drop` y la suite del esquema **dejan de guardarse a sí
mismas**: llevaban desde el 24-08 sin poder correr.

**Aviso para quien actualice**: `composer` toma el PHP del PATH, y si ese PHP está por debajo del
piso declarado en `composer.json` **se niega a resolver sin tocar nada**. Aquí el PHP por defecto
es 8.1.34, así que la orden que funciona es:

```bash
php8.5 /usr/bin/composer update piecesphp/database
```

## Herramientas — `bin/cli gates`, y una suite omitida deja de contar como verde

Una suite que no corre no informa: calla. Y el silencio se lee como verde.
`unit-tests:core/scheme-sql-round-trip` llevaba desde el 24-08 omitiéndose sola —el paquete
instalado no traía `createScript()`— sin que nada lo señalara.

`bin/cli gates` corre todas las suites y **termina en 1 si alguna no dijo si pasó**. No hay lista
que mantener: las suites salen del registro de acciones y el veredicto se exige por la línea de
balance que cada una imprime. Acepta `only=<trozo>` para acotar.

Y la suite del esquema **ya no se omite**: si el paquete instalado se queda corto, **falla**.

## Herramientas — la lista de rutas que un recorredor nunca pide vive en un solo sitio

Estaba **copiada** en `bin/walk-routes` y en `bin/walk-attribute`. Los 17 patrones aún coincidían
—el comentario que explica por qué se mira también la URL, no: ya solo estaba en uno de los dos—,
pero lo que cuesta que diverja aquí no es ruido: un recorredor que pida una ruta de escritura
**escribe creyendo que solo lee**, y se lo atribuye a una ruta de lectura.

Ahora los patrones viven en `files/dev/forbidden-routes.json` —legible por cualquier herramienta,
en PHP o no— y la comparación en `bin/tools/forbidden-routes.php`. Los dos recorredores lo leen de
ahí y **ninguno conserva copia**; si el archivo falta, el recorrido no empieza.

Comprobado sobre las **351 rutas** del inventario real: los veredictos son los mismos que antes,
**cero cambios**. Y `verify-integrity` gana una **duodécima comprobación** que falla si alguien
vuelve a declarar la lista en otro sitio o deja de leerla de ahí.

## Herramientas — `verify-integrity` gana una undécima comprobación

La lista de tablas con acuñado de slug de `files/dev/volatile-state.json` está **copiada** de lo
que descubre el código, y una vez escrita nada detectaba que divergiera: añadir un módulo con
`preferSlug` la dejaba corta **en silencio**, y el recorrido de atribución reportaría un hallazgo
falso. Ahora se comparan las dos y falla si no coinciden.

## Cambios que rompen compatibilidad — `QueueJobMapper::migrate()` se retira

Era un experimento de migraciones anterior a este sistema y está superado por
`bin/cli scheme-create`. **No estaba dormido**: colgaba de `EVENT_INIT_ROUTES_NAME` en
`config/final-configurations-includes/event-listeners.php`, así que **ejecutaba DDL en cada
petición local**.

Comprobado antes de retirarlo: `databases/piecesphp_structure.sql` aplicado sobre una base vacía
crea **35 tablas**, `pcsphp_jobs_queue` entre ellas. El camino normal ya la creaba.

Con esto **la aplicación no ejecuta DDL en ningún sitio: solo lo emite.** Los únicos que lo
ejecutan son las suites y las tareas de terminal, que son herramientas.

## Añadido — `bin/cli db-restore`, el inverso de `db-backup`

Restaurar era `mysql < archivo.sql`: a mano y sin registro. Ahora es una tarea, con las mismas
salvaguardas que el respaldo y **con confirmación explícita**, porque destruye datos:

```bash
bin/cli db-restore file=dumps/x.sql confirm=yes
```

Deja rastro en `files/dev/last-restore.json` —fecha, archivo de origen y base destino—, y
`bin/walk-attribute` lo lee para avisar cuando una pasada de atribución **no** se hace sobre una
base recién restaurada. Probado de verdad: respaldar, cambiar, restaurar y comprobar que volvió.

## Corregido — abrir un formulario de contenido genérico creaba una fila

`GenericContentPseudoMapper::__construct()` insertaba la fila de configuración si no existía, así
que **abrir el formulario y no enviarlo dejaba rastro**. Ahora la fila se crea al guardar, que es
cuando alguien decide que existe.

Comprobado antes de tocarlo que **nada dependía de que existiera**: el valor por defecto vive en
las propiedades de la clase —apartando la fila, la lectura sigue devolviéndolo— y `save()` ya
inserta cuando no hay fila.

Se van con ello dos ramas muertas y el parámetro `$setDefaultData`, que no hacía nada.

## Corregido — tres guiones de `bin/` no arrancaban por sus finales de línea

`.gitattributes` pone todo el repositorio en CRLF y luego exceptúa **guion por guion** los de
`bin/`. Los que se añadieron después no estaban en esa lista, así que git les daba CRLF y **`env`
buscaba un intérprete llamado «php\r»**: `bin/live-cache`, `bin/walk-attribute` y
`bin/pieces-completion.bash` estaban inservibles.

La lista de excepciones se sustituye por **un patrón**, `bin/* text eol=lf`, para que no vuelva a
quedarse corta. Y la comprobación de `verify-integrity` sobre los guiones de `bin/` pasa a mirar
también el final de la primera línea, no solo el bit de ejecución.

## Corregido — dos peticiones simultáneas podían acuñar dos slugs y matar una URL

Los 14 mappers con `preferSlug` rellenan el slug de las filas que no lo tienen —las que entran por
importación o alta directa en base—. Esa escritura **no estaba condicionada**: dos peticiones
simultáneas generaban dos slugs distintos y ganaba la última, así que **si el primero ya había
salido en una URL, esa URL moría**. Ahora el `UPDATE` va condicionado a que el slug siga nulo, y
quien pierde la carrera relee el del ganador.

- La escritura sale del cuerpo de `objectToMapper()` a `mintPreferSlugIfMissing()`, en el trait
  `PreferSlugMinter`, y el docblock del convertidor **declara que escribe**.
- Los dos mappers que no comprobaban el nombre antes de acuñar pasan a hacerlo, como los otros
  doce. Comprobado contra `information_schema` que sus columnas de nombre son `NOT NULL`, así que
  el comportamiento de esos módulos no cambia.
- Las 14 tablas quedan declaradas en `files/dev/volatile-state.json` como escritura legítima en
  camino de lectura.

## Añadido — tarea para rellenar los slugs de golpe

`Terminal\Jobs\PreferSlugsFiller`, registrada como cronjob «Rellenar slugs pendientes», rellena
los `preferSlug` que falten sin esperar a que alguien navegue — pensada para después de una
importación. **No sustituye al relleno perezoso: lo complementa**, y usa exactamente el mismo
método, así que no hay dos versiones del mismo acuñado.

## Rendimiento — tres módulos generaban su `CREATE TABLE` en cada petición

`Documents`, `Forms\DocumentTypes` y `Forms\Categories` tenían el volcado comentado pero **el
`$sqlCreate` de encima no**: dentro de `routes()` instanciaban el mapper y generaban el DDL
completo en cada petición, para tirarlo. **Los seis sitios que tenían ese idiom se han borrado** —`Documents`,
`Forms\DocumentTypes`, `Forms\Categories`, `Newsletter`, `ImagesRepository` y `EventsLog`—,
tanto los vivos como los que ya estaban comentados: `bin/cli scheme-create module=<Nombre>` hace
lo mismo mejor, para cualquier módulo, y avisa si un mapper no se puede instanciar.

Medido: **0 consultas** y **1,228 ms** por petición de trabajo puro tirado.

## Añadido — dos interruptores para el área pública

Dos constantes booleanas en `config/constants.php`, **las dos en `true` por defecto**, así que
ningún despliegue cambia de comportamiento al actualizar:

```php
define('PUBLIC_AREA_VIEWS', true);          // las cinco vistas públicas GET
define('PUBLIC_AREA_CONTACT_FORMS', true);  // el destino POST del formulario de contacto
```

Están separadas a propósito: `contact-forms-general` es un **POST** y no aparece en ningún
listado de vistas, así que apagarlo por accidente no se vería — el formulario seguiría pintándose
y dejaría de enviar. Medido: con las vistas apagadas y el contacto encendido, 350 rutas → 345,
cero `public-*`, y el destino del formulario sigue ahí.

## Corregido — apagar el área pública rompía el envío de correo

Cinco plantillas pedían la URL de baja **sin** `$silentOnNotExists`, así que con la ruta
`public-unsubscribe` sin registrar lanzaban `RuntimeException` **al renderizar** —dentro de una
cola o de un cronjob—, no un `href` vacío. Ahora la piden en silencio y **omiten el bloque de baja
entero** si no hay URL. Ni enlace vacío ni excepción, y **no depende de ninguna constante**.

Se retira además la línea comentada `//self::$startSegmentRoutes = uniqid();`, que "apagaba" el
área pública **escondiéndola** tras un segmento impronunciable: las rutas seguían existiendo y
respondiendo. `$startSegmentRoutes` sigue siendo un prefijo legítimo y no cambia.

## Corregido — el enlace de baja de los correos llevaba un apóstrofo de más

`UNSUSCRIBE_TEXT` emitía `<a href='{{url}}'' target='_blank'>` en los **seis idiomas**. Sale
hacia el usuario en cada correo que manda el sistema. Un carácter por archivo.

## Cambios que rompen compatibilidad — el bloque `$showSQL` ya no existe

Diez `<Modulo>Routes` traían un bloque `$sqlCreate = […]; $showSQL = false; if ($showSQL) {…}`
que había que **editar en el código fuente** para volcar el `CREATE TABLE` de ese módulo. Se
retira entero, junto con 13 `use` que quedaban huérfanos.

**En su lugar**, y para todos los módulos, no solo para diez:

```bash
bin/cli scheme-create module=MiModulo    # el CREATE, ordenado por claves ajenas
bin/cli scheme-drop   module=MiModulo    # su inverso
```

**Si tu despliegue usaba el bloque**, el procedimiento cambia; el resultado, no. La regla 7 de
`CLAUDE.md` y cinco documentos de `.agents/context/` quedan corregidos en este mismo cambio.

> **Y una corrección de contabilidad nuestra**: estos diez `if ($showSQL)` **nunca contaron como
> ramas muertas**. Su supresión en `bin/phpstan.neon` estaba colocada **fuera del rango que mide
> `bin/phpstan-deadcode`**, así que no aparecían ni en el baseline ni en la deuda. Medido en las
> dos direcciones: con y sin los bloques, 285 ramas y 859 errores. Una supresión fuera del rango
> que mide la deuda no la reduce: **la esconde del instrumento que la cuenta.**

## Corregido — los mappers declaraban `int` en 39 claves ajenas que apuntan a un `bigint`

**Ahora el esquema entero se puede generar desde los mappers.** `bin/cli scheme-create module=all`
produce un script que MariaDB acepta de principio a fin; antes rechazaba 21 de las 34 tablas.

39 declaraciones en 21 archivos pasan de `'type' => 'int'` a `'type' => 'bigint'`: son las que
referencian `pcsphp_users.id`, que es `bigint`. **No hay migración**: las columnas reales ya eran
`bigint(20)` en el esquema que se distribuye —comprobado contra `information_schema` y contra
`databases/piecesphp_structure.sql`—; el que mentía era el mapper.

**El único cambio observable es el DDL generado**, y se comprobó por partes:

- `length` no interviene: `EntityMapper::$typesValidateLength` es `['varchar','text']`, y
  `SchemeCreator::$typesLengths` solo tiene `varchar => 255`, así que ni `int` ni `bigint` llevan
  longitud.
- La conversión no cambia: los dos tipos van juntos en `$typesInt` y en `SQLTypesEnum::INTEGERS`,
  y sus únicos consumidores los tratan como un grupo.
- Nadie compara: **cero** ocurrencias de `== 'int'`, `case 'int'` o `in_array('int'` en toda la
  aplicación.
- Y medido sobre datos: leída una fila real **por el mapper** antes y después, en las tres
  columnas que tienen filas, el valor devuelto y su tipo son idénticos.

**Lo que NO entra**: 20 claves ajenas cuya tabla referenciada es realmente `int(11)` —comprobadas
una a una contra `information_schema`— y 65 campos `int` que no referencian nada.

## AVISO PARA DESPLIEGUES EXISTENTES — el `CREATE TABLE` que el framework genera NO SE APLICA

**Versiones afectadas: todas hasta esta entrada.** La regla del proyecto dice que el SQL de las
tablas se genera con `SchemeCreator` y no se escribe a mano, y **hasta ahora esa regla no se podía
cumplir**: de las 34 tablas del proyecto, **21 eran rechazadas por MariaDB** al aplicar el script
generado. **Corregido en esta misma versión** (ver la entrada de las 39 claves ajenas); esto queda
como explicación de qué había y por qué.

| Causa | Cuántas |
| :-- | --: |
| `errno 150` — la clave ajena declara `int` y la columna referenciada es `bigint` | 19 |
| `Unknown data type: 'test'` — `'text'` mal escrito en `SystemApprovalsMapper` | 1 |

**Causa raíz: 38 claves ajenas declaran un tipo distinto del de la columna que referencian**,
en 19 archivos de mappers. Casi todas son `createdBy` / `modifiedBy` apuntando a
`pcsphp_users.id`, que es `bigint`.

**Por qué nadie lo había visto.** Once módulos tapan el síntoma con un reemplazo de cadenas
sobre el SQL ya generado, dentro de su bloque `$showSQL`:

```php
'createdBy` int' => 'createdBy` bigint',
```

Los módulos que no tienen ese bloque —`Documents`, `Forms`, `ImagesRepository`, `EventsLog`—
no tapan nada. Y el parche arregla la salida **dejando el mapper mintiendo**, que es justo lo
que la regla existe para evitar.

**Qué hacer al actualizar:** nada urgente — las tablas ya creadas funcionan. Pero **si vas a
regenerar una tabla desde su mapper, revisa el tipo de sus claves ajenas antes de aplicar el
script**. La comprobación está en `bin/cli unit-tests:core/scheme-sql-round-trip`, que hoy
**sale en rojo a propósito**: 5 de 8, y las tres que fallan son las que describen este defecto.

**Y el DDL generado es `CHARSET=utf8 COLLATE=utf8_bin`** —`utf8mb3`— escrito a fuego en
`piecesphp/database`. La conexión va en `utf8mb4`; las tablas recién generadas, no.

## Dependencias

- **`piecesphp/database` v3.5.0**: el DDL generado pasa de `utf8mb3` a `utf8mb4` y el juego de
  caracteres se vuelve configurable. Era la **tercera pata** del problema de los emojis —conexión
  (v3.2.1), valor por defecto de la base (documentado) y DDL generado—, y las tres eran
  independientes: arreglar una no arreglaba las otras. **Las tablas ya creadas no cambian.**

## Herramientas — el SQL del esquema, de ida y de vuelta

- **`bin/cli scheme-create module=<Nombre>|all`**, el gemelo de `scheme-drop`. Las dos
  **descubren** los mappers (`Mappers/`, `SubMappers/`, `ORM/` y `app/model`), sacan el orden
  del grafo que los propios `$fields` declaran en `reference_table`, y **emiten: no ejecutan**.

  Hasta ahora la única forma de sacar el `CREATE` de un módulo era **editar el código fuente**
  y poner un literal `$showSQL` en `true`. Eso no es una herramienta: es un interruptor
  escondido, y solo existía en once de los módulos.

  Necesita `piecesphp/database` **v3.4.0**; con una versión anterior avisa y sale con 1.

- **`Terminal\Tasks\SchemeSqlTask`**: el descubrimiento, compartido por las dos tareas. Dos
  listas serían dos verdades.

- **Corregido: una clase abstracta en `Tasks/` tumbaba la CLI entera.** `TerminalController`
  instancia por reflexión todo lo que encuentre allí con un método `route()`, y una abstracta
  cumple `method_exists()` pero revienta `call_user_func`. Ahora se comprueba `isAbstract()`.

- **Corregido: la tarea contaba mappers y decía «tablas».** Dos mappers pueden compartir tabla
  y el resolvedor los funde: decía «34 tablas» con 33 sentencias en el script. Ahora cuenta las
  sentencias y avisa de la diferencia.

## Corregido — `scheme-create`/`scheme-drop` se dejaban mappers fuera

El descubrimiento buscaba mappers solo en carpetas llamadas `Mappers`, `SubMappers` u `ORM`.
`UserProfileMapper` vive suelto en `Profile/`, así que **la tabla `user_system_profile` no
entraba en el script**. Ahora se recorre el módulo entero descartando por lista negra
—`Views`, `Statics`, `lang`, `lang-public`, `Exceptions`, `Controllers`—.

| | Antes | Después |
| :-- | --: | --: |
| Mappers descubiertos | 34 | **35** |
| Tablas en `scheme-create module=all` | 33 | **34** |

Si tu despliegue tiene mappers fuera de esas tres carpetas, **el script que generabas estaba
incompleto**.

## Herramientas — `bin/walk-attribute`: qué ruta de lectura escribe, y dónde

Toma una foto de la base de datos y del árbol de archivos **después de cada petición** y le
atribuye la diferencia a la ruta que la provocó. Hasta ahora los caminos de lectura que escriben
aparecían de uno en uno y por accidente.

En su primera corrida, sobre 184 rutas GET, encontró **dos escrituras no declaradas** y sus
causas, que son la misma de siempre —crear al leer—:

- **`NewsCategoryMapper::objectToMapper()` llama a `update()`**: un convertidor que escribe.
  Listar categorías actualiza filas. **Y no es un caso aislado: 14 de los 21 mappers que
  implementan `objectToMapper()` llaman a `update()` dentro.**
- **`GenericContentPseudoMapper::__construct()` llama a `save()`**: construir el objeto crea la
  fila. Es la misma forma del defecto de `UserDataPackage` en el login.

**No se ha corregido ninguno**: se documentan con su medición para decidir sobre ellos.

## Herramientas — la caché de la aplicación viva deja de depender de la memoria

- **`bin/live-cache`** y **`bin/tools/live-cache.php`**. Cualquier medición A/B contra la web
  tiene que invalidar la caché de código antes de medir; esa regla estaba escrita y **falló tres
  veces**. Ahora vive en el arnés: `bin/walk-routes` la llama al arrancar, y si no puede
  invalidar **aborta con código 1**.

  La espera no es folclore: sale de `php-fpm<version> -i` —**al binario, no a los `.ini`**, que
  no mencionan OPcache porque viene compilado— y es
  `max(revalidate_freq, file_update_protection) + 1`. En el entorno de referencia, 3 segundos.

  **`bin/live-cache --self-test` provoca la trampa y después la desactiva**: exige ver el código
  viejo sin invalidar y el nuevo invalidando. Una puerta vista solo en verde no se ha visto
  funcionar.

## AVISO PARA DESPLIEGUES EXISTENTES — un tipo mal escrito deja el campo SIN VALIDAR

**Versiones afectadas: todas.** `SystemApprovalsMapper` declaraba `'type' => 'test'` —«text» mal
escrito— en el campo `reason`. **No es un error de escritura suelto: es la sonda de un problema
de fondo**, porque ninguna capa lo rechazaba.

**Qué hace cada capa con un tipo que no existe, medido:**

| Capa | Qué hace |
| :-- | :-- |
| `EntityMapper::validateType()` | **Devuelve `true` para TODO** — cadenas, arrays, objetos, `null`. El campo **deja de validarse** |
| `EntityMapper::castPHPToSQLTypes()` | **No convierte**: el valor sale tal como entró |
| `SchemeCreator` | **Lo copia tal cual al DDL** |
| `MetaProperty` | **Lanza.** La única que lo rechaza |

La guarda que el ORM ya trae —`$onlySupportedTypes`— **no la activa ningún mapper**, y además
solo salta al asignar un valor, no al construir el mapper.

**Corregido** (`'test'` → `'text'`; la columna real ya era `text NULL`, así que no hay migración)
**y con puerta**: `verify-integrity` gana una décima comprobación que exige que todo
`'type' => '…'` declarado en un `$fields` esté en el vocabulario de `EntityMapper`. **370 tipos
comprobados, uno cazado.**

**Revisa tus mappers propios.** Si algún despliegue añadió campos con un tipo inventado, ese
campo lleva sin validarse desde que se escribió. `bin/cli verify-integrity` los lista.

## Corregido — las rutas de prueba de colas solo existen en local

`/pcsphp-testing/queue-request/` y su `handle` se registraban **también en producción**, públicas
y sin login, guardadas solo por `requestIsSameDomain()`. Ahora van dentro de `is_local()`.

Y con eso **dejan de necesitar la ocultación por `uniqid()`**: tenían el nombre de ruta generado
al azar en cada arranque, lo que impedía usar `get_route()` y obligaba a la vista a escribir la
URL a mano —rompiendo la regla 3 del proyecto como consecuencia forzosa—. Ahora tienen nombre
normal y la vista genera su URL.

**`img-gen` no cambia**: es otro grupo del mismo archivo, lo usan la portada, el cropper y las
tarjetas de subida, y conserva su guarda de `requestIsSameDomain()` contra el hotlinking.

## Corregido — cuatro guiones de `bin/` llegaban sin permiso de ejecución

El repositorio tiene `core.fileMode = false`, así que `chmod +x` funciona en el disco y **git
no lo registra**. Cuatro guiones estaban en el índice como `100644` y llegaban sin permisos a
cualquiera que clonara: `bin/rector`, `bin/package-css`, `bin/pieces-completion.bash` y
`bin/node/copyDependencies.sh`.

**`bin/rector` ni siquiera era ejecutable aquí**: devolvía «Permiso denegado» (salida 126) pese
a estar documentado en `CLAUDE.md` como una de las herramientas del proyecto.

Arreglados los cuatro, y **`verify-integrity` gana una novena comprobación** para que no vuelva
a pasar: todo archivo de `bin/` que empiece por `#!` tiene que estar en el índice como `100755`.

## Pruebas

- **`bin/cli unit-tests:core/scheme-sql-round-trip`**: descubre todos los mappers, emite el
  `CREATE` y el `DROP`, y **se los da a MariaDB** en una base de usar y tirar. **El juez es la
  base, no el generador.**
- `files/dev/tests.md` marca ahora, suite por suite, **quién la juzga**: un oráculo externo
  —base de datos, sistema de archivos, servidor HTTP— o ella misma. Las que se juzgan solas son
  las que hay que mirar con más cuidado: dos de ellas ya dejaron pasar un defecto.

## Seguridad

- **`db-backup` cifraba las contraseñas y NADIE las descifraba: una restauración dejaba a todos
  los usuarios sin poder entrar.** Severidad alta, y está embarcado en todos los despliegues.
  Medido de punta a punta —volcado, restaurado en una base de usar y tirar, intento de login—:
  `password_verify` contra lo restaurado devuelve **false**.
    - El cifrado tampoco protegía nada: la clave era la **cadena literal `'ENCRYPTION_KEY'`**
      escrita en el propio archivo, y aparecía **una sola vez en todo el código** —en la llamada
      que cifra—. No hay ni un `decrypt` con esa clave en ninguna parte.
    - **Si tienes volcados viejos, no están perdidos**: la columna `password` se recupera con
      `BaseHashEncryption::decrypt($valor, 'ENCRYPTION_KEY')`, comprobado.
    - `bin/cli unit-tests:core/db-backup-round-trip` fija el viaje de ida y vuelta, y se validó
      rompiéndola: con la transformación puesta, falla 2 de 4.

- **Dos `false` que eran un fatal en ejecución, no un aviso de tipos.**
    - `src/index.php`: al recorrer el directorio de sesiones expiradas, la fecha se sacaba del
      **nombre del archivo**. Un archivo que no encajara con el patrón hacía que
      `DateTime::createFromFormat()` devolviera `false`, y la llamada siguiente reventaba la
      petición entera. Ahora ese archivo se descarta.
    - `PublicationsController`: el sello de última modificación podía llegar sin hidratar desde
      el mapper, y `getTimestamp()` sobre una cadena es un fatal. Guarda con `instanceof`, y el
      valor por defecto pasa a `new DateTime()`, que no puede devolver `false`.
    - Con esto quedan **cero** errores de la forma «llamar a un método sobre un `false`».

- **Ver el QR del doble factor dejaba el flujo sin salida: PREPARAR NO ES ACTIVAR.**
  `OTPSecretsUsersMapper::toggle2FA()` escribía `twoAuthFactor = ENABLED` al pulsar
  «Activar», antes de que el usuario confirmase nada. A partir de ahí, volver a pulsar
  «Activar» entraba por la rama `if (!$isCurrentlyEnabled)` de
  `UserSystemFeaturesController::configureTOTP()`, que **no regeneraba el código de
  seguridad y lo devolvía vacío** aunque respondiera «Activado.»; y `user-security.js` solo
  muestra el QR si ese código no viene vacío. Al recargar, la vista revertía el estado y
  **regeneraba el secreto**, invalidando en silencio cualquier QR ya escaneado.
    - `toggle2FA()` solo **prepara**: guarda secreto, alias y hash del código de seguridad, y
      deja el estado en `DISABLED`.
    - El nuevo `confirm2FA()` es lo único que escribe `ENABLED`, junto con
      `twoAuthFactorQRViewed = 1`, y **no toca el secreto**: el QR escaneado sigue siendo
      válido. `markQRDataAsViewed()` lo llama y **propaga su fallo** en vez de responder éxito.
    - **No había bloqueo de acceso**: la puerta de login exige `isEnabled2FA` **y**
      `wasViewedQRData`, y esta última seguía en `0`. Comprobado reproduciendo el estado
      antiguo, no deducido.
    - La rama de reversión de `MySpace/Views/user-security.php` **se mantiene**: es el camino
      de salida de las filas que quedaran armadas antes de este cambio.
    - Queda abierto y anotado: confirmar **no exige demostrar un TOTP válido**, así que
      confirmar sin haber escaneado bien sí deja fuera de verdad.

- **Comprobar credenciales dejaba de escribir en base de datos.**
  `OTPSecretsUsersMapper::getOTPData()` y `getTOTPData()` eran get-or-create: si no
  encontraban registro, lo insertaban generando un secreto TOTP. Como
  `UserDataPackage::__construct()` llama al segundo sin condiciones, **construir un paquete
  de usuario escribía**, y lo alcanzan sin autenticar `checkValidityOTP`,
  `checkValidityTOTP`, `toExpireOTP` y `generateOTP`. Los dos buscadores son ahora puros y
  devuelven `null`; la creación vive en `createOTPData()` / `createTOTPData()` y solo la
  llama `toggle2FA()`, donde el usuario ya autenticado configura su segundo factor.
    - Severidad **baja** en seguridad: el secreto se regenera al activar el 2FA, así que el
      material pregenerado nunca llega a ser credencial viva.
    - `TOTPData` pasa a ser nulable de verdad; seis sitios lo manejan de forma explícita.

## Rendimiento

- **El registro de traducciones faltantes deja de escribir en producción.** `Config::i18n()`
  anotaba cada cadena sin traducir sin ninguna guarda de entorno, así que **cada página servida
  hacía un `file_exists()` por llamada a `__()`** también en producción — y una página del panel
  hace decenas. Su consumidor es `bin/cli scan-missing-lang`, una herramienta de desarrollo, y
  nadie lee ese directorio en producción.
    - Ahora solo anota en local, **coherente con la decisión ya tomada para las deprecaciones**:
      abortan en local, se registran en producción. No se inventa criterio nuevo.
    - Comprobado en las dos direcciones: con `is_local()` verdadero escribe, con falso no.
    - **Salida disponible y no implementada**: una constante de módulo permitiría recolectar
      contra tráfico real en un despliegue. Se descarta por ahora porque sería un interruptor
      más que configurar por una necesidad hipotética.

- **`clean-logs` vacía `missing-lang-messages`.** También crece en local: había **1.586
  anotaciones en 61 grupos** y nada las retiraba.

- **Un `foreach` duplicado y anidado sobre sí mismo, en el mapper del que se clonan los
  módulos.** `objectToMapper()` recorría `$defaultMetaPropertiesValues` dentro de un bucle
  idéntico sobre el mismo array: **cada meta-propiedad por defecto se comprobaba una vez por
  cada meta-propiedad por defecto.** El resultado es correcto —la asignación es idempotente—
  pero el trabajo es cuadrático, y `objectToMapper()` corre por cada fila que se hidrata.
    - **16 sitios, y en 10 el array NO está vacío**, así que el bucle interno se ejecutaba de
      verdad. Entre ellos `PublicationMapper` y `PublicationCategoryMapper`, **el módulo
      canónico**: cualquier módulo clonado desde ahí lo heredaba.
    - No es limpieza de código muerto: **es un defecto de rendimiento en la plantilla**, y por
      eso se cuenta aquí y no en «Cambios internos».
    - Barrido de comprobación tras el arreglo: **cero `foreach` anidados sobre sí mismos en
      todo `src/app`** y cero en los cuatro paquetes. Ningún módulo se quedó fuera.

- **`createOTPAlternativesRecords()` sale del registro de rutas.** Se llamaba desde
  `UserSystemFeaturesRoutes::routes()`, que corre **en cada petición**: dos consultas con
  `GROUP_CONCAT` y `LEFT JOIN` sobre la tabla entera de usuarios por cada carga de página.
  Ahora es la tarea `bin/cli sync-otp-records`, que **solo informa** salvo que se le pase
  `apply=yes`.

## Corregido

- **El iframe de SurveyJS se quedaba en blanco.** La vista vacía toda la configuración de
  assets —a propósito, para que el CSS y el JS del panel no se cuelen— y con ello se llevaba
  `statics/core/js/configurations.min.js`, **único emisor en todo el proyecto** del evento
  `PiecesPHP-Configurations-And-Window-Load` del que depende su propio arranque. Sin él no
  fallaba nada: simplemente no ocurría nada. Se restaura **ese archivo y solo ese**; el borrado
  sigue siendo grueso a propósito.
    - `survey-js-creator.php` no lo necesita —arranca con `load`, no con el evento— y se deja
      como estaba.

- **La URL del generador de imágenes del cropper deja de ser relativa.**
  `view/panel/built-in/utilities/cropper/workspace.php` y
  `view/panel/pages/test-cropper.php` construían `img-gen/{w}/{h}` a mano, saltándose la
  regla de que las URLs se generan con los helpers. Funcionaba **solo** porque
  `view/panel/layout/header.php` emite `<base href>`, que normaliza cualquier relativa: sin
  esa etiqueta, las tres pantallas que usan el cropper —`Documents` add y edit, y
  `usuarios/form.php`— quedaban con el marcador de imagen roto. Ahora las dos usan
  `baseurl()`, que es la forma que ya usaban las otras ocho apariciones de `img-gen`.
    - Barrido completo de las vistas: eran **las dos únicas** de su especie. Los otros 200
      atributos de URL delegan en una variable construida con `routeName()`, y los 18
      literales `statics/…` están en páginas que llevan su propio `<base href>`.

- **Rector dejaba fuera 34 de 195 archivos.** El formateador de tabla de PHPStan recorta las
  rutas al ancho de terminal (80 al redirigir), y `Rector.php` descartaba con `file_exists()`
  lo que no resolvía **sin avisar**: el 17 % de la superficie con errores no entraba al
  análisis. `bin/phpstan` fija `COLUMNS=400` y emite `PHPStanResult.json`; Rector lee el JSON
  y ya no descarta en silencio. `bin/rector` pasa a usar `php8.4`, no el `php` por defecto.
- **`toggle2FA()` devolvía siempre `false`**, incluso al guardar bien: inicializaba
  `$result = false` y no lo reasignaba nunca.
- **Dos defectos que escondía la familia `strpos`.**
    - `PublicationsController:1388` devolvía el resultado de `mb_strpos()` como predicado de
      `array_filter`, y ese resultado se evalúa por veracidad: una coincidencia en la
      **posición 0** vale `0`, que es falso. Un campo llamado exactamente
      `systemApprovalStatus` quedaba fuera del filtro, que es justo el caso buscado.
    - `FixWebmDurationTask:130` usaba `mb_strpos()` como longitud de `mb_substr()`: si la
      extensión no aparecía, `false` valía `0` y `$fileName` quedaba **vacío**, de modo que
      los tres archivos derivados se llamaban `.tmp.wav`, `.bk` y `.fixed` a secas y se
      pisaban entre iteraciones.
- **`Config::basepath()` y `app_basepath()` tenían una carrera.** Comprobaban
  `file_exists($path)` y **después** llamaban a `realpath($path)`; entre las dos llamadas el
  archivo puede desaparecer, y entonces devolvían `false` desde un método que declara
  `string`. Una sola llamada responde ambas preguntas sin ventana entre ellas.
  `Config::app_path()` no comprobaba nada, y de su valor cuelgan las otras dos.
- **`json_encode()` declara sus fallos** en 15 sitios, con `JSON_THROW_ON_ERROR`. Con datos
  válidos la salida es **byte a byte idéntica**; lo único que cambia es el camino de fallo,
  y ese camino era peor de lo que parecía: en `PublicationsController:1152` el código era
  `sha1(json_encode($checksumData))`, así que **todo dato no codificable compartía el mismo
  checksum** (`sha1(false)` = `sha1('')`) y el caché HTTP servía contenido equivocado.
  La excepción es `GenericHandler:192`, dentro del manejador de errores, donde lanzar
  rompería justo el registro que se intenta escribir: ahí la respuesta es manejo explícito.
- **Una entrada de caché vacía ya no cuenta como caché.** `hasCachedData()` solo
  comprobaba `file_exists()`, así que un archivo de **cero bytes se servía como contenido
  válido indefinidamente** —no hay recaché hasta que algo marque `shouldBeRecached`—. Es
  la vía por la que un `json_encode()` fallido llegaba a disco: `setDataCache(string $data)`
  sin `strict_types` convierte `false` en `''` en silencio. Ahora un archivo vacío se trata
  como ausencia de caché y **la entrada envenenada se cura sola en la siguiente petición**.
    - **Al desplegar no hace falta purgar nada por este motivo.** Se comprobó el árbol de
      `src/app/cache`: no hay ninguna entrada almacenada en este entorno. Si en producción
      quedara alguna, la corrección la invalida automáticamente por tamaño. `bin/cli
      clean-cache` sigue disponible si se prefiere forzarlo.
    - **Matiz sobre el ETag**, que se describió de más en el análisis inicial: el checksum
      de `PublicationsController:1152` no se almacena en servidor, va a una cabecera `ETag`
      emitida junto a `Cache-Control: no-cache`. Eso obliga a revalidar, así que un ETag
      viejo provoca un 200 con contenido fresco y también se cura solo. El defecto real era
      que dos contenidos distintos podían compartir tag, no que se sirvieran cruzados: los
      ETag se comparan por URL.
- **El subsistema de exportación se retipa de `PDO` a `Database`.** Los 17 errores de
  `PDOStatement|false` vivían ahí porque el receptor estaba declarado como el padre, y por
  eso subir el paquete a v3.2.0 no movía el contador. Los dos únicos llamantes pasan
  `getDatabase()`, y uno ya usaba `getDatabaseName()`, que solo existe en `Database`.
- **`APP_VERSION_DATE` deja de depender del reloj.** `createFromFormat('d-m-Y', …)` sin parte
  horaria hereda la hora actual; `->format('Y-m-d')` la descartaba, así que el valor nunca
  cambió, pero el objeto intermedio era distinto en cada petición.

## Seguridad

- **AVISO, defecto abierto y de la misma familia que el del login**: `UserDataPackage`
  llama a `UserProfileMapper::getProfile()`, que es **get-or-create**, en la línea
  siguiente a la que se corrigió para el OTP. Ese constructor se alcanza **sin autenticar**
  desde el formulario de login, así que **comprobar credenciales sigue creando una fila de
  perfil**. Acotado —una por usuario— pero es escritura no autenticada. No se corrige aún.
- **`get-current-totp-qr-data/` devolvía 500 para todo usuario que no hubiera nombrado su
  segundo factor**, que es el estado normal de una cuenta nueva: se pasaba un alias nulo a
  un parámetro `string`. Ahora el emisor por defecto es **el nombre del sitio**, que es lo
  que ya hacían las vistas y lo que el usuario reconoce en su aplicación de autenticación.
- **Suite nueva `bin/cli unit-tests:core/otp-fresh-user`.** Inserta un usuario real sin
  filas OTP y recorre toda la superficie de autenticación. Confirma que **el arreglo del
  login no dejó a nadie sin poder entrar**: `setOTP()` crea la fila que falta. Es la única
  suite que escribe, y borra lo que crea pase lo que pase.

## Corregido — arranque de un despliegue

- **La línea de crontab documentada llevaba `--local`, y ese flag DECIDE LA BASE DE DATOS.**
  En terminal `is_local()` devuelve lo que diga el flag, y `config/database.php` elige
  credenciales y nombre de base según ese valor: una línea así en un servidor apunta los
  cronjobs **a la base de desarrollo**. Corregida en los dos sitios donde estaba escrita.
- **La activación de idiomas dejaba de copiar en silencio.** Los dos `@copy()` de la
  configuración SEO tragaban el fallo: sin fila y sin registro, cada render lo reintentaba
  para siempre sin que nadie se enterara. Ahora registran qué archivo y a dónde. **La
  lógica no cambia**: materializar la configuración por idioma al pintar la vista es el
  camino de activación, no un efecto lateral.

## Seguridad — cifrado

- **`BaseHashEncryption::encrypt()` y `decrypt()` dejaron de descifrar en PHP 8.5.** Las dos
  suman y restan bytes y **dependen de que `chr()` dé la vuelta en 256**; desde 8.5 eso emite
  una deprecación y, con el manejador de errores del framework, **lanza**. Efecto visible: la
  configuración de correo dejaba de descifrarse y **cinco páginas públicas devolvían 500** —
  recuperación de contraseña, desbloqueo de usuario, «no recuerdo mi usuario», problemas de
  ingreso y solicitud de soporte.
    - El arreglo es `& 0xFF`, que **reproduce ese desbordamiento exactamente**. Comprobado
      valor a valor entre −600 y 600 contra el `chr()` de 8.4: cero diferencias.
    - **Compatible con lo ya cifrado**, demostrado en las dos direcciones: el código nuevo
      descifra lo cifrado por el viejo y el viejo descifra lo del nuevo, con cifrado byte a
      byte idéntico.
    - **NO SE TOCA ESA ARITMÉTICA.** Cualquier otro cambio vuelve indescifrable todo lo ya
      guardado en cada despliegue. El archivo lleva la prohibición escrita.

## Corregido — el servidor de estáticos

- **23 rutas `*/statics/` devolvían 500 a la vez.** El patrón `{params:.*}` es **opcional** y
  `ServerStatics` lo leía como obligatorio en tres sitios: pedir `/statics/` sin recurso daba
  `E_WARNING` → excepción → 500, una por módulo. Ahora responden **404**, que es lo correcto,
  y los estáticos reales siguen sirviéndose.
## Herramientas — los cinco repositorios al mismo instrumental

- **Los cuatro paquetes `piecesphp/*` tenían la misma configuración que había cegado al
  framework**: `phpVersion` como rango, que reporta la intersección y no la unión. Se les
  propagan las **dos pasadas**, `PCSPHP_PHP_BIN`, el trinquete leyendo JSON y su baseline
  con nota de método. **Delta por configuración: cero en los cuatro** — pero ahora medido,
  no supuesto.
- **Séptima comprobación de `verify-integrity`: instrumental común.** `files/dev/shared-toolchain.json`
  lista qué debe estar presente en cada paquete, y la tarea falla si uno se desvía o si le
  falta un archivo. Si no están clonados al lado, lo dice y no aprueba en silencio.
- **Repuesto el prefijo `project://` del informe de PHPStan**, que se perdió al cambiar el
  resumen de la tabla al JSON. Lo consume un plugin del editor para saltar al archivo; el
  formato es idéntico al anterior. Ahora sale del JSON, sin expresión regular.

## PHP 8.5 — la migración no estaba terminada

- **Se borran nueve llamadas a funciones deprecadas en PHP 8.5**: `imagedestroy()` ×4,
  `finfo_close()` ×4 y `curl_close()` ×1. Desde 8.0 no hacen nada, y **en 8.5 avisan**;
  como el manejador de `bootstrap.php` promueve `E_DEPRECATED` a excepción, cada una era
  una petición abortada esperando a que alguien pisara esa línea.
    - **`img-gen` devolvía 400 por esto.** Demostrado poniendo la llamada de vuelta: con
      ella HTTP 400, sin ella HTTP 200 y un JPEG de 400×300.
    - **Por qué no se detectó**: Apache sirve **8.5.9** y todas las herramientas
      (`bin/cli`, `bin/phpstan`, `bin/rector`) corren con **php8.4**, donde esas funciones
      no avisan. Y `phpVersion` en `phpstan.neon` es un RANGO 8.4–8.5, que reporta solo lo
      que es error en TODAS las versiones del rango. **Los dos detectores miraban a la
      versión equivocada.**
- **`bin/cli verify-integrity` gana una sexta comprobación: FUNCIONES DEPRECADAS.** Busca
  por tokens las funciones de `files/dev/deprecated-functions.json` y falla si aparece
  alguna. La lista es un archivo editable, con la versión en que cada una se deprecó y las
  rutas donde se permite con su razón. **Es determinista: mira el código, no la ejecución.**
- **Nuevo: `bin/cli route-inventory` y `bin/walk-routes`.** Recorren por HTTP todas las
  rutas GET que el framework declara —347, sacadas del propio framework— y después todos
  los assets de las páginas visitadas. Solo lectura. Ver el README.
- **`bin/phpstan` pasa a DOS PASADAS y el baseline es la UNIÓN de 8.4 y 8.5.** El
  `phpVersion` como rango reportaba la **intersección**, no la unión: solo lo que es error en
  todas las versiones del rango, así que **toda deprecación exclusiva de 8.5 se descartaba en
  silencio**. El baseline pasa de 875 instancias a **888 tripletas**, que se reconcilia
  exacto: −9 duplicados que la unión deduplica, +22 que solo existen en 8.5.
    - **No es una regresión: es un destape por configuración más un cambio de unidad.** El
      resumen lleva ahora un bloque `[UNIDAD]` y otro `[REPARTO POR VERSIÓN DE PHP]`.
    - `phpstan-process-result.php` **deja de parsear la tabla** y construye el resumen desde
      el JSON de la unión.
- **Las herramientas de `bin/` usaban `php8.4` mientras Apache sirve 8.5.9**, así que las
  suites nunca habían corrido en la versión de producción. Ahora el binario es explícito y
  configurable con `PCSPHP_PHP_BIN`, **y por defecto es el que sirve el navegador**. Las seis
  suites y `verify-integrity` pasan en **las dos** versiones.
- **El recorredor pide también las rutas con parámetros**, cosechando los enlaces reales de
  las páginas ya visitadas en vez de inventar valores: 122 páginas más por recorrido.
## Seguridad y corrección de datos

- **El XML exportado declaraba `encoding="utf8mb4"` y NINGÚN parser podía leerlo.**
  `utf8mb4` es un nombre de charset de MySQL, no un encoding XML: un lector estándar
  rechaza el documento **entero** por la primera línea, por bien formado que esté el resto.
  Confirmado con dos parsers independientes (`libxml` y `xmllint`). `XmlFormat` traduce
  ahora los nombres de MySQL a los nombres IANA.
- **CAMBIO DE FORMATO EN LA SALIDA XML, y cambia porque antes era ilegible.** Las columnas
  binarias se exportan ahora en hexadecimal (`0x…`), igual que ya hacía la salida SQL. Antes
  se escribían con sus bytes crudos, y **XML 1.0 prohíbe los caracteres de control**, así que
  un export de cualquier tabla con un BLOB producía un documento que ningún parser aceptaba.
    - La detección era incorrecta de raíz: se preguntaba si el valor era UTF-8 válido, y eso
      **no responde «¿esto es binario?»** — los bytes de un PNG son UTF-8 válido. Ahora lo
      decide **el tipo de la columna**, leído del esquema, como en `SqlFormat`.
    - Además, cualquier valor que conserve un carácter prohibido en XML sale también en
      hexadecimal, **independientemente de la opción `hex_blob`**: un dato suelto no puede
      tumbar el documento entero.
    - **Si algo consume ese XML, tiene que contar con el `0x…`.** A cambio, por primera vez
      puede leerlo.
- **AVISO, defecto abierto y de otra capa**: la semilla de pruebas inserta un PNG que empieza
  por el byte `0x89`, y **las dos** exportaciones lo devuelven como `0x3f` —el signo `?`—.
  Los dos formatos leen con el mismo `SELECT *`, así que **el byte se pierde antes**, en la
  capa de base de datos. Afecta a cualquier dato binario, no solo al exportador. **No se
  toca aún**: si el fallo está en la escritura, los datos ya guardados están dañados y eso
  cambia qué significa arreglarlo.

## Pruebas — puertas verificadas en las dos direcciones

- **Desactivar el 2FA se comprueba por su EFECTO, no por su valor devuelto.** La suite
  verificaba que `toggle2FA(false)` devolviera `true`; ahora comprueba que la cuenta **deje de
  pedir código** y que el código de seguridad quede vacío. Validada rompiéndola: con un inverso
  que devuelve `true` sin desactivar, falla 2 de 27.

- **Apagar un módulo, medido por primera vez.** `NEWS_MODULE` en `false` retira sus 19 rutas del
  inventario, `/admin/news/list/` pasa de 200 a 404, el panel sigue en 200 y el menú deja de
  mencionarlo. Reencendido, **vuelve el mismo conjunto de rutas nombre por nombre**. No quedan
  restos.

- **`UnitTest-DbBackupRoundTrip` comprueba el viaje ENTERO**: exporta, restaura en una base de
  usar y tirar y **entra**. Leer el archivo no basta: un volcado puede tener el hash bien y no
  restaurar. Validada rompiéndola — con la transformación reintroducida falla 3 de 5.

- **`otp-write-separation` deja de juzgar por el texto.** Sus comprobaciones eran `grep` de
  `->save(` sobre el cuerpo del método, y **pasaban en verde con el defecto D2 reintroducido**
  porque un grep no ve una escritura delegada una llamada más abajo — que es exactamente la
  forma que tenía D2. Ahora llama a los buscadores y cuenta filas. 7/7.

- **`verify-integrity` deja de pedir el borrado de una sobreescritura de ruta viva.** Su
  clasificador decidía «¿este método decide algo?» leyendo el cuerpo, así que una sobreescritura
  cuya decisión se mudara a un método que ella llama se marcaba como **«YA NO DECIDE NADA:
  Bórralo»**. Ahora, si la clase declara también el método al que llama, no se declara inerte.
  Ese registro se usa en E3 para borrar, así que el agujero se tapa antes.

- **`UnitTest-OTPFreshUser` sube a 25 comprobaciones**: tres nuevas fijan que preparar el
  doble factor no lo activa, que confirmarlo sí, y que el secreto **no** se regenera al
  confirmar.

- **Se provocó el fallo de todas las puertas**, no solo de las nuevas. De doce, nueve
  funcionaban; los tres hallazgos van abajo. Las mutaciones exactas quedan anotadas en
  `.agents/context/18-siguientes-ventanas.md` (T23) para poder repetirlas.
- **`bin/phpstan` gana el trinquete, que hasta ahora no existía.** `CLAUDE.md` mandaba
  comparar contra `PHPStanResult.Summary.baseline.txt` y **nada lo comparaba**. Ahora
  `bin/phpstan-process-result.php` compara instancias contra instancias y **sale con 1 si
  el total sube** — y también si no consigue leer una de las dos medidas, porque una puerta
  que no puede medir no puede aprobar. El baseline pasa de **968 a 877**, con su nota de
  método dentro del archivo.
- **`unit-tests:core/database-exporter` valida ahora que la salida esté BIEN FORMADA**
  (JSON y XML), no solo que contenga lo esperado. Antes, un JSON corrupto en cada fila daba
  23/23. Esa comprobación es la que destapó el defecto del XML.
- **`unit-tests:functions/systemOutFormatted` llevaba meses roja diciendo que iba bien.**
  Afirmaba códigos ANSI que la función suprime a propósito sin terminal, así que fallaba
  7 de 10 en cuanto la salida se redirigía — y como no contaba nada, devolvía éxito. Ahora
  omite con su razón lo que exige terminal, y tiene balance y resultado real.
- **`bin/cli` devolvía código 0 aunque la aplicación muriera al arrancar.** El manejador
  global de excepciones terminaba en `die($content)`, y `die()` con una cadena **sale con
  código cero**. Cualquier puerta lanzada por la CLI —`verify-integrity`, las suites, los
  cronjobs— informaba de éxito **sin haberse llegado a ejecutar**. Ahora sale con **1** en
  CLI; en HTTP no cambia nada, porque ahí manda el 500 que ya se envió.
    - **Afecta a cualquier despliegue que lance tareas por CLI y mire el código de salida**:
      hasta esta versión, un árbol que no compila pasaba por bueno.

## Cambios internos

- **Una guarda de una línea contra un cuelgue**: `createOTPData()` y `createTOTPData()` llaman a
  su buscador para ser idempotentes, así que el día que alguien vuelva a hacer que el buscador
  cree, no habrá un defecto: habrá una **recursión infinita**, y se descubrirá por tiempo de
  espera agotado, que es la peor forma de descubrirlo. Ya nos costó dos ejecuciones al intentar
  reintroducir el defecto para validar una prueba.

- **`verify-integrity` cambia una orden por una pregunta.** Su clasificador de sobreescrituras
  decía «YA NO DECIDE NADA: Bórralo», y **nadie verifica una orden antes de obedecerla**. Como
  la comprobación lee el cuerpo y no ve lo que se delega, ahora pregunta si sigue decidiendo y
  pide comprobarlo antes de borrar.

- **Recorte de comentarios narrativos: de 132 bloques y 729 líneas de prosa a 91 y 486.** Los
  41 bloques recortados eran todos de esta campaña, y pasan al reparto que fija la regla: la
  guarda que impide romper algo se queda **en una línea**, y el relato —qué pasaba antes, qué
  se midió, por qué se decidió así— vive en este `CHANGELOG` y en
  `.agents/context/18-siguientes-ventanas.md`. Un comentario largo envejece mal: el día que el
  arreglo que narra sea irrelevante, el comentario miente y nadie va a ir a buscarlo.
    - Los 91 que quedan son heredados y **se recortan al pasar**, cuando se toque el archivo
      por otro motivo. Nada de barridos.

- **Se retira el hueco de plantilla `$defaultPropertiesValues = []` y su `foreach`**, 24
  apariciones en 19 mappers. El array se declaraba vacío y su bucle no podía hacer nada. Con
  esto, `foreach.emptyArray` desaparece del análisis y las ramas muertas bajan de **309 a 285**.

- **`routeName()`, `allowedRoute()` y `_allowedRoute()` dejan de estar copiados en cada
  controlador.** Los aporta **un solo trait**, `PiecesPHP\Core\Routing\ControllerRoutingTrait`,
  con el hook y su `return true;` por defecto. **Se borraron 89 copias** repartidas en 26
  controladores.
    - **El criterio es una sola pregunta: ¿este método DECIDE algo?** No el parecido con el
      cuerpo canónico — ese criterio, el de la primera pasada, dejaba vivas dieciséis copias
      que solo devolvían si la ruta vino vacía, con closures que nadie llamaba, variables
      asignadas y no leídas y un `if` comparando contra una ruta llamada `'SAMPLE'` que no
      existe.
    - **Sobreviven 25**, todas con su razón escrita: 15 que deciden de verdad —propiedad del
      recurso, conflicto de interés, registro protegido— y 10 estructurales, que nombran la
      ruta de otra forma o tienen otra firma.
    - **Sin cambio de comportamiento.** PHPStan queda en **877** en cada paso, con los
      **606** sitios de llamada resolviendo en nivel 8, y las suites sin moverse.
    - **`bin/cli verify-integrity` gana una quinta comprobación**: falla si un controlador
      sobreescribe uno de los tres sin estar en `KNOWN_ROUTE_OVERRIDES`, si una entrada del
      registro **deja de decidir algo**, o si apunta a una declaración que ya no existe.
    - **Al crear un módulo ya no se copian esos métodos**: se añade `use ControllerRoutingTrait;`
      y se escribe `_allowedRoute()` solo si hay reglas de autorización propias. Los tres
      patrones están documentados en la receta 9 de `.agents/context/13-recetas.md`.

## Análisis estático — el .neon deja de ser cajón de sastre

- **Las 285 ramas muertas pasan a supresión documentada agrupada por MOTIVO**, no por
  identificador. Eran veintiséis identificadores silenciados sin una sola razón escrita; ahora
  son cinco motivos, cada uno con lo que lo sostiene y su condición de retirada: defensa sobre
  datos que el tipo no describe (129), interruptores de módulo (65, sin condición de retirada
  porque borrarlos cablearía todos los módulos en encendido), variables que inyecta el
  renderizador de vistas (38), estrechamientos que en ejecución no lo están (46) e inofensivo y
  real (4).
    - Se retira la supresión de `foreach.emptyArray`, que ya no silencia nada.
    - Baseline **874 → 859**, y el reparto va escrito: **4 arreglos y 11 supresiones**. Una
      cifra que baja por supresión no significa lo mismo que una que baja por arreglo, y el
      trinquete no distingue solo.

- **`catch.neverThrown` pasa de supresión de familia a dos supresiones por ruta.** Se
  miraron los cinco: **tres eran muertos de verdad y se han borrado** —un `try` alrededor
  de una asignación de cadena, otro con el cuerpo entero comentado, y un
  `$exception->getCode()`—. Los dos que quedan son **alcanzables en ejecución**, cada uno
  por una razón distinta: uno pasa por `__set()`, que sí lanza, y el otro por un
  `E_WARNING` que el manejador de `bootstrap.php` promueve a excepción.
    - No se puede expresar en configuración: se probó
      `exceptions.reportUncheckedExceptionDeadCatch: false` y no mueve ninguno. PHPStan
      modela el lenguaje; aquí manda además el manejador de errores.
- **`if.alwaysFalse` en los `<Modulo>Routes` pasa a supresión PERMANENTE**, sin condición
  de retirada. Son el bloque `$showSQL` que la regla 7 de `CLAUDE.md` manda usar para sacar
  el DDL, más un módulo apagado en árbol con `const ENABLE = false`. **No es deuda: es un
  interruptor.** Los otros 9, en controladores, siguen contados como candidatos.

## Herramientas

- **`bin/cli scheme-drop module=<Nombre>` EMITE el SQL de borrado de un módulo. No lo ejecuta.**
  La regla de que el SQL de las tablas se genera y no se escribe a mano solo existía hacia
  adelante: deshacer un módulo obligaba a escribir a mano justo lo que la regla prohíbe, y cada
  despliegue que actualice necesita ese SQL.
    - El orden —hijas antes que padres— **sale del grafo que los mappers ya declaran** en
      `reference_table`. No hay lista aparte que mantener.
    - Emite y no ejecuta a propósito: esto viaja a despliegues ajenos, lo revisa una persona y
      se aplica deliberadamente.
    - Necesita **`piecesphp/database` >= 3.3.0**; con una versión anterior avisa y sale, en vez
      de reventar.

- **`bin/cli snapshot compare` exige que la volatilidad esté DECLARADA.** Un comparador con
  ruido enseña a ignorar los diffs, igual que un rojo permanente enseña a ignorar el rojo. El
  registro cerrado vive en `files/dev/volatile-state.json`, cada entrada con su razón medida, y
  la comparación **falla con salida 1 ante cualquier cambio no declarado**.
    - Dos entradas, las dos comprobadas: `login_attempts`, que escribe una fila por intento de
      acceso, y `src/app/lang/missing-lang-messages/`, donde el framework anota cada cadena sin
      traducir — **una escritura en un camino de lectura, y deliberada**.

- **El trinquete acota la declaración del reparto contra el `.neon`.** Una supresión cambia el
  `.neon` y un arreglo cambia el código: si se declaran cero supresiones y el bloque de
  `ignoreErrors` creció, la declaración es imposible y la puerta lo dice. **No verifica la
  atribución —se puede seguir mintiendo dentro de la cota— pero descarta lo imposible.**

- **`bin/cli snapshot`: fotografía la base entera y el árbol servido, y compara dos fotos.**
  Por tabla guarda el conteo, un hash agregado y **un hash por fila indexado por su clave
  primaria**, sacada de `information_schema`; del árbol, tamaño, `mtime` y hash de cada
  archivo. Comparar dos fotos atribuye cada diferencia a lo que corrió entre ellas.
    - Existe porque «solo GET» resultó no ser una propiedad de seguridad en este código: hay
      caminos de lectura que escriben, y se descubrieron de uno en uno y por accidente.
    - Por encima de 20.000 filas no guarda hashes por fila, **y lo dice nombrando las tablas**:
      un recorte que no se declara se lee como cobertura completa.
    - Las fotos no se versionan; la herramienta sí.

- **El trinquete exige el REPARTO de cada movimiento del baseline.** Premiaba igual arreglar
  que callar: una bajada por supresión se lee exactamente igual que una por arreglo. Ahora cada
  cifra del baseline declara `[REPARTO] n <- anterior = x arreglos + y supresiones`, las
  cuentas tienen que cuadrar, y sin esa línea `bin/phpstan` no pasa. No es para prohibir
  suprimir: es para que suprimir no pueda disfrazarse de progreso.

- **`shared-toolchain` deja de mirar solo el contenido y pasa a mirar el estado.** Aprobaba en
  verde sobre cuatro repositorios que no estaban sincronizados. Ahora comprueba tres capas: las
  marcas de siempre (más `bin/cli`), el **estado de seguimiento de lo que las herramientas
  producen** —qué debe estar versionado, qué ignorado, y distinguiendo el caso de *ni una cosa
  ni la otra*— y el **bit de ejecución** de `bin/phpstan` y `bin/cli`, que estaba en `100644`
  en dos paquetes y hacía que `./bin/phpstan` respondiera «Permiso denegado».
    - Se alinean los cinco repositorios: `PHPStanResult.json` versionado, los dos intermedios y
      `bin/Preview/` ignorados, y `database/bin/cli` pasa a honrar `PCSPHP_PHP_BIN` en vez de
      fijar `php8.4` a mano.

- **`bin/cli verify-integrity` gana su octava comprobación: comentarios narrativos.** Un
  bloque con **más de dos líneas de prosa y ninguna anotación** (`@param`, `@return`, `@var`,
  `@package`, `@author`, `@throws`) es la firma mecánica del relato: los docblocks de API
  siempre traen anotaciones, las historias no. El registro cerrado vive en
  `files/dev/narrative-comments.json` y **solo puede encoger**.
    - Guarda las **líneas de prosa** de cada entrada, no solo el número de bloques: un bloque
      que crece de 4 a 30 líneas no cambia el conteo de entradas y sí empeora el archivo.
    - Se ancla por **archivo**, no por línea, para que editar algo encima no genere ruido.
    - `bin/cli verify-integrity list-narrative=yes` lista los bloques con archivo, línea y
      prosa, que es lo que hace falta para recortarlos.
    - Falla en las tres direcciones posibles —bloque nuevo sin registrar, prosa que crece,
      entrada que ya no tiene bloques— y las tres se probaron provocándolas.

- **`bin/phpstan.neon` declara los interruptores de módulo como `dynamicConstantNames`.**
  Las 25 constantes de `config/constants.php` que **cada despliegue configura** ya no se
  resuelven al valor de este árbol, así que PHPStan deja de dar por muertas las ramas que
  dependen de ellas. **No es una supresión**: borrar esas ramas habría cableado todos los
  módulos en ENCENDIDO para cualquier despliegue que los apague.
- **`bin/phpstan-deadcode`**, nuevo. Mide las ramas muertas que el bloque de ignores de
  `phpstan.neon` silencia, derivando la configuración de ese mismo archivo en cada
  ejecución para que no pueda quedarse atrás. El baseline visible no se mueve (877); la
  medición tapada baja de 465 a 373 tripletas.

## Dependencias

- `piecesphp/database` sube a **v3.2.0**: `query()` y `prepare()` declaran `\PDOStatement`
  en vez de `\PDOStatement|false`.

## Pruebas

- Suite nueva `bin/cli unit-tests:core/otp-write-separation`: comprobar credenciales y
  registrar rutas no deben escribir en base de datos.
- **`bin/cli verify-integrity` gana una cuarta comprobación: ECLIPSES DE CLASES.** Falla si
  el núcleo declara bajo `PiecesPHP\Core\` una clase que también existe en un paquete
  `piecesphp/*`. PSR-4 resuelve por prefijo más largo, así que en ese caso el núcleo gana
  **siempre y en silencio**. Ya pasó con `MetaProperty`, y el coste no fue el eclipse sino
  que un arreglo aplicado al archivo del paquete no llegaba al framework **sin que nadie
  pudiera notarlo**. Los eclipses aceptados se registran en `KNOWN_ECLIPSES` con su razón y
  la condición que los retira; una entrada cuyo eclipse desaparezca también hace fallar la
  tarea, porque una supresión que sobrevive a su motivo es una mentira que nadie relee.
- **Suite nueva `bin/cli unit-tests:core/meta-property-hybrid`.** Prueba `MetaProperty`
  **tal como se ejecuta en el framework**, que no es como lo prueba nadie: lo que corre
  aquí es la copia del núcleo llamando a `EntityMapper::validateType()` del paquete, y esa
  combinación no la cubre ninguno de los dos repositorios. El arreglo de la deprecación de
  PHP 8.5 llegó a este código **de rebote**, por el único hilo que quedaba. Doce
  comprobaciones, todas de solo lectura.

# 7.1.0 (20-08-2026)

**Rango de PHP soportado: `>=8.4.1 <8.6`** (antes `>=8.1 <8.5`).

## Cambios que rompen compatibilidad

- **El piso de PHP sube de 8.1 a 8.4.1.** No es una elección estética: 8.1 lleva sin
  parches de seguridad desde el 31-dic-2025. El `.1` lo impone Symfony 8.1, que exige
  `>=8.4.1`; declarar `>=8.4` a secas mentía sobre lo que la aplicación necesita.
    - **Ubuntu 24.04 LTS trae PHP 8.3 por defecto**, así que el despliegue ahora requiere
      el repositorio de ondrej. Ver `source-docs/.../general.md`.
- **`bootstrap.php` cambia cómo trata los errores.** Ver abajo, es el único cambio de
  esta versión que altera el comportamiento en producción.

## Corregido — compatibilidad con PHP 8.5

13 sitios en el código propio, en tres familias:

- **9 casts no canónicos `(double)` → `(float)`** en 7 archivos. Es el mismo cast; solo
  cambia la grafía. Importaba porque la deprecación se emite **en tiempo de compilación**:
  bastaba con que el autoloader tocara el archivo.
- **3 llamadas a `Reflection*::setAccessible()` eliminadas** (`Config.php:712`,
  `BaseEntityMapper.php:159`, `index.php:338`). Desde 8.1 no tienen efecto. La de
  `BaseEntityMapper` era la grave: `__callStatic` la ejecutaba en cada `fieldsToSelect()`,
  o sea en el camino de **todo `SELECT` de mapper**.
- **`$http_response_header` → `http_get_last_response_headers()`** en `HttpClient.php:186`.
  Sin guarda `function_exists()`: la función existe desde 8.4, que es el piso.

## Manejo de errores — cambio de comportamiento en producción

`bootstrap.php` promovía a excepción cualquier nivel de su tabla, y **devolvía `true`
para todo lo demás**, de modo que lo descartaba en silencio.

- **`E_USER_ERROR` ya no se traga.** Se perdían todos los `trigger_error()` de librerías,
  incluido el `platform_check` de Composer: la aplicación arrancaba sin decir nada sobre
  PHP 8.1 con un `vendor/` que declara necesitar 8.4.1. Ahora aborta.
- **`E_RECOVERABLE_ERROR` ahora aborta**; antes se descartaba en silencio.
- **Las deprecaciones solo abortan en local.** En producción se registran en
  `app/logs/deprecations.log` y la petición continúa. Un cronjob lanzado sin `--local`
  cae en la rama de producción.
- `CleanLogsTask` limpia el log nuevo; limpiaba por nombre explícito y no lo conocía.
- **Deuda anotada**: `E_WARNING` y `E_NOTICE` siguen abortando. Es herencia, y cambiarlo
  merece su propia ventana de pruebas.

## Dependencias

- **Los cuatro paquetes propios** pasan a la misma forma canónica `">=8.4 <9.0"`, sin
  techo por minor. `piecesphp/database` era el único bloqueante real de 8.5.

| Paquete | Antes | Ahora |
| :-- | :-- | :-- |
| `piecesphp/database` | v3.0.4 | **v3.1.0** |
| `piecesphp/datastructures` | v3.0.0 | **v3.1.0** |
| `piecesphp/html` | v2.0.0 | **v2.1.0** |
| `piecesphp/geojson` | v2.0.0 | **v2.1.0** |

- **Symfony salta de 6.4 a 8.1** (`cache`, `filesystem`, `process`, `var-exporter`), más
  `phpspreadsheet` 5.9.0, `zipstream` 3.2.2 y `macroable` 2.1.0. Entran como transitivos:
  ningún archivo de `src/app` importa `Symfony\Component\*`.
- **`src/composer.lock` y `bin/tools/composer.lock` pasan a versionarse.** `src/` es la
  aplicación, no una librería, y con Symfony saltando dos majors hace falta
  reproducibilidad entre máquinas y despliegue.
- `composer why-not php 8.5` no devuelve nada.

## Herramientas

- PHPStan analiza el **rango** `{min: 80400, max: 80500}`, no una sola versión, y se añade
  `phpstan/phpstan-deprecation-rules`. Línea base congelada en
  `PHPStanResult.Summary.baseline.txt`: 1.078 errores en 192 archivos.
- Se retiran seis `ignoreErrors` de `cast.*` que no casaban con ningún error. `cast.*`
  significa «Cannot cast X to Y», no la sintaxis no canónica: esa PHPStan no la detecta.
- `bin/phpstan-process-result.php`: el regex de número de línea buscaba un formato que
  PHPStan no emite, así que el resumen imprimía `Líneas: , ,` y abortaba la generación de
  `bin/Preview`.
- `bin/cli` prefiere `php8.4` con fallback a `php`.
- `src/dumps/` (volcados de base de datos) pasa a estar ignorado por git.

## Validado

Recorrido completo del panel en **8.4 y 8.5**, con la promoción de deprecaciones activa:
login, panel, listado de Publications y su endpoint `-datatables`, formularios, las tres
exportaciones a Excel y el CLI completo. **Cero deprecaciones y cero 500.**
Las 9 suites de `piecesphp/database` (72 pruebas) verdes en ambas versiones.

# 7.0.6 (05-04-2026)

- **Exportador de Base de Datos Nativo**:
    - Se elimina el uso del ejecutable del sistema `mysqldump`.
    - Integración de `PiecesPHP\Core\Database\Export\Exporter` como nuevo motor para respaldo y exportación de bases de datos de forma agnóstica al sistema operativo.
    - Soporta múltiples formatos de salida (SQL, JSON, CSV, PHP, XML) y algoritmos de compresión (ZIP, Gzip, Bzip2, File).
- **Sistema CLI y Terminal**:
    - Novedades en el comando `db-backup` para elegir qué componentes backupear (`data`, `routines`, `views`, `definer`).
    - Scripts de autocompletado nativos en terminal para bash (`bin/pieces-completion.bash`) y zsh (`bin/pieces-completion.zsh`).
- **Sistema de Archivos y Logs**:
    - Soporte completo de manipulación de enlaces simbólicos (`Symlinks`) en `DirectoryObject` y borrado seguro.
    - Nuevo modelo de logs bajo demanda con trazas exclusivas y control de redundancia en formato plano para fácil lectura (`error.plain.log`).

# 7.0.5 (27-03-2026)

- **Sistema CLI y Terminal**:
    - Implementación de la clase `PiecesPHP\Cli` para gestionar argumentos y salida formateada en terminal.
    - Integración del soporte de `Cli` en `TerminalData` y actualización de `bootstrap.php`.
    - Refactorización de la detección de entorno local en `AppHelpers` y `CustomSlimErrorHandler`.
    - Se actualiza la versión de la aplicación a v7.0.5.

# 7.0.3 (26-03-2026)

- **Protección de archivos**:
    - Se implementó un sistema de protección de archivos que permite restringir el acceso a ciertos directorios.
    - Para ello se usa `ProtectFileMiddleware::protect`.
        - El cual es validado por `ServerStatics::protectFileMiddleware`.
    - El primer parámetro es la ruta del directorio a proteger.
    - El segundo parámetro es una función que recibe como parámetros un objeto Request y la ruta del archivo.
    - La función debe retornar true si se permite el acceso y false si se deniega.
    - Se puede usar la función `SessionToken::isActiveSession(SessionToken::getJWTReceived())` para validar la sesión.
```php
ProtectFileMiddleware::protect(append_to_path_system($uploadsDir, 'ruta/al/directorio'), function (Request $request, string $filePath) {
    return true;
});
```

# 7.0.2 (25-03-2026)

- **CLI**:
    - Mejor semántica en tareas de terminal que no son cronjobs y desacopladas del sistema de rutas con PiecesPHP\Terminal\CliActions.
    - Tareas afectadas
```bash
#Antes
bin/cli run-cronjobs unit-tests core/http-client
#Ahora
bin/cli unit-tests:core/http-client
#Antes
bin/cli run-cronjobs unit-tests core/helpers-directories
#Ahora
bin/cli unit-tests:core/helpers-directories
#Antes
bin/cli run-cronjobs mautic run
#Ahora
bin/cli tests:mautic-batch-send
```
- **Pruebas unitarias añadidas**:
    - [Ver](./files/dev/tests.md)
- **Eliminaciones**:
    - Se elimina la función `objectToArray`.

# 7.0.1 (25-03-2026)

- **Núcleo y Gestión de Archivos**:
    - Implementada normalización de rutas manual en `DirectoryObject` y `FileObject` para soportar enlaces simbólicos sin resolver `realpath()`.
    - Mejora en la seguridad de borrado recursivo para proteger las fuentes originales de los enlaces simbólicos.
    - Actualización en `ServerStatics` para la creación de enlaces simbólicos dinámicos más robustos.
- **Sistema de Logs**:
    - Nuevo método `loggingUniqueMessage()` en `GenericHandler` para registrar errores únicos con una firma detallada de 5 líneas (Cabecera + 4 niveles de traza).
    - Optimización del log JSON:
        - Eliminado el anidamiento redundante por segundos, agrupando ahora por día.
        - Limpieza automática de argumentos (`args`) en las trazas para reducir drásticamente el tamaño del archivo y mejorar la seguridad.
        - Eliminación de ordenamientos costosos (`uksort`) en cada escritura para mejorar el rendimiento.
- **ORM y Modelos**:
    - Ajuste de sintaxis en `where()` del `OTPSecretsUsersMapper` para compatibilidad con PiecesPHP\Core\* en versiones futuras.
- **Dependencias**:
    - Actualización de librerías composer: `pragmarx/google2fa` (v9), `hubspot/api-client` (v14), `spatie/url` (v2.4), `slim/psr7` (v1.8), `guzzlehttp/guzzle` (v7.10), entre otras.
    - Sincronización de la vista "About Framework" con las nuevas versiones.
- **Pruebas**:
    - Nueva suite de pruebas unitarias para validación de gestión de directorios y symlinks.
```bash
php index.php cli --local run-cronjobs unit-tests core/helpers-directories
```

# 7.0.0 (23-03-2026)

- Migración a PHP 8.4 funcional. Con soporte hasta 8.1.

# 7.0.0-beta

- Soporte para PHP 8.4 en proceso.
- Ajuste de composer.json.
- Upgrade con PHPStan:
    - Se ignoran falsos positivos con __() añadiendo doc condicional.
    - Se corrigieron nullables implicitos en el código.
    - Se corrigieron errores de variables no declaradas.
    - Hasta level 2 completo.

# 6.4.4 (22-03-2026)

- **Integración con Mautic**:
    - Refactorización de `MauticEmailAdapter` para mayor confiabilidad.
    - Prueba de procesamiento vía cronjob (`test-mautic-cronjob.php`).
        - Plantilla de ejemplo de correo (`template_mautic.php`).
```bash
php index.php cli --local run-cronjobs mautic run
```
- **HttpClient**:
    - Mejoras significativas en `HttpClient.php` con soporte para métodos modernos y mayor robustez.
    - Adición de pruebas unitarias exhaustivas para el cliente HTTP en src/app/core/system-controllers/local-tests/UnitTest-HttpClient.php
```bash
php index.php cli --local run-cronjobs unit-tests core/http-client
```
- **Gestión de Usua7rios (Soporte Mejorado sin Organizaciones)**:
    - Optimizada la lógica de visualización para admitir el funcionamiento del sistema cuando el módulo de organizaciones está desactivado.
    - Los formularios se ajustan dinámicamente ocultando campos relacionados con organizaciones si son innecesarios.
    - Reestructuración de formularios por tipos para mayor claridad.
    - Mejora en la visualización de perfiles en `user-card.php`.
    - Nuevo estado de usuario "Eliminado". Para una gestión ordenada las eliminaciones.
- **Núcleo y Otros**:
    - Ajustes en utilidades de `AppHelpers.php`.
    - Ajustes en utilidades de `Utilities.php`.
    - Mejoras menores en el punto de entrada `index.php`, incluyendo soporte para estados de inactividad equivalentes.

# 6.4.3 (18-03-2026)

- **Sistema de Colas (Implementación Inicial)**:
    - Introducción del sistema de procesamiento de tareas en segundo plano.
    - Implementación de `QueueTask` y `QueueHandlerResponse` para la gestión de colas.
    - Nuevo mapeador `QueueJobMapper` para persistencia de tareas con soporte para reintentos, programación diferida (`scheduledAt`) y registro de errores.
    - Tarea CLI `ProcessQueueTask` para el procesamiento robusto de la cola con manejo de señales y aislamiento de errores.
    - Ejemplo de implementación sugerida integrado en `TestQueueRequest`.
- **FreezeRequest (Persistencia de Contexto HTTP)**:
    - Motor de "congelación" de peticiones para su posterior ejecución en tareas de cola.
    - Captura completa de `$_POST`, `$_GET`, `$_FILES` (PSR-7 jerárquico), `$_COOKIE`, `$_SESSION` y `Body`.
    - Soporte para metadatos personalizados (`customData`) persistidos junto a la petición.
    - `UploadedFilesStructureMapper`: Nueva utilidad para normalizar y reconstruir estructuras complejas de archivos.
    - Lógica de limpieza recursiva de archivos temporales con gestión de permisos (`chmod 0777`) para operación multiplataforma (Web -> CLI).
- **Eventos de Base (Centralización)**:
    - Mejor centralización de los eventos del sistema en `BaseEventDispatcher`.
    - Introducción de `event-listeners.php` como archivo centralizado de utilidades para escuchar eventos globales de forma organizada. Ejemplo de migración.
- **Núcleo y Configuración**:.
    - Soporte mejorado para rutas y manejo de archivos subidos en `UploadedFileAdapter`.

# 6.4.201

- Ajuste en lógica de cronjobs internos.
- Añadido endpoint para ejecutar cronjobs desde terminal.

# 6.4.200002

- Ajuste de SQL a utf8mb4.
- Otros ajustes menores.

# 6.4.200001

- Ajustes para CORS.
- Algunos ajustes en mailing.
- Mejor gestión de errores en rutas 404.

# 6.4.2

- Eliminación de console.log innecesarios.
- Independización de archivos que gestionan la traducción con IA en el front.
- Internacionalización:
    - Mejora en revisión de traducciones pendientes.
    - Optimización de adaptadores de modelo IA para mejor manejo de las traducciones.
    - Fragmentos grandes de HTML se dividen en traducción con IA, deben ser especificados en asHTMLProperties.
    - Se destruye la conexión con la base de datos actual para evitar el error de "MySQL server has gone away" por tiempo de espera en la traducción con IA.
    - Se añade en lang.php la configuración DYNAMIC_TRANSLATIONS para gestionar elementos relevantes del sistema de inyección dinámica de mensajes de traducción.
    - Gestión de JSON de "traducciones" dinámicas se reemplaza por GeneriContentPseudoMapper.
    - Se simplifica la lógica de translations/saveGroup por solo interacción con base de datos.
    - Introducción de DynamicTranslationsHelper para persistencia de traducciones dinámicas. Ahora la base de datos se usa solo como un estado intermedio para guardar las traducciones "pendientes" y los mensajes fijos se circuncriben a un JSON denominado current-translations. Se gestiona fechas de actualización para no leer innecesariamente desde la base de datos. Se refactoriza add-dynamic-translations.php
- Servido estático de archivos personalizado:
    - Mejora en el servido de archivos estáticos desde los módulos.
    - Refactorización de ServerStatics.php.
    - ServerStatics.php crea enlaces simbólicos en statics/server-delegated para tener que servirlos siempre con PHP.
    - Los métodos staticRoute ahora se soportan con staticRouteModulesResolver de container para hacer funcionar la lógica de ServerStatics.php anteriormente descrita. Valida si el enlace simbólico existe.
- Bases de datos:
    - En BaseModel si introdujo gestión de tiempo de ejecución de MySQL con PDO::ATTR_TIMEOUT basado en 'max_execution_time' de PHP.
- Sesión:
    - Corregido: Ahora se toma en cuenta distintos estados de organización que son candidatos para habilitar el ingreso.
- Configuraciones en config.php:
    - Se introducen: domain, domain_protocol, base_domain_path, base_url.
        - i.e.: domain.tld, https://, /ruta/base/src, https://domain.tld/ruta/base/src
- En librerías base del framework:
    - Loader general:
        - Modulizarización de showGenericLoader, removeGenericLoader activeGenericLoader por un manejador desde una clases.
        - Se mejoró la lógica interna y se añadió la posibilidad mostrar un mensaje.
    - Se independizó la función genericFormHandler hacia un archivo único.
- En el adaptador del editor CKEditor:
    - Se añadió insertLink para permitir la posibilidad de carga de cualquier tipo de archivo como link.
    - Se hizo el ajuste correspondiente en la gestión del manejador de archivos.
- Se recomienda el tag en comentarios @category SpecialCaseSolution para soluciones particulares de modo que sean fáciles de buscar.
- Llaves mapbox se manejan desde "variables de entorno".

# 6.4.2-beta

- Ajuste de bug que hacía que se registraran sesiones "expiradas" sin motivo.
- Separación de require-dev del composer principal hacia bin/tools.
- Mejoramiento de base de código js del framework:
    - CookiesHandler.
    - GenericStepsViewHandler.
    - Mejor gestión de adición de librerías adicionales en helpers mediante combinación en gulp con helpers-lib/*
    - Exposiciones de pcsAdminSideBarIsOpen y pcsAdminSideBarToggle para manipular el sidebar del menú.
    - Manejo de persistencia de estado (plegado/desplegado) del sidebar con localStorage.
    - registerDynamicLocalizationMessages y relacionados puede cargar múltiples grupos simultáneamente.
    - Adición ignoreSearch en MapBoxAdapter para casos en los que no se quiera ejecutar la búsqueda de forma automática.
- Vista de reporte integrada en front.
- Internacionalización:
    - Adición de mensajes, en general.
    - Optimización de manejo persistente de idiomas, preferencia según navegador y otras mejoras. Se delega el manejor pleno a Config.php
- Ajustes de permisos según organizaciones y de sistema de aprobaciones.
- Ajustes de algunas opciones por defecto en inicio de MySpace.
- Simplificación general de archivos delete-config.js en términos de internacionalización. Es el primer paso para le delegación completa al sistema en lugar de manejarlo en el archivo.
- Mejora en el manejo de errores para renderización de BaseController.
- Mejora de funcionalidad de validaciones en PiecesPHP\Core\Validation\Validator.
- Sesión:
    - El inicio de sesión toma en cuenta estados de usuario y de organización que son candidatos para habilitar el ingreso.
- Sidebar interno diferenciado según tipos de usuario.
- En aprobaciones:
    - Se verifica isActive que se añade dinámicamente por el manejador.
    - Optimización de auto aprobaciones.
- Soporte base de reportes.
- Soporte de "variables de entorno" con GeneriContentPseudoMapper.
- Varios modos de listado base de publicaciones.

# 6.4.0

- Unificación de archivos del módulo de ubicación.
- getPCSPHPConfig a configurations.js.
- Mejora del sistema de traducciones y agrupaciones de mensajes más modularizadas.
    - Actualización de módulo de noticias internas y de publicaciones.
    - Mejoramiento de función de cambio de idioma y manejo persistente de selección (lang_by_cookie, cookie_lang_definer).
    - Búsqueda de traducciones faltantes con scan-missing-lang y registro de faltantes en app/lang/missing-lang-messages.
- Unificación de plantillas de correo en view/mailing/template_base.php y plantilla con poco html en view/mailing/template_base_no_style.php.
- Ampliación de roles de usuarios base.
- Mejora del listado de usuarios.
- Sistema de usuarios con capa de aprobación y mejor acoplado a sistema de organizaciones. Como medida que "prescinde" de esa características se puede dejar la organización base única.
- Sistema de "Perfiles" para usuarios y organizaciones.
- Ajuste de error en DefaultAccessControlModules que hacía que algunas rutas se mostran indebidamente con 403. Se verifica que empiece por la parte comparada del nombre de la ruta que se está buscando.
- Eliminación y reordenamiento de código scss.
- Mejoramiento de LocationsAdapter para trabajar con par país-ciudad (y más) y de MapBoxAdapter para mejorar la búsqueda del geocoder. Y mejoras en general.
- En AttachmentPlaceholder se agregó una opción para nombres personalizados distinto del nombre del archivo.
- Para ROOT, se integra en backend la posibilidad de "conectarse" como otro usuario.
- Adjuntos en Publications es añadible.
- Ajustes de lógica y orden en sistema de reporte de login.
- Ajuste dinámico de algunos permisos según si se es el administrador de una organización.
- Sistema de aprobación, según el que si no se está aprobado el márgen de acción es limitado (integrado con organizaciones, usuarios, convocatorias y publicaciones).
    - BaseEntityMapper intercepta fieldsToSelect (por lo tanto debe definirse como protected) con y devuelve un campo en consulta relacionado al estatus de aprobación (systemApprovalStatus).
- Comentarios @category AddToBackendSidebarMenu para rastrear mejor el uso del menú lateral del backend.
- ContentNavigationHub como un módulo de navegación entre los contenidos de otros módulos internamente.
- Implementación de un sistema de eventos en BaseEventDispatcher. Útil para el sistema de aprobaciones.
    - En BaseEntityMapper se disparan: saving, saved, updating y updated.
    - aseEventDispatcher::dispatch('AddDynamicTransaltions', 'added') para después de añadidas las traducciones dinámicas.

# 6.3.4

- Mejoramiento de multi-idioma.
- Traducción de textos faltantes.
- Integración con IA para traducción.
- Configuración dinámica de IA OpenAI y Mistral.
- Flujo de multi-idioma de Publicaciones mejorado, integración con traducción por IA.
- Acceso a claves seguras con getKeyFromSecureKeys.
- Evento onChange en RichEditorAdapterComponent y método textareaTarget.get(0).updateRichEditor
- onSuccessFinally en genericFormHandler
- PCSPHP-Response-Expected-Language como método de definir un idioma para la respuesta back-end desde front-end (recibe el idioma, ie.: es, en, fr, etc....)
- Mejoramiento de configuraciones finales, se pueden añadir archivos indefinidamente para configuraciones más claras.
- getExtension en FileObject

# 6.3.1

- Módulo de localización mejorado con LocalizationSystem que permite acceder a las traducciones desde front mediante una ruta con registerDynamicLocalizationMessages.
    - Se añade en el header la ruta lang-messages-from-server-url
- onLogout en PiecesPHPGenericHandlerSession.
- Actualización de adminer.
- Estructura de base de datos definida en utf8mb3.
- Organizaciones:
    - Ajustes en permisos.
    - Campos requeridos.
    - Traducciones.
- Ajustes menores en filtro de países.
- Ajustes menores en vistas de recursos de MySpaceController.
- Adición de SurveyJS como plugin frontend integrado.
- GEO_IP en config.php.
- Mejoramiento en manejo de errores 403 y 404.
- Función para devolver banderas según idioma en set_config 'get_fomantic_flag_by_lang', lang.php.
- Más idiomas por defecto.
- Remoción de #[\ReturnTypeWillChange].
- Prevención de inexistencia de constantes de carpeta de errores en GenericHandler.
- Mejor manejo de errores en BaseController.
- Mejoramiento en convert_lang_url y adición de lang2 y getCookie.
- Configuración pcsphp_system_translations contiene todas las traducciones.
- setConfigValue en AppConfigModel para agilizar la creación.
- Tipo de usuario Administrativo => Administrador.
- Ajustes en plantillas de correo.
- Adición de mailing-logo en gestión de imágenes.
- Intentar usar color principal en círculo de carga genérico.
- Configuración alternatives_url_include_current incluye la ruta del idioma actual.
- Configuración calculate_alternatives_langs_urls es una función que recrea las alternatives_url y alternatives_url_include_current.

# V6.3.0

- Independización de módulo importador.
- Manejador de sesiones sin usuario: PiecesPHPGenericHandlerSession, SessionTokenIsolated.
- Ajustes de seguridad en rutas expuestas.
- En módulo de publicaciones cambio de self::view por $this->render sobreescrito para no repetir importación de módulos.
- Unificación y simplificación de plantillas de correo electrónico.
- Nuevos métodos de encriptación bidireccional (BaseHashEncryption).
- Utilidad para crear cookie: setCookieByConfig.
- @strftime para ignorar deprecated.
- TokenModel/TokenController ajustados.
- Ajustes menores en módulo de ubicaciones.
- GoogleReCaptchaV3 ajustado para poder ser desactivado.
- Ajustes en recursos de prueba.

# V6

- Cambio de versión de Slim a v4.
    - Ya no es retrocompatible.
- Verisión mínima de compatibilidad de PHP: 7.4

# V5

- Implementación de la plantilla Editorial de HTML5UP en el front por defecto.
	- Formulario de contacto.
	- Vistas de blog.
	- Slidershow.
- Migración a Gulp 4 para las tareas.
	- Se recomiendan los pasos:
		- npm install
		- npm audit --force -fix
		- npm --force install
- Actualización a JQuery 3.5.1
- PiecesPHPSystemUserHelper.js libre de JQuery (usa Fetch API).
- Creación de CustomNamespace.js para algunas tareas genéricas (con la intención de eliminar helpers.js en el futuro)
	- Slideshow.
	- Desplazamiento suave.
	- Loader.
- Varias modificaciones que no afectan el comportamiento en algunos archivos JS/PHP.
- En el módulo de imágenes (HeroController en PHP) se implemento internacionalización y posibilidad de eliminar.
- Mejoramiento del sistema de traducciones.
- Mejoramiento en el sistema de rutas.
Nota: No hay nigún problema de retro-compatibilidad conocido.
