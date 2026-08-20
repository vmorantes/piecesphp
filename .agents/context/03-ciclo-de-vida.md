# 03 — Ciclo de vida de una petición

Todo entra por **`src/index.php`** (Front Controller único), tanto HTTP como CLI.
Apache redirige ahí todo lo que no sea archivo/carpeta real (ver `src/.htaccess`).

## Fase 1 — `app/core/bootstrap.php`

En orden:

1. **`config/critical-definitions.php`** — primer archivo cargado, sin acceso a
   funciones del framework. Define `CRITICAL_CONSTANTS` (hoy solo
   `ORGANIZATIONS_MODULE`).
2. **Detección de terminal**: instancia `PiecesPHP\Cli` con `$argv`. Si no hay
   `HTTP_HOST` y el script es `index.php` con el comando `cli`, parsea el primer
   argumento como acción, quita `--local`, y rellena
   `$_SERVER['PCSPHP_TERMINAL_DATA']` (`isTerminal`, `arguments`, `route`, `local`,
   `cli`). Simula `HTTP_HOST=localhost` para que el router funcione.
3. **Manejo de errores**: `error_reporting(E_ALL)`, `display_errors` solo en local
   (host `localhost`/`*.localhost`, o `--local` en CLI). `set_error_handler` convierte
   errores en `ErrorException` y lanza para `E_ERROR|E_WARNING|E_PARSE|E_NOTICE|E_DEPRECATED`.
   Define la función global **`global_custom_exception_handler()`**, que limpia
   buffers, usa `CustomSlimErrorHandler` para producir la respuesta (JSON o HTML),
   pone cabeceras CORS si `API_MODULE`, y muere con código 500.
4. **Autoloads**: `bin/tools/vendor/autoload.php` (si existe) → `src/vendor/autoload.php`
   → `core/autoload.php`. Luego se registra `set_exception_handler`.
5. **Constantes**: `BASEPATH`, `APP_VERSION` (`v7.0.6`), `APP_VERSION_DATE`.
6. **Requires en orden**: `core/Utilities.php`, `config/config.php`, `core/Config.php`,
   `config/database.php`, `config/cookies.php`, `config/roles.php`. Todo el array
   `$config` se vuelca a `Config::set_config()`.
7. `core/AppHelpers.php`, `config/lang.php`, `Config::init()`.
8. `config/functions.php`, `config/constants.php`, `config/autoloads.php`
   (vía `core/custom-autoloads-config.php`).
9. Ajustes finales: `ServerStatics::setStaticPath()`, `BaseToken::setSecretKey(app_key)`,
   `BaseHashEncryption::setSecretKey(app_key)`,
   `BaseController::setViewDir(app_path().'/app/view/')`, y
   `set_config('terminalData', TerminalData::getInstance()->setData(...))`.

## Fase 2 — `src/index.php`

1. `require bootstrap.php`.
2. `config/assets.php` — registro de librerías front.
3. `config/containers.php` → `new DependenciesInjector($container_configurations)`
   guardado en `set_config('slim_container', …)`.
   Servicios disponibles en el contenedor: **`foundHandler`**, **`notFoundHandler`**,
   **`forbiddenHandler`**, **`staticRouteModulesResolver`**, **`params`**, **`cors`**.
4. Si `control_access_login`, añade los JS globales de sesión
   (`statics/core/js/user-system/*`).
5. Si `APP_CONFIGURATION_MODULE`: `AppConfigModel::initializateConfigurations()` con
   valores por defecto (favicon, logo, backgrounds, mail, meta_theme_color…), y luego
   **sobrescribe la configuración desde la base de datos** con
   `AppConfigModel::getConfigurations()`. → *Las configuraciones de BD ganan sobre
   `config.php`.*
6. `Router::createRouter($di)` y `setBasePath()` calculado desde `appbase()`.
7. `$app->addBodyParsingMiddleware()` — soporte automático de JSON y XML en el body.
8. **Middleware global principal (pre-routing)** — el bloque más grande del archivo
   (~líneas 203-791). Hace, entre otras cosas: CORS, sesiones y expiración por
   inactividad, validación de `SessionToken`/JWT, "conectarse como otro usuario"
   (`asUser` / cookie `asUserID`), resolución de idioma (i18n) y jerarquía de roles.
9. `set_config('upload_dir' | 'upload_dir_url' | 'slim_app')`.
10. **`config/routes.php`** — registro de todas las rutas.
11. **`config/final-configurations.php`** — carga `final-configurations-includes/*`.
12. `addRoutingMiddleware()`, `addErrorMiddleware(is_local(), false, false)`,
    y `setDefaultInvocationStrategy(new InvocationStrategy())`.
13. **Middleware de idioma esperado**: lee la cabecera
    `PCSPHP-Response-Expected-Language` y, si viene, fuerza `app_lang`. En la salida
    aplica el servicio `cors` si `API_MODULE`.
14. `RouteGroup::initRoutes(false)` → construye el árbol real de rutas Slim, marca
    `AppRoutesInit` y dispara el evento `EVENT_INIT_ROUTES_NAME`.
15. Si estamos en terminal: resuelve la acción contra las rutas registradas
    (`TerminalController::routeID()`), y si no hay match prueba `CliActions`, luego
    el evento `EVENT_CLI_ROUTE_NOT_FOUND_NAME`, y si nada responde imprime error.
16. Handlers de error por excepción: `HttpNotFoundException`/`NotFoundException` →
    `notFoundHandler`; `HttpForbiddenException` → `forbiddenHandler`;
    `HttpMethodNotAllowedException` → 404 con métodos permitidos; el resto →
    `global_custom_exception_handler` + 500. Hay detección especial del contexto
    `MissingResponseInController` (cuando un controlador no devuelve un
    `ResponseInterface`).
17. `$app->run(RequestRouteFactory::createFromGlobals())`.

## Objetos Request/Response

No se usan los de Slim directamente, sino envoltorios propios:

```php
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
```

Con sus factories `RequestRouteFactory` / `ResponseRouteFactory`. `ResponseRoute`
ofrece helpers estilo Slim 3 (p. ej. `withJson()`), y hay una capa
`Routing/Slim3Compatibility/` para excepciones y códigos de estado.

**Un método de controlador siempre debe retornar un `Response`.** Si no lo hace, el
error se reporta con el contexto `MissingResponseInController`.

## `InvocationStrategy`

`PiecesPHP\Core\Routing\InvocationStrategy` es la estrategia de invocación por
defecto del route collector. Permite registrar callbacks previos a la ejecución del
controlador con `InvocationStrategy::appendBeforeCallMethod(fn)` — patrón usado por
los módulos para inicializar menús y configuraciones de front en el momento correcto
(ver `NewsRoutes::routes()`).

## Errores y logs

- `CustomSlimErrorHandler` (en `Core/CustomErrorsHandlers/`) genera la respuesta de
  error, en JSON si el request lo pide.
- `log_exception($e)` es el helper para registrar excepciones.
- Logs en `src/app/logs` (`LOG_ERRORS_PATH`), backups en `logs/olds`
  (`LOG_ERRORS_BACKUP_PATH`). Desde 7.0.6 existe además un `error.plain.log` de
  lectura fácil.
- La vista de log de errores del panel es la ruta `admin-error-log` (solo roles
  superiores).
