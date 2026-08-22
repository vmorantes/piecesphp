# 18 — Siguientes ventanas

> ## TRASPASO — 2026-08-21
>
> Escrito para que alguien que llega en frío ejecute sin preguntar. Lo de aquí no está en
> el código y se pierde si no se lee.

## T0 · CRITERIO DE CIERRE DE LA FASE — decidido por el propietario

**El framework es una PLANTILLA QUE SE CLONA.** Hay muchos despliegues, cada uno congelado
en su versión. Nadie se rompe hoy con lo que tocamos aquí, pero **cada despliegue es un
consumidor futuro**, así que la regla no es «no rompas producción»: es

> **NO EMBARQUES UNA TRAMPA.**

Más exigente, porque el daño llega tarde y sin nadie delante para diagnosticarlo.

### La fase no cierra con un número. Cierra con esto:

1. **Cero errores que señalen un defecto real.**
2. **Todo lo demás arreglado, o suprimido CON RAZÓN ESCRITA** y, si la supresión es
   temporal, **con la condición que la retira**.
3. **Los `.neon` dejan de ser cajón de sastre y pasan a ser registro documentado.** La
   auditoría de los 67 `ignoreErrors` —48 vivos silenciando 3.083 errores, 19 muertos— es
   el formato de referencia. **Los cinco repositorios al mismo estándar.**
4. **TRINQUETE: el baseline solo baja.** Un error nuevo se arregla o se justifica por
   escrito. El que no cumpla ninguna de las dos cosas, no entra.

5. **TODA CIFRA QUE SOBREVIVE A LA SESIÓN LLEVA SU MÉTODO ESCRITO.** No es un paralelismo
   con la regla 2: **es lo que hace funcionar el trinquete.** Comparar dos baselines solo
   significa algo si se midieron igual, y ya falló una vez — cuando el pipeline pasó de
   tabla a JSON, la pregunta «¿es comparable?» no tuvo respuesta hasta que alguien fue a
   averiguarla. Un trinquete que compara peras con manzanas no impide nada.

   Con dos límites, para que no se vuelva burocracia:

   - **Solo las cifras que SOBREVIVEN a la sesión**: las que entran en un documento, en un
     baseline o en el `CHANGELOG`. Un número dicho en un reporte se explica por su contexto
     y muere con él; ese no lleva método.
   - **El método debe ser REPRODUCIBLE, NO DESCRIPTIVO**: el comando o la herramienta
     nombrada, no una frase. «Medido sobre la salida de PHPStan» no sirve; «`bin/phpstan`,
     contando instancias en `PHPStanResult.json`» sí.

### El baseline vigente y su método

| Cifra | Con qué se midió |
| :-- | :-- |
| **877 errores** | `bin/phpstan`, leyendo `PHPStanResult.json`: `totals.file_errors`. Cuenta **instancias**, no tripletas distintas. |
| **190 archivos** | El mismo JSON: número de claves de `files`. |

El cambio de configuración del punto 3 —`dynamicConstantNames`— lo movió **cero**: ver T13,
donde el delta va contado aparte con su método.

Verificado con un segundo método independiente, como manda T20: `PHPStanResult.Summary.txt`
lo obtiene parseando **la tabla** con otro código —`bin/phpstan-process-result.php`— y da
los mismos **877 y 190**. Distinto formateador y distinto parser, así que el acuerdo no es
tautológico.

### Las tres unidades, y por qué hay que decir cuál se usa

Casi todas las discrepancias de esta campaña han sido **la misma cifra en otra unidad**, no
un error. Estas son las únicas tres que se usan, y **ninguna cifra publicada puede omitir la
suya**:

| Unidad | Qué cuenta | Cómo se obtiene |
| :-- | :-- | :-- |
| **Instancia** | Cada error reportado, aunque otro sea idéntico | `totals.file_errors` de `PHPStanResult.json` |
| **Tripleta** | Cada `(ruta, línea, mensaje)` **distinta** | Deduplicar las instancias por esa clave |
| **Archivo** | Cada archivo con al menos un error | Número de claves de `files` |

La diferencia entre instancia y tripleta son los **duplicados exactos**: dos errores en la
misma línea con el mismo mensaje. Hoy hay **9** (877 contra 868). No es ruido de medición:
son reales, y se cuentan dos veces cuando lo que importa es el volumen de trabajo y una sola
cuando lo que importa es el número de sitios a tocar.

**Es la reconciliación del «469 contra 464»**: las dos eran correctas, en unidades distintas.

### Refinamiento del marco de las cuatro respuestas

**«CONTRATO» solo está disponible cuando el lenguaje OFRECE una expresión para la garantía.**

Para `createFromFormat` existía: `new \DateTime(...)` devuelve `DateTime` sin condiciones.
Para `json_encode` no hay ninguna función que devuelva `string` incondicionalmente, así que
las opciones eran la bandera, un cast, un ignore o una rama inalcanzable — y solo una es
honesta.

> **Cuando el lenguaje no ofrece la expresión, la forma honesta más cercana es convertir el
> fallo imposible en un fallo RUIDOSO. No sustituye al contrato: es el contrato escrito de
> la única forma disponible.**

## T0bis · LA PRE-AUTORIZACIÓN CUBRE LA SEGURIDAD MECÁNICA, NO LA ESCALA

**Regla escrita a raíz de un fallo concreto, y el fallo fue de proceso, no de técnica.**

C.2 —unificar `routeName`/`allowedRoute` en un trait— estaba autorizado con una frontera
técnica correcta: **solo copias demostrablemente idénticas**. La frontera se respetó: la
identidad se comprobó por tokens archivo a archivo y las 28 que no coincidían quedaron
intactas. Y aun así **54 métodos borrados en 44 controladores aterrizaron sin que el
propietario los hubiera visto**.

Que el commit tuviera que ser único —borrar los métodos sin el `use` puesto deja el árbol
roto— **era cierto y no eximía de nada**: obliga a que el commit sea atómico, no a que el
plan sea invisible.

### La regla

> **Cualquier cambio que borre o mueva declaraciones en más de DIEZ archivos se enseña
> ANTES de commitear** —el plan y la evidencia—, **aunque esté pre-autorizado y aunque cada
> cambio individual sea trivial.**

Lo que se enseña son las dos cosas, no una:

- **El plan**: qué se toca, con qué criterio, y qué queda fuera y por qué.
- **La evidencia**: la medición que sostiene el criterio, y el diff de una muestra.

### Por qué diez y no «los grandes»

Porque «grande» lo juzga quien hace el cambio, y ahí está el sesgo. **Un umbral contable no
se puede racionalizar.** Y la escala es justo lo que vuelve irrevisable un lote de cambios
individualmente triviales: nadie revisa 44 archivos para comprobar que sobran — es el mismo
mecanismo que escondió el desastre de los CRLF (T21).

**La pre-autorización responde «¿es seguro?». No responde «¿es esto lo que quería?».**
## T1 · La garantía de `ERRMODE_EXCEPTION`, con su razón real

`Database::query()` y `Database::prepare()` declaran `\PDOStatement` con **tipo nativo**
desde `piecesphp/database` **v3.2.0**. La garantía de que nunca devuelven `false` **no
viene de `Database.php` fijando el atributo**: viene de que **PDO usa
`ERRMODE_EXCEPTION` por defecto desde PHP 8.0**, y el paquete declara `php: >=8.4 <9.0`.

Verificado empíricamente con un `new PDO(...)` pelado, sin tocar atributos:
**modo 2 en PHP 8.1 y en 8.4**.

El `setAttribute(ATTR_ERRMODE, ERRMODE_EXCEPTION)` de `instance()` **se conservó** aunque
hoy sea redundante: si la garantía descansara solo en un valor por defecto del lenguaje,
dependería de una decisión que podría volver a cambiar. Fijarlo en el paquete la vuelve
local y verificable.

**Es minor, no parche**, porque estrechar un retorno rompe a quien hereda: una subclase
que redeclare `query(): PDOStatement|false` deja de ser legal por covarianza y **PHP falla
al declarar la clase**. Comprobado que ninguna clase de los cinco repos hereda de
`Database` — `ActiveRecord` la compone.

## T2 · PHPStan truncaba las rutas largas — **RESUELTO**

**Era un defecto de herramienta, y salió más caro de lo estimado.**

El formateador de tabla de PHPStan recorta la cabecera de cada archivo al ancho de
terminal. Con la salida redirigida a un archivo ese ancho cae a 80, así que las rutas
salían **cortadas a 73 caracteres** (`ApplicationCallsController.p`).

`bin/tools/refactorization/Rector.php` armaba su lista de archivos con una expresión
regular sobre ese `.txt` y **descartaba con `file_exists()` lo que no resolvía, sin decir
nada**. Medido: **34 de 195 archivos —el 17 % de la superficie con errores— nunca entraron
al análisis.** Rector no fallaba; simplemente no los veía. Eso explica por qué parecía
proponer tan poco.

**Coste comprobado:** al recuperar la superficie, Rector propone cambios en **los 34**.

**Qué se hizo:**

| Archivo | Cambio |
| :-- | :-- |
| `bin/phpstan` | `COLUMNS=400` para que la tabla deje de recortar, y una segunda pasada con `--error-format=json` que emite `PHPStanResult.json` |
| `bin/tools/refactorization/Rector.php` | Lee el JSON como fuente de verdad; el `.txt` queda de respaldo con aviso. **Ya no descarta en silencio**: enumera lo que no resuelve y aborta si la lista queda vacía |
| `bin/rector` | Fija `php8.4`; corría con el `php` por defecto, que es 8.1.34 y está por debajo del piso |

La segunda pasada de PHPStan cuesta **menos de un segundo** con la caché de resultados
caliente, así que no hay motivo para no tener siempre la salida de máquina.

**Efecto lateral que confirma el alcance:** el resumen humano pasó de decir 192 archivos
a decir **195**. El truncado también estaba corrompiendo el conteo que leíamos nosotros.

**La regla que deja:** una herramienta que descarta entradas en silencio es peor que una
que falla. «0 cambios propuestos» y «no miré nada» son indistinguibles desde fuera.

## T3 · D2 — comprobar credenciales escribía en base de datos — **RESUELTO**

**El caso más instructivo de toda la campaña, porque la primera evidencia era falsa.**

El diagnóstico inicial decía que el defecto estaba en `getOTPData()`, alcanzado desde
`checkValidityOTP()` en el login. Al ir a comprobarlo antes de arreglarlo, se cayó:
`getOTPData()` filtra el método y **solo acepta `METHOD_ONE_USE_CODE`**, así que no puede
crear las 34 filas `TOTP` que se le atribuían. La atribución era imposible.

**Dónde estaba de verdad**, que resultó ser peor:

| | |
| :-- | :-- |
| `getTOTPData()` | Mismo patrón get-or-create, y lo llama **sin condiciones el constructor de `UserDataPackage`** (línea 243). **Construir un paquete de usuario escribía en base de datos.** |
| `createOTPAlternativesRecords()` | Llamada desde `UserSystemFeaturesRoutes::routes():67`. `routes()` corre **en cada petición**: dos `GROUP_CONCAT` + `LEFT JOIN` sobre la tabla entera de usuarios por carga de página. |

La superficie no era una función: era **el constructor del paquete de usuario**, que
alcanzan sin autenticar `checkValidityOTP`, `checkValidityTOTP`, `toExpireOTP` y
`generateOTP` — todos con `new UserDataPackage($id)` antes de verificar credencial alguna.

**Lo que NO era el defecto**, y conviene dejarlo escrito para que nadie lo «arregle»: el
orden de `UsersController` 899/900 es correcto. La condición es
`password_verify(...) || $otpIsValid`, así que el código de un solo uso es una vía de
autenticación alternativa y la comprobación OTP **tiene** que correr en todo intento. El
defecto nunca fue cuándo se comprueba: era que **comprobar escribía**.

### Severidad, reencuadrada tras comprobar

| Eje | Nivel | Por qué |
| :-- | :-- | :-- |
| Seguridad | **BAJA** | El secreto se regenera al activar el 2FA (`toggle2FA()` hace `generateSecret()` incondicional), así que el material pregenerado **nunca llega a ser credencial viva**. Y como el relleno masivo ya había creado todas las filas, al sondear no había escritura diferencial: el oráculo de enumeración por temporización tampoco existía. |
| Rendimiento | **ALTO** | Dos barridos sobre la tabla de usuarios por petición. Con 34 usuarios no se nota; con cien mil es un incendio. |
| Arquitectura | **ALTO** | Una migración de datos ejecutándose en bucle infinito dentro del registro de rutas, que debe ser puro. |

### La trampa de orden

**(b) tapaba a (a).** Si se hubiera sacado el relleno de `routes()` sin arreglar los
buscadores, los usuarios nuevos habrían dejado de tener filas precreadas, el get-or-create
habría vuelto a escribir en la ruta no autenticada y el oráculo de enumeración habría
**renacido**. Los dos cambios aterrizan juntos, y si hay que partirlos, **(a) primero**.

### Qué se hizo

- Los dos buscadores del mapper son **puros** y devuelven `null`. La mitad de escritura
  vive en `createOTPData()` / `createTOTPData()`, y su único llamante es `toggle2FA()`,
  que es donde el usuario ya autenticado pide configurar su segundo factor.
- `TOTPData` pasa a ser nulable de verdad: seis sitios que encadenaban sobre él lo manejan
  ahora de forma explícita. En `toExpireOTP()` la respuesta al `null` es **registrar y
  continuar** — sin registro no hay código de un uso que caducar.
- `createOTPAlternativesRecords()` sale de `routes()` y pasa a la tarea
  **`bin/cli sync-otp-records`**, que por defecto **solo informa** y exige `apply=yes` para
  escribir. El inventario se separó en `missingOTPRecords()`, de solo lectura.
- `toggle2FA()` inicializaba `$result = false` y no lo reasignaba nunca: devolvía `false`
  incluso al guardar bien. Su único llamante ignoraba el valor, así que no rompía nada —
  pero era una trampa esperando al primero que se fiara del docblock.

**Suite nueva:** `bin/cli unit-tests:core/otp-write-separation`. Falla antes del arreglo.
Las dos primeras comprobaciones son **estructurales**, no de comportamiento, y a propósito:
la versión de comportamiento exigiría crear un usuario sin filas —escribir datos de prueba
en una base ajena— y además **hoy no fallaría**, porque el relleno masivo tapaba el
defecto. Ese es justo el motivo por el que las dos reglas se comprueban por separado.

**Detalle del test que merece recordarse:** la primera versión buscaba por texto plano y
fallaba contra el propio comentario que documenta lo que se quitó. Se tokeniza con
`token_get_all()` y se descartan `T_COMMENT`/`T_DOC_COMMENT`. Un test que confunde
documentar con hacer obliga a no documentar, que es peor que el test.

### Las 34 filas huérfanas: no se purgan

Son basura **inerte**: el secreto se regenera al activar, así que nunca serán credenciales.
Y borrarlas antes del arreglo no servía de nada, porque el relleno las recreaba en la
siguiente petición. Vaciar la columna `secret` de las filas `TOTP` con
`twoAuthFactor = 'DISABLED'` es **higiene, no arreglo**: prioridad baja. Las
`ONE_USE_CODE` **no se tocan** — pueden sostener códigos vigentes; mirar `maxDate` antes
de nada.

## T4 · El token de GitHub sigue sin rotar

El framework tiene **credenciales en texto plano dentro de las URL de sus remotos**, en
`.git/config`. Avisado el 2026-08-20 y **sigue ahí**.

**No es un remoto: son TRES.** `origin` (GitHub), `origin2` (GitLab) y `origin3`
(Bitbucket) llevan cada uno su token incrustado en la URL. Rotar solo el de GitHub deja dos
fuera.

**DECIDIDO POR EL PROPIETARIO Y CERRADO**: las URL solo existen en su máquina local, a la
que nadie más accede, y ese razonamiento vale igual para tres que para uno. **No se vuelve a
levantar.** Queda anotado que son tres, no uno, por si alguna vez cambia el contexto.

## T5bis · Dos patrones del proyecto

### `token_get_all()` es la respuesta estándar para distinguir código de prosa

Cada vez que hay que preguntarle algo al código fuente —¿hay una llamada a X?, ¿este
docblock está cerrado?— **se tokeniza, no se busca por texto**. Ya lo usan
`verify-integrity` y `unit-tests:core/otp-write-separation`.

Las dos veces que se hizo a ojo salió mal: la primera versión de `verify-integrity`
contaba `/*` contra `*/` y daba 32 falsos positivos porque `'image/*'` aparece dentro de
cadenas; la primera versión del test de OTP buscaba por subcadena y **casaba con el
comentario** que documenta la llamada retirada.

### Un test que confunde documentar con hacer obliga a no documentar

Si un test castiga un comentario, **la herramienta está mal, no el comentario**. La salida
correcta no es quitar el comentario para que el test pase: es enseñarle al test a
distinguir. Descartar `T_COMMENT` y `T_DOC_COMMENT` cuesta seis líneas.

Un test que empuja a documentar menos es peor que no tenerlo, porque su coste no se ve en
la ejecución sino meses después, en lo que nadie escribió.

## T5 · Por qué las puertas son las que son

`bin/cli verify-integrity`, las dos suites y `PHPStanResult.Summary.baseline.txt` no son
ceremonia. Existen porque en esta migración **hubo diagnósticos que se sostenían al leer
el código y se caían al ejecutarlo**:

- El `E_DEPRECATED` escondido en un `array_keys()` de `bootstrap.php`: leyendo la tabla de
  niveles parecía informativa; ejecutando, promovía **toda** deprecación a excepción y
  tumbaba la aplicación en 8.5.
- **D1**, un defecto de reconexión que se argumentó, se documentó y **no existía**: la
  prueba que iba a confirmarlo lo desmintió.
- El `setAttribute` del constructor: se añadió creyéndolo necesario y **ninguna prueba
  notó la diferencia**, porque la garantía venía de otro sitio.
- Los **46 errores que apuntaban a archivos inexistentes**, que parecían una categoría del
  triaje y eran un defecto del parseo.

**La regla que queda escrita: una afirmación sobre comportamiento en ejecución no se da
por cierta hasta que se ejecuta.** Escribir la prueba primero no es rigor ceremonial —
tres veces evitó un cambio que no hacía falta.

---



Backlog posterior a la migración de PHP, ordenado por dependencias y por lo que
desbloquea cada cosa. **Sin fechas ni estimaciones de calendario**: el orden es la
información, no el plazo.

Estado de partida: migración cerrada en `v7.1.0`, rango `>=8.4.1 <8.6`, integrada en la
tríada salvo `last-stable`, congelada a propósito. Ver
[16-plan-php85.md](./16-plan-php85.md) y [17-ruta-de-ejecucion.md](./17-ruta-de-ejecucion.md),
ambos ejecutados.

---

## 1. ~~Cerrar el hueco de las tres librerías~~ — HECHO

`datastructures`, `geojson` y `html` ya tienen el rango `{min: 80400, max: 80500}`, las
reglas de deprecación, Rector y línea base. **Resultado: cero deprecaciones y cero
errores reales.** Los 3 de `html` son `function.alreadyNarrowedType` benignos —
`is_scalar()` tras `is_string()`, y un `!is_array()` sobre un parámetro ya tipado.
Publicar `">=8.4 <9.0"` sin haberlas analizado salió bien, pero fue suerte.

Corregido de paso: el `composer.lock` de `html` seguía fijado a `datastructures` v3.0.0
pese a pedir `^3.1`, así que sus 7 pruebas se habían dado por verdes contra la versión
anterior.

---

## 2. Cobertura de pruebas unitarias

