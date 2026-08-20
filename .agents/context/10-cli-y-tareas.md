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
| `bundle` | `BundleTask` | `app`, `statics`, `all`, `zip` (yes/no) | Empaqueta la app y/o estáticos en `bundle/` |
| `clean-cache` | `CleanCacheTask` | — | Renueva el token de caché de estáticos |
| `clean-logs` | `CleanLogsTask` | — | Limpia logs de errores y sesiones expiradas |
| `clean-all` | `CleanAllTask` | — | `clean-cache` + `clean-logs` |
| `scan-missing-lang` | `ScanMissingLangTask` | `--exclude-lang=`, `--exclude-group=` | Genera el reporte de traducciones faltantes |
| `run-cronjobs` | `RunCronjobsTask` | — | Ejecuta los cronjobs cuya condición horaria se cumple |
| `process-queue` | `ProcessQueueTask` | `--limit` (def. 60) | Worker de la cola |
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
* * * * * php /ruta/al/proyecto/src/index.php cli --local run-cronjobs run
```

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

**Worker**: `php index.php cli --local process-queue` (programar en crontab).
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

## Análisis estático

```bash
bin/phpstan            # config en bin/phpstan.neon y bin/phpstan.services.neon
```

Resultados en `PHPStanResult.txt` y `PHPStanResult.Summary.txt` (generado por
`bin/phpstan-process-result.php`). Hay extensiones propias en
`Core/PHPStan/` (p. ej. `SystemDynamicFunctionReturnTypeExtension`).
También existe `bin/rector` para refactors automatizados.
