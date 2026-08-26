# 10 — CLI, tareas programadas, colas y eventos

## Ejecutar comandos

```bash
# Desde src/
php index.php cli <acción> [parámetro=valor ...] [--flag=valor]

# Atajo desde la raíz del repo (añade automáticamente --local)
bin/cli <acción> [...]
```

Autocompletado con TAB:

```bash
source bin/pieces-completion.bash   # o bin/pieces-completion.zsh
```

`PiecesPHP\Cli` detecta si la salida va a un pipe o a un archivo y **quita los
códigos de color ANSI** automáticamente.

### Orden de resolución de una acción CLI

1. Rutas registradas del sistema (vía `TerminalController::routeID()`).
2. Acciones personalizadas registradas con `CliActions::make()`.
3. Evento `EVENT_CLI_ROUTE_NOT_FOUND_NAME`.
4. Error.

## Acciones integradas

Implementadas en `src/app/classes/Terminal/Tasks/`:

| Acción | Clase | Parámetros | Qué hace |
| :-- | :-- | :-- | :-- |
| `db-backup` | `DbBackupTask` | `gz`, `data`, `routines`, `views`, `definer` (yes/no) | Respalda la BD por defecto en `dumps/`. Usa `Database\Export\Exporter`, ya no `mysqldump` |
| `db-restore` | `DbRestoreTask` | `file=`, `confirm=yes`, `database=` | **Restaura** desde un volcado. DESTRUYE datos: exige `confirm=yes`. Deja rastro en `files/dev/last-restore.json` |
| `bundle` | `BundleTask` | `app`, `statics`, `all`, `zip` (yes/no) | Empaqueta la app y/o estáticos en `bundle/` |
| `clean-cache` | `CleanCacheTask` | — | Renueva el token de caché de estáticos |
| `clean-logs` | `CleanLogsTask` | — | Limpia logs de errores y sesiones expiradas |
| `clean-all` | `CleanAllTask` | — | `clean-cache` + `clean-logs` |
| `scan-missing-lang` | `ScanMissingLangTask` | `--exclude-lang=`, `--exclude-group=` | Genera el reporte de traducciones faltantes |
| `run-cronjobs` | `RunCronjobsTask` | — | Ejecuta los cronjobs cuya condición horaria se cumple |
| `process-queue` | `ProcessQueueTask` | `--limit` (def. 60) | Worker de la cola |
| `scheme-create` | `SchemeCreateTask` | `module=<Nombre>\|all`, `output=` | **Emite** el `CREATE TABLE` del módulo, ordenado padres → hijas. Descubre los mappers; no ejecuta |
| `scheme-drop` | `SchemeDropTask` | `module=<Nombre>\|all`, `output=` | **Emite** el `DROP TABLE`, ordenado hijas → padres. No ejecuta |
| `snapshot` | `SnapshotTask` | `label=`, `compare=a,b`, `dir=` | Foto de la base y del árbol de `src/`, y su diff. La usa `bin/walk-attribute` ruta a ruta |
| `verify-integrity` | `VerifyIntegrityTask` | `update-snapshot`, `list-narrative` | Once comprobaciones estructurales sobre el repositorio |
| `help` / `h` | `HelpTask` | — | Lista las acciones disponibles |
| — | `FixWebmDurationTask` | — | Corrige la duración de archivos WebM |

> El comando CLI **ignora `max_execution_time`**, pero no el límite de memoria.
> Algunas tareas requieren usuario root del framework.
> Salidas: backups en `dumps/`, bundles en `bundle/`.

## CronJobs

Se definen en `src/app/config/final-configurations-includes/cronjobs.php`:

```php
use PiecesPHP\Terminal\CronJobTask;

$cronjobs = [];
$cronjobs[] = CronJobTask::make('Limpieza temporales', function () {
    return ['success' => true, 'message' => 'Archivos temporales eliminados.'];
})->dailyAt("00:00");

CronJobTask::addCronJobs($cronjobs);   // o ->addCronJob() por tarea
```

Métodos de programación: `onMinute(int)`, `hourly()`, `dailyAt(string)`,
`weeklyOn(int $day, $time)` (0=domingo), `when(callable)`.

Un solo crontab del sistema dispara todo:

```cron
* * * * * php /ruta/al/proyecto/src/index.php cli run-cronjobs run
```

> **SIN `--local`. NUNCA con `--local` en un servidor.**
>
> Ese flag **decide a qué BASE DE DATOS se conecta la aplicación**: en terminal,
> `is_local()` devuelve exactamente lo que diga `--local` (`Utilities.php:607-610`), y
> `config/database.php` elige credenciales y nombre de base según ese valor. Una línea de
> crontab con `--local` en producción **apunta los cronjobs a la base de desarrollo**.
>
> `bin/cli` sí lo añade solo, y está bien: es el atajo para trabajar en local.