**El riesgo dominante del proyecto, y lo ha sido desde el primer diagnóstico.**

Estado real:

| Repo | Cobertura |
| :-- | :-- |
| `database` | 9 suites, 72 pruebas — **la referencia a imitar** |
| `datastructures` | 26 pruebas |
| `html` | 7 pruebas |
| `geojson` | ninguna |
| **`piecesphp`** | **3 suites para ~88.000 líneas** |

La metodología ya existe y es la del propio framework: acciones de terminal
`bin/cli unit-tests:<suite>`, con los archivos en `src/app/core/system-controllers/local-tests/`
y las tareas registradas en el CLI. `piecesphp/database` demuestra que escala a 9 suites.
No hace falta inventar nada: hay que aplicarlo.

Prioridad dentro de esta ventana, por orden de lo que más se toca y peor se rompe:

1. **ORM** — `save`, `update`, meta-propiedades, `$fields` con relaciones,
   `systemApprovalStatus`
2. **Rutas y permisos** — `routeName`, `allowedRoute`, `Roles::hasPermissions`
3. **`ServerStatics`** — resolución de rutas, protección de directorios
4. **i18n** — `__()`, `LangInjector`, caída al idioma base
5. **Validación** — `Parameters`, `Validator`

Esta ventana **bloquea las ventanas 5 y 6**: no se refactoriza sin red.

---

## 3. Azure — cerrar la fase E

`microsoft/azure-storage-blob` sigue abandonado y `composer audit` lo reporta. Migrar a
`azure-oss/storage-blob`, reescribir `BlobStorageAzureAdapter::read()` como un `getBlob`
directo en vez de listar el contenedor entero, y arreglar la recursión infinita de
`BlobStorageFileAzurePackage::blob()`. Detalle en
[16-plan-php85.md](./16-plan-php85.md).

Independiente de todo lo demás.

---

## 4. `strftime()` antes de PHP 9

El único hallazgo con fecha de caducidad. Tres usos en `localeDateFormat()`
(`Utilities.php:1681, 1686, 1694`), alimentados por **47 puntos de llamada**.

Hoy no molesta porque están escritas como `@strftime()` y el handler respeta la
supresión. Cuando PHP 9 la elimine, el `@` no salva: será
`Call to undefined function`. El reemplazo natural es `IntlDateFormatter`, que respeta
locale — que es justo lo que esa función busca — y `intl` ya está cargado.

Hacerlo **con la ventana 2 hecha**, porque 47 puntos de llamada sin pruebas es una
apuesta.

---

## 5. Que PHPStan diga la verdad — **y no es lo que este documento decía**

> **Corrección del 2026-08-21.** La versión anterior afirmaba que el 91% de los errores
> venía de que PHPStan no entiende el `__get`/`__set` de los mappers, y que una
> `PropertiesClassReflectionExtension` los eliminaría. **Se midió y es falso.** No hace
> falta ninguna extensión.

Reparto real de los 1.060 errores visibles, medido causa a causa:

| Causa | Errores | % |
| :-- | --: | --: |
| Unión con `null` sin comprobar | 541 | 51% |
| Unión con `false` sin comprobar (funciones nativas) | 144 | 14% |
| Otras uniones sin estrechar | 157 | 15% |
| Tipo `object` pelado | 60 | 6% |
| `@property` más estrecho que la asignación | 21 | 2% |
| Cola larga sin patrón | 137 | 13% |

**El 79% es una sola cosa: uniones que no se estrechan antes de usarse.**

### Y no son ruido

Este documento los trataba como el precio inevitable de un ORM dinámico, buenos solo
como detector de regresión. **Es al revés.** Son 541 sitios donde algo puede ser `null`
y nadie lo comprueba — y en este framework, con `E_WARNING` promovido a excepción
(ver [03-ciclo-de-vida.md](./03-ciclo-de-vida.md)), un `null->metodo()` no es un aviso:
**mata la petición**.

Es exactamente la clase de defecto contra la que hay que blindarse. PHPStan llevaba
tiempo señalándolos.

### Los arreglos, por premio sobre esfuerzo

| Qué | Errores | Cómo |
| :-- | --: | :-- |
| ~~`getBy()` declarado `@return static\|object\|null`~~ **HECHO** | −36 | Fueron **36 docblocks en 29 archivos**, no 26: el patrón también estaba en `lastModifiedElement` (7), `getByMultipleCriteries` (2) y `getExactAttachment` (1). El flag **no** se llama igual en todos —`$as_mapper` y `$asMapper`—, así que hubo que ajustarlo uno a uno |
| `getLoggedFrameworkUser()` encadenado sin comprobar | 123 | **Contrato, no parches**: añadir `getLoggedFrameworkUserOrFail(): UserDataPackage` y usarla donde `requireLogin = true` garantiza sesión |
| ~~`@property` que mienten sobre lo asignado~~ **HECHO** | −15 | Fueron **6**, no 21: el resto de `assign.propertyType` no son `@property` de mapper. Cada corrección se contrastó contra el `$fields` del mapper. **Cinco casos se dejaron sin tocar a propósito**: ahí el docblock no miente, es el código el que asigna algo que el esquema no admite; ensancharlos habría documentado el bug |
| ~~Regex sin escapar en el ignore de `BaseEntityMapper`~~ **HECHO** | +2 | Escaparlo destapa **2**, no 8. El 8 salía de quitar la regla entera; los otros 6 son `undefined method` genuinos que la rama correcta sigue silenciando, que es para lo que la regla existe |
| `BaseController::$model` con unión de tres tipos | 6 | Docblock o genérico. La escala era mucho menor de lo estimado |
| El resto de los 541 `null` | ~500 | Triaje por categorías, con pruebas hechas |

---

## 5bis. Triaje de la nulabilidad — 2026-08-21

Estado tras la primera tanda: **514 errores con `null`** (eran 541; 27 resueltos).

### Por origen

| Origen | Errores | Qué son |
| :-- | --: | :-- |
| Buscador de mapper que no encuentra | **137** | `Mapper::getBy()` y hermanos usados sin comprobar |
| Escalar opcional | **136** | `string\|null`, `int\|null`… de propiedades, parámetros o retornos |
| Otro objeto nulable | 92 | Colaboradores opcionales |
| Sesión: `getLoggedFrameworkUser()` | 87 | Lo que queda tras la primera tanda |
| Fila cruda de consulta | 39 | `object\|null` / `stdClass\|null` |
| `Database` no inicializada | 20 | **Todos del mismo defecto**, ver abajo |
| Sin clasificar | 3 | |

### Por respuesta del marco

| Grupo | Errores | Criterio |
| :-- | --: | :-- |
| **1. No puede ser null por contrato** | **36** | Manejadores de ruta con login. **HECHOS**: pasan a `getLoggedFrameworkUserOrFail()` |
| 2. Puede ser null, comportamiento definido | ~350 | Mayoría de B, D y F: hay que decidir caso a caso |
| **3. Puede ser null y nadie lo pensó** | **ver abajo** | Defectos |

**Cómo se clasificó el grupo 1, y es reutilizable**: el framework expone su listado
resuelto de rutas en `/configurations/routes/`, con clase, método y roles. Las públicas
dicen **«No requiere autenticación»**; cualquier otro valor implica login. Cruzar los
errores contra ese listado da la respuesta sin adivinar. De los 123 de sesión: **36** en
rutas con login, **0** en rutas públicas, 61 en métodos que no son ruta, 26 en vistas.

---

## 5ter. DEFECTOS ENCONTRADOS — grupo 3

Son el motivo de la ventana. **Ninguno está arreglado**: hay que decidir qué debe hacer
cada uno.

### D1 · ~~`getDatabase()` puede devolver null~~ — **REFUTADO el 2026-08-21**

> **La sospecha era falsa.** Se escribió una prueba para reproducirla y no se reprodujo:
> `piecesphp/database`, suite `core/database/db-fallback`.

Se sospechaba que `restoreInstancesDb()`, al actuar solo
`if (!isset(self::$db[$key]))`, dejaría sin conexión a las instancias que pidieran
`getDatabase()` **después** de que otra repoblara el pool.

El escenario montado —dos instancias, `destroyDb()`, A pide primero y B después— **no
falla**: B se recupera con normalidad.

**Error de lectura**: `restoreInstancesDb()` no restaura a quien llama. Recorre
`self::$instances` y reconfigura la **cohorte entera** con esa clave, así que la
restauración que dispara A alcanza también a B. La guarda `if (!isset(...))` evita
restaurar dos veces; no limita a quién se restaura.

**Los 20 errores `Database|null` no son un defecto de ejecución**: vienen de que
`ActiveRecord::$database` se declara `@var Database|null` en la clase base y el override
hereda esa firma. Estrechar el docblock a `@return Database` sería la respuesta 1 del
marco, pero **no se hizo**: no se puede demostrar que sea imposible en toda la jerarquía,
y afirmarlo sin prueba es exactamente el «callar al analizador» que este trabajo evita.

La suite se conservó: el comportamiento de cohorte no lo cubría nada y es fácil de romper.

### D2 · `OTPHandler::toExpireOTP()` desreferencia sin comprobar, en el camino de login

`Authentication/OTPHandler.php:68-71`:

```php
$otpData = OTPSecretsUsersMapper::getOTPData($userData->id, ...METHOD_ONE_USE_CODE);
$otpData->maxDate = new \DateTime('2000-01-01');   // <- sin comprobar
```

La guarda `if ($userDataPackage !== null)` protege al **usuario**, no al resultado de
`getOTPData()`, que declara `OTPSecretsUsersMapper|null` y devuelve null si su `save()`
interno falla (`return $mapper->id !== null ? $mapper : null;`).

**Dónde duele**: el único llamante es `UsersController.php:909`, dentro del login, justo
**después** de validar el segundo factor y **antes** de emitir el token de sesión. Si
ocurre, el usuario se autentica correctamente y la petición aborta con un
«Attempt to read property on null».

**Decisión pendiente**: `toExpireOTP()` es una rutina de invalidación. Si no hay nada que
invalidar, lo razonable es no hacer nada y seguir, no tumbar un login válido. Pero eso
hay que decidirlo, no asumirlo.

### D3 · `UsersModel` desreferenciaba el usuario de sesión antes de comprobarlo — **CORREGIDO**

Cuatro métodos aceptaban `?UserDataPackage = null` y caían a `getLoggedFrameworkUser()`,
y tres leían `->organization` en la línea siguiente. Corregido con la forma que el propio
`getBy()` ya usaba. Latente: ningún llamante llegaba al fallback. Ver commit.

---

## 5quater. Triaje de los `false` — por MECANISMO DE FALLA — 2026-08-21

**148 errores.** Ordenarlos por función no basta: lo que determina la respuesta es **cómo
falla** cada una en ESTE framework, donde `bootstrap.php` promueve `E_WARNING` a
excepción.

### Verificado, no supuesto

Ejercitando el handler real del framework en modo producción:

| Función | Qué hace al fallar |
| :-- | :-- |
| `file_get_contents()` ruta inexistente | **ABORTA** — `ErrorException` |
| `fopen()` ruta inexistente | **ABORTA** |
| `imagecreatefromstring()` con basura | **ABORTA** |
| `unlink()` ruta inexistente | **ABORTA** |
| `createFromFormat()` que no casa | devuelve `false` |
| `realpath()` ruta inexistente | devuelve `false` |
| `json_encode()` UTF-8 inválido | devuelve `false` |
| `strpos()` no encontrado | devuelve `false` |

La separación es limpia: **o abortan siempre, o devuelven `false` siempre.**

### GRUPO A — fallan con warning: la petición ya murió (40)

| Función | Err | Arch |
| :-- | --: | --: |
| `file_get_contents()` | 19 | 9 |
| `fopen()` | 8 | 3 |
| `finfo_open()` | 4 | 2 |
| `imagecopyresampled()`, `unlink()`, `glob()`, `opendir()`, `readdir()`, `imagecreatetruecolor()`, `imagesavealpha()` | 9 | 6 |

**Aquí el `false` nunca llega.** La pregunta en cada sitio no es cómo silenciarlo, sino
**¿queremos que esto sea un 500?** Para una operación de usuario casi nunca: un adjunto
que no está no debería tumbar la petición. La respuesta es **manejo explícito real**
—`is_file()` antes, o `@` más comprobación—, no una anotación.

### GRUPO B — fallan en silencio: el `false` llega y es información (80)

| Función | Err | Arch | Respuesta canónica |
| :-- | --: | --: | :-- |
| `json_encode()` | 24 | 14 | `JSON_THROW_ON_ERROR` donde el sitio pueda permitirse la excepción |
| **`createFromFormat()`** | **15** | **13** | Casi siempre entrada de usuario mal formada: **necesita rama** |
| `realpath()` | 10 | 6 | Suele ser un `is_dir()`/`is_file()` mal ordenado |
| `fetch()` | 6 | 5 | **No es error**: `false` significa «no hay más filas» |
| `strpos()` / `mb_strpos()` | 9 | 5 | `!== false`, nunca `if ($pos)` |
| `ob_get_contents()`, `array_search()`, `strftime()`, `gzencode()`, `json_decode()`, `ini_get()`, `saveHTML()`, `preg_match()` | 16 | — | Cola |

### GRUPO C — no pueden devolver `false` (11)

`query()` y `prepare()`. **Resuelto en `piecesphp/database` v3.1.1**: se estrecha el tipo
de retorno con tipo nativo. `false` es el retorno del modo `ERRMODE_SILENT`, y desde
**PHP 8.0 el default de PDO es `ERRMODE_EXCEPTION`**; el paquete declara `>=8.4 <9.0`.

> **`fetch()` NO entra aquí**, aunque lo pareciera: `ERRMODE_EXCEPTION` cubre los errores,
> pero `fetch()` devuelve `false` legítimamente cuando se acaban las filas. Va al grupo B.

### Sin identificar (17)

Bajaron de 63 al resolver un artefacto propio: **PHPStan trunca las rutas largas** en su
salida de tabla, así que 18 errores apuntaban a archivos inexistentes y no se les podía
leer el contexto. Resueltas contra el árbol real.

Los 17 que quedan necesitan lectura directa; no son una categoría.

### Por dónde empezar

**Grupo B, `createFromFormat`**: 15 errores en 13 archivos, y un `false` ahí casi siempre
significa una fecha que no encaja con el formato esperado —entrada de usuario o dato de
BD—, que es justo el caso que nadie previó.

---

## 6. Las supresiones: el 74% está oculto

**67 entradas**, no 55. Auditadas una a una quitándolas y midiendo: **48 vivas, 19
muertas**. Silencian **3.083 errores** frente a los 1.078 visibles.

> **Actualización del 2026-08-21 — las muertas ya están borradas.** 12 entradas
> completamente muertas, más **5 rutas `**/*` redundantes** que no estaban muertas por
> casualidad: en esta configuración `../src/app/*` ya casa de forma recursiva, así que su
> hermana `**/*` nunca llega a ignorar nada. Comprobado dejando solo la `**/*`: silencia
> igual. Se separó además la entrada de las vistas, porque los tres identificadores
> compartían lista de rutas y en `BaseMongoModel.php` solo casa `class.notFound`.
>
> **Quedan 0 patrones muertos.** Nueva línea base: **1.011** errores.
> Las 48 vivas siguen intactas; las 357 de la familia `alwaysTrue`/`alwaysFalse` son la
> siguiente ventana.

| Grupo | Errores | Entradas | Veredicto |
| :-- | --: | --: | :-- |
| Vistas con `extract()` | 1.350 | 1 | Legítima, estructural |
| `missingType.*` (nivel 8 exigiendo `array<K,V>`) | 946 | 5 | Legítima, decisión de rigor |
| **Siempre-cierto / siempre-falso / inalcanzable** | **357** | **19** | **Sospechosa: son ramas muertas** |
| Idioma del ORM (`fieldsToSelect`, `where in empty`) | 233 | 6 | Legítima |
| Diseño estricto (`unused`, `return.void`) | 19 | 4 | Menor |
| Resto | 178 | 13 | Mixto |

Las **19 muertas** se borran sin más: no casan con nada, y PHPStan cuenta cada patrón
muerto como error, así que borrarlas *baja* el total.

Los **357 sospechosos** merecen su propia ventana: cada uno señala una rama que nunca se
ejecuta, o una comprobación que el autor creía necesaria y no lo es. Puede haber relación
con los 541 `null`: si una comprobación «siempre es cierta», a veces es que el tipo
declarado miente.

---

## 6b. Rector, medido en seco

Propuestas por set, sin aplicar nada:

| Repo | CODE_QUALITY | DEAD_CODE | TYPE_DECLARATION | PRIVATIZATION |
| :-- | --: | --: | --: | --: |
| piecesphp | 130 | 127 | 117 | 0 |
| database | 16 | 7 | 25 | 0 |
| datastructures | 5 | 2 | 1 | 0 |
| html | 10 | 4 | 7 | 0 |
| geojson | 7 | 2 | 7 | 0 |

**`PRIVATIZATION` no propone nada en ningún repo**: fuera del `sets()`.

Sobre `DEAD_CODE`, que era la sospecha principal: se verificaron una a una las tres
reglas capaces de romper con despacho dinámico y **no apareció ningún falso positivo**.
`RemoveUnusedPrivateMethodRector` propone borrar `AdminPanelController::showPermissionsRoles()`,
que parece un endpoint pero no tiene ni una referencia en PHP, JS ni TS: se escribió y
nunca se cableó.

> **Hallazgo estructural**: ese resultado limpio es engañoso. El Rector del framework toma
> su lista de archivos de `PHPStanResult.txt`, y las vistas están silenciadas por el
> ignore de 1.350 errores. **Rector no mira las vistas porque PHPStan no las reporta**, no
> porque sea seguro ahí. Si algún día se levanta ese ignore, Rector empezará a ver justo
> los archivos donde `extract()` hace su análisis inservible.

Desconfiar de aplicar a ciegas: `RemoveNullPropertyInitializationRector` (53 archivos) y
`RemoveUselessParamTagRector` (98). `database` ya los tiene en su `skip`; el framework no.

---

## 7. Limpieza de módulos

Todo [14-deuda-y-limpieza.md](./14-deuda-y-limpieza.md). Por orden de riesgo:

- **Bajo**: `Importers` (duplicado), 24 `HelperController` triviales, `Components`,
  `scssphp`, `PDFManager` + `mpdf`.
  **Webflow SALE de esta lista y se conserva**: ver la tabla de código contra material en
  [14-deuda-y-limpieza.md](./14-deuda-y-limpieza.md).
- **Medio**: `ImagesRepository` / `FileManager` / `Banner` — tres formas de gestionar
  imágenes; `Newsletter`; `EventsLog`; el temporizador
- **Alto**: invertir la dependencia `BaseEntityMapper` → `SystemApprovals`, y separar la
  capa de proyecto del framework base

Con la ventana 2 hecha, el riesgo alto deja de serlo tanto.

---

## 8. Suelto, sin dependencias

| Qué | Dónde |
| :-- | :-- |
| Trait para `routeName()` / `allowedRoute()` — 44 controladores lo reimplementan | [05-routing-y-permisos.md](./05-routing-y-permisos.md) |
| Guzzle 8 — topado por `hubspot/api-client`; se aborda cuando dé errores | [16-plan-php85.md](./16-plan-php85.md) |
| `DataTablesHelper.php:230/1090` — `columns` con defecto `null` y exigido `array` | [14-deuda-y-limpieza.md](./14-deuda-y-limpieza.md) |
| `/admin/reports-access/` da 404 pese a estar registrada | [14-deuda-y-limpieza.md](./14-deuda-y-limpieza.md) |
| `E_WARNING` y `E_NOTICE` siguen abortando en producción | [03-ciclo-de-vida.md](./03-ciclo-de-vida.md) |
| Humo de correo: nunca se ejercitó, no había cola pendiente | — |

