# Pruebas útiles en desarrollo

> Para fines de ejecución local se usa ```$ bin/cli ``` para declarar explícitamente que es loca, que es lo mismo que usar ```$ php index.php cli --local ```.

## Verificación de integridad

```bash
bin/cli verify-integrity                      # comprueba
bin/cli verify-integrity update-snapshot=yes  # regenera la instantánea
```

Devuelve **código de salida 1** si algo falla, así que sirve tal cual en CI.

Comprueba **treinta y una** cosas (la 30, `GET_ROUTE`, desde P36: ver `05-routing-y-permisos.md`; la 31, la carga del arranque), numeradas en el propio `VerifyIntegrityTask::run()`: **esa numeración es la fuente, no esta lista.** Las dos primeras, sobre los archivos PHP de `src/app` e `index.php`:

1. **Docblocks sin cerrar.** Un comentario de bloque que no cierra, y —lo importante— un
   docblock que se ha tragado una declaración de función.
2. **Firmas desaparecidas.** Compara el inventario de funciones y métodos contra
   `files/dev/integrity-signatures.json`, que se versiona. Solo reporta desapariciones:
   una firma nueva es trabajo normal, que algo deje de existir no lo es.

**Por qué existe.** Una sesión que solo tocaba docblocks dejó uno sin cerrar; el
comentario se tragó la declaración siguiente y ese método dejó de existir.
**`php -l` no lo detecta** —un docblock sin cerrar no es un error de sintaxis— y PHPStan
tampoco lo señaló. Se reprodujo el fallo para validar la tarea: `php -l` responde «No
syntax errors» y `verify-integrity` lo caza por las dos vías.

Usa el analizador léxico de PHP, no expresiones regulares sobre el texto: `/*` aparece
dentro de cadenas —`'image/*'` es el caso típico— y contarlo a pelo daba 32 falsos
positivos en las vistas.

Las otras veintisiete, en el orden en que corren:

| # | Qué comprueba |
| --: | :-- |
| 3 | Toda clase declarada **se puede cargar** por su ruta PSR-4 |
| 4 | El núcleo **no eclipsa** ninguna clase de los paquetes `piecesphp/*` |
| 5 | Las sobreescrituras de ruta **siguen decidiendo algo** |
| 6 | No hay llamadas a **funciones deprecadas** |
| 7 | Los cuatro paquetes **no se han desviado** del instrumental común |
| 8 | No crecen los **comentarios narrativos** |
| 9 | Los guiones de `bin/` están **marcados como ejecutables en el índice de git** (T51) |
| 10 | Todo tipo declarado en un `$fields` **existe en el vocabulario** de `EntityMapper` |
| 11 | La lista de tablas con acuñado de slug de `volatile-state.json` **coincide con la que el código descubre** |
| 12 | La lista de **rutas prohibidas vive en un solo sitio**, y sus **excepciones solo liberan rutas GET**, con su razón escrita (T100) |
| 13 | Todo el PHP versionado **se analiza o está declarado fuera** del universo de PHPStan |
| 14 | Todo `objectToMapper()` **siembra la instantánea** de la fila |
| 15 | Ninguna propiedad se declara **después del primer método** (T89) |
| 16 | Ningún docblock quedó **separado de lo que documenta** (T91) |
| 17 | La versión **instalada** de cada paquete contra la **última etiquetada** en su repositorio hermano. **AVISA, no falla** (T107) |
| 18 | Ningún `if/else` tiene **las dos ramas iguales**. Por ÁRBOL DE SINTAXIS (nikic/php-parser), no por expresiones regulares (bloque S) |
| 19 | Los **retornos ignorados** sin declarar no han crecido |
| 20 | Ningún **enlace** del árbol servido apunta al vacío |
| 21 | Las **claves de traducción** que nadie pide no han crecido |
| 22 | Las **etiquetas de cada vista** cuadran |
| 23 | Ninguna **ruta** de un módulo con control de acceso queda **sin declarar** |
| 24 | Las **concatenaciones de SQL** con valor de petición no han crecido |
| 25 | Ninguna forma **«para leer»** acaba ejecutándose sin declararlo |
| 26 | Las cifras de la **línea base de PHPStan** dicen lo mismo |
| 27 | Ningún **identificador de SQL** viene de la petición |
| 28 | La **interpolación de SQL** con valor de petición no ha crecido |
| 29 | Toda **carpeta de subidas** está protegida o declarada |

