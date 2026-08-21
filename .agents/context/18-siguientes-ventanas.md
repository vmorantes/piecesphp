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

Verificado con un segundo método independiente, como manda T20: `PHPStanResult.Summary.txt`
lo obtiene parseando **la tabla** con otro código —`bin/phpstan-process-result.php`— y da
los mismos **877 y 190**. Distinto formateador y distinto parser, así que el acuerdo no es
tautológico.

### Refinamiento del marco de las cuatro respuestas

**«CONTRATO» solo está disponible cuando el lenguaje OFRECE una expresión para la garantía.**

Para `createFromFormat` existía: `new \DateTime(...)` devuelve `DateTime` sin condiciones.
Para `json_encode` no hay ninguna función que devuelva `string` incondicionalmente, así que
las opciones eran la bandera, un cast, un ignore o una rama inalcanzable — y solo una es
honesta.

> **Cuando el lenguaje no ofrece la expresión, la forma honesta más cercana es convertir el
> fallo imposible en un fallo RUIDOSO. No sustituye al contrato: es el contrato escrito de
> la única forma disponible.**

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

El remoto del framework tiene un **token de acceso personal de GitHub en texto plano en
`.git/config`**. Avisado el 2026-08-20 y **sigue ahí**.

**Pendiente del propietario del repositorio, no de un agente.** No lo toques: rótalo en
GitHub y guarda la credencial en un *credential helper*, no en la URL del remoto.

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
  restos de Webflow, `scssphp`, `PDFManager` + `mpdf`
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

| Commit | Errores | Con `false` | Qué resolvió |
| :-- | --: | --: | :-- |
| `89030cb6` | 968 | **157** | *(punto de partida bajo el pipeline corregido)* |
| `61a0a474` | 968 | 157 | D2 — nulabilidad, no `false` |
| `14d0886a` | 950 | 157 | Migración fuera de `routes()` |
| `6a33b77e` | 933 | **140** | Retipar `PDO` → `Database` (−17) |
| `7f5f00a2` | 916 | **123** | `JSON_THROW_ON_ERROR` (−17) |
| `7ac91445` | 907 | **114** | Familia `strpos` (−9) |
| `d6924b39` | 900 | **107** | `realpath` y `createFromFormat` (−7) |

**Resueltos: 50 de 157. Quedan 107.**

### Los 107 que quedan, por grupo y destino

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

## T12 · `routeName` / `allowedRoute` — PASO A MEDIDO, PASO B HECHO

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

- **C.1** Añadir `use RouteNamingTrait;` a los controladores. Cambio de comportamiento CERO:
  el método declarado en la clase gana al del trait. Si PHPStan o las suites se mueven un
  punto, algo se entendió mal y se para.
- **C.2** Borrar las copias locales **una a una**, solo las idénticas al cuerpo canónico.
- **C.3** *(no autorizado)* Las variantes intencionales conservan su método local.
- **D** Tipar. **`AdminPanelController` es base de varios controladores: la base y todos sus
  descendientes se tipan EN EL MISMO COMMIT.** Si se tipa la base y un hijo redeclara sin
  tipo, el hijo ensancha y **PHP falla al declarar la clase**, no al llamarla.

## T13 · Ramas muertas — TRIAJE HECHO, supresión NO autorizada

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

## T16 · COLISIÓN DE CLASE `MetaProperty` — verificada, viva y silenciosa

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
3. En piecesphp hay **28 mappers** usando `MetaProperty`; **todos** reciben la del núcleo.

**Lo que NO se puede hacer**: borrar la del paquete «porque no se usa». Sí se usa —en el
paquete, con sus propias pruebas— y borrarla lo rompe como librería independiente.

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

### La regla

> **Antes de reportar un hallazgo inesperado, verifícalo con un SEGUNDO MÉTODO
> INDEPENDIENTE.** Una cifra que sorprende es, con más frecuencia de la que parece, una
> herramienta que no mide lo que uno cree.

«Independiente» significa que no comparta mecanismo con el primero: `--stat` contra
`--name-only` sirve porque aplican el filtro en sitios distintos; repetir el mismo comando
con otra bandera cosmética, no.

### Por qué importa aquí más que en otro sitio

Una alarma falsa cuesta el doble: se pierde la confianza en la medición **y** en las que
vengan después. En una campaña que se sostiene sobre cifras —877 errores, 464 ramas, 107
`false`, 44 controladores—, un instrumento que miente contamina todo el razonamiento
construido encima. Ya obligó a derogar el «148» y a reconciliar «469 contra 464».

**Corolario**: cada cifra que se publique en un documento debería poder decir con qué se
midió. Ver T8 y T18, donde la unidad y el método están escritos junto al número.