---

## El orden, en una línea

```
A  Que PHPStan diga la verdad     docblocks y configuración, cero runtime
B  El null (541 + 123)            contrato antes que parches. Necesita pruebas
C  Las 357 ramas muertas
   Pruebas unitarias              en paralelo desde ya: B es la primera que toca lógica
   Azure, strftime, sueltos       cuando convenga
   Limpieza de módulos            la última, cuando ya haya red
```

## T6 · Mapa de destino de los módulos — **léelo antes de arreglar nada**

Arreglar código que va a desaparecer es trabajo tirado, y **reescribir un módulo que en
realidad hay que fusionar es peor**: se pierde lo que había que conservar. Este mapa decide
qué trato recibe cada error antes de mirarlo.

| Destino | Módulos | Trato |
| :-- | :-- | :-- |
| **Se borran completos** | `ImagesRepository`, `ApplicationCalls`, `InterestResearchAreas` | **Nada.** Ni un ignore cosmético. Se anota que el error muere con el módulo. |
| **Se borran parciales** | `MySpace`, `ContentNavigationHub`, `ReportsManage` | Solo se retira lo relacionado con los de arriba. El resto, trato completo. |
| **Se reescribe** | `DataImportExportUtility` | Atención **solo si el error es un defecto real**, no si es un tipo mal declarado. |
| **Trato completo** | todo lo demás | Las cuatro respuestas: contrato / manejo explícito / defecto / protocolo. |

### Experiencias previas — **se borran, y el trabajo con riesgo NO es el borrado**

`PreviousExperiencesMapper` y `OrganizationPreviousExperiencesMapper` se van, con sus dos
tablas: **`previous_experiences`** y **`organization_previous_experiences`**.

#### Frontera limpia: el directorio entero desaparece

`PiecesPHP/UserSystem/Profile/SubMappers/` tiene **exactamente tres archivos**, y los tres
mueren juntos:

| Archivo | Líneas |
| :-- | --: |
| `PreviousExperiencesMapper.php` | 768 |
| `OrganizationPreviousExperiencesMapper.php` | 768 |
| `InterestResearchAreasMapper.php` | 30 |

**El directorio se borra completo.** No queda nada que reubicar.

#### AVISO: LOS MAPPERS NO VIVEN EN `MySpace`

Los controladores, vistas y JS sí están en `MySpace`. **Los mappers están en el núcleo del
sistema de usuarios.** Quien borre los parciales de `MySpace` sin saber esto deja
**1.536 líneas huérfanas** en `PiecesPHP/UserSystem/` que nadie va a echar de menos hasta
que alguien pregunte qué hace ese directorio.

#### Inventario medido

**Se borran (9 archivos):**

```
PiecesPHP/UserSystem/Profile/SubMappers/            (los 3, el directorio entero)
MySpace/Controllers/Util/PreviousExperiencesController.php              (283)
MySpace/Controllers/Util/OrganizationPreviousExperiencesController.php  (296)
MySpace/Views/my-profile/util/experience-list-card.php
MySpace/Views/my-organization-profile/util/experience-list-card.php
MySpace/Statics/js/experience/delete-config.js
MySpace/Statics/js/experience-organization/delete-config.js
```

**Se EDITAN (9 archivos) — aquí está el riesgo del lote:**

```
SystemApprovals/Views/forms/approval-profile-user.php          ← módulo que SE CONSERVA
SystemApprovals/Views/forms/approval-profile-organization.php  ← módulo que SE CONSERVA
MySpace/Controllers/MyProfileController.php
MySpace/Controllers/MyOrganizationProfileController.php
MySpace/Controllers/Util/ProfileTasksUtilities.php
MySpace/Views/my-profile/my-profile.php
MySpace/Views/my-organization-profile/my-organization-profile.php
MySpace/Views/profile/profile.php
MySpace/Views/profile-organization/profile.php
```

**Dos correcciones al inventario que circulaba**, comprobadas archivo a archivo:

1. **`ProfileTasksUtilities.php` NO se borra: se edita.** Además de las experiencias genera
   el SQL de `UserProfileMapper`, que se queda. Borrarlo se lleva por delante la creación
   del esquema de perfiles.
2. **`MyProfileController` y `MyOrganizationProfileController` también se editan**, y no
   estaban señalados. Importan el mapper, lo instancian, llaman a `getBy()` y registran
   rutas hacia los controladores que sí se borran. Son los controladores principales de
   `MySpace`: se quedan.

#### La parte delicada

`SystemApprovals` **SE CONSERVA** y su acoplamiento es **estructural, no un import**:
`approval-profile-user.php` y `approval-profile-organization.php` llaman a
`allBy('profile', ...)`, instancian el mapper y tienen bucles de render. **Hay que editar un
módulo que se queda.** Ese es el trabajo con riesgo del lote, no el borrado — borrar no
puede romper lo que no existe, editar sí.

#### Nota de diseño, para el día que vuelva

Los dos mappers son **gemelos de copia y pega**: 768 líneas cada uno, diferenciándose en un
`reference_table` (`UserProfileMapper::TABLE` contra `OrganizationMapper::TABLE`). **Si la
funcionalidad vuelve, vuelve como una clase parametrizada, no como dos.**

#### `CountryMapper` y `CityMapper`: comprobado, no quedan huérfanos

Ambos los referencian los dos mappers que se van, pero **los dos conservan consumidores que
sobreviven al lote**: `App/Locations/` (su propio módulo), `Organizations/OrganizationMapper`
y `PiecesPHP/UserSystem/Profile/UserProfileMapper`. **No hay nada que borrar detrás.**

#### Efecto inmediato

Los archivos entran **ya** en el cubo «se borra»: el grupo B no los trabaja y Rector tampoco.
Se llevan **17 errores de PHPStan** que nadie tiene que arreglar — **ninguno** de la familia
`false`, así que la contabilidad de T8 no se mueve.

### URGENCIA DE ORDEN — lo único de esta ventana que tiene fecha

**`FieldTranslationUtility` tiene HOY tres copias, y dos están en módulos condenados**
(`ApplicationCalls` e `InterestResearchAreas`). Después de las eliminaciones queda **una**, y
parecerá un caso único en vez de un patrón.

> **La evidencia de que es un patrón se va con los módulos.** Si se va a abstraer, se **mide
> y se extrae ANTES** de las eliminaciones, no después.

Lo mismo, en menor grado, con `AttachmentPackage`: dos copias, una en `ApplicationCalls`.

Ver T25 para la medición completa.
### REQUISITOS de la centralización de utilidades clonadas — no son notas

Medición completa en T25. Lo que sigue **condiciona el diseño** y hay que cumplirlo, no
tenerlo en cuenta.

#### 1 · La clase centralizada usa `static::CODES`, y hay una prueba que lo demuestra

El constructor de `SafeException` y `DuplicateException` valida
`in_array($code, self::CODES)` y **degrada a `UNDEFINED_CODE`** lo que no esté en la lista.
Hoy funciona porque cada módulo tiene su propia clase con su propia lista.

> **Con una clase centralizada y subclases por módulo, `self::` seguiría resolviendo a la
> lista del PADRE, y el código específico del módulo se degradaría A SILENCIO.** Sin error,
> sin aviso: la excepción se lanza igual y llega con el código equivocado.

**Requisito**: `static::CODES` —enlace estático tardío—, y **una prueba con una subclase que
añade un código propio** y comprueba que sobrevive al constructor. Sin esa prueba el
requisito es una intención.

Los dos módulos que hoy añaden códigos son los que la prueba tiene que reproducir:
`PiecesPHP\UserSystem` (`USER_NOT_EXISTS`) y `Publications` (`PUBLICATION_CODE`,
`CATEGORY_CODE`).

#### 2 · Antes de centralizar hace falta la SEXTA comprobación de integridad

**Toda clase nombrada en un `catch` debe resolver a una clase existente.**

Sin ella la centralización **no se puede hacer con seguridad**, porque el modo de fallo es
mudo: cada módulo captura su clase por el nombre corto que resuelve su `use` de cabecera, y
si uno se queda sin actualizar, **PHP no falla — el `catch` simplemente deja de capturar** y
la excepción sube hasta el manejador global. Un 500 en vez de un mensaje de validación, y
nada que lo explique.

La superficie, medida:

| | |
| :-- | --: |
| Sitios que capturan `SafeException` | **35** |
| Sitios que capturan `DuplicateException` | **12** |
| `throw new` de las dos | **48** |
| Archivos que las importan con `use` | **25** |

**No la cubre ninguna comprobación actual.** La tercera valida que las clases DECLARADAS se
carguen, y su propio docblock ya avisa del hueco: *«un `use` que falta y solo se referencia
dentro del cuerpo de un método se le escapa»* — que es exactamente este caso.
### La fusión `DataImportExportUtility` + `Importers` es una REFACTORIZACIÓN PLANIFICADA

**No borres ninguno de los dos.** El reparto de piezas:

- **`PiecesPHP\Core\Importer` (1.586 líneas, en el núcleo) SE QUEDA.** Es el motor
  extensible y no es código de módulo.
- **`Importers` SE QUEDA.** Es la única implementación viva del motor.
- **`DataImportExportUtility` se FUSIONA en `Importers`**, no se elimina: hay que llevarse
  lo que aporta antes de que desaparezca su carpeta.

Quien llegue después y vea `DataImportExportUtility` en una lista de «módulos a limpiar»
tiene que leer esto primero. Borrarlo sin fusionar pierde trabajo que nadie va a echar de
menos hasta que haga falta.

### Cuánta cola se lleva el filtro

De los **157** errores de la familia `false` (el conteo anterior de 148 salía de la tabla
truncada de PHPStan y estaba contaminado, ver T2):

| Destino | Errores | Archivos |
| :-- | --: | --: |
| Se borra completo — `ApplicationCalls` | 8 | 3 |
| Se borra completo — `ImagesRepository` | 4 | 1 |
| Se borra completo — `InterestResearchAreas` | 3 | 2 |
| Se reescribe — `DataImportExportUtility` | 2 | 2 |
| Se reescribe — `Importers` | 1 | 1 |
| **Trato completo** | **139** | **52** |

**15 errores mueren con su módulo sin que nadie los toque.** Ninguno de los tres módulos
de borrado parcial aporta errores a esta familia.

## T7 · Ventana de pruebas de correo — **planificada, no empezada**

Nueve o diez sitios envían correo. **Medido: 10 llamadas en 7 archivos** —
`RecoveryPasswordController` tiene 3 y `UserProblemsController` 2, de ahí que un recuento
por archivo dé menos. Transporte vivo: **`phpmailer/phpmailer` v7.1.1**. Ya existe
`Mailer::checkSMTP(string $host, int $port)` como sonda, y `checkSettedSMTP()` para saber
si hay configuración.

| Archivo | Sitios |
| :-- | --: |
| `controller/RecoveryPasswordController.php` | 3 |
| `controller/UserProblemsController.php` | 2 |
| `classes/API/Controllers/APIController.php` | 1 |
| `classes/PiecesPHP/UserSystem/Authentication/OTPHandler.php` | 1 |
| `classes/SystemApprovals/Controllers/SystemApprovalsController.php` | 1 |
| `controller/ContactFormsController.php` | 1 |
| `controller/GenericTokenController.php` | 1 |

### Tres capas, y solo la tercera necesita a un humano

**1 · Composición — el grueso del trabajo.** Plantilla, sustitución de variables, grupo de
traducción correcto, y enlaces generados con `get_route()` y no concatenados. Suite normal
del framework, **sin red**: se sustituye el transporte por uno que capture el mensaje en vez
de enviarlo. Aquí cabe casi todo lo que de verdad se rompe.

**2 · Transporte — sumidero SMTP local.** Mailpit o MailHog. Tienen API HTTP, así que la
suite **afirma sobre el mensaje recibido sin que nadie mire**. Es la capa que demuestra que
el sobre sale bien formado, no solo que el cuerpo se compuso bien.

**3 · Entrega real — un puñado de casos.** Buzones temporales de mailinator, autorizados por
el propietario, que confirma la recepción por ojo. Solo lo que no se puede afirmar de otra
forma.

### REGLA DE SEGURIDAD — INNEGOCIABLE

**Los buzones gratuitos de mailinator son PÚBLICOS.** Cualquiera los lee adivinando el
nombre; no hay contraseña que valga.

**Tres de los emisores mandan credenciales vivas:**

| Emisor | Qué manda |
| :-- | :-- |
| `OTPHandler` | Código de un solo uso **que autentica por sí solo** — ver T3: la condición de login es `password_verify(...) \|\| $otpIsValid` |
| `RecoveryPasswordController` | Enlace de recuperación de contraseña |
| `GenericTokenController` | Token genérico |

Esos tres van a mailinator **SOLO con cuentas desechables y sin privilegios**. Nunca con una
cuenta real, y nunca con una administrativa. Un código de un uso publicado en un buzón
público no es una prueba: es una toma de cuenta esperando a que alguien pase por ahí.

Las capas 1 y 2 **no tienen este problema** y por eso deben absorber todo lo que puedan: lo
que se pueda afirmar sin salir de la máquina, no sale de la máquina.

### Lastre detectado de paso

`Core/Email/Mailgun.php` y `Core/MailjetHandler.php` tienen **cero consumidores** — verificado
por búsqueda en todo `src/app`. Mismo patrón que `scssphp` y `mpdf`: integraciones que se
dejaron a medias y nadie retiró. Pertenecen a `14-deuda-y-limpieza.md`; se anotan aquí porque
salieron al inventariar los emisores, y porque **decidir su suerte antes de escribir las
pruebas evita escribir pruebas para código que se va a borrar** — el mismo criterio de T6.

## T8 · CONTABILIDAD DE LOS ERRORES `false` — **fuente única, se actualiza, no se recalcula**

Las cifras se han movido tres veces (148 → 144 → 157) y cada movimiento obligó a
re-verificar. Esta tabla corta ese gasto: **es la única fuente**. Cuando se resuelva un
grupo, se edita aquí; no se vuelve a contar desde cero.

### Cómo mapea el 148 viejo al 157

| | |
| :-- | :-- |
| **148** | Medido parseando `PHPStanResult.txt`, la tabla. **No es reproducible ni reconciliable exactamente**: las rutas venían recortadas a 73 caracteres, así que había errores mal atribuidos y errores descartados por apuntar a archivos inexistentes. |
| **157** | Medido sobre `PHPStanResult.json` en la MISMA ejecución de 968 errores. Salida de máquina, sin recorte. |
| **Diferencia** | +9. No se reconstruye la procedencia exacta de cada uno: la medición vieja se hizo sobre una salida corrupta y fabricar una reconciliación sería inventarla. **El 148 queda derogado.** |

**La cifra de partida autorizada es 157.**

### ¿Sigue siendo comparable el baseline?

**Sí en lo que importa, no en todo.** El recorte afectaba a la cabecera de archivo, nunca
a las filas de error: la misma ejecución da **968 leída como tabla y 968 leída como JSON**.
Lo que sí venía mal era el conteo de ARCHIVOS —192 en vez de 195—, porque cuatro vistas de
`ContentNavigationHub` comparten los primeros 73 caracteres de su ruta y colapsaban en una.

`PHPStanResult.Summary.baseline.txt` lleva ahora una nota de medición con esto y el conteo
de archivos corregido. **El total de errores no se regenera: 968 nunca fue otro número.**

### El método de la columna «Con `false`» — escrito, porque no lo estaba

El «157» se anotó como «medido sobre `PHPStanResult.json`», que es **descriptivo, no
reproducible**: no dice qué se contaba dentro del JSON. Bajo el punto 5 de T0 eso ya no
vale. La expresión autorizada de aquí en adelante:

```bash
php -r '$j=json_decode(file_get_contents("PHPStanResult.json"),true);
$n=0; foreach($j["files"] as $d) foreach($d["messages"] as $m)
if (str_contains($m["message"],"false")) $n++; echo $n,"\n";'
```

Cuenta **instancias** cuyo mensaje menciona `false`. Hoy da **100**, repartidas en cinco
identificadores —`argument.type` (74), `method.nonObject` (14), `return.type` (6),
`foreach.nonIterable` (4), `assign.propertyType` (2)—, todos coherentes con «un `false` se
coló en un valor».

**Advertencia de comparabilidad:** las filas anteriores de la tabla (157 → 107) se midieron
antes de fijar esta expresión, así que **son indicativas, no aptas para el trinquete**. La
serie apta empieza aquí. No se reconstruyen hacia atrás: sería inventar la reconciliación,
el mismo error que derogó el 148.
### Recorrido, medido commit a commit

| Commit | Errores *(instancias)* | Con `false` *(instancias)* | Qué resolvió |
| :-- | --: | --: | :-- |
| `89030cb6` | 968 | **157** | *(punto de partida bajo el pipeline corregido)* |
| `61a0a474` | 968 | 157 | D2 — nulabilidad, no `false` |
| `14d0886a` | 950 | 157 | Migración fuera de `routes()` |
| `6a33b77e` | 933 | **140** | Retipar `PDO` → `Database` (−17) |
| `7f5f00a2` | 916 | **123** | `JSON_THROW_ON_ERROR` (−17) |
| `7ac91445` | 907 | **114** | Familia `strpos` (−9) |
| `d6924b39` | 900 | **107** | `realpath` y `createFromFormat` (−7) |

**Resueltos: 50 de 157. Quedan 107.** Todas las cifras de esta tabla son **instancias**,
no tripletas: se midieron sobre `totals.file_errors` y sobre el conteo de mensajes, sin
deduplicar. La serie apta para el trinquete empieza donde el método quedó escrito, más
arriba.

### Los 107 que quedan, por grupo y destino

Las columnas de abajo cuentan **instancias** agrupadas por función de origen, no archivos:

| Grupo | Trato completo | Se reescribe | Se borra | Total |
| :-- | --: | --: | --: | --: |
| `file_get_contents` / `file` | 44 | 3 | 7 | **54** |
| `DateTime` / `strtotime` | 11 | 0 | 3 | **14** |
| Retorno propio declarado con `\|false` | 12 | 0 | 1 | **13** |
| GD (`imagecreate*`, `imagecolor*`) | 11 | 0 | 1 | **12** |
| Recursos (`fopen`, `opendir`) | 5 | 0 | 0 | **5** |
| `readdir` | 2 | 0 | 0 | **2** |
| `finfo_file` | 1 | 0 | 1 | **2** |
| `fputcsv` | 1 | 0 | 0 | **1** |
| `stream_get_contents` | 1 | 0 | 0 | **1** |
| Otros | 1 | 0 | 2 | **3** |
| **TOTAL** | **89** | **3** | **15** | **107** |

**15 mueren con su módulo y no reciben nada** (ver T6). Los 3 de reescritura solo reciben
atención si son defecto real, no si son un tipo mal declarado. **El trabajo real son 89.**

### Dónde mirar primero

- **Los 12 «retorno propio con `|false`»** son la apuesta principal. Un retorno propio que
  declara `|false` es **una decisión nuestra, no una herencia de PHP**: lo probable es que
  la firma sea lo que está mal, no el sitio de llamada.
- **Los 11 de `DateTime`/`strtotime`** son los siguientes en probabilidad de esconder un
  defecto: una fecha mal parseada no revienta, produce un valor equivocado.
- **Los 44 de `file_get_contents`** son grupo A —el manejador de `bootstrap.php` promueve
  el `E_WARNING` a excepción, así que abortan en vez de devolver `false`—, de modo que son
  volumen mecánico, no caza.

## T9 · Rector — lote 1 aplicado, y las 98 reglas que faltan