> La de los tipos existe porque `'type' => 'test'` —«text» mal escrito— sobrevivió años en
> `SystemApprovalsMapper`: con un tipo desconocido, `validateType()` devuelve **`true` para
> todo**, o sea que el campo **deja de validarse**. Ver T54. Comprueba 370 tipos declarados.

> La de los volátiles existe porque esa lista está **copiada**: sale de
> `PreferSlugsFiller::mappersWithSlug()`, y una vez escrita nada detectaba que divergiera. Añadir
> un módulo con `preferSlug` la dejaba corta en silencio y el recorredor reportaría un hallazgo
> falso.

> Esa última existe porque el repositorio tiene `core.fileMode = false`: `chmod +x` funciona en
> el disco y **git no lo registra**, así que el guion corre aquí y llega sin permisos a quien
> clone. **Mira también el final de la primera línea**: un guion con CRLF no arranca, porque
> `env` busca un intérprete llamado «php\r» — pasó con tres. En su primera corrida encontró cuatro, y uno era **`bin/rector`**, que está documentado
> en `CLAUDE.md` y devolvía «Permiso denegado» (salida 126).

Cuando un cambio de firmas sea intencionado, regenera la instantánea y **commitéala con
el mismo cambio**.

> **`ReflectionProperty::setAccessible()` está DEPRECADO en PHP 8.5** y aquí una deprecación **aborta**: una prueba
> con reflexión que lo llame muere con «deprecated since 8.5, as it has no effect since PHP 8.1». Desde 8.1 la
> reflexión lee lo protegido sin él: no se llama (medido en `#763`).

> **Trampa de la 19, medida el 2026-10-02 (`#759` y `#769`):** la marca `//RETORNO-IGNORADO:` exime **su línea y las
> DOS siguientes, contando desde la PRIMERA línea de la marca**. Así que con tres llamadas seguidas la tercera se
> queda fuera, y **si la propia marca ocupa dos líneas, solo cubre UNA llamada**. Lo seguro: la marca en una línea,
> delante de cada llamada.

> **`gates` NO corre las suites que declaran `network`** (10 el 2026-10-02, entre ellas `core/injected-scripts` y
> `core/backups-screen`): lo dice con su motivo y la forma de incluirlas, `bin/cli gates with=external`. Ojo: eso
> arrastra `tests:mautic-batch-send`, que declara `email` y envía correo de verdad (ADR 0011). Una suite de pantalla
> que se mezcle con una de funciones puras le pega su `network` y **saca a las dos de `bin/verify`**: por eso la
> pantalla de respaldos tiene suite propia.

> **Trampa de la 19, la otra cara:** el trinquete falla si las **sin declarar** crecen, pero **no si las
> «declaradas» que dice el artefacto son MENOS de las que mide el campo** (ver la memoria
> `trinquete-declaradas-count-mayor`). El 2026-10-02 el artefacto decía 79 y el campo medía 89 sin que nada fallara.

## La línea base de PHPStan

`PHPStanResult.Summary.baseline.txt` **no es una corrida viva**: es el punto de
comparación congelado. `bin/phpstan` reescribe `PHPStanResult.txt` y
`PHPStanResult.Summary.txt` en cada ejecución, pero el `.baseline.txt` solo cambia
cuando **decidimos aceptar** un recuento nuevo.

Se versiona a propósito, por lo mismo que la instantánea de integridad: un clon limpio o
CI no tienen contra qué comparar sin él. Y `PHPStanResult.txt` también, porque
`bin/tools/refactorization/Rector.php` lee de ahí la lista de archivos que analiza.

```bash
bin/phpstan        # corrida viva: sale con 1 si el total sube, o si la base no declara su reparto
```

**También sale con 1** (`#324`):

- si PHPStan no pudo analizar algún archivo (error de sintaxis, `phpstan.parse`, un archivo ilegible): con uno roto,
  PHPStan informa **1 error en vez de 669** y antes eso pasaba en verde;
- si el total queda **por debajo** de la base sin haberla actualizado con su `[REPARTO]`: bajar es bueno, pero hasta que
  se declara no es una cifra de confianza. Se anota el reparto, se vuelve a correr y da «igual que el baseline».

