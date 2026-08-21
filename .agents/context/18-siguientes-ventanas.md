# 18 — Siguientes ventanas

> ## TRASPASO — 2026-08-21
>
> Escrito para que alguien que llega en frío ejecute sin preguntar. Lo de aquí no está en
> el código y se pierde si no se lee.

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

## T3 · D2 — escritura en base de datos desde una ruta NO autenticada

**Verificado, no heredado de una lectura ajena.**

`UsersController.php:899` llama a `OTPHandler::checkValidityOTP($password, $username)`.
La línea **900** es `if (password_verify($password, $user->password) || $otpIsValid)`.
Es decir: **la comprobación OTP se ejecuta ANTES de verificar la contraseña.**

`checkValidityOTP()` llama sin condiciones a
`OTPSecretsUsersMapper::getOTPData($userData->id, METHOD_ONE_USE_CODE)`, y ese método,
cuando no encuentra registro, **crea uno y lo guarda**:

```php
if ($mapper->id === null) {
    $mapper = new OTPSecretsUsersMapper();
    $mapper->secret = TOTPStandard::generateSecret();
    ...
    $mapper->save();          // INSERT
}
```

**Consecuencia**: una petición de login **sin autenticar**, con solo un nombre de usuario
existente y activo, provoca un `INSERT` y **genera material criptográfico** (un secreto
TOTP) para un usuario que no lo pidió.

**Prueba empírica en el entorno de pruebas**, sin escribir nada:

| | |
| :-- | :-- |
| Filas en `OTPSecretsUsers` | 68 = 34 `TOTP` + 34 `ONE_USE_CODE` |
| Usuarios | 34 |
| **Usuarios con `twoAuthFactor` activo** | **0** |
| **Filas con secreto TOTP generado** | **34** |

Nadie ha activado el 2FA y aun así **los 34 usuarios tienen secreto generado**. Esas filas
no las creó nadie configurando nada.

**Qué es y qué no**: no es crecimiento sin límite —hay como mucho una fila por usuario y
método, y ya están todas creadas—, pero sí es **escritura no autenticada**, **generación
de secretos por cuenta ajena** y un **canal de enumeración de usuarios**: el estado de la
base cambia solo si el nombre existe.

**Queda como hallazgo de seguridad, no como deuda de tipos.** El nivel lo decide el
propietario.

La decisión sobre el `null` sigue en pie y es aparte: `toExpireOTP()` desreferencia el
resultado sin comprobar, y ahí lo correcto es **registrar y continuar**, porque en la ruta
crítica `checkValidityOTP` ya encontró el registro.

## T4 · El token de GitHub sigue sin rotar

El remoto del framework tiene un **token de acceso personal de GitHub en texto plano en
`.git/config`**. Avisado el 2026-08-20 y **sigue ahí**.

**Pendiente del propietario del repositorio, no de un agente.** No lo toques: rótalo en
GitHub y guarda la credencial en un *credential helper*, no en la URL del remoto.

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