Tras arreglar el truncado (T2) y excluir los módulos condenados (T6), Rector ve **175
archivos**. El primer lote —17 reglas mecánicas en 103 archivos— ya está aplicado.

**Quedan 98 reglas en 103 archivos.** No se aplican en bloque: están dominadas por
categorías que cambian comportamiento observable, y en este código base concreto hay dos
trampas que las hacen peligrosas.

### Trampa 1 · Los tipos de retorno y la covarianza

| Regla | Archivos |
| :-- | --: |
| `AddVoidReturnTypeWhereNoReturnRector` | 37 |
| `StringReturnTypeFromStrictStringReturnsRector` | 35 |
| `ClosureReturnTypeRector` | 32 |
| `ReturnTypeFromStrictParamRector` | 32 |
| `BoolReturnTypeFromBooleanStrictReturnsRector` | 28 |
| `AddArrowFunctionReturnTypeRector` | 19 |
| `ReturnTypeFromStrictNewArrayRector` | 18 |
| `AddClosureVoidReturnTypeWhereNoReturnRector` | 17 |
| `ReturnTypeFromStrictFluentReturnRector` | 14 |
| `ReturnUnionTypeRector` | 10 |

**Es la misma lección que costó la minor de `piecesphp/database` v3.2.0**: estrechar el
retorno de un método rompe a quien lo hereda **al declarar la clase, no al llamarla**. Y
este código base es especialmente vulnerable: `routeName()` y `allowedRoute()` están
**copiadas en 36 controladores** a propósito (ver `07-modulos.md`), y los buscadores de
mapper están copiados en 26. Si Rector tipa una copia y no las demás, el fallo aparece al
cargar la clase, lejos del cambio.

**Antes de aplicar cualquiera de estas hay que comprobar, por cada método tocado, si
alguien lo redeclara.**

### Trampa 2 · Las propiedades tipadas contra `__get`/`__set`

| Regla | Archivos |
| :-- | --: |
| `RemoveNullPropertyInitializationRector` | 40 |
| `TypedPropertyFromStrictConstructorRector` | 29 |
| `ClassPropertyAssignToConstructorPromotionRector` | 12 |

Las tres están **explícitamente en el `skip()` del Rector de `piecesphp/database`**, y por
un motivo que aplica igual aquí: los mappers usan `__get`/`__set` con intensidad. Una
propiedad tipada **sin inicializar lanza `Error` al leerla** en vez de devolver `null`, y
quitar la inicialización `= null` de una propiedad tipada la deja justo en ese estado.
Cambia el comportamiento en tiempo de ejecución sin tocar ninguna línea de lógica.

**Propuesta: alinear el `skip()` de este repositorio con el del paquete.**

### El resto, por decidir

| Regla | Archivos | Por qué no es automática |
| :-- | --: | :-- |
| `RemoveUselessParamTagRector` | 83 | Retira `@param`/`@return` en masa. Es una decisión de estilo, no una corrección; el Rector del paquete la salta. |
| `RemoveUselessReturnTagRector` | 72 | Igual. |
| `SimplifyUselessVariableRector` | 61 | Reestructura flujo; seguro en general, conviene lote propio. |
| `UseIdenticalOverEqualWithSameTypeRector` | 55 | `==` → `===`. Si la inferencia de tipo falla, cambia el resultado. |
| `RemoveAlwaysElseRector` | 52 | Reestructura flujo. |
| `ClosureToArrowFunctionRector` | 49 | Cambia la semántica de captura. |
| `SimplifyEmptyCheckOnEmptyArrayRector` | 48 | `empty($a)` → `$a === []`; difieren si `$a` no es array. |
| `NewMethodCallWithoutParenthesesRector` | 36 | Emite **sintaxis de PHP 8.4** (`new Foo()->bar()`). Legal en el piso actual, pero es un cambio visual grande y ata al piso. |
| `RemoveUnusedVariableAssignRector` | 35 | Borra según inferencia: si falla, borra código vivo. |
| `RemoveAlwaysTrueIfConditionRector` | 25 | Igual, sobre condiciones. |

## T10 · NO SE EDITA PHP POR LÍNEA, SE EDITA POR ESTRUCTURA

**Tres incidentes de la misma familia, todos silenciosos, todos con `php -l` en verde:**

| Incidente | Causa | Qué lo delató |
| :-- | :-- | :-- |
| `OrganizationMapper` con un docblock sin cerrar | Un script partió por `\r\n` un archivo con 1.342 CRLF **y un LF suelto**: todos los índices siguientes se desplazaron | Nada automático. Se encontró a mano |
| 32 falsos positivos en `verify-integrity` | Contaba `/*` contra `*/` en texto plano, y `'image/*'` aparece dentro de cadenas | La propia revisión de los resultados |
| `SyncOTPRecordsTask` sin `namespace` ni `use` | Un corte por posición casó con el docblock **de archivo** en vez del **de clase** y se llevó la cabecera | Ejecutar la tarea |

**La regla: cuando haya que preguntarle algo al código fuente, se tokeniza.** `token_get_all()`
lleva cuatro apariciones —`verify-integrity` en dos comprobaciones,
`unit-tests:core/otp-write-separation`, y `declaredClass()`—. Es patrón del proyecto.

Y su corolario, que vale igual: **cortar por índice de cadena es editar por posición**. El
tercer incidente no partió por líneas, partió por `str.index()`, y falló por la misma razón:
buscó una silueta —`/**\n * SyncOTPRecordsTask.`— que casaba dos veces.

### La tercera comprobación de integridad

`bin/cli verify-integrity` comprueba ahora, además de docblocks y firmas, que **toda clase
bajo una raíz PSR-4 se llame como su ruta manda y se pueda cargar**:

1. **Ruta contra namespace.** El FQCN esperado sale de la RUTA; el declarado, del archivo.
   Es la única forma de detectar un `namespace` perdido — derivar el nombre del propio
   archivo es circular y no detecta nada. *(Primer intento hecho así: no detectaba nada.)*
2. **Carga real** con `class_exists($fqcn, true)`, que atrapa un padre, interfaz o trait
   que no resuelve.