**La base no se acepta copiando el archivo** (la forma con `cp` quedó vieja). Se edita a mano:

- la cifra, en un solo sitio: el campo `[TOTAL DE ERRORES VISIBLES]` del `[RESUMEN]` del final;
- una línea `[REPARTO] <nueva> <- <anterior> = <n> arreglos + <n> supresiones`, con `+ <n> destapados` o
  `+ <n> murieron` cuando los haya (`bin/phpstan-process-result.php:190-258` rechaza un reparto que no cuadra).

La comprobación 26 de `verify-integrity` falla si las cifras de la base se separan. Mover la base es una
decisión, no un paso rutinario: solo con el cambio que la justifica, y **en el mismo commit**.

### Las supresiones van en dos listas (lote 9.2)

`ignoreErrors` de `bin/phpstan.neon` está partido en dos:

- **PERMANENTES**: el motivo es el diseño del framework (variables que inyecta el renderizador en las vistas,
  interruptores de módulo por despliegue, suites de caracterización, la política de tipos que no se exige). No
  llevan condición de retirada.
- **TEMPORALES**: deuda. Cada una lleva `# SE RETIRA CUANDO: <condición>`. Se retira quitándola, corriendo
  `bin/phpstan` y arreglando o acotando por ruta lo destapado, con la línea `[REPARTO]`.

Entre las dos queda el bloque **«Condiciones redundantes y código muerto»**, que es mixto y no se parte:
`bin/phpstan-deadcode` lo localiza por dos delimitadores literales (el título del bloque y `# ── PHPDoc ──────────`).
Dentro, cada MOTIVO dice la clase: permanentes el 1, 2, 3 y 5; temporales el 4 y `if.alwaysFalse` por ruta.

**EL ORDEN IMPORTA.** Un error que casa con dos entradas lo consume la PRIMERA; la otra sale en `PHPStanResult.txt`
como «Ignored error pattern … was not matched». El total visible **no se entera**. Medido en `#184`: un primer
reordenamiento dejó el total en 715 y pasó de 6 a 9 patrones sin casar. Por eso las específicas van antes que las
generales que las cubren. La de `DataImportExportUtilityRoutes.php` no casaba (la consumía el `if.alwaysFalse` por
ruta) y se retiró en el lote 10, con su sección «fuera de lista por orden».

**Una supresión nueva entra en una de las dos listas**, y si es temporal, con la condición. Un reordenamiento se da
por bueno solo si `PHPStanResult.txt` sale idéntico salvo el ancho de la tabla, no por el total.

**Un patrón que no casa es una puerta roja** (lote 10, ronda C): `bin/phpstan-process-result.php` lee los errores
sin archivo de la pasada (`PHPStanResult.8.5.json`; desde el ADR 0020 solo se mide PHP 8.5) y sale con 1 si alguno es «Ignored error
pattern … was not matched». Una supresión muerta miente sobre lo que se calla, y al reordenar puede empezar a tapar
otra cosa. Los seis que había se retiraron en esa ronda.

## Unitarias

### Quién juzga cada suite

**Una prueba vale más cuando quien juzga no es quien produjo el resultado** (T21). Esta tabla
está para saber, de un vistazo, cuáles se apoyan en un juez externo y cuáles se creen a sí
mismas — las segundas son las que hay que mirar con más cuidado.