Para tareas largas, cuida la conexión:

```php
use PiecesPHP\Core\{BaseModel, Config};

BaseModel::destroyDb(Config::app_db('default')['db'], Config::app_db('default')['host']);
// trabajo pesado
BaseModel::restoreInstancesDb(Config::app_db('default')['db'], Config::app_db('default')['host']);
```

Ejemplo real en `config/final-configurations-includes/cronjobs.php`.

## Colas (Queues)

Componentes: `PiecesPHP\Terminal\QueueTask` (despacho y registro),
`Terminal\Mappers\QueueJobMapper` (persistencia, tabla `pcsphp_jobs_queue`),
`ProcessQueueTask` (worker).

**Handler** — en `final-configurations-includes/queues.php`:

```php
use PiecesPHP\Terminal\{QueueTask, QueueHandlerResponse};

$queueHandlers[] = QueueTask::make('nombre-de-la-cola', function ($data) {
    return $ok
        ? QueueHandlerResponse::success()
        : QueueHandlerResponse::error("Error al procesar", true); // true = reintentar
});
```

**Despacho** — desde cualquier parte:

```php
QueueTask::dispatch('nombre-de-la-cola', ['user_id' => 123], $retries = 3, $scheduledAt = "2026-03-24 10:00:00");
```

**Worker**: `php index.php cli process-queue` (programar en crontab). **Sin `--local`**, por
la misma razón que los cronjobs: ese flag elige la base de datos.
Estados: `pending`, `processing`, `completed`, `failed`. Se registran el último
error y el número de intentos. Hay locks para evitar ejecuciones paralelas.

## Acciones CLI personalizadas

En `final-configurations-includes/cli-actions.php`:

```php
use PiecesPHP\Terminal\CliActions;
use React\EventLoop\Loop;

CliActions::make('mi-motor', function ($args) {
    echoTerminal("Iniciando...");
    Loop::addPeriodicTimer(1.0, fn() => echoTerminal("Revisando tareas..."));
    Loop::run();
})->setDescription('Ejecuta un motor reactivo')->register();
```

Helpers de salida: `echoTerminal()`, `systemOutFormatted()`,
`Cli::systemOutFormatted($msg, ['color' => 'red', 'newLine' => true])`.
Utilidades: `getCurrentProcessOwnerUser()`, `getCurrentProcessOwnerGroup()`.

## Sistema de eventos

`PiecesPHP\Core\BaseEventDispatcher`.

```php
// Escuchar (final-configurations-includes/event-listeners.php)
BaseEventDispatcher::listen('NombreEvento', function ($payload) { ... }, 'MiContexto');

// Disparar
BaseEventDispatcher::dispatch('MiContexto', 'NombreEvento', $datos);

// Eventos del sistema
BaseEventDispatcher::defaultListen(BaseEventDispatcher::EVENT_INIT_ROUTES_NAME, fn() => ...);
BaseEventDispatcher::defaultDispatch(BaseEventDispatcher::EVENT_INIT_ROUTES_NAME, null);
```

Eventos predefinidos:

| Evento | Cuándo |
| :-- | :-- |
| `EVENT_INIT_ROUTES_NAME` | Al terminar de registrar todas las rutas |
| `EVENT_ADD_DYNAMIC_TRANSLATIONS_NAME` | Tras cargar las traducciones dinámicas desde BD |
| `EVENT_CLI_ROUTE_NOT_FOUND_NAME` | Cuando una acción CLI no resuelve |

Los mappers disparan automáticamente `saving`/`saved`/`updating`/`updated` usando
el nombre de su clase como contexto (ver [06-orm-mappers.md](./06-orm-mappers.md)).

Los **contextos** agrupan eventos por dominio y evitan colisiones de nombres.

## Otras utilidades del núcleo

| Clase | Para qué |
| :-- | :-- |
| `PiecesPHP\Core\Http\HttpClient` | Cliente HTTP del framework |
| `PiecesPHP\Core\Http\FreezeRequest` | Congelar/reproducir un request |
| `PiecesPHP\Core\Cache\CacheControllersManager` | Caché de respuestas de controlador por criterios |
| `PiecesPHP\Core\Importer\*` | Importación con schema, campos y colecciones |
| `PiecesPHP\Core\Sitemap\*` | Generación de sitemap |
| `PiecesPHP\Core\PDFManager` | PDFs (mPDF) |
| `PiecesPHP\Core\Mailer` / `MailjetHandler` / `Email\Mailgun` | Correo |
| `PiecesPHP\Core\Forms\{FileUpload, FileValidator, UploadedFileAdapter}` | Subida y validación de archivos |
| `PiecesPHP\Core\Helpers\Directories\*` | `DirectoryObject`, `ProtectFileMiddleware`, symlinks y borrado seguro |
| `PiecesPHP\Core\FlashMessages` | Mensajes flash (`set_flash_message()`, `get_flash_messages()`) |