**`composer dump-autoload --strict-psr` NO sirve para esto, y conviene saber por qué:** el
`psr-4` de `src/composer.json` declara únicamente `PiecesPHP\Core\`. Todo `src/app/classes`
—donde vive la mayoría del código propio— lo resuelve el autoloader del framework
(`config/autoloads.php`), que registra esa carpeta como raíz **sin prefijo**. Composer no ve
nada de ahí. Por eso la comprobación se implementa directamente, con las dos raíces en
`VerifyIntegrityTask::PSR4_ROOTS`. **Si se añade una raíz nueva, va ahí o deja de
comprobarse.**

**Probada contra el fallo real**, no solo escrita: una clase con el namespace equivocado da
`declara Espacio\Equivocado\Broken y su ruta exige ZzIntegrityProbe\Broken`, y un padre
inexistente da `Class "…" not found`. Las dos con salida 1.

### LO QUE ESTA PUERTA NO ATRAPA

- **Un `use` que falta y solo se referencia dentro del cuerpo de un método.** La clase se
  declara y se carga sin problema; el fallo aparece al ejecutar esa línea. Eso solo lo caza
  una prueba que la ejecute.
- **Clases que se cargan durante el arranque** —tareas de terminal, mappers referenciados
  al registrar rutas—. Si una de esas se rompe, el CLI muere ANTES de que la puerta corra.
  No es grave: el fallo es ruidoso e inmediato. Pero significa que la puerta protege sobre
  todo el código de carga tardía, que es justo donde el fallo sí sería silencioso.

## T11 · `declare(strict_types=1)` en CERO archivos — la raíz que explica la familia

**Medido: 0 de 782 en `piecesphp`, 0 de 61 en los cuatro paquetes.**

Ahí está el porqué estructural de toda la familia `false`. Sin `strict_types`, un `false` que
entra donde se espera `string` se convierte en `''` **en silencio**:

| Sitio | Qué producía |
| :-- | :-- |
| `sha1(json_encode($checksumData))` | `sha1('')` — todo dato corrupto con el MISMO ETag |
| `setDataCache(json_encode($result), …)` | Un archivo de cero bytes servido como caché válido |
| `base64_encode(json_encode(...))` | Una carga vacía con apariencia de dato |

**No son tres accidentes: son el mismo mecanismo tres veces.**

### NO SE HACE UN BARRIDO

Activarlo en código existente cambia la coerción de **todas** las llamadas de ese archivo, y
un `"5"` donde se espera `int` empieza a lanzar. Cambiaríamos 843 riesgos silenciosos por un
número desconocido de fallos ruidosos, de golpe y sin red.

### La regla, desde hoy

**Todo archivo NUEVO lleva `declare(strict_types=1)`.** Cuesta cero en un archivo que se
escribe con la regla puesta.

### La ventana para el código existente

De uno en uno, **empezando por los archivos que tengan cobertura de pruebas**, y nunca en
lote. El orden natural es: primero lo que cubren las suites del framework y la del
exportador; después el núcleo; los módulos, al final, y los condenados nunca.

## T12 · `routeName` / `allowedRoute` — **CERRADO**: medido, normalizado y unificado en dos traits

**La duplicación era deliberada; las diferencias no.** Ese es el corte que decide el lote.

### Medición por tokens (no por sangría)

| Método | Cuerpos distintos ANTES | Archivos | Cuerpos DESPUÉS del paso B |
| :-- | --: | --: | --: |
| `routeName` | 10 | 44 | **9** (dominante: 21 → **26** archivos) |
| `allowedRoute` | 5 | 38 | **5** (dominante: 22 → **28** archivos) |
| `_allowedRoute` | **26** | 32 | 26 (no se toca) |

**La estimación anterior con `awk` se quedaba CORTA en `routeName`** —decía 6, son 10—, no
inflada como se temía. Cortar en la primera llave a cuatro espacios pierde cuerpos con
bloques anidados en vez de inventarlos.

### Diferencias clasificadas

**DERIVA — normalizadas en el paso B, todas demostrablemente equivalentes:**

| Forma encontrada | Forma canónica | Por qué es equivalente |
| :-- | :-- | :-- |
| `!is_null($name) ? $name : ''` | `$name ?? ''` | Idénticas por definición de `??` |
| `strlen($name) > 0` | `$name !== ''` | Va tras `trim()`: `$name` es string |
| `mb_strlen($name) > 0` | `$name !== ''` | `mb_strlen` vale 0 solo con cadena vacía |
| `strlen($route) > 0` | `(string) $route !== ''` | Mismo predicado |
| `is_string($route) ? $route : ''` | `!is_string($route) ? '' : $route` | Ternario invertido |

**Parte de esa divergencia la introdujo el lote 1 de Rector**: antes había 39 archivos con
`strlen($route) > 0`; Rector convirtió 25 y dejó 14, porque solo pudo probar el tipo en
esos. Un lote mecánico partió en dos un grupo homogéneo. El paso B lo deshace.

**INTENCIONAL — se quedan como están, y el trait NO las cubre:**

| Grupo | Archivos | Qué hace distinto |
| :-- | :-- | :-- |
| Controladores públicos | `PublicationsPublicController`, `ApplicationCallsPublicController`, `BuiltInBannerPublicController`, `GoogleReCaptchaV3Controller`, `FileManagerController`, `AppConfigController` | **No llaman a `_allowedRoute()`**: devuelven la ruta sin pasar por el hook |
| `App/Locations/*` (5) | `City`, `Country`, `Point`, `Region`, `State` | Prefijo de **dos niveles**: `$prefixParentEntity . '-' . $prefixEntity` |
| `ContactFormsController`, `PublicAreaController` | 2 | Usan `self::$prefixNameRoutes` en vez de `self::$baseRouteName` |
| `TerminalController` | 1 | **Otra firma**: `routeName(?string, bool)` sin `$params`, y usa `self::routeID()` |
| `DataImportExportUtilityController` | 1 | `get_config('current_user')` y `!= false` en vez de `getLoggedFrameworkUser()` y `!== null`. Legado; el módulo se reescribe (T6) |

**`TerminalController` merece una nota**: su `allowedRoute` llama a `self::routeName($name, true)`
con dos argumentos, lo que parece un error hasta que se mira su firma — que efectivamente
tiene dos parámetros. **Es coherente consigo mismo, no un defecto.**

### El destino aprobado: `RouteNamingTrait`

`_allowedRoute()` como **hook `protected` con implementación por defecto `return true;`**.

El razonamiento: hoy quien clona un módulo copia sesenta líneas sin saber cuáles debe tocar,
porque son todas ruido idéntico. Con el trait, lo único escrito en su controlador es
`_allowedRoute()` — **exactamente la parte que sí debe pensar**. La convención no se pierde,
se afila.

Es **trait y no clase base** porque los controladores públicos extienden `BaseController`,
no `AdminPanelController`: componen en vez de heredar. Dentro del trait, `self::` y `static::`
siguen resolviendo a la clase que lo usa, así que las constantes por módulo siguen
funcionando.

### Pasos pendientes

- **C.1 · HECHO.** Dos traits en `PiecesPHP\Core\Routing`: `RouteNamingTrait` —`routeName()`
  más el hook `_allowedRoute()` con `return true;` por defecto— en los **44**, y
  `RouteGuardTrait` —`allowedRoute()`— en los **38** que exponen guardián. Los otros seis
  nombran rutas y no guardan: los cinco de `App\Locations` y `ContactFormsController`.

  **Medido con C.1 solo, antes de borrar nada: 877 instancias, 868 tripletas, 190 archivos.
  Idéntico al estado anterior, sin mover un punto.** Suites 20/20, 13/13, 6/6, 12/12.

  El hook vive en el trait de NOMBRADO y no en el de guarda por funcionamiento, no por
  gusto: `routeName()` lo llama siempre, así que un controlador que usara el trait sin
  declararlo tendría un fatal.

- **C.2 · HECHO.** Borradas **26** copias de `routeName` y **28** de `allowedRoute`. El
  criterio fue **identidad por TOKENS contra el cuerpo del trait** —firma incluida,
  comentarios fuera—, comprobada archivo a archivo justo antes de borrar; la herramienta se
  niega y deja el archivo intacto si no coincide. Por eso **18 y 10 se conservaron**, y son
  exactamente los grupos no dominantes ya clasificados arriba.

  **Después de borrar 54 métodos: 877 / 868 / 190 otra vez.** Con **606 sitios de llamada**
  a `routeName(` / `allowedRoute(` en el código, que PHPStan resuelve en nivel 8: si alguno
  hubiera quedado sin método, habría salido como `staticMethod.notFound`. Los dos únicos
  `method.notFound` del informe son de `TerminalController` y ya estaban.

  `verify-integrity` reportó **54 firmas desaparecidas**, que es justo `26 + 28`. La
  instantánea se regeneró: las firmas no desaparecieron, se mudaron al trait.

- **C.3** *(no autorizado)* Las variantes intencionales conservan su método local. **Su `use`
  del trait no las cambia**: el método declarado en la clase gana siempre. Comprobado en
  aislamiento, no supuesto — una clase con método propio devuelve el suyo y una sin él, el
  del trait.
- **D** Tipar. **`AdminPanelController` es base de varios controladores: la base y todos sus
  descendientes se tipan EN EL MISMO COMMIT.** Si se tipa la base y un hijo redeclara sin
  tipo, el hijo ensancha y **PHP falla al declarar la clase**, no al llamarla.

### SEGUNDA PASADA — el criterio de la primera era el equivocado

**«Idéntico por tokens al cuerpo del trait» dejó vivas dieciséis copias que no hacían nada.**
Era un criterio de FORMA, y el andamio no se distingue por la forma: se distingue por si
decide algo.

**El criterio nuevo es una sola pregunta: ¿ESTE MÉTODO DECIDE ALGO?**

| Método | Copias borradas · 1.ª pasada | Copias borradas · 2.ª pasada | Sobreviven |
| :-- | --: | --: | --: |
| `routeName` | 26 | **9** | **9** |
| `allowedRoute` | 28 | **9** | **1** |
| `_allowedRoute` | 0 | **17** | **15** |
| **TOTAL** | 54 | **35** | **25** |

**89 copias borradas entre las dos pasadas.**

Lo que las diecisiete de `_allowedRoute` tenían dentro: `$allow = $route !== ''` con
variaciones de forma —`strlen($route) > 0`, `(string) $route !== ''`—, un closure `$getParam`
que nadie llamaba, `$currentUserType` y `$currentUserID` asignados y **jamás leídos**, y un
`if` de relleno comparando contra `'SAMPLE'`, `'sample'` o `'NOMBRE_RUTA'`.

> **No eran variantes de un comportamiento. Eran estratos de una plantilla y huecos que nadie
> rellenó.**

#### El susto de `'SAMPLE'`, que casi conserva cuatro cuerpos muertos

Hay literales `'SAMPLE'` **vivos** en `view/webflow/layout/menu.php`, llamando a
`genericViewRoute('SAMPLE')`. Parecía que la rama sí se alcanzaba.

**No se alcanza:** esa función pasa `'SAMPLE'` dentro de `$params['name']` y llama a
`PublicAreaController::routeName('generic', …)`, así que `$name` vale `'generic'`. Y
comprobado además que **ninguna llamada del proyecto pasa `'SAMPLE'`, `'sample'` ni
`'NOMBRE_RUTA'` como primer argumento** de `routeName()` o `allowedRoute()`.

Sin esa comprobación se habrían conservado cuatro cuerpos muertos por miedo. **T20 en la
dirección contraria: una medición que asusta también hay que verificarla.**

#### Los tres traits pasan a ser UNO

`ControllerRoutingTrait`, con los tres métodos. La separación entre nombrado y guarda **era
una frontera inventada**: `routeName()` llama SIEMPRE a `_allowedRoute()`, y `allowedRoute()`
no hace más que preguntarle a `routeName()` si devolvió cadena.

#### La puerta: `KNOWN_ROUTE_OVERRIDES`

Quinta comprobación de `verify-integrity`. **Tres direcciones, las tres probadas
provocándolas:**

| Dirección | Mutación | Resultado |
| :-- | :-- | :-- |
| Declaración sin registrar | Añadir un `allowedRoute()` a `NewsController` | Salida **1**, nombrando `News\Controllers\NewsController::allowedRoute` |
| Entrada que deja de decidir | Vaciar el `_allowedRoute` de `SystemApprovals` | Salida **1**: *«está registrado pero YA NO DECIDE NADA»* |
| Entrada cuya declaración no existe | Meter `Zz\Sonda::routeName` en el registro | Salida **1**: *«ya no se declara»* |

El veredicto lo da **el mismo clasificador con el que se construyó el registro**, de modo que
la puerta no puede separarse del criterio. Y es **conservador a propósito**: solo dice «no
decide» para un conjunto cerrado de formas: un falso «decide» deja un método de más, un falso
«no decide» **borra una regla de autorización**.

**Lo que la puerta NO atrapa**, escrito en su propio docblock: el clasificador razona sobre
el cuerpo, no sobre quién llama. Un `if ($name == 'SAMPLE') { $allow = false; }` cuenta como
que decide aunque ninguna ruta se llame así.

#### Efecto medido sobre las ramas muertas

**373 → 325 tripletas, −48.** *Menos de las 89 que se habían anunciado*, y la razón es que
**89 contaba el andamio de los 32 `_allowedRoute`, y quince sobreviven** porque deciden. De
los que quedan dentro de los métodos supervivientes: **41 tripletas**, casi todas el
`isset($_POST)` / `isset($_GET)` del closure `$getParam` — que **9 de los 10 que lo declaran
sí usan**, así que ahí lo que sobra es el `isset`, no el closure.
## T13 · Ramas muertas — TRIAJE HECHO y VUELTO A MEDIR; supresión NO autorizada

**Son 464, no ~357.** Medidas destapando los 27 identificadores silenciados en `phpstan.neon`:
1.347 errores con ellos fuera contra 878 con ellos dentro.

### Un dato que reduce el miedo

**`treatPhpDocTypesAsCertain: false` está activo.** PHPStan **no se fía de nuestros
docblocks** para decir «siempre verdadero»: solo usa tipos nativos e inferencia real. Eso
recorta estructuralmente la clase (b) —«el tipo declarado miente»— porque los tipos nativos
los impone PHP en ejecución.

Donde (b) **sí** puede darse en este código base es donde la inferencia es incompleta por
diseño: `__get`/`__set` de los mappers, y variables inyectadas en vistas por `extract()`.

### Reparto estimado

| Clase | Cantidad | % | Trato |
| :-- | --: | --: | :-- |
| **(a)** Resto defensivo de la era PHP 5/7 | **300** | 64,7 % | Se borra la rama |
| **(b)** Vistas y `extract()`, variable inyectada | **105** | 22,6 % | **No tocar**: la variable existe, PHPStan no puede verlo |
| **(b)** Clase con `__get`/`__set` o mapper | **59** | 12,7 % | **Mirar una a una**: aquí es donde la rama puede estar protegiendo de la realidad |

**Dos tercios son (a).** El tercio restante no se suprime en bloque: en las vistas la
supresión ya existe por ruta y es correcta; en los mappers cada una es una pregunta.

Archivos con más ramas de la clase magia: `ImagesRepositoryMapper` (4, **condenado**),
`BuiltInBannerMapper` (4), `ApplicationCallsMapper` (3, **condenado**), `CategoriesMapper` (3),
`DocumentTypesMapper` (3), `NewsMapper` (3).

### APLICADO · `dynamicConstantNames`, con el delta contado aparte

**No son 27 constantes: son 25.** El «27» eran los **identificadores** de ignore del bloque
de código muerto, que es otra cosa. Las constantes, con su comando:

| Cifra | Comando |
| --: | :-- |
| **24** interruptores booleanos | `grep -oE "define\('([A-Z0-9_]+)',\s*(true\|false)\)" src/app/config/constants.php` |
| **+1** `ORGANIZATIONS_MODULE` | Se define desde `CRITICAL_CONSTANTS`, no con un literal, así que el patrón anterior no la ve |
| **27** identificadores de ignore | Las reglas del bloque son 28: 27 `identifier` y un `message` (`left side of ?? always exists`) |

**Esto NO es una supresión.** No oculta un error: le dice a PHPStan la verdad, que el valor
de esas constantes no se conoce en análisis porque **cada despliegue las configura**. Por eso
no lleva condición de retirada — la única que la retiraría es que el framework dejase de ser
una plantilla que se clona.

**Los cuatro paquetes NO llevan el bloque.** No ven `constants.php` y **ninguno menciona
estas constantes**: `grep -rlE "PUBLICATIONS_MODULE|NEWS_MODULE|…"` sobre los cuatro `src/`
da **cero archivos**. Ponérselo sería escribir en un registro algo que allí no significa
nada, que es justo lo que el punto 3 de T0 quiere evitar.

#### El delta, contado aparte

| Medición | Antes | Después | Delta |
| :-- | --: | --: | --: |
| **Baseline visible** (instancias / tripletas) | 877 / 868 | **877 / 868** | **0** |
| Ramas muertas, instancias | 470 | **373** | **−97** |
| Ramas muertas, tripletas | 465 | **373** | **−92** |

**El baseline visible no se mueve, y tenía que no moverse**: esas ramas ya estaban
silenciadas por el bloque de ignores, así que retirarles el veredicto falso no cambia nada
de lo que se ve. **El movimiento entero vive en la medición que hoy está tapada.**

Contraste con la estimación previa, que aguanta: la muestra decía **21,7 %** de la clase (a);
la medición da **92 de 465 = 19,8 %** del conjunto entero. Dos instrumentos distintos y el
mismo orden de magnitud.

**Método, para que la cifra sea comparable la próxima vez:**

```bash
bin/phpstan-deadcode
```

Deriva la configuración sin el bloque a partir de `bin/phpstan.neon` en cada ejecución —no
la duplica, así que no puede quedarse atrás— y resta las dos corridas. Cuenta **tripletas**
(ruta, línea, mensaje).

#### Un susto que era del instrumento, otra vez

La primera lectura dio **877 → 878** y estuvo a un paso de escribirse como «destapar las
constantes sacó a la luz un error nuevo». **No era eso.** El +1 era un error de mi propia
suite recién añadida —`UnitTest-MetaPropertyHybrid.php:70`, un `ReflectionClass` sobre un
`string` sin estrechar—, que entró en la medición por estar bajo `src/app`.

El segundo método que lo destapó: correr con una copia de la configuración **sin** el bloque
de constantes dinámicas. Dio **869 tripletas en las dos direcciones**, así que el cambio no
podía ser el culpable. **T20, la quinta vez.**

### El reparto cambió solo, como se sospechaba — el «235» queda derogado

| Familia | Antes *(estimado por muestra)* | Ahora *(medido por ruta)* |
| :-- | --: | --: |
| **Total** | 465 | **373** |
| Vistas y `extract()` | 105 | **22** |
| Mappers | 59 | **43** |
| Suites de caracterización | — | **3** |
| Resto | ~300 | **305** |

> **Cuidado con las columnas: no son el mismo instrumento.** La de la izquierda es una
> estimación por muestreo; la de la derecha, un conteo por ruta de archivo. **Solo el total
> es comparable.** El **235 «borrables sin tocar configuración» queda derogado**: nacía de
> restar 65 a 300, y ninguna de las dos cifras sobrevive a la medición nueva.

Las de arriba por identificador, que es por donde conviene atacarlas —**por familia entera,
nunca sueltas** (T17):

| Instancias | Identificador | Qué es |
| --: | :-- | :-- |
| **129** | `function.alreadyNarrowedType` | `is_array()` sobre un `array`, `is_string()` sobre un `string`. Resto defensivo puro |
| **50** | `isset.variable` | `isset($x)` sobre una variable que PHPStan ya sabe definida |
| **42** | `notIdentical.alwaysTrue` | `!==` que nunca puede ser falso |
| **24** | `foreach.emptyArray` | Recorrer un array que la inferencia da por vacío |
| **21 / 20** | `if.alwaysTrue` / `if.alwaysFalse` | La rama entera sobra |

**Nada de esto se borra todavía.** El encargo era medir antes de tocar, y la medición dice
que el mapa anterior ya no sirve.
## T14 · `scan-invalid-utf8` VIAJA CON EL FRAMEWORK

`bin/cli scan-invalid-utf8` es **comprobación previa a actualizar**, no una herramienta de
esta ventana.

Cualquier despliegue congelado que vaya a descongelarse puede mirarse antes de hacerlo:
desde que los sitios de codificación llevan `JSON_THROW_ON_ERROR`, un texto con UTF-8
inválido en base de datos deja de servir un dato ligeramente mal y pasa a **cortar la
petición con un 500**. Esta tarea dice si hay pólvora **antes** de actualizar.

Es de **solo lectura** y comprueba con `mb_check_encoding()`, no en SQL: quien tiene que
aceptar el dato es PHP.

**El entregable es la tarea, no su resultado.** El cero que da aquí no prueba nada —763 filas
en 41 tablas es una base de juguete—; el valor está en poder correrla contra una base real.

## T15 · CANDIDATO FUTURO — registrar `src/app/classes` en el `psr-4` de Composer

**No hacer todavía. Ventana propia, con su riesgo evaluado.**

El `psr-4` de `src/composer.json` declara únicamente `PiecesPHP\Core\`. **Composer no ve
`src/app/classes`, donde vive el código de negocio entero.** Por eso todas las herramientas
estándar necesitan aquí trabajo a medida: fue el motivo de que `--strict-psr` no sirviera
para la tercera comprobación de integridad (T10).

Registrarlo como **prefijo vacío** devolvería visibilidad a todo el instrumental y podría
sustituir parte de esa comprobación por `--strict-psr` nativo.

**El riesgo a evaluar antes**: hoy conviven el autoloader de Composer y el propio del
framework; registrar la misma carpeta en los dos puede provocar resoluciones ambiguas —ya hay
una, `PiecesPHP\Core\Database\Meta\MetaProperty`, declarada en `src/app` y en el paquete
`piecesphp/database`—.

**Ese riesgo ya no es ciego**: `bin/cli verify-integrity` falla si el núcleo eclipsa una
clase de cualquier paquete, con los eclipses aceptados registrados en `KNOWN_ECLIPSES`. Si
registrar la carpeta creara colisiones nuevas, la puerta las nombra en vez de dejarlas pasar.

## T16 · COLISIÓN DE CLASE `MetaProperty` — verificada, viva y silenciosa

> **RESUELTA EN EL PLAN POR T22, y con una corrección de diagnóstico.** Lo que sigue
> describe bien la colisión y su mecanismo, pero la palabra «HÍBRIDO» que se usa más abajo
> **es imprecisa**: `EntityMapper` del paquete no usa `MetaProperty` —cero menciones—, la
> usa `ExtensibleORM`. Los dos repositorios están internamente bien y el único defecto es
> el FQCN compartido. **Lee T22 antes que esto.**

**No es una «resolución ambigua». Son dos clases distintas con el mismo FQCN.**

`PiecesPHP\Core\Database\Meta\MetaProperty` está declarada en dos sitios y **los archivos
difieren de raíz**:

| Dónde | Líneas | Se construye sobre | Tiene en exclusiva |
| :-- | --: | :-- | :-- |
| `src/app/core/psr4/.../Meta/MetaProperty.php` | 617 | `EntityMapper` | `$internalName`, `setInternalName()`, `getInternalName()`, `getType()`, excepciones que nombran el campo |
| `src/vendor/piecesphp/database/src/.../Meta/MetaProperty.php` | 636 | `ORM` | `TYPE_DATE` con `'now'`, `validateType()` propia, `call_user_func($mapper, 'getInstance', ...)` |

### Cuál gana — AVERIGUADO, no deducido

`(new \ReflectionClass(MetaProperty::class))->getFileName()` devuelve **la del núcleo**. La
del paquete queda eclipsada dentro de piecesphp.

### El mecanismo, y por qué es general

| Repositorio | Prefijo PSR-4 |
| :-- | :-- |
| Los cuatro paquetes | `PiecesPHP\` → `src/` |
| **piecesphp (raíz)** | **`PiecesPHP\Core\`** → `./app/core/psr4/PiecesPHP/Core` |

PSR-4 resuelve por **prefijo más largo**. **Cualquier clase que el núcleo declare bajo
`PiecesPHP\Core\` eclipsa a la del paquete, siempre.** No es un accidente de este archivo:
es la regla.

### Barrido de los cuatro paquetes

| Paquete | Clases bajo `Core/` | Eclipsadas y **distintas** |
| :-- | --: | --: |
| `database` | 32 | **1** — `Database/Meta/MetaProperty.php` |
| `datastructures` | 5 | 0 |
| `html` | 16 | 0 |
| `geojson` | — | sin `Core/` |

**Una sola colisión en los cinco repositorios**: un archivo suelto y no un subárbol
duplicado, que es justo lo que la hace difícil de sospechar.

### Lo que corre en piecesphp es un HÍBRIDO

`ExtensibleORM.php` —del **paquete**— usa `MetaProperty` y recibe la del **núcleo**. Y la del
núcleo llama a `EntityMapper::validateType()`, clase que **solo existe en el paquete**
(verificado por reflexión).

Es decir: **`MetaProperty` del núcleo + `EntityMapper` del paquete**, una combinación que
**ninguno de los dos repositorios prueba**. Las suites del paquete (`UnitTest-MetaUtil`,
`UnitTest-ActiveRecord`) corren con su propio autoloader y validan el `MetaProperty` **del
paquete**, que dentro de piecesphp no se ejecuta nunca.

### Consecuencia concreta, ya medida

El arreglo de la deprecación de PHP 8.5 —`new \DateTime($value ?? 'now')`— se aplicó a los
**dos** archivos del paquete. En `MetaProperty` **no tiene ningún efecto en piecesphp**; en
`EntityMapper` sí, porque esa clase no está eclipsada y es la que el núcleo acaba llamando.

**Esta vez el arreglo llegó por el otro camino. La próxima puede no llegar.**

### Cuál es la buena — NO DECIDIDO. No se borra nada todavía

No son dos versiones de lo mismo: son **dos linajes**. `EntityMapper` contra `ORM` no es un
detalle de estilo, es otra jerarquía. Antes de tocar nada hay que responder:

1. ¿`ORM` y `EntityMapper` son el mismo concepto con dos nombres, o dos abstracciones vivas?
2. `$internalName` (solo núcleo) y `TYPE_DATE` con `'now'` (solo paquete): ¿alguna es
   funcionalidad viva que se perdería al unificar?
3. En piecesphp hay **17 archivos** con **39** llamadas a `addMetaProperty()`; **todos**
   reciben la del núcleo. *(El «28» de la primera redacción queda derogado: ver más abajo.)*

**Lo que NO se puede hacer**: borrar la del paquete «porque no se usa». Sí se usa —en el
paquete, con sus propias pruebas— y borrarla lo rompe como librería independiente.

### 2a · CUÁL ES EL CORRECTO **PARA CÓMO SE EJECUTA AQUÍ** — respondido

**La del núcleo, y no por poco.** No porque esté mejor escrita: porque es la única
compatible con el linaje que hay poblado en piecesphp.

Si mañana ganara la del paquete —basta con que alguien borre el archivo del núcleo
«porque está duplicado»— rompería por **cuatro** sitios distintos:

| # | Qué rompe | Evidencia |
| :-- | :-- | :-- |
| 1 | **Fatal al arrancar cualquier mapper.** `EntityMapperExtensible::addMetaProperty()` llama a `getInternalName()` y `setInternalName()` | `EntityMapperExtensible.php:81-83`. Esos métodos **solo existen en la copia del núcleo** |
| 2 | **Todo campo `TYPE_MAPPER` / `TYPE_ARRAY_MAPPER` dejaría de validar.** La copia del paquete comprueba `is_subclass_of($value, ORM::class)` | En `src/app`: **35** clases extienden el linaje `EntityMapper`, **0** extienden `ORM` |
| 3 | **La conversión de un valor a mapper llamaría a un método inexistente.** La copia del paquete hace `call_user_func($mapper, 'getInstance', …)` | `ORM::getInstance()` existe (`ORM.php:123`). **`EntityMapper` no tiene `getInstance()`** |
| 4 | **`null` en un campo mapper anulable.** El núcleo envuelve todo el bloque en `if ($value !== null)` y fuerza `$value = null; $existsOnMapper = true`. La copia del paquete no: el `null` entra en la conversión | Hay campos así declarados: `OrganizationMapper.php:332`, `UserProfileMapper.php:187` |

**Y al revés, la del paquete es la correcta EN SU CASA**: `ORM` no tiene `validateType()`,
así que su `MetaProperty` tuvo que traerse una propia; `EntityMapper` sí la tiene
(`EntityMapper.php:1307`), así que la del núcleo delega. **Ninguna es «la buena versión»:
cada una está adaptada a su clase base.** Por eso el diff es de 567 líneas y no de veinte.

### Las tres preguntas abiertas de T16, ya con respuesta

1. **¿`ORM` y `EntityMapper` son el mismo concepto con dos nombres?** **No: son dos
   abstracciones vivas.** Dentro del paquete coexisten y **ninguna extiende a la otra**.
   En piecesphp solo una está poblada (35 contra 0). `DataTablesHelper` es el único sitio
   que acepta las dos, y lo hace con un `instanceof`, no con una jerarquía común.
2. **¿`$internalName` y `TYPE_DATE` con `'now'` son funcionalidad viva?**
   - `$internalName` **sí**: se añadió a propósito en `06a491e7` («Mejor debug de meta
     properties»), lo consume `EntityMapperExtensible` y nombra el campo en **tres**
     mensajes de excepción. Unificar sin él degrada el diagnóstico.
   - `TYPE_DATE` con `'now'` **aquí no tiene consumidores**: `grep -rn
     "MetaProperty::TYPE_DATE" src/app --include=*.php` da **0**. No puede tenerlos, porque
     la copia que se ejecuta no ofrece esa semántica.
3. **Cuántos mappers dependen de esto** — *cifra corregida bajo el punto 5 de T0.* El «28»
   anotado antes **no es reproducible**: ninguna medición lo devuelve. Las cifras con su
   comando:

   | Cifra | Comando |
   | --: | :-- |
   | **17** archivos llaman a `addMetaProperty()` | `grep -rln "addMetaProperty" src/app --include=*.php \| wc -l` |
   | **39** llamadas en total | `grep -rc "addMetaProperty" $(grep -rln …) ` sumando la segunda columna |
   | **16** archivos construyen `new MetaProperty` | `grep -rln "new MetaProperty" src/app --include=*.php \| wc -l` |
   | **19** declaraciones de tipo mapper | `grep -rn "TYPE_MAPPER\|TYPE_ARRAY_MAPPER" src/app --include=*.php \| wc -l` |

   **El «28» queda derogado.** No se reconstruye: no consta con qué se midió.

### Por qué el arreglo de 8.5 llegó de rebote — el rastro completo

El commit del paquete `41a3e9d` («fix(php85): pasar null a `DateTime::__construct()`»)
tocó **tres** archivos: `EntityMapper.php`, `Meta/MetaProperty.php` y
`ORM/Fields/DataProcess.php`.

De esos tres, en piecesphp solo tiene efecto el primero. Y la copia del núcleo **no
construye ninguna fecha**: `grep -n "DateTime\|strtotime"` sobre ella da **cero**. Delega
en `EntityMapper::validateType()`, que no está eclipsada. **Ese es el único hilo por el que
llegó el arreglo, y nadie lo tendió a propósito.**

### Nadie prueba lo que aquí se ejecuta — demostrado

`unit-tests/UnitTest-MetaUtil.php` del paquete llama a
`MetaProperty::validateType('TEXT', 'world')`. **Ese método estático no existe en la copia
que corre en el framework** (verificado por reflexión: `hasMethod('validateType')` → `NO`).
La suite pasa en el paquete y sería un fatal aquí. Eso es 2c.

### Qué NO se decide aquí

**Cuál se borra sigue sin decidirse**, y este análisis no lo empuja hacia ningún lado: la
del paquete se usa en el paquete, con sus pruebas, y borrarla lo rompe como librería
independiente. Lo que este análisis sí cierra es que **la del núcleo no puede irse sin
reescribir el linaje entero**.
## T17 · REGLA — una regla mecánica se aplica a TODA la familia o a ninguna

**La mejor lección del lote, y salió de un error propio.**

Antes del primer lote de Rector había **39 archivos** con `strlen($route) > 0`. Rector
convirtió **25** y dejó **14**, porque solo pudo probar el tipo en esos.

**Cada cambio individual era correcto. El resultado agregado fue peor:** un lote mecánico
**partió en dos un grupo homogéneo**, justo en el código que se estaba a punto de unificar.

> **Antes de correr una regla mecánica, cuenta a cuántos sitios de la familia alcanza. Si no
> los cubre todos: o completas el resto a mano EN EL MISMO COMMIT, o no la corres.**

La aplicación parcial **fabrica divergencia** aunque acierte caso por caso. Y en una plantilla
que se clona, la divergencia fabricada se hereda.

### Corolario medido en la misma sesión

Los scripts de edición en Python convirtieron **CRLF a LF en 20 archivos**, entre ellos
`OrganizationMapper.php` (1.342 líneas) y `PublicationsController.php` (1.935). PHP no
distingue, pero el commit del paso B declaraba **4.185 inserciones para 41 líneas de cambio
real**: imposible de revisar. **Hay que abrir con `newline=''`.** Es la misma familia que
T10 —editar por estructura y no por posición—, aplicada a los finales de línea.

## T18 · Las 300 ramas de clase (a) — MUESTRA VERIFICADA, EL LOTE NO SE APLICA

Muestra determinista de 20, repartida por todo el conjunto y **verificada a mano**.
**No sale limpia**, y el motivo invalida el borrado en lote.

### El hallazgo: 6 de 20 dependen de configuración por despliegue

| Muestra | Código | Por qué PHPStan dice «siempre verdadero» |
| :-- | :-- | :-- |
| `APIRoutes.php:43` | `if (self::ENABLE \|\| self::ENABLE_TRANSLATIONS \|\| …)` | Las constantes valen `true` **en este despliegue** |
| `FileManagerRoutes.php:42` | `if (self::FILE_MANAGER_ENABLE)` | Ídem |
| `NewsletterRoutes.php:43` | `if (self::ENABLE)` | `NEWSLETTER_MODULE` está en `true` |
| `ImagesRepositoryRoutes.php:35` | `const ENABLE = IMAGES_REPOSITORY && LOCATIONS_ENABLED` | Las dos en `true` |
| `HelpersSystemRoutes.php:43` | `if (self::ENABLE)` | Ídem |
| `APIController.php:1828` | `if (APIRoutes::ENABLE_USERS)` | Ídem |

**Esas constantes son los interruptores de módulo** (`config/constants.php`, regla 8 de
`CLAUDE.md`), y **cada despliegue las configura**. PHPStan las resuelve al valor de *este*
árbol. **Borrar esos `if` cablearía todos los módulos en ENCENDIDO**, y un despliegue que
apague `NEWSLETTER_MODULE` se rompería sin que nadie pueda ver por qué.

**Es exactamente «embarcar una trampa»** (T0).

### Reparto corregido

| | Cantidad |
| :-- | --: |
| Clase (a) medida | **300** |
| **De ellas, la condición depende de una constante de configuración** | **65 (21,7 %)** |
| Borrables sin tocar configuración | **235** |

### Qué hacer con cada parte

- **Las 65 NO se borran nunca.** Van a **supresión documentada** en el `.neon` con su razón:
  *«la condición depende de una constante de `config/constants.php`, que cada despliegue
  configura; PHPStan la resuelve al valor de este árbol»*. Esa razón es permanente, no
  temporal, así que **no lleva condición de retirada**.
- **Las 235 restantes**: la muestra de 20 dejó 14 verificadas como resto defensivo real
  —`is_array()` sobre `array`, `is_null()` sobre `string`, `if (false)`, `Dead catch`—.
  Borrables, pero **por familia y con una muestra por familia**, no las 235 de una vez.

### Reconciliación de la resta, que estaba pendiente

| | |
| :-- | --: |
| Errores con los ignores **dentro** | 878 (869 claves únicas) |
| Errores con los ignores **fuera** | 1.347 (1.333 claves únicas) |
| **Resta de totales** | **469** |
| **Resta de claves únicas** | **464** |

**Las dos cifras son correctas en unidades distintas**: 469 son *instancias* de error y 464
son *tripletas distintas* (ruta, línea, mensaje). La diferencia de 5 son duplicados exactos:
14 en la corrida nueva contra 9 en la vieja.

## T19 · Los hooks `_allowedRoute` — comprobado, NO hay ninguno muerto

La sospecha era razonable: un `_allowedRoute()` declarado que nadie invoca sería **una
restricción escrita y jamás cumplida**, es decir un hallazgo de seguridad y no limpieza.

**Medido: de los 32 archivos que declaran `_allowedRoute()`, los 32 lo llaman. Cero muertos.**

Y una corrección a lo que se dijo antes: los seis controladores públicos **no tienen el hook
muerto — no tienen hook**. Ni lo declaran ni lo llaman, así que son coherentes consigo mismos.
Eso refuerza el diseño de dos traits: nombrar una ruta es universal, guardarla no.

## T20 · CUANDO UNA MEDICIÓN SORPRENDE, SE SOSPECHA PRIMERO DE LA MEDICIÓN

**Cuatro veces en esta campaña el defecto estaba en el INSTRUMENTO, no en lo medido.**

| Instrumento | Qué medía mal | Qué llegó a afirmarse |
| :-- | :-- | :-- |
| `awk` cortando en la primera llave a cuatro espacios | Perdía los cuerpos con bloques anidados | «`routeName` tiene 6 variantes». Por tokens son **10** |
| Contar `/*` contra `*/` en texto plano | Contaba las apariciones dentro de cadenas — `'image/*'` | **32 falsos positivos** en la primera `verify-integrity` |
| La tabla de PHPStan | Recorta la ruta al ancho de terminal | «192 archivos con errores». Son **195**, y Rector no veía 34 |
| `git diff --name-only --ignore-cr-at-eol` | **Lista el archivo aunque el filtro no encuentre diferencias**; el filtro lo aplica `--stat`, no `--name-only` | «58 de 59 archivos del paquete cambian de contenido». Cambian **cero** |
| Una extracción PARCIAL presentada como censo | Trece cuerpos de `_allowedRoute` sacados a mano, de treinta y dos | «doce de trece no deciden nada, solo una decide». **Son quince de treinta y dos** |
| La memoria, sobre lo que hay en git | Nadie miró el historial | «el directorio queda en la historia». **Nunca estuvo en git**: `git log` sobre él no devuelve nada |

### La regla

> **Antes de reportar un hallazgo inesperado, verifícalo con un SEGUNDO MÉTODO
> INDEPENDIENTE.** Una cifra que sorprende es, con más frecuencia de la que parece, una
> herramienta que no mide lo que uno cree.

«Independiente» significa que no comparta mecanismo con el primero: `--stat` contra
`--name-only` sirve porque aplican el filtro en sitios distintos; repetir el mismo comando
con otra bandera cosmética, no.

**Los dos últimos casos son de otra clase y por eso están en la tabla:** no falló una
herramienta, falló **dar por buena una medición ajena**. Una extracción parcial se presentó
como censo y una afirmación sobre el historial de git se escribió sin mirar el historial.

> **Que la medición la haya hecho otro no la exime de verificación.** Al contrario: la
> propia no se sabe de dónde salió, la ajena tampoco — y encima no se puede reconstruir de
> memoria. En los dos casos bastó un comando: contar los treinta y dos cuerpos por tokens, y
> `git log --diff-filter=A` sobre el directorio.

### Por qué importa aquí más que en otro sitio

Una alarma falsa cuesta el doble: se pierde la confianza en la medición **y** en las que
vengan después. En una campaña que se sostiene sobre cifras —877 errores, 464 ramas, 107
`false`, 44 controladores—, un instrumento que miente contamina todo el razonamiento
construido encima. Ya obligó a derogar el «148» y a reconciliar «469 contra 464».

**Corolario**: cada cifra que se publique en un documento debería poder decir con qué se
midió. Ver T8 y T18, donde la unidad y el método están escritos junto al número.

Ese corolario **subió al criterio de cierre**: es el punto 5 de T0. La razón no es la
simetría con la regla de las supresiones, es que **el trinquete no funciona sin él**:
comparar dos baselines solo significa algo si se midieron igual.

## T21 · PEDIR LA DEMOSTRACIÓN NO ES CEREMONIA, ES UN DETECTOR

**El peor defecto de esta campaña no apareció revisando. Apareció al intentar ENSEÑAR.**

Los scripts que normalizaron los cuerpos de `routeName` convirtieron de paso CRLF a LF en
**20 archivos**. El commit declaraba **4.185 inserciones para 41 líneas reales** —
irrevisable, y por eso mismo invisible en una revisión: nadie lee cuatro mil líneas para
comprobar que sobran. Salió cuando el propietario dijo **«enséñame el Paso B»** y hubo que
producir el diff.

### No fue una casualidad. Pasa cada vez

| Lo que se afirmaba | Qué lo tumbó |
| :-- | :-- |
| «El Paso B son 41 líneas» | **Enseñar el diff**: 4.185 inserciones |
| «Con `.gitattributes` ya está arreglado» | **Correrlo de verdad**: `dos2unix` + `git add` seguía preparando 1.181 líneas. Faltaba renormalizar |
| «Con `-X renormalize` no habrá conflictos» | **La fusión de prueba en un clon aislado**: 2 conflictos sin la opción, 0 con ella. El propietario lo exigió con «no lo escribas porque yo lo diga» |
| «La tercera comprobación de integridad detecta un `namespace` perdido» | **Provocar el fallo**: la primera versión derivaba el FQCN del propio archivo y no detectaba nada |
| «Hay hooks `_allowedRoute` muertos» | **Contarlos**: de 32 que lo declaran, los 32 lo llaman |
| «El guardián de eclipses funciona» | **Fabricar una colisión**: la primera sonda tumbó la aplicación entera antes de llegar a la comprobación — que demostró el mecanismo, pero no el guardián |

### La regla

> **Antes de dar algo por hecho, produce el artefacto que lo demostraría** —el diff que
> enseñarías, la corrida que lo prueba, el fallo provocado a mano— **aunque nadie lo haya
> pedido.** Si no puedes producirlo, no está hecho: está supuesto.

Y para los cambios que se prueban con una puerta —una comprobación, un test, un guardián—,
la demostración tiene **dos direcciones y hacen falta las dos**: que **pase** cuando debe
pasar, y que **falle** cuando debe fallar. Una puerta que solo se ha visto en verde no se ha
visto funcionar; solo se ha visto callar.

### Por qué funciona

Revisar comprueba **la afirmación que uno hace**. Demostrar obliga a fabricar el artefacto, y
**el artefacto trae consigo todo lo que la afirmación omitía** — incluido lo que uno no sabía
que estaba omitiendo. Por eso pedir la demostración detecta una clase de fallo que ninguna
revisión alcanza: **la de los errores que uno no sabe que cometió.**

### El corolario que salió de aplicarla hacia atrás

> **PROVOCAR EL FALLO DE UNA PUERTA NO VALIDA LA PUERTA: VALIDA LO QUE HAY DETRÁS.**

El defecto del `encoding="utf8mb4"` —XML exportado que **ningún parser podía leer**— no lo
encontró nadie mirando el exportador. Lo encontró **la comprobación que se añadió para
probar que la puerta no fallaba**: al descubrir que la suite daba 23/23 con un JSON corrupto,
se le añadió validación de sintaxis, y esa validación destapó el XML.

La puerta era el pretexto. **El hallazgo estaba detrás de ella, y llevaba ahí desde siempre.**

### «TOCA TODO» ES UNA HIPÓTESIS, NO UNA MEDICIÓN

H1 —que `bin/cli` devolviera 0 con la aplicación muerta— se reportó como *«no corregido: toca
a la CLI entera»*. **Era una línea**: `die($content)` sale con código cero.

La estimación de coste se dio por buena sin medirla, y una estimación es una afirmación como
cualquier otra.

> **Antes de descartar un arreglo por caro, mide su coste igual que medirías cualquier otra
> cosa que fueras a escribir.** Abrir el archivo y buscar dónde sale el proceso costaba dos
> minutos; el precio de no hacerlo fue dejar en pie un día más la puerta que decía «todo
> bien» sin haber mirado.

Y el patrón se repite: `dynamicConstantNames` iba a ser «una ventana propia» y fue un bloque
de config; el trinquete del baseline iba a ser «infraestructura» y fueron cuarenta líneas.
**Las tres veces el coste real estaba un orden de magnitud por debajo del estimado.**

**Relación con T20**: T20 dice de qué desconfiar cuando un número sorprende. T21 dice qué
hacer cuando **nada** sorprende, que es el caso peligroso.

## T22 · `MetaProperty` — DECIDIDO: el defecto es el FQCN compartido, y se arregla mudando

**Decisión del propietario. Es plan, no está ejecutado.** Nada de código todavía.

### Primero, una corrección: el diagnóstico anterior era impreciso

Se dijo que el paquete emparejaba `EntityMapper` con un `MetaProperty` de sabor `ORM`.
**Es falso, y se comprobó:** `EntityMapper.php` del paquete **no menciona `MetaProperty`
ni una vez** (`grep -c` → 0). Quien la usa es **`ExtensibleORM`**, que `extends ORM`.

Así que **los dos repositorios están internamente bien**:

| Repositorio | Quién usa `MetaProperty` | Contra qué linaje está escrita |
| :-- | :-- | :-- |
| piecesphp | `EntityMapperExtensible` (linaje `EntityMapper`) | `EntityMapper` — delega en `EntityMapper::validateType()` |
| `piecesphp/database` | `ExtensibleORM` (linaje `ORM`) | `ORM` — trae su propia `validateType()` porque `ORM` no ofrece ninguna |

Cada copia está escrita para su base, y su base es la que la usa. **El único defecto es que
las dos ocupan el mismo FQCN.** La palabra «híbrido» sobraba: no hay un Frankenstein de dos
linajes, hay **dos casas correctas discutiéndose un nombre**.

### La decisión

1. **`ORM` se queda: tiene futuro.** `EntityMapperExtensible` **no** va al paquete y **nadie
   migra ningún mapper**.
2. **La copia del framework se queda** ocupando `PiecesPHP\Core\Database\Meta\MetaProperty`.
3. **La del paquete se muda a `PiecesPHP\Core\Database\ORM\Meta\MetaProperty`.** Mismo
   nombre de clase: **lo que estaba mal era la ubicación, no el nombre.** Se actualizan los
   `use` de `ExtensibleORM.php`, `unit-tests/UnitTest-MetaUtil.php` y
   `unit-tests/UnitTest-ActiveRecord.php`.
4. **Desaparecen** la entrada de `KNOWN_ECLIPSES` y la suite `core/meta-property-hybrid`:
   sin colisión, no tienen nada que vigilar ni que probar. La puerta seguirá ahí para la
   siguiente.

### Por qué esto DESBLOQUEA a `ORM` en vez de enterrarlo — demostrado, no supuesto

Hoy `Meta\MetaProperty` resuelve **siempre** a la copia del núcleo. Un mapper nuevo montado
sobre `ExtensibleORM` **dentro de piecesphp** recibiría esa copia. Y no falla de forma
evidente:

```
validateValue(instancia de ExtensibleORM) -> false
setValue LANZÓ: ExtensibleORM::__construct(): Argument #1 ($findValue) must be of type
                ?int, ExtensibleORM given, llamado en .../Meta/MetaProperty.php:207