| Suite | Quién juzga |
| :-- | :-- |
| `core/scheme-sql-round-trip` | **MariaDB** — aplica los dos scripts de verdad |
| `core/prefer-slug` | **MariaDB** — crea filas reales y comprueba lo que queda escrito |
| `core/generic-content` | **MariaDB** — cuenta filas antes y después |
| `core/db-restore` | **MariaDB** + **`db-backup`** — restaura un volcado real producido por la tarea de respaldo, no uno de juguete. Ver T96 |
| `core/database/type-vocabulary` (paquete) | **Se juzga sola**, pero compara seis fuentes entre sí: la deriva de una la delatan las otras cinco |
| `core/db-backup-round-trip` | **MariaDB** + `password_verify()` |
| `core/mapper-finders` | **MariaDB** |
| `core/otp-fresh-user` | **MariaDB** |
| `core/helpers-directories` | **El sistema de archivos** |
| `core/http-client` | **La red** — sale a `example.com` y comprueba el tiempo de espera contra `10.255.255.1`. Declara `network`: solo corre con `gates with=external` (T130) |
| `core/http-client-request-build` | **Se juzga sola** — mira la URI, el cuerpo y las cabeceras que construye el cliente, sin red. Entra en la pasada por defecto |
| `core/database-exporter` | Base de datos y archivo |
| `core/otp-write-separation` | **Mixta** — tres comprobaciones leen el cuerpo del método |
| `core/meta-property-hybrid` | **Se juzga sola** (reflexión) |
| `core/session-user` | **Se juzga sola** (valores devueltos) |
| `functions/systemOutFormatted` | **Se juzga sola** |
| `core/read-paths-survive` | **MariaDB** — y exige que el caso exista en los datos antes de medirlo |
| `core/symlink-no-window` | **El sistema de archivos** — un banco en `sys_get_temp_dir()`, más tres comprobaciones que leen el cuerpo del método (T108) |
| `functions/systemOutFormatted` | **Se juzga sola** — ejerce los DOS modos pasando la condición de terminal: **17/17, cero omitidas y sin pty** (T124) |
| `core/operation-from-route` | **MariaDB** — invoca el controlador de verdad; el caso coherente usa un id inexistente, así que **no escribe** (T120) |
| `core/cache-criteries-round-trip` | **Se juzga sola** — la IDA la produce `jsonSerialize()` de producción, no la prueba (T123) |
| `core/mautic-batch-logic` | **Se juzga sola** — el recorrido de envío masivo contra un transporte FALSO: sin red, sin claves, sin correo (T125) |
| `tests:mautic-batch-send` | **RED y CORREO** — la otra mitad, con el adaptador real. Fuera de la pasada por defecto, y VISIBLE con su motivo (T125, T126) |
| `verify-integrity` | **Se juzga sola**, salvo el analizador léxico de PHP |

#### `core/http-client`: dos suites desde T130

El 2026-08-25 salió de la pasada por defecto, porque escribía a un buzón de `webhook.site` con un identificador
fijo, y nunca había impreso balance. Se reconstruyó en dos: `core/http-client-request-build` prueba sin red lo
que el cliente construye, y entra en `gates`; `core/http-client` sale a la red contra destinos reservados
(`example.com`, por la RFC 2606, y `10.255.255.1` para el tiempo de espera), y solo corre con `with=external`.



- PiecesPHP\Core\Helpers\Directories
    - Se probaron las siguientes funcionalidades:
        - Normalización de rutas
        - FileObject y Enlaces Simbólicos
        - DirectoryObject Scan y No-Recursión en Symlinks
        - FilesIgnore (Exclusión e Inclusión)
        - Borrado Seguro (Trust the Path)
    - src/app/core/system-controllers/local-tests/UnitTest-Helpers_Directories.php
```bash
bin/cli unit-tests:core/helpers-directories
```
- PiecesPHP\Core\Http\HttpClient
    - Se probaron las siguientes funcionalidades:
        - GET con parámetros de consulta
        - POST con cuerpo JSON
        - Fusión con override_defaults = true
        - Fusión con override_defaults = false
        - Timeout configurado
    - src/app/core/system-controllers/local-tests/UnitTest-HttpClient.php
```bash
bin/cli unit-tests:core/http-client
```
- Buscadores de mapper (`getBy`, `lastModifiedElement`, `getByMultipleCriteries`)
    - Congela el contrato de los buscadores estáticos antes de tocar la nulabilidad.
    - Se probaron:
        - `getBy()` con id inexistente devuelve `null`
        - `getBy()` sin el flag devuelve `\stdClass`
        - `getBy()` con el flag devuelve una instancia del propio mapper
        - `lastModifiedElement()` respeta el mismo contrato, y sus dos ramas coinciden
          en si hay resultado
        - `getByMultipleCriteries()` sin coincidencia devuelve `null`
    - **Es de solo lectura**: no inserta, no actualiza y no borra. Descubre un id
      existente en ejecución y omite el caso «encontrado» si la tabla está vacía.
    - **Recorre mappers reales a propósito.** `getBy` no se hereda: está copiado en 26
      mappers concretos, así que una prueba contra un mapper de juguete sería una copia
      más y no protegería ninguno.
    - src/app/core/system-controllers/local-tests/UnitTest-MapperFinders.php