## Pruebas

Ver `files/dev/tests.md` y
`source-docs/project/docs/piecesphp/new-features/unit-testing.md`.
Se ejecutan como acciones CLI, p. ej.:

```bash
bin/cli unit-tests:core/http-client
bin/cli unit-tests:core/helpers-directories
```

## Verificación de integridad

```bash
bin/cli verify-integrity
```

`Terminal\Tasks\VerifyIntegrityTask`. Detecta **docblocks sin cerrar** y **funciones o
métodos que han desaparecido**, comparando contra `files/dev/integrity-signatures.json`.
Sale con código 1 si algo falla, para CI.

Comprueba **dieciséis** cosas, numeradas en `VerifyIntegrityTask::run()` —que es la fuente—:
docblocks sin cerrar, firmas desaparecidas, que **toda clase bajo una raíz PSR-4 se llame como
su ruta manda y se pueda cargar**, que el núcleo **no ECLIPSE una clase de ningún paquete
`piecesphp/*`**, y doce más. La lista entera, con lo que motivó cada una, está en
`files/dev/tests.md`; **no se duplica aquí para que no vuelva a quedarse corta.**

El eclipse es el que menos se sospecha y el más difícil de ver: PSR-4 resuelve por prefijo
más largo, los paquetes registran `PiecesPHP\` y el proyecto `PiecesPHP\Core\`, así que
cualquier clase que el núcleo declare bajo ese namespace gana **siempre y en silencio**.
Los eclipses aceptados viven en `VerifyIntegrityTask::KNOWN_ECLIPSES` con su razón y la
condición que los retira; uno que no esté ahí hace fallar la tarea, y una entrada de ahí
cuyo eclipse ya no exista, también.

Cubre un hueco real: ni un docblock sin cerrar ni un `namespace` perdido son errores de
sintaxis, así que `php -l` los da por buenos. Ver `files/dev/tests.md` y, para lo que la
puerta **no** atrapa, `.agents/context/18-siguientes-ventanas.md` (T10).

## Recorrido de rutas — la puerta que sustituye al paseo humano

```bash
bin/cli route-inventory                                       # inventario, del propio framework
bin/walk-routes --base=https://85.localhost/vicsen/piecesphp/src
bin/walk-routes --base=… --no-assets                          # solo rutas
bin/walk-routes --base=… --json=salida.json
```

**Existe porque el propietario no debe encontrar fallos haciendo clic**, y porque un paseo
humano **no mira los assets**: una imagen que revienta no rompe la página, solo deja un
hueco. Ahí vivían las nueve llamadas deprecadas que tumbaban `img-gen`.

- El inventario sale de `get_routes()`, **no de una lista escrita a mano**: una lista a mano
  envejece en silencio y deja de cubrir justo lo que se acaba de añadir.
- **SOLO GET**, y descarta por nombre **y** por URL lo que declare
  `files/dev/forbidden-routes.json`: `-actions-`, `-add`, `-edit`, `-delete`, `delete`,
  `destroy`, `remove` o `logout`, **aunque esté declarado como GET**. No escribe nada.
  Ese archivo tiene además un bloque `allow` que **gana** sobre los patrones, para las rutas
  cuyo nombre parece de escritura y no lo es —`-forms-add`, `-forms-edit` y
  `actions-logs-`, que son vistas—. Cada excepción lleva su razón escrita y
  `verify-integrity` falla si libera algo que no sea GET o si no la trae. Ver T100.
- Recorre después **los assets** que aparecen en las páginas visitadas. Hasta T102 esta
  línea decía «todos» y era falsa: el extractor solo aceptaba comillas dobles y los
  ayudantes del framework emiten simples, así que **no pidió un solo `.css` ni `.js`** en
  toda la campaña. Hoy acepta las dos comillas y descarta los dominios de terceros.
- **Con sesión**: exporta `PCSPHP_WALK_USER` y `PCSPHP_WALK_PASS` antes de llamarlo. Hace
  `POST /users/login/` y manda el JWT en la cabecera `JWTAuth`. **Las credenciales no se
  escriben en ningún archivo ni entran en el repositorio.** Sin ellas, todo `/admin/*`
  responde 302 y el recorrido sigue siendo útil para la zona pública y los assets.
- **Antes de correrlo, vacía el log** para que las entradas que aparezcan sean suyas.
- **Para comprobar que una respuesta ejecutó el ORM de verdad**, buscar
  `systemApprovalStatus` en el cuerpo: ese campo **no existe en ninguna tabla** y solo
  aparece si `fieldsToSelect()` pasó por `BaseEntityMapper::__callStatic`. Un 200 no
  prueba que se consultara nada; ese campo sí.
- Sale con código 1 si alguna ruta o asset no dio 2xx.

**Lo que NO cubre, y hay que decirlo**: las rutas que exigen parámetros no se piden, porque
habría que inventar valores. Son 45 de 347. El recorredor las lista como omitidas con esa
razón, nunca en silencio.

## Comprobación previa a actualizar

```bash
bin/cli scan-invalid-utf8                 # solo informa, no escribe
bin/cli scan-invalid-utf8 table=usuarios  # una tabla
bin/cli scan-invalid-utf8 limit=50000     # más filas por tabla
```

`Terminal\Tasks\ScanInvalidUtf8Task`. **De SOLO LECTURA.** Busca UTF-8 inválido en las
columnas de texto de la base de datos y sale con código 1 si encuentra alguna.

**VIAJA CON EL FRAMEWORK como comprobación previa a actualizar.** Desde que los sitios de
codificación llevan `JSON_THROW_ON_ERROR`, un texto con UTF-8 inválido deja de servir un dato
ligeramente mal y pasa a **cortar la petición con un 500**. Cualquier despliegue congelado que
vaya a descongelarse debería correr esto **antes**, no después.

La comprobación se hace con `mb_check_encoding()` y no en SQL a propósito: quien tiene que
aceptar el dato es PHP, así que preguntárselo a PHP es la única respuesta que vale.

## Sincronización de registros OTP

```bash
bin/cli sync-otp-records            # solo informa
bin/cli sync-otp-records apply=yes  # aplica
```

`Terminal\Tasks\SyncOTPRecordsTask`. Crea los registros OTP que falten, uno por usuario y
método. **Por defecto no escribe nada.**

Vivía dentro del registro de rutas, que corre en cada petición: dos barridos sobre la tabla
de usuarios por carga de página. **No la llames desde una petición.**

## Análisis estático

```bash
bin/phpstan            # config en bin/phpstan.neon y bin/phpstan.services.neon
```

Resultados en `PHPStanResult.txt` y `PHPStanResult.Summary.txt` (generado por
`bin/phpstan-process-result.php`). Hay extensiones propias en
`Core/PHPStan/` (p. ej. `SystemDynamicFunctionReturnTypeExtension`).

La configuración analiza el **rango** `{min: 80400, max: 80500}`, no una sola versión, y
carga `phpstan/phpstan-deprecation-rules`. La línea base vive en
`PHPStanResult.Summary.baseline.txt`: es **detector de regresión, no meta a cero**.

> **La cifra visible no es el total.** Los `ignoreErrors` silencian unas tres veces más
> errores de los que se ven. Antes de concluir que algo «está limpio», comprueba si no
> está simplemente suprimido — la auditoría de las entradas está en
> [18-siguientes-ventanas.md](./18-siguientes-ventanas.md).

## Refactor automatizado

```bash
bin/rector             # config en bin/tools/refactorization/Rector.php
```

Emite al nivel del **piso** (`phpVersion(PhpVersion::PHP_84)`) aunque detecte hasta 8.5,
para no introducir sintaxis que rompa en la versión mínima soportada.

> ### ⚠️ Rector NO analiza las vistas, y no porque sea seguro hacerlo ahí
>
> `Rector.php` construye su lista de archivos leyendo las rutas `project://` de
> **`PHPStanResult.txt`**: solo mira archivos que PHPStan reportó con errores.
>
> Y los errores de las vistas están silenciados por un `ignoreErrors` —el mayor de
> todos, ~1.350 errores— porque las vistas reciben sus variables por `extract()` y
> PHPStan no puede seguirlas. **Como no se reportan, no entran en `PHPStanResult.txt`, y
> Rector nunca las ve.**
>
> El resultado hoy es que ninguna regla peligrosa toca vistas. Pero eso es una
> coincidencia de la cadena de herramientas, **no una garantía**: si algún día se levanta
> ese `ignoreErrors`, Rector empezará a ver exactamente los archivos donde `extract()`
> hace inservible su análisis de alcanzabilidad, y reglas como
> `RemoveUnusedVariableAssignRector` propondrán borrar variables que sí se usan.
>
> Si eso ocurre, hay que añadir `src/app/view` y `**/Views` al `skip()` de Rector
> **antes** de volver a ejecutarlo.

También conviene saber que `AppHelpers.php` y `Utilities.php` están en el `skip()` por
diseño: son los dos archivos más grandes y su revisión es manual.