```

Dos cosas a la vez. La validación de tipo mapper **rechaza el linaje `ORM`** —comprueba
`is_subclass_of($value, EntityMapper::class)` y `ExtensibleORM` no lo es—; y al rechazarlo
entra en la conversión de estilo `EntityMapper`, `new $mapper($value)`, que **revienta con
un `TypeError`** porque la copia del paquete habría hecho ahí
`call_user_func($mapper, 'getInstance', …)`.

> **Mientras compartan nombre, `ORM` no se puede usar dentro de piecesphp.** Mudar el
> archivo no es higiene: es lo único que devuelve `ORM` al terreno de juego.

### Las validaciones NO se homologan

**Descartada la base abstracta que se había sugerido.** Cada una se completa con lo que le
falta y le sirve de la otra, **sin mezclarlas**:

| | Le falta | De dónde sale |
| :-- | :-- | :-- |
| La de `ORM` | `internalName` y `getType()` | De la del framework |
| La del framework | `TYPE_DATE` con `'now'` | De la de `ORM` |

**La validación se queda distinta a propósito**: la del framework delega en
`EntityMapper::validateType()`; la de `ORM` carga la suya **porque `ORM` no ofrece
ninguna**. Homologarlas sería inventar un acoplamiento que hoy no existe.

**Cada archivo lleva una línea diciendo que son paralelos a propósito**, para que el
siguiente que las vea no crea que descubrió una duplicación.

### Pregunta aparcada

**La viabilidad de migrar de `EntityMapper` a `ORM`.** No se responde aquí y no bloquea nada
de lo anterior.

### El principio, que vale para las dos mitades de esta ventana

`routeName()` eran **copias de LA MISMA COSA escrita distinto**: se unifican.
`MetaProperty` son **COSAS DISTINTAS que se parecen**: se separan.

> **SE UNIFICA LO QUE ES LO MISMO; SE SEPARA LO QUE SOLO LO PARECE.**

Y las dos veces **lo distinguió la medición, no la intuición**: en `routeName`, comparar
cuerpos por tokens; en `MetaProperty`, mirar quién usa a quién y contra qué clase base está
escrito cada archivo. La intuición decía «duplicado» en los dos casos y acertó en uno.

## T23 · T21 APLICADO HACIA ATRÁS — todas las puertas con su fallo provocado

**Encargo: «una puerta que solo se ha visto en verde no se ha visto funcionar» no vale solo
para las nuevas.** Se provocó el fallo de cada puerta que llevaba meses en verde, con la
mutación exacta anotada para que se pueda repetir.

### Las puertas que sí gritan

| Puerta | Mutación exacta | Qué hizo |
| :-- | :-- | :-- |
| `verify-integrity` · docblocks **y** firmas | Quitar el `*/` del docblock de `ExifHelper::__construct` | **Reproduce el incidente original**: `php -l` dice *No syntax errors*, `method_exists()` dice **false** —el método desapareció— y la tarea sale **1** avisando por partida doble, `DOCBLOCK` y `FIRMA` |
| `verify-integrity` · rutas PSR-4 | Dos sondas en `src/app/classes/ZzProbe/`: una con `namespace Espacio\Equivocado`, otra `extends \No\Existe\Padre` | Salida **1**: *«declara `Espacio\Equivocado\Broken` y su ruta exige `ZzProbe\Broken`»* y *«Class "No\Existe\Padre" not found»* |
| `verify-integrity` · eclipses, ida | Crear `Core/HTML/Exceptions/MalformedChildException.php`, que ya existe en el paquete `html` | Salida **1**, nombrando el paquete |
| `verify-integrity` · eclipses, vuelta | Añadir a `KNOWN_ECLIPSES` una entrada que no colisiona con nada | Salida **1**: *«la entrada sobrevivió a su motivo»* |
| `bin/phpstan-deadcode` | Cambiar el texto del delimitador del bloque en `bin/phpstan.neon` | Salida **1**: *«No se encontraron los delimitadores»*. **No mide de menos: se niega a medir** |
| `unit-tests:core/mapper-finders` | `NewsMapper::getBy()` devuelve `false` en vez de `null` cuando no encuentra | **19/20**, señalando el mapper |
| `unit-tests:core/session-user` | `SessionToken::getJWTReceived()` devuelve `null` antes de nada | **2 fallos**, los dos del contrato de cadena vacía |
| `unit-tests:core/otp-write-separation` | Meter `if (false) { $probe->save(); }` dentro de `getOTPData()` | **5/6**. Caza una escritura **inalcanzable**, que es lo correcto: ahí no puede haber una escritura ni escrita |
| `unit-tests:core/meta-property-hybrid` | Quitar el bautizo de `EntityMapperExtensible::addMetaProperty()` | **10/12** |

### Los cinco hallazgos

**H1 · `bin/cli` devolvía 0 aunque la aplicación muriera al arrancar — CORREGIDO, y era lo
más grave del lote.**

Con un `ParseError` en un archivo de `src/app`, la CLI escupía el JSON del manejador de
excepciones **y salía con código 0**; `verify-integrity` no llegaba a ejecutarse. **Cada vez
que en esta campaña se dijo «verify-integrity en verde» se apoyaba en un código de salida que
no distinguía «pasé» de «no me ejecuté».**

**No hacía falta rediseñar la CLI: era UNA LÍNEA.** `global_custom_exception_handler()`
terminaba en `die($content)`, y **`die()` con un argumento de tipo cadena imprime y sale con
código CERO**. Comprobado antes de tocar nada:

```bash
php -r 'die("hola\n");' ; echo $?     # -> 0
php -r 'echo "hola\n"; exit(1);'; echo $?   # -> 1
```

Ahora imprime y hace `exit(PHP_SAPI === 'cli' ? 1 : 0)`. **En HTTP no cambia nada**: ahí el
código de salida no lo lee nadie y quien manda es el 500 que ya se envió.

**Provocado y demostrado**, inyectando un error de sintaxis real en un archivo que la
aplicación carga al arrancar:

| Comando | Antes | Ahora |
| :-- | --: | --: |
| `bin/cli verify-integrity` con el árbol roto | **0** | **1** |
| `bin/cli unit-tests:core/session-user` con el árbol roto | **0** | **1** |
| Los mismos con el árbol sano | 0 | **0** |

**Lo que NO cubre, para que nadie confíe de más**: un fatal incatchable de verdad —agotar la
memoria, por ejemplo— no pasa por este manejador. Ahí PHP sale con su propio código, que
tampoco es cero, así que la puerta tampoco miente. **No hay `register_shutdown_function` en
todo el proyecto**: si algún día quiere cubrirse ese hueco con un mensaje propio, ese es el
sitio.

**H2 · `unit-tests:functions/systemOutFormatted` fallaba 7 de 10 y decía que todo iba bien.**
No hizo falta mutarla: **ya estaba roja**. `systemOutFormatted()` tiene **dos contratos** —con
terminal emite ANSI, sin terminal los suprime a propósito (`Cli.php`, rama `$isTty`)— y la
suite afirmaba el primero siempre, así que fallaba en cuanto la salida se redirigía. Y como
no contaba nada, **devolvía éxito igual**.

Comprobado con un segundo método, como manda T20: bajo pseudo-terminal
(`script -qec … /dev/null`) pasa **10/10**; redirigida, **7 fallos**. *Corregido*: las siete
comprobaciones que exigen ANSI se **omiten con su razón** sin terminal, y la suite tiene
balance y resultado real. Probada después: mutar `'red' => 32` en `Cli.php` la deja en
**8/10**.

**H3 · El trinquete del baseline NO EXISTÍA.** `CLAUDE.md` mandaba comparar contra
`PHPStanResult.Summary.baseline.txt` y **nada lo comparaba**: era una instrucción para un
humano, no una puerta. No podía fallar porque no se ejecutaba nunca.

*Corregido*: `bin/phpstan-process-result.php` compara al final, **instancias contra
instancias**, y sale con **1** si el total sube. Probado en las dos direcciones —bajar el
baseline a 800 da *TRINQUETE ROTO (+77)*; romper su cabecera da *«la comparación NO se
hizo»*—. Esa segunda dirección es la que importa: **una puerta que no puede medir no puede
aprobar.**

El baseline pasa de **968 a 877**, con su nota de método dentro del propio archivo.

**H4 · `unit-tests:core/database-exporter` no miraba la sintaxis.** Comprobaba el
**contenido** —filtro `WHERE`, enmascarado GDPR, `include_data`, hex-blob— y **jamás si el
archivo estaba bien formado**. Añadiendo `"ROTO"` a cada fila de `JsonFormat`, el JSON
generado quedó ilegible —799 marcas, `json_decode` → *Syntax error*— y la suite dio
**23/23**.

*Corregido*: valida JSON con `json_decode()` y XML con `simplexml_load_string()`. Probado:
con la mutación puesta, **21/23**.

**H5 · Y esa comprobación nueva destapó un defecto real de producción: el XML exportado no
lo puede leer ningún parser.** Dos defectos encadenados:

- **H5a — `encoding="utf8mb4"`.** El plugin metía el charset de MySQL en la declaración XML.
  `utf8mb4` **no es un encoding XML**, así que un parser estándar rechaza el documento
  **entero** por la primera línea. Confirmado por dos parsers independientes —`libxml` y
  `xmllint`—. **Corregido**: `XmlFormat` traduce los nombres de MySQL a los nombres IANA.
- **H5b — bytes de control crudos. CORREGIDO.** Con la cabecera ya arreglada, el XML seguía
  siendo inválido: *«PCDATA invalid Char value 26»*, *«Char 0x0 out of allowed range»*, por
  una columna BLOB escrita tal cual.

**La detección era incorrecta de raíz.** El plugin preguntaba
`!mb_check_encoding($val, 'UTF-8')` para decidir si un valor era binario, y eso **no responde
esa pregunta**: los bytes de un PNG son UTF-8 perfectamente válido, así que la rama de
hexadecimal no se activaba nunca.

> **Lo que decide que una columna es binaria es SU TIPO, y está en los metadatos del
> esquema.** `SqlFormat` ya lo hacía —`SHOW COLUMNS` más un `preg_match` sobre
> `binary|blob|varbinary`—; `XmlFormat` no. Ahora sí, con el mismo criterio.

Y por debajo, **una red que no depende de `hex_blob`**: si un valor conserva algún carácter
que XML 1.0 prohíbe —los controles `0x00–0x08`, `0x0B`, `0x0C`, `0x0E–0x1F`, que
`htmlspecialchars()` no toca porque no son caracteres *especiales* sino *prohibidos*—, ese
valor también sale en hexadecimal. Una columna de texto con basura dentro no puede tumbar el
documento entero.

**Verificado con dos parsers independientes**: `xmllint --noout` y `simplexml_load_file()`
dan válido, y el BLOB aparece como `0x3f504e47…`. La suite vuelve a **23/23**.

**No se dejó en rojo a propósito, y la razón para no tocarlo no se sostenía**: el dato
exportado hoy **no se puede leer**. No se rompía algo que funcionaba; se arreglaba algo roto.

### HALLAZGO NUEVO, ABIERTO: se pierde un byte entre escribir y leer el BLOB

Al comprobar el hexadecimal apareció otra cosa. La semilla inserta un PNG real:

```php
$binaryData = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01";
```

y **las dos** exportaciones —SQL y XML, que leen con el mismo `SELECT *`— devuelven
`0x3f504e470d0a1a0a…`. El primer byte es **`3f`, que es `?`**: el `\x89` no sobrevivió al
viaje.

**No es un defecto del exportador**: los dos formatos coinciden, así que el byte ya venía
perdido. Apunta a la capa de base de datos —el `charset` de la conexión aplicándose a un
parámetro que debería viajar como binario—.

> **Ventana propia.** Afecta a cualquier dato binario del framework, no solo al exportador, y
> comprobarlo bien exige leer de vuelta lo insertado y comparar byte a byte. **No se toca sin
> decidirlo**: si el defecto está en la escritura, los datos ya guardados en los despliegues
> están dañados y eso cambia por completo qué significa «arreglarlo».

### Lo que enseña el ejercicio

De doce puertas, **nueve funcionaban**, **una no existía** (H3), **una llevaba meses roja
diciendo que iba bien** (H2) y **una tenía un agujero por el que se coló un defecto de
producción** (H4 → H5). **Una de cada cuatro.**

Y el orden importa: **H5 no lo encontró nadie mirando el exportador. Lo encontró la
comprobación que se añadió al probar que la puerta no fallaba.** Provocar el fallo de una
puerta no valida la puerta: valida lo que hay detrás.

## T24 · MAPA DE LAS 373 RAMAS — **DEROGADO por T29**

> Se hizo sobre 373 y antes de cerrar el punto del trait. **El mapa vigente es T29**, sobre
> las 309 que quedan. Esto se conserva por la parte que sigue valiendo: el dato de que 89
> ramas vivían dentro de los tres métodos de ruta, que fue lo que decidió el orden.

**El «235 borrables» está derogado** (ver T13). Este es el mapa sobre la medición nueva.

### El instrumento, columna por columna

| Columna | Con qué se midió |
| :-- | :-- |
| **Ramas** | `bin/phpstan-deadcode`: resta de la corrida con el bloque de ignores y sin él. **Unidad: tripletas** (ruta, línea, mensaje) |
| **Zona** | La RUTA del archivo: `/view/` o `/Views/` → vistas; `*Mapper.php` → mappers; `local-tests/` → suites; `src/app/config/` → config; `src/app/core/` → núcleo; `*Controller.php` → controladores; el resto, otros |
| **Dentro del andamio** | Rango de líneas de `routeName`, `allowedRoute` y `_allowedRoute` calculado **por tokens**, no por sangría |

### EL DATO QUE MANDA EN EL ORDEN

> **89 de las 373 ramas —el 24 %— viven DENTRO de `routeName`, `allowedRoute` o
> `_allowedRoute`.** Es decir, **dentro del andamio que el punto 2 va a borrar**.

| Identificador | Ramas dentro del andamio |
| :-- | --: |
| `function.alreadyNarrowedType` | 44 |
| `isset.variable` | 44 |
| `if.alwaysTrue` | 1 |

De los 50 `isset.variable`, **44 son el `isset($_POST)` / `isset($_GET)` del closure
`$getParam`** que casi todos los `_allowedRoute` copian y que casi ninguno usa.

**Conclusión de orden: el mapa se rehace DESPUÉS del punto 2, no antes.** Un cuarto del
trabajo desaparece solo, y medirlo ahora es medir algo que va a dejar de existir.

### Reparto por familia — el mapa, para cuando toque

| Ramas | Identificador | Zona dominante | Veredicto de la muestra verificada a mano |
| --: | :-- | :-- | :-- |
| **129** | `function.alreadyNarrowedType` | controladores 58, núcleo 36 | 44 mueren con el andamio. Del resto, `is_array()` sobre `array`: resto defensivo puro |
| **50** | `isset.variable` | controladores 45 | **44 mueren con el andamio.** Quedan 6 |
| **42** | `notIdentical.alwaysTrue` | controladores 35 | `$userMapper !== null` sobre un valor ya estrechado. Borrable **con cuidado**: es una condición viva, no un hueco |
| **24** | `foreach.emptyArray` | mappers 24 | **Hueco de plantilla puro.** `$defaultPropertiesValues = []` seguido de su `foreach`. Se borran el array y el bucle |
| **21** | `if.alwaysTrue` | otros 9, controladores 6 | Mezclado. Sin veredicto de familia |
| **20** | `if.alwaysFalse` | otros 12, controladores 7 | **10 de 20 son el `$showSQL` de los `<Modulo>Routes`: NO SE TOCAN** |
| **13** | `function.impossibleType` | — | Sin verificar |
| **12** | `ternary.alwaysTrue` | — | Sin verificar |
| **11** | `nullCoalesce.offset` | — | Sin verificar |
| **10** | `booleanNot.alwaysTrue` | — | Sin verificar |
| **5** | `catch.neverThrown` | — | **NO SE BORRAN sin comprobar el manejador de errores.** Ver abajo |
| **4** | `deadCode.unreachable` | `SystemApprovalManager` (3) | Sin verificar |
| **32** | *(las 14 familias restantes, ≤3 cada una)* | — | Sin verificar |

### Las dos familias que parecen mecánicas y NO lo son

**`if.alwaysFalse` — la mitad es una herramienta documentada.** `$showSQL = false;` seguido
de `if ($showSQL)` es el bloque que la **regla 7 de `CLAUDE.md`** manda usar para sacar el
DDL de un módulo. Borrarlo no limpia: **quita la forma sancionada de generar el SQL de las
tablas**. Diez de las veinte.

**`catch.neverThrown` — «nunca se lanza» según PHPStan NO es «nunca se lanza» aquí.**
`AppHelpers.php:2600` envuelve un `return $roles[$e];` en un `try/catch (\Throwable)`. Un
índice inexistente emite un **`E_WARNING`, no una excepción**, así que PHPStan tiene razón
mirando solo el lenguaje. Pero **el manejador de `bootstrap.php` promueve `E_WARNING` a
excepción**, así que en ejecución ese `catch` **sí es alcanzable**.

> **Es el caso más peligroso de todo el lote**: un análisis correcto y una conclusión falsa,
> porque la herramienta no ve la configuración del manejador de errores. Los cinco se miran
> uno a uno, y ninguno se borra sin comprobar qué puede emitir el bloque `try`.

### Regla de trabajo para esta ventana

1. **Primero el punto 2.** Se lleva 89 ramas por delante.
2. **Volver a medir** con `bin/phpstan-deadcode`.
3. **Familia por familia**, y cada una con su muestra verificada a mano **antes** de tocarla —
   nunca la familia entera de golpe por parecerse a otra que sí era andamio.
4. Y sigue valiendo T17: **una regla mecánica se aplica a toda la familia o a ninguna.**

## T25 · UTILIDADES CLONADAS — medido, NO ejecutado

**La misma enfermedad que `routeName`, a mayor escala.** Solo medición: la ejecución va en su
propia ventana.

### Identidad POR CONTENIDO, no por número de líneas

Que coincida el conteo de líneas no es que coincida el archivo (T20). Se comparan **tokens**,
descartando comentarios y espacios, y **neutralizando el `namespace`** —que es lo único que
TIENE que diferir—:

| Utilidad | Archivos | Contenidos DISTINTOS | Reparto |
| :-- | --: | --: | :-- |
| `SafeException` | **17** | **2** | 16 idénticas + 1 distinta (`PiecesPHP\UserSystem`) |
| `DuplicateException` | **13** | **2** | 12 idénticas + 1 distinta (`Publications`) |
| `FieldTranslationUtility` | **3** | **3** | las tres distintas |
| `AttachmentPackage` | **2** | **2** | las dos distintas |
| **TOTAL** | **35** | | ~1.750 líneas |

Comando: `token_get_all()` por archivo, fuera `T_COMMENT`/`T_DOC_COMMENT`/`T_WHITESPACE`, el
`namespace` sustituido por un marcador, y `md5` del resultado.

### Las dos raras SÍ añaden algo real, y deciden el diseño

| Archivo | Qué añade |
| :-- | :-- |
| `PiecesPHP\UserSystem\Exceptions\SafeException` | `const USER_NOT_EXISTS = 1;` y su entrada en `CODES` |
| `Publications\Exceptions\DuplicateException` | `const PUBLICATION_CODE = 1;`, `const CATEGORY_CODE = 2;` y sus entradas en `CODES` |

**Lo que hacen esos códigos**: el constructor valida `in_array($code, self::CODES)` y, si no
está, lo degrada a `UNDEFINED_CODE`. Es decir, **la lista de códigos es el contrato de la
excepción**, no decoración.

> **TRAMPA DE DISEÑO, y es silenciosa.** El constructor usa **`self::CODES`**. Hoy funciona
> porque cada módulo tiene su propia clase. En una clase centralizada con subclases por
> módulo, `self::` seguiría resolviendo a la lista del PADRE, así que **el código específico
> del módulo se degradaría a `UNDEFINED_CODE` sin un solo error**. La clase centralizada
> **tiene que usar `static::CODES`** —enlace estático tardío—, y eso tiene que ir probado.

### `FieldTranslationUtility`: el tipo del mapper ES la única variación — comprobado

Los tres archivos difieren **solo** en: `namespace`, el `use` del mapper, la línea `@package`
del docblock, y el tipo del mapper en **tres** sitios (`@var`, el constructor y `mapper()`).
Nada más.

Y **los tres mappers extienden `EntityMapperExtensible`**:

| Mapper | Padre |
| :-- | :-- |
| `PublicationMapper` | `EntityMapperExtensible` |
| `ApplicationCallsMapper` | `EntityMapperExtensible` |
| `InterestResearchAreasMapper` | `EntityMapperExtensible` |

> **La abstracción es tipar por ahí y la clase se vuelve UNA.** Sin genéricos, sin
> configuración: cambiar tres firmas de `XMapper` a `EntityMapperExtensible`.

`AttachmentPackage` es el mismo caso con un grado más de trabajo: además del tipo del mapper
de adjuntos, **renombra una propiedad** (`$publicationID` contra `$applicationCallID`).

### EL RIESGO QUE HACE ESTO DELICADO PESE A PARECER TRIVIAL

**Cada módulo captura SU clase por FQCN**, resuelto por el `use` de cabecera. Medido:

| | |
| :-- | --: |
| Sitios que **capturan** `SafeException` | **35** (22 sueltos, 12 en `SafeException \| DuplicateException`, 1 con otro nombre de variable) |
| Sitios que **capturan** `DuplicateException` | **12** (todos dentro de esa unión) |
| `throw new SafeException` | **33** |
| `throw new DuplicateException` | **15** |
| Archivos que las importan con `use` | **25** |

> **Si se unifican y queda un `use` viejo sin actualizar, PHP NO FALLA.** El `catch`
> simplemente deja de capturar y la excepción sigue subiendo, en silencio, hasta el
> manejador global. Un 500 en vez de un mensaje de validación, y ni un aviso que lo explique.

**La puerta que cualquier plan tiene que incluir**: *toda clase nombrada en un `catch` debe
resolver a una clase existente*. Y **no la cubre nada de lo que hay hoy**: la tercera
comprobación de `verify-integrity` valida que las clases DECLARADAS se carguen, y su propio
docblock ya avisa de que **un `use` que falta y solo se usa dentro de un método se le
escapa** — que es exactamente este caso.

### Lo que NO cura borrar los módulos condenados

Borrar `ApplicationCalls`, `ImagesRepository` e `InterestResearchAreas` se lleva unas cuantas
copias **y no cura nada**: el próximo módulo clonado desde `Publications` las trae otra vez.
**La enfermedad es la plantilla, no las copias.**

## T26 · DOS PREGUNTAS DE SEGURIDAD ABIERTAS SOBRE EL ENRUTADO

**Las dos son PREEXISTENTES y las dos viven ahora en `ControllerRoutingTrait`, o sea en los
44 controladores a la vez.** No se tocan sin decisión explícita: **cambiar cualquiera de las
dos cambia quién ve qué en toda la aplicación**.

Que estén escritas aquí es el punto. Antes estaban repartidas en 44 copias y nadie las había
mirado juntas.

### 1 · Generar una URL EJECUTA la comprobación de permiso

`routeName()` llama a `Roles::hasPermissions()` y a `_allowedRoute()` **cada vez que se
construye una cadena**. Hay **606 sitios de llamada** en el código.

Es lo que hace que `allowedRoute()` funcione —una ruta no permitida devuelve cadena vacía, y
de ahí sale la visibilidad de menús y botones—, así que **no es un accidente**. Pero merece
revisarse algún día: `_allowedRoute()` de los módulos con reglas de propiedad **consulta la
base de datos** (`getBy($id)`, y en varios además `UsersModel::getBy` y
`OrganizationMapper::getBy`). Pintar un listado con veinte botones de editar puede disparar
sesenta consultas para decidir si se pintan.

### 2 · SIN USUARIO, CONCEDE

El cuerpo canónico dice:

```php
$current_user = getLoggedFrameworkUser();