```bash
bin/cli unit-tests:core/mapper-finders
```
- Sesión y usuario (`getLoggedFrameworkUser`, `SessionToken`)
    - **Pruebas de caracterización, no de aspiración**: describen el comportamiento
      ACTUAL, incluido el defectuoso, para poder cambiarlo con red.
    - Se probaron:
        - `getLoggedFrameworkUser()` sin sesión devuelve `null`, y es estable
        - Encadenar sobre ese resultado sin comprobar **falla** — la forma exacta de los
          123 errores de nulabilidad
        - `SessionToken::getJWTReceived()` devuelve **cadena vacía**, nunca `null`
        - `isActiveSession()` con entradas inválidas devuelve `false` sin lanzar
        - Sin sesión activa no hay usuario: las dos vías coinciden
    - Cuando la ventana de nulabilidad cambie el contrato, varias fallarán. **Ese fallo
      es la señal**, no un problema: cada prueba dice qué se espera que pase entonces.
    - src/app/core/system-controllers/local-tests/UnitTest-SessionUser.php
```bash
bin/cli unit-tests:core/session-user
```
- Separación de lectura y escritura en OTP
    - **Dos comprobaciones son ESTRUCTURALES a propósito**: la versión de comportamiento
      exigiría crear un usuario sin registros —escribir en una base ajena— y además no
      fallaría, porque el relleno masivo que había en `routes()` tapaba el defecto.
    - Se probaron:
        - `getOTPData()` y `getTOTPData()` no contienen ninguna escritura
        - `UserSystemFeaturesRoutes::routes()` no consulta ni escribe
        - Un intento de credenciales fallido no cambia el conteo de filas (solo lectura)
    - src/app/core/system-controllers/local-tests/UnitTest-OTPWriteSeparation.php
```bash
bin/cli unit-tests:core/otp-write-separation
```
- `MetaProperty` tal como se ejecuta AQUÍ (el híbrido)
    - **Existe porque nadie prueba esta combinación.** `MetaProperty` está declarada dos
      veces —núcleo y `piecesphp/database`— y PSR-4 hace ganar siempre a la del núcleo por
      prefijo más largo. Lo que corre es `MetaProperty` del núcleo llamando a
      `EntityMapper::validateType()` del paquete, y **ninguno de los dos repositorios prueba
      eso**: la suite del paquete llama a `MetaProperty::validateType()`, un estático que en
      la copia que corre aquí no existe.
    - Se probaron:
        - `MetaProperty` resuelve al archivo del núcleo y `EntityMapper` al del paquete
        - Existen los métodos que `EntityMapperExtensible::addMetaProperty()` consume, y el
          mensaje de error nombra el campo
        - La ruta de fecha —por donde llegó **de rebote** el arreglo de PHP 8.5— acepta
          `null` y guarda la cadena TAL CUAL, sin convertirla en `DateTime`
        - `null` en un campo mapper anulable no instancia nada
        - Nada de lo anterior emite una deprecación
    - **Es de solo lectura**: el caso de tipo mapper usa `null` justamente porque es el
      camino que no llega a tocar la base de datos.
    - **Contraste comprobado**, no supuesto: cargando la copia del paquete en aislamiento,
      `getInternalName()` no existe, `validateType()` sí, y una fecha vuelve como `DateTime`
      en vez de como cadena. Cuatro de las doce comprobaciones cambian de resultado según
      qué copia se cargue, que es exactamente lo que se quería fijar.
    - src/app/core/system-controllers/local-tests/UnitTest-MetaPropertyHybrid.php
```bash
bin/cli unit-tests:core/meta-property-hybrid
```
- `db-restore`, el viaje de ida y vuelta
    - Respaldar, cambiar, restaurar y comprobar que volvió. Más el rastro que la LEY 12 necesita.
    - src/app/core/system-controllers/local-tests/UnitTest-DbRestore.php
```bash
bin/cli unit-tests:core/db-restore
```
- Contenido genérico: leer no crea, guardar sí
    - Construir el pseudo-mapper —abrir el formulario— y leerlo **no** deja fila; guardarlo sí, y
      guardar de nuevo no duplica.
    - src/app/core/system-controllers/local-tests/UnitTest-GenericContent.php
```bash
bin/cli unit-tests:core/generic-content
```
- El acuñado del `preferSlug`
    - Que dos peticiones simultáneas **no** acuñen dos slugs distintos, que una fila sin nombre
      no reciba URL permanente, y que la tarea de relleno masivo rellene lo nulo **sin tocar** lo
      que ya tiene valor.
    - src/app/core/system-controllers/local-tests/UnitTest-PreferSlug.php
```bash
bin/cli unit-tests:core/prefer-slug
```
- El SQL del esquema, de ida y de vuelta
    - Descubre TODOS los mappers, emite el `CREATE` y el `DROP`, y **se los da a MariaDB** en
      una base de usar y tirar.
    - **En verde.** Salió en rojo mientras había tablas que no se podían crear desde sus propios mappers
      (T52); si vuelve a rojo, un mapper dejó de describir su tabla.
    - src/app/core/system-controllers/local-tests/UnitTest-SchemeSqlRoundTrip.php
```bash
bin/cli unit-tests:core/scheme-sql-round-trip
```
- Pruebas variadas sobre funciones
    - src/app/core/system-controllers/local-tests/UnitTest-Functions.php
```bash
bin/cli unit-tests:functions/systemOutFormatted
```

## Otras

- Prueba de uso de Mautic
    - Se probaron las siguientes funcionalidades:
        - Segmentación automática
        - Envío de emails
    - src/app/core/system-controllers/local-tests/test-mautic-cronjob.php
    - Se deben configurar las credenciales de Mautic en secure-keys/mautic en el siguiente formato:
```txt
[API_URL]::[CLIENT_ID]::[CLIENT_SECRET]::[EMAIL_FROM]
```
```bash
bin/cli tests:mautic-batch-send
```

## Recorrer las rutas

```bash
bin/cli route-inventory                                   # primero, el inventario
bin/walk-routes    --base=https://85.localhost/…/src       # ¿revienta algo?
bin/walk-attribute --base=https://85.localhost/…/src       # ¿qué ruta de lectura ESCRIBE?
```

Las dos **invalidan la caché de código al arrancar** y abortan si no pueden.

`walk-attribute` toma una foto de la base y del árbol **después de cada petición** y le atribuye
la diferencia a la ruta que la provocó. Separa lo declarado en `files/dev/volatile-state.json` de
lo que no, y **sale con código 1** si aparece algo no declarado.

Con sesión, que es donde está lo interesante:

```bash
PCSPHP_WALK_USER=… PCSPHP_WALK_PASS=… bin/walk-attribute --base=… --json=salida.json
```

**Ojo con lo que NO puede ver**: 22 de las 36 tablas están vacías, así que un camino que solo
escribe cuando encuentra una fila incompleta no se dispara. El recorrido **subestima**.

## Una suite o una tarea que no carga (`#318`)

Cada suite de `local-tests/` y cada tarea de `Terminal/Tasks/` se carga con `PiecesPHP\Terminal\LoadFailures::includeFile()`,
dentro de un `try/catch`. Una que no carga (un error de sintaxis, una excepción al incluirla) **no tumba `bin/cli`**:

- cada comando escribe en STDERR `AVISO: no se cargó <archivo>: <clase>: <mensaje>` y sigue, sin cambiar su código de
  salida;
- `gates` la pinta `[NO CARGÓ]` y la cuenta como **fallo** (nunca como omitida), y sale en rojo;
- `verify-integrity`, comprobación 31, falla con «ARRANQUE: suite|task: no se cargó …» y, sin fallos, informa cuántos
  archivos cargó de cada tipo.

**Límite:** un error de compilación que PHP no convierte en excepción (redeclarar una clase) sigue siendo mortal:
`bin/cli` sale con 255. Una ruta con un patrón inválido también tumba todo, web incluida (P57): la detecta
**`bin/check-routes`**, que arranca el framework por la rama `_list-actions` de `src/index.php` (vuelve antes de
`$app->run()`), analiza cada patrón con `FastRoute\RouteParser\Std` y nombra la ruta mala aunque `bin/cli` no arranque.
Sale 0 (todas analizables), 1 (alguna inválida) o 2 (el framework no llegó a registrar las rutas: no aprueba). **No
puede ir dentro de `gates`**: con una ruta mala, `bin/cli` cae antes de llamarlo. Va en `bin/verify` (grupo L).