if ($current_user !== null) {
    $allowed = Roles::hasPermissions($name, $current_user->type);
} else {
    $allowed = true;          // <-- aquí
}
```

**Anónimo ⇒ permitido.** Para la zona pública tiene sentido: sus rutas no tienen restricción
de rol y el control real está en el middleware de sesión, no aquí.

**Lo que lo vuelve una pregunta y no una nota** es cómo se obtiene ese `null`:

> `getLoggedFrameworkUser()` devuelve `null` **también cuando el constructor de
> `UserDataPackage` lanza** —`AppHelpers.php:3047-3053`: el `catch` deja `$currentUser` en
> `null` y registra la excepción con `log_exception()`, así que **queda rastro en el log,
> pero quien llama no distingue ese caso de «no hay sesión»**—. Es decir: un fallo al
> construir al usuario se trata como «no hay usuario», y **«no hay usuario» concede**.

No es una vulnerabilidad demostrada: para explotarla habría que provocar ese fallo, y aun
concediendo el nombre de la ruta queda el control de acceso de la petición real. Pero **la
dirección del fallo es la equivocada**: ante un error, un sistema de permisos debería cerrar,
no abrir.

**Una buena noticia primero, que conviene decir**: **antes del trait esto estaba en 44 sitios
y ahora está en uno.** El trait no creó el problema — lo volvió **arreglable**. Corregirlo era
antes una campaña de 44 archivos; hoy es una línea en un sitio.

#### PRECONDICIÓN: no se arregla cambiando el `else` a `false`

**Hoy `getLoggedFrameworkUser()` NO PUEDE distinguir los dos casos.** Devuelve `null` tanto
si no hay sesión como si el constructor lanzó, así que cerrar sin distinguir **negaría el
acceso a los anónimos en las rutas públicas** — y eso hoy es correcto: la zona pública no
tiene restricción de rol y su control real está en el middleware de sesión.

> **Primero hay que hacer los dos casos DISTINGUIBLES**, y solo entonces se puede cerrar el
> borde sin romper lo público. Dos formas, las dos aceptables:
>
> - **Un accesor aparte** que diga si hubo fallo de construcción —o que devuelva el estado en
>   vez de solo el usuario—.
> - **Que el fallo de construcción se propague** en vez de convertirse en `null`, y que quien
>   quiera tolerarlo lo capture explícitamente.
>
> **Sin esa precondición, la pregunta no se puede responder**: cualquier respuesta rompe uno
> de los dos casos.

Relacionado: `DataImportExportUtility` conserva su `routeName()` propio precisamente porque
toma el usuario por otra vía y **no cae en este borde** — es más restrictivo. Está en
`KNOWN_ROUTE_OVERRIDES` con esa razón.

### Opción futura, NO implementar: una interfaz que haga obligatorio el contrato

**Sola no sirve.** Una interfaz no lleva implementación por defecto, así que obligaría a cada
controlador a escribir su copia de los tres métodos y **volveríamos al andamio, ahora
obligatorio**. Además `_allowedRoute()` es `private static` y las interfaces solo declaran
miembros públicos, y `BaseController` lo extienden también controladores que no son de rutas.

**La forma correcta es interfaz MÁS trait**: la interfaz declara el contrato público
—`routeName()` y `allowedRoute()`—, el trait lo implementa, y el controlador declara las dos
cosas. Se añade encima de lo que hay **sin conflicto y sin tocar ningún cuerpo**.

## T27 · `catch.neverThrown` y `if.alwaysFalse` — dos familias que NO eran familias

**El encargo era buscar la expresión, no arreglar cinco casos.** La conclusión fue mejor y
peor que eso: en configuración **no se puede expresar**, pero **tampoco hacía falta**, porque
los cinco no eran el mismo caso.

### La configuración: probada y descartada, con el comando

PHPStan 2.2.8 ofrece, bajo `parameters.exceptions`:

```
implicitThrows: true
reportUncheckedExceptionDeadCatch: true
uncheckedExceptionClasses / uncheckedExceptionRegexes
checkedExceptionClasses / checkedExceptionRegexes
```

Se probó `reportUncheckedExceptionDeadCatch: false` y **no mueve ni uno de los cinco**.

> **Y la razón explica por qué ninguna opción va a servir:** el desajuste no es entre
> excepciones comprobadas y no comprobadas. Es que **PHPStan modela el LENGUAJE**, y aquí
> manda además el **manejador de errores**. Ninguna opción de un analizador estático puede
> saber que `bootstrap.php` convierte un `E_WARNING` en excepción.

### Los cinco, mirados uno a uno: TRES eran muertos de verdad

| Caso | Qué había en el `try` | Veredicto |
| :-- | :-- | :-- |
| `BundleTask.php:205` | Una asignación de cadena | **Muerto. Borrado** |
| `api-keys.php:32` | El cuerpo **entero comentado** | **Muerto. Borrado** |
| `CustomSlimErrorHandler.php:79` | `$exception->getCode()` | **Muerto. Borrado** |
| `LoginAttemptsModel.php:122` | `$mapper->extra_data = …` | **VIVO** |
| `AppHelpers.php:2600` | `return $roles[$e];` | **VIVO** |

**Y los dos vivos lo son por razones DISTINTAS**, lo cual es el hallazgo:

1. **`LoginAttemptsModel`** — la asignación pasa por **`__set()`**, que lanza
   `DatabaseClassesExceptions` (`EntityMapper.php:529-534`). PHPStan no ve a través de los
   métodos mágicos. **No tiene nada que ver con el manejador de errores.**
2. **`AppHelpers`** — `$roles[$e]` sobre una clave inexistente emite un **`E_WARNING`**, y el
   manejador lo **promueve a excepción**. Este sí es el desajuste del manejador.

> **«Los cinco casos de una familia» eran tres muertos y dos vivos por dos motivos que no se
> parecen.** Suprimir la familia entera —que es lo que había— tapaba las tres oportunidades
> de limpieza reales **y** los dos avisos genuinos, con el mismo silencio.

**Resultado**: el `- identifier: catch.neverThrown` de familia entera desaparece del bloque
de código muerto y se convierte en **una supresión por ruta, para dos archivos, con las dos
razones escritas**. La próxima que aparezca tendrá que mirarse en vez de heredar el silencio.

### `if.alwaysFalse`: 11 son interruptores y 9 son candidatos

Medido **por ruta**, no por contexto de texto —la primera cuenta dijo «10 de 20» con un grep
de tres líneas; el conteo por ruta da **11 de 20**—:

| Cuántos | Dónde | Qué son |
| --: | :-- | :-- |
| **10** | `*Routes.php` | El bloque `$showSQL = false; … if ($showSQL)` que **la regla 7 de `CLAUDE.md` manda usar** para sacar el DDL |
| **1** | `DataImportExportUtilityRoutes.php:42` | `const ENABLE = false;` **literal**: el módulo está apagado en árbol |
| **9** | Controladores y núcleo | Candidatos de verdad, quedan en el mapa de T24 |

**Los 11 pasan a supresión PERMANENTE por ruta, sin condición de retirada.** No es deuda:
borrarlos quitaría la forma sancionada de generar el SQL de las tablas.

**Los 9 quedan en supresión TEMPORAL**, dentro del bloque de código muerto y por tanto
todavía contados como ramas muertas, con su condición de retirada escrita: cuando se hayan
mirado uno a uno.

### EL TRINQUETE SE DISPARÓ, Y ERA CONTRA MÍ

Al sacar `if.alwaysFalse` del bloque de familia, los 9 de fuera de `*Routes.php` quedaron sin
silenciar y el total subió:

```
TRINQUETE ROTO: 886 errores contra un baseline de 877 (+9).
```

**La puerta que se construyó hace unas horas atrapó una regresión propia el mismo día**, y en
el paso siguiente al que la creó. Es la mejor demostración de por qué H3 —que no existiera—
era grave: sin ella, esos nueve errores se habrían colado en el baseline sin que nadie los
notase.

### LA REGLA QUE FALTABA: comprobar la HOMOGENEIDAD antes de suprimir una familia

Es tentador leer esto como «prefiere mirar caso a caso antes que configurar». **No es eso**,
y la prueba está a dos secciones de distancia:

| Familia | ¿Homogénea? | Qué funcionó |
| :-- | :-- | :-- |
| Los 25 interruptores de módulo (T13) | **SÍ**: todos constantes de `config/constants.php`, todos por la misma razón | Un bloque de configuración, `dynamicConstantNames` |
| Los 5 `catch.neverThrown` | **NO**: tres muertos, uno por `__set()`, uno por el manejador de errores | Mirarlos uno a uno |
| Los 20 `if.alwaysFalse` | **NO**: 11 interruptores documentados, 9 candidatos | Partirla en dos |

> **Antes de suprimir una familia entera, comprueba que sus miembros están ahí POR LA MISMA
> RAZÓN.** Si las razones difieren, la supresión de familia no simplifica: **tapa con el
> mismo silencio los casos que había que arreglar y los que había que respetar.**

Es la otra cara de T17. T17 dice que una regla mecánica se aplica a toda la familia o a
ninguna; esta dice **qué es una familia**: no los que comparten identificador, sino los que
comparten motivo.

### Efecto medido

| | Antes | Después |
| :-- | --: | --: |
| Baseline visible (instancias) | 877 | **877** |
| Ramas muertas (tripletas) | 325 | **309** |

De las 16 que salen: **3 borradas** de verdad y **13 reclasificadas** —2 `catch.neverThrown`
y 11 `if.alwaysFalse`— que **nunca fueron ramas muertas** y ahora están donde debían, en el
registro documentado.

## T28 · EL BYTE DEL PNG — respondido: **el daño está en la ESCRITURA**

**Todo lo de aquí es de SOLO LECTURA.** Ninguna consulta tocó una tabla para escribir, y la
segunda sonda no tocó ninguna tabla en absoluto.

### La prueba que separaba los dos escenarios

`HEX()` se resuelve en el servidor y no pasa por la conversión del cliente:

```sql
SELECT HEX(avatar_blob) AS serverHex, avatar_blob AS clientValue
FROM pcs_unit_tests_core_database_exporter_v1 LIMIT 1
```

| | |
| :-- | :-- |
| **Servidor** (`HEX()`) | `3f504e470d0a1a0a…` |
| **Cliente** (`bin2hex()` en PHP) | `3f504e470d0a1a0a…` |
| Longitud | **20 bytes en los dos**, igual que el original |

> **El dato guardado YA ESTÁ DAÑADO.** No es un defecto del camino de lectura: el `0x89` no
> llegó nunca a la tabla. La longitud se conserva y solo cambia un byte — la firma clásica
> de una conversión de juego de caracteres, no de un truncado.

Y la columna está bien declarada: `avatar_blob` es **`blob`**, tipo binario.

### El mecanismo, demostrado SIN TOCAR NINGUNA TABLA

Una ida y vuelta del literal, con `SELECT HEX(?)` sobre ninguna tabla:

| Cómo se envía el parámetro | Qué vuelve |
| :-- | :-- |
| Original en PHP | `89504e470d0a1a0a0000000d4948445200000001` |
| **`PARAM_STR`** *(el de por defecto)* | **`3f`**`504e470d0a1a0a0000000d4948445200000001` |
| `PARAM_LOB` | `89504e47…` **intacto** |
| `SET NAMES binary` | `89504e47…` **intacto** |

**`PDO::ATTR_EMULATE_PREPARES` está en `true`.** Con emulación, PDO **interpola el parámetro
en la cadena SQL dentro de PHP** y lo entrecomilla según el juego de caracteres que PDO cree
que tiene la conexión.

### La causa raíz, y está en el PAQUETE

`Database::instance()` acepta un `$charset` y **NUNCA lo pone en el DSN**:

```php
$dsn = "{$driver}:dbname={$database};host={$host}";   // <-- sin charset
…
$instance->prepare("SET character set {$charset}; …");
```

Dos consecuencias, las dos medidas:

1. **PDO no se entera.** El `charset` del DSN es lo único que fija cómo entrecomilla PDO al
   emular. Ejecutar `SET character set` después cambia el servidor, **no a PDO**.
2. **`SET character set` deja la conexión descuadrada.** Pone `character_set_client` y
   `character_set_results` al valor pedido, pero `character_set_connection` **al de la base
   de datos**. Medido en esta instalación:

   ```
   character_set_client     = utf8mb4
   character_set_connection = utf8mb3   <-- el de la base, no el pedido
   character_set_results    = utf8mb4
   ```

### CUÁNTO ALCANZA — la parte que decide qué contarle a los despliegues

**El ORM del framework NO PUEDE crear una columna binaria.** Los tipos que `SchemeCreator`
admite son `varchar, text, mediumtext, longtext, int, bigint, float, double, json, datetime,
date` — **no hay `blob` ni `binary`**. Y **cero mappers** declaran un campo binario:
`grep -rlniE "'(blob|longblob|mediumblob|varbinary)'" src/app/classes src/app/model` da **0**.

| | |
| :-- | :-- |
| Datos del framework en riesgo | **Ninguno por la vía del ORM**: no existe el tipo |
| Quién sí está en riesgo | Un despliegue que haya añadido una columna binaria **a mano** y escriba en ella con `prepare()/execute()` |
| El dato dañado que se encontró | La semilla de la suite del exportador, **tabla de pruebas que se regenera en cada corrida** |

> **No se corrige aquí, y no por prudencia sino porque la decisión tiene dos ramas.** Poner
> `charset=` en el DSN cambia el entrecomillado de TODAS las escrituras de TODOS los
> despliegues, y es un paquete que consumen cinco repositorios. La alternativa acotada
> —`bindValue(..., PARAM_LOB)` donde haya binario— **no tiene sitio donde aplicarse hoy**,
> porque el framework no escribe binario.

### Lo que hay que decidir

1. **¿Se pone `charset=` en el DSN de `Database::instance()`?** Es la corrección de fondo y
   deja la conexión coherente. Riesgo: cambia el entrecomillado en todos los despliegues.
2. **¿Se desactiva `ATTR_EMULATE_PREPARES`?** Corrige la clase entera de problema —los
   preparados de verdad no interpolan— pero cambia el comportamiento de todas las consultas.
3. **¿O solo se documenta**, dado que el ORM no ofrece columnas binarias, y se añade el tipo
   `blob` a `SchemeCreator` el día que alguien lo necesite, ya con el binding correcto?

**Ninguna es obvia y las tres son del propietario.** Lo que sí está cerrado es el
diagnóstico: **el daño ocurre al escribir, la causa es la emulación de preparados sobre una
conexión cuyo charset PDO desconoce, y el alcance real es cero por la vía del ORM.**

## T29 · MAPA DE LAS 309 RAMAS — el de T24 queda derogado, y NO SE BORRA NADA

T24 se hizo sobre 373 y **antes** de cerrar el punto del trait. Este es sobre las **309** que
quedan. Sigue sin borrarse nada.

### El instrumento, columna por columna

| Columna | Con qué se midió |
| :-- | :-- |
| **Ramas** | `bin/phpstan-deadcode`. **Unidad: tripletas** (ruta, línea, mensaje) |
| **Zona** | La RUTA del archivo: `/view/` o `/Views/` → vistas; `*Mapper.php` → mappers; `local-tests/` → suites; `src/app/config/` → config; `src/app/core/` → núcleo; `*Controller.php` → controladores; el resto, otros |
| **Veredicto** | **Lectura a mano** de una muestra de cada familia. Donde no la hay, dice «sin verificar» |

### Por qué bajó de 373 a 309

| | |
| :-- | --: |
| Punto del trait: 35 métodos borrados | **−48** |
| `catch.neverThrown`: 3 borrados y 2 reclasificados | **−5** |
| `if.alwaysFalse`: 11 interruptores reclasificados | **−11** |

**De las 64, solo 38 eran borrado real. Las otras 26 nunca fueron ramas muertas.**

### El mapa

| Ramas | Identificador | Zona dominante | Veredicto de la muestra verificada |
| --: | :-- | :-- | :-- |
| **105** | `function.alreadyNarrowedType` | núcleo 36, controladores 34 | `is_array()` sobre un `array`. **Resto defensivo puro**, borrable por familia |
| **42** | `notIdentical.alwaysTrue` | controladores 35 | `$userMapper !== null` sobre un valor ya estrechado. Borrable **con cuidado**: es una condición viva, no un hueco |
| **26** | `isset.variable` | controladores 21 | `isset($_POST)` dentro del closure `$getParam` de los `_allowedRoute` **que sobreviven**. Sobra el `isset`, **no el closure**: 9 de los 10 que lo declaran lo usan |
| **24** | `foreach.emptyArray` | mappers 24 | **Hueco de plantilla puro**: `$defaultPropertiesValues = []` seguido de su `foreach`. Se borran el array y el bucle |
| **21** | `if.alwaysTrue` | otros 9, controladores 6 | Sin verificar |
| **13** | `function.impossibleType` | núcleo 8 | `is_scalar($x) && !is_null($x)` — guarda redundante. Resto defensivo |
| **12** | `ternary.alwaysTrue` | **vistas 10** | **NO SE TOCA. Ver abajo** |
| **11** | `nullCoalesce.offset` | config 9 | `$array['clave'] ?? null` sobre un array literal donde la clave existe. Defensivo, borrable |
| **10** | `booleanNot.alwaysTrue` | controladores 7 | **6 de 10 son un INTERRUPTOR. Ver abajo** |
| **9** | `if.alwaysFalse` | controladores 7 | Los que quedaron fuera de los `<Modulo>Routes`. Sin verificar |
| **7** | `booleanAnd.leftAlwaysTrue` | mappers 4 | Sin verificar |
| **5** | `booleanAnd.rightAlwaysFalse` | vistas 2 | Sin verificar |
| **4** | `deadCode.unreachable` | `SystemApprovalManager` 3 | `break;` detrás de un `return` dentro de un `switch`. Inofensivo y real |
| **30** | *(las 12 familias restantes, ≤3 cada una)* | — | Sin verificar |

### DOS FAMILIAS MÁS QUE PARECEN MECÁNICAS Y NO LO SON

Van ya tres, contando el `$showSQL` de T27. **El patrón se repite y conviene reconocerlo: un
interruptor de desarrollo tiene esta forma exacta** —una variable local puesta a un literal,
con su comentario al lado, y un `if` que la lee—.

**`booleanNot.alwaysTrue` — `$showAlways`, 6 de 10.**

```php
$showAlways = false; //Define si se muestra siempre aunque no tenga traducción
…
if (!$showAlways) {
    // añade el criterio que filtra por idioma
}
```

Borrar ese `if` **cablearía «mostrar solo lo traducido»** y quitaría el interruptor. Es
exactamente `$showSQL` con otro nombre.

**`ternary.alwaysTrue` — constantes de módulo, 10 de 12 en vistas.**

```php
$withAttachments = PublicationMapper::WITH_ATTACHMENTS;   // en la vista
…
style="<?= $withAttachments ? '' : 'display:none;' ?>"
```

**Corrección de una lectura propia**: la primera hipótesis fue que eran variables inyectadas
por `extract()` y que PHPStan no las ve. **Falso, y bastó un `grep` para saberlo**: se asignan
en la propia vista desde una **constante de clase**. Es la misma trampa que los interruptores
de módulo de T13, pero con constantes que **no están en `config/constants.php`**, así que
`dynamicConstantNames` no las cubre hoy.

> **Opción a evaluar, NO implementar**: `dynamicConstantNames` admite constantes de clase
> (`PublicationMapper::WITH_ATTACHMENTS`). Habría que inventariar qué constantes de mapper son
> configuración por despliegue y cuáles no — y esa distinción no se deduce del código.

### Regla de trabajo

1. **Empezar por `foreach.emptyArray`** (24, todas en mappers, todas la misma forma): es la
   única familia con veredicto homogéneo verificado y sin sorpresas.
2. Después `function.alreadyNarrowedType` (105) y `function.impossibleType` (13).
3. **`ternary.alwaysTrue` y `booleanNot.alwaysTrue` NO se tocan** hasta decidir lo de las
   constantes de módulo.
4. **Cada familia, con su muestra verificada a mano ANTES de tocarla** — nunca por parecerse a
   otra que sí era andamio.
5. Y antes de suprimir una familia entera, **comprobar que sus miembros están ahí por la misma
   razón** (T27). Tres familias de este mapa ya han demostrado que no basta con compartir
   identificador.