## Pintar una vista del panel desde la terminal

Una prueba que quiera el HTML de `panel/layout/header`, `topbar` o el menú lateral con un usuario concreto tiene que
preparar lo que `src/index.php` §9 hace en cada petición web (`#310`, H1):

- `set_config('alternatives_url', [])` (sin eso, `topbar.php` falla en su bucle de idiomas);
- `Roles::setCurrentRole($tipo)` del usuario fijado;
- incluir `src/app/config/menu.php` en modo silencioso (sin eso, `get_sidebar_menu()` falla).

Y **fijar un usuario**: sin usuario, `routeName()` y `allowedRoute()` conceden todo y la prueba da un verde falso.
Comprobación útil: el HTML no contiene `href=""`.

## La caché de la aplicación viva

Cualquier medición A/B contra la web **tiene que invalidar la caché de código antes de medir**,
y desde T51 eso no depende de acordarse: vive en el arnés y aborta si no puede hacerlo.

```bash
bin/live-cache --base=https://85.localhost/vicsen/piecesphp/src --report      # qué SAPI, qué ventana
bin/live-cache --base=… --invalidate --file=<archivo editado>                 # invalida y explica la espera
bin/live-cache --base=… --self-test                                           # provoca la trampa y la desactiva
```

`bin/walk-routes` la llama al arrancar. **Nadie puede recorrer sin invalidar.**

**La ventana medida en esta máquina son 3 segundos** —`max(revalidate_freq,
file_update_protection) + 1`, con los dos en 2— y sale de `php-fpm8.5 -i`, no de los `.ini`:
OPcache viene compilado en el binario y los archivos de configuración no lo mencionan.

## Las seis identidades de prueba y cómo se reconstruyen

**Por qué está aquí y no solo en el ADR 0023:** el ADR decide *que* las contraseñas viven en `.claude/settings.local.json`
y propuso los nombres `PCSPHP_TEST_<TIPO>_USER|PASS`. **Los nombres que existen de verdad son otros**, y esta sección es
la verdad de hoy. El ADR no se reescribe: es inmutable.

**Permiso:** el PO lo dio en la sesión del coder el 2026-09-23 («T3 escribe lo que necesites»), como amplía el ADR 0023.

En el bloque `env` de `.claude/settings.local.json` (ignorado por git, modo `0600`) viven **seis identidades**:

| Clave | Quién |
| :-- | :-- |
| `PCSPHP_WALK_USER` / `PCSPHP_WALK_PASS` | el root |
| `PCSPHP_AGENTE_<TIPO>_USER` / `_PASS` | uno por tipo: `ADMIN_GENERAL`, `ADMIN_ORGANIZACION`, `GENERAL`, `INSTITUCIONAL`, `COMUNICACIONES` |
| `PCSPHP_AGENTE_TYPES` | mapa `usuario:tipo` de los seis, separado por comas, **para no adivinar el tipo** |

**Los dos recorredores NO toman la sesión igual**, y confundirlos hace perder el tiempo:

- **`bin/walk-matrix` pide un archivo** con `--credentials=<ruta a un JSON>` (`bin/walk-matrix:67-69`), y sin él no
  arranca. Ese archivo vive FUERA del repositorio.
- **`bin/walk-routes` no acepta `--credentials`** y no quiere ningún archivo: lee `PCSPHP_WALK_USER` y
  `PCSPHP_WALK_PASS` del entorno (`bin/walk-routes:77-78`). Y el bloque `env` de `.claude/settings.local.json` **sí
  llega al entorno de las órdenes**, comprobado el 2026-09-23 por presencia —nunca por valor—, así que **arranca con
  sesión él solo**.

**Reconstruir el archivo que pide `bin/walk-matrix`**, sin mirar ningún temporal:
leer `env`, partir `PCSPHP_AGENTE_TYPES` por comas, y armar `{usuario: {type, password}}`. Comprobado el 2026-09-23
reconstruyéndolo solo desde la configuración: «reconstruidas 6 identidades; tipos [0, 1, 2, 3, 4, 12]», y la puerta de
la matriz dio «SIN CAMBIOS» contra su línea base.

**Trampa:** los usuarios existen en la base LOCAL con el prefijo `zz-agente-`. Si las claves se pierden, los usuarios
siguen ahí pero **no se puede entrar con ellos**: hay que restablecer sus contraseñas o crear otros con el mismo
prefijo. Nada de esto viaja a un clon, y la medición de la matriz depende de ello.

**Y una frontera que el coder sostuvo bien** (2026-09-23): se negó a escribir en `.claude/settings.local.json` cuando se
lo pidió el arquitecto y lo hizo cuando se lo pidió el PO en su propia sesión. La diferencia no es el contenido, es
quién lo pide: **una sesión hermana no toca la configuración de otra**. Ver `.agents/rules/30-protocolo-coder.md`, «Si
el PO te corrige directamente».

## Leer la base local desde una orden (ADR 0024, abierto el 2026-09-24)

**Qué se puede.** `mysql` y `mariadb` contra la base LOCAL, en dos moldes:

- **LECTURA:** `SELECT`, `SHOW`, `DESCRIBE`, `DESC`, `EXPLAIN` y `USE`. Nada más, y ninguna con `INTO`.
- **REVERSIBLE:** `BEGIN` … `ROLLBACK`, con lecturas más `INSERT`, `UPDATE`, `DELETE`, `REPLACE`,
  `SAVEPOINT` y `RELEASE`. Sin `COMMIT`.

**La forma de invocarlo, que no es opcional:**

```bash
mysql --defaults-file=<archivo fuera del repositorio> -e '<una sola consulta>'
```

- **`--defaults-file` es obligatorio** y solo puede haber uno. Sin él, el cliente leería además
  `/etc/mysql/my.cnf`, `~/.my.cnf` y el que señale `MYSQL_HOME`, y de cualquiera de ellos puede
  salir SQL o un host que la guarda no ha visto. `--defaults-extra-file` **no vale**: se SUMA en vez
  de sustituir.
- **El archivo solo admite** host, user, password, port, socket, database, protocol, juego de
  caracteres y las tres de TLS. Cualquier otra clave bloquea con su nombre, porque ahí dentro cabe
  `execute=` y el cliente lo concatena con el `-e` de la orden.
- **Un solo `-e`**: el cliente concatena varios y los ejecuta todos.
- **Sin contraseña en la orden** (`-p…`, `--password=`, ni sus abreviaturas): va en el archivo.
- **Sin comentarios SQL** (`--`, `#`, `/*`) dentro de la consulta.
- Vetadas: `--init-command`, `--tee`, `--pager`, `--login-path`, `--defaults-group-suffix`.

**Trampas medidas, que no son de la guarda sino del terreno:**

- **Un prefijo único del nombre largo vale como el nombre**: el cliente acepta `--hos=` y `--pas=`.
  La guarda los trata como el nombre entero; si alguna vez se añade una opción vigilada, hay que
  recordar que sus abreviaturas entran solas.
- **«Reversible» depende del motor.** El `ROLLBACK` solo deshace en tablas transaccionales. Medido
  el 2026-09-24: las 29 tablas de la base son InnoDB y las 3 entradas restantes son vistas. Un clon
  con una tabla MyISAM o Aria dejaría la fila escrita y **la guarda no puede saberlo**: el SQL sería
  el declarado.
- **Un texto que nombre a estos clientes no se escribe en la línea de órdenes.** La guarda mira el
  comando entero, y además convierte cada acento grave en un salto de línea, así que un mensaje de
  commit que cite `mysql` entre acentos graves se lee como una invocación y bloquea. Se escribe en
  un archivo y se pasa con `git commit -F`. Es el mismo caso que la batería de la guarda, que
  tampoco puede escribirse en una orden.

**Lo que NO se abrió.** La escritura que persiste sigue yendo por la aplicación o por `bin/cli`
(ADR 0025, «Estado de la implementación»).

**Cómo se auditó, por si hay que ampliarla** (LEY 35): ocho pasadas, doce formas de saltársela.
Ninguna la encontró la batería: salieron de **cargar `guardia.py` como módulo y sondearla con
casos**, y de ejecutar contra el cliente real solo lo que no se podía decidir leyendo. La batería
—`probar_guardia.py`, 350 casos— sirve para que no vuelvan. Cada bloqueo lleva al lado su pareja
legítima, porque una guarda que lo bloquea todo también «pasa» la mitad de las pruebas.

