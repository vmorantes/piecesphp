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
3. **Manejo de errores**: `error_reporting(E_ALL)`, `display_errors` solo en local. **«Local» ya no es la cabecera
   `Host`** (P58, `c843341d`): en la web, `is_local()` es `app_environment() === 'local'`, que lee
   `src/app/config/environment.php` (NO versionado; plantilla `environment.example.php`) con
   `PiecesPHP\Core\AppEnvironment::load()` en `bootstrap.php:272`, antes de `config.php` y `database.php`. Sin el archivo
   o con otro valor: **producción**. En CLI, `--local` como siempre. `$config['environment']` lo guarda. `set_error_handler` convierte
   errores en `ErrorException` y lanza para `E_ERROR|E_WARNING|E_PARSE|E_NOTICE|E_DEPRECATED`.
   **P56, parte 1** (`#353`): `GenericHandler` pone a cada entrada un `reference` (`ERR-Ymd-6HEX`); `log_exception()` lo
   devuelve; `CustomSlimErrorHandler::getResponse($req, ?bool $isLocal)` fuera de local responde SOLO
   `success/message/reference` (HTML: página mínima), en local lo de siempre más `reference`. Suite `core/error-reference`.
   Partes 2 y 3 (`#357`-`#362`): los 67 internos a `genericMessage(log_exception($e))`; los errores técnicos de
   `Validation\Parameters` en catch propio ANTES del `\Exception` (dicen el parámetro); los textos de usuario se lanzan
   como `SafeException` del módulo. **Puerta:** comprobación 32 de `verify-integrity` («MENSAJE DE EXCEPCIÓN:»), lista en
   `files/dev/exception-message-declared.json` (109: tecnico 55, usuario 39, no-excepcion 9, terminal 3, solo-local 2,
   log 1), cuadra en los dos sentidos. Trampa: `->operation(...)` suelto tras `setMessage()` no hace nada (PHPStan).
   **P61 (`82d95543`)**: el `foundHandler` de `src/app/config/containers.php` YA NO escribe
   `OrganizationMapper::INITIAL_ID_GLOBAL` al usuario logueado sin organización. Si su tipo no la requiere
   (`TYPES_USER_DONT_REQUIRE_ORGANIZATION`), no pasa nada; si la requiere, se anota el defecto con `log_exception()` —una
   vez por sesión vía `SessionDataHandler`, y **solo si ya hay sesión activa**, porque su constructor llama a
   `session_start()` y navegar no puede abrir una sesión que no existía—. Suite `core/organization-default` (13), que
   corre el middleware REAL sacado de `get_router()->getDI()->get('foundHandler')`; provocada. Censo del 2026-09-22: 66
   lecturas de `->organization` en 30 archivos, y solo 3 encadenan (`MySpace/MyOrganizationProfileController.php:215`,
   `:303`, `:304`), las tres ya protegidas por un `!== null` previo.
   **La sesión, medida en `#403` y `#407`.** No vive en `$_SESSION`: vive en un JWT (`id` y `type`, 31 días por
   defecto) que llega por la cabecera `JWTAuth` o por la cookie del mismo nombre (`SessionToken::getJWTReceived()`; con
   las dos, manda la cabecera). `UserDataPackage` es una foto de la petición guardada en `Config`, que es `static`: muere
   con ella. **Dos mundos en el CLI:** las acciones de `CliActions` (las suites) retornan antes de `$app->run()`
   (`index.php:933-940`) y corren SIN sesión —ese es su contrato—; las TAREAS del sistema de rutas pasan por el
   middleware y reciben la sesión automática de root (`index.php:317`). **Punto único de fallo:** `TerminalData` declara
   `REMOTE_ADDR=127.0.0.1`; sin ella `BaseToken::aud()` revienta y caen todas las tareas. Suites que lo fijan:
   `core/session-terminal-root` (11) y `core/session-contract` (26), las dos provocadas.
   **El nombre de la sesión (tramo 1 de 11a, `c8030790`).** `SessionToken::tokenName()` es la única puerta; la
   constante `TOKEN_NAME` pasa a ser el valor por defecto y `TOKEN_NAME_CONFIG` (`session_token_name`) la clave. Pasan
   por ella `getJWTReceived()` (cabecera y cookie), `index.php:321` y `:444`, y **las dos listas de cabeceras permitidas
   de CORS** (`bootstrap.php:200`, `containers.php:267`), que sin eso bloquearían al cliente de otro origen. El
   JavaScript lo recibe por `add_to_front_configurations()` → `<meta name="front-configurations">` →
   `pcsphpGlobals.frontConfigurationsFromBackend`, y solo conserva un `defaultJWTAuthName` como último recurso.
   Criterio de validez: `/^[A-Za-z0-9_]{1,64}$/` (el guion NO, porque PHP lo convierte en guion bajo al leer la
   cabecera y el nombre casaría por la cookie y no por la cabecera). **Puerta: comprobación 33 de `verify-integrity`.**
   **Contrato del token (tramo 2, `61fc0618`): dice QUIÉN, nunca QUÉ ES.** La carga es solo `id`; `validType()` y
   `hasType()` de la clase anónima de `index.php` se retiraron —dejarlas habría echado a todo el mundo, porque un token
   nuevo no lleva tipo— y `isValid()` es `hasID()`. Lo que un usuario ES se lee de la fila, releída en cada petición.
   Los tokens anteriores valen con su `type` ignorado. Censo de `#421`: 34 sitios leen contenido de token, 14 son
   lecturas y **ninguna concede nada con el tipo**; punto ciego del instrumento (no sigue propiedades de objeto)
   cerrado a mano. Pruebas: `core/session-contract` i1-i5 (i5 lee los tres emisores en el código fuente) y j1-j2 para
   `SessionTokenIsolated`.
   **Sesiones aisladas (`486d1430`, suite `core/session-isolated`, 23).** `SessionTokenIsolated` + su pareja del
   navegador son superficie PÚBLICA para quien clona, no código muerto. Divergen de `SessionToken` en seis cosas, todas
   fijadas por prueba: **el aislamiento es del canal, no criptográfico** (misma `app_key` por defecto; medido: un token
   de usuario en el canal aislado SE ACEPTA); duración en MINUTOS (60) frente a segundos (31 días); `aud` apagado
   frente a encendido; fecha mínima `2024-12-13` frente a `1990-01-01`; fecha mínima **por instancia** frente a
   `static` —de ahí que en la de usuarios ponerla a «ahora» expulse a todos—; y de instancia frente a estática.
   **Limpieza del 2026-09-23 (`ba15be99`)**: los dos `if (!$isLocal)` de `CustomSlimErrorHandler` posteriores al
   bloque de producción eran **inalcanzables** desde P56 —ese bloque tiene dos salidas y las dos son `return`—, y se
   retiraron tras demostrarlo por lectura y por provocación (se instrumentaron para lanzar si se ejecutaban, en los dos
   estados de `$isLocal`: no se ejecutaron). Queda una nota de dos líneas en su lugar; el primero que leyó el archivo
   por trozos dedujo de ellos algo falso.
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
11. **`config/final-configurations.php`** — carga `core/extensions/*` (del framework), después `config/extensions/*` (del clon) y, si aún existe, la carpeta vieja `final-configurations-includes/` con un aviso del sistema.
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

## Eventos — `BaseEventDispatcher`

**Tiene DOS APIs, no una**, y se confunden con facilidad porque los nombres se parecen.

| API | Para qué | Firma |
| :-- | :-- | :-- |
| `defaultListen` / `defaultDispatch` | Los **tres eventos del framework**, sin contexto | `defaultListen(string $nombre, callable $cb)` |
| `listen` / `dispatch` | Eventos **por clase**: el contexto es el FQCN | `listen(string $evento, callable $cb, ?string $contexto)` |

También hay `hasListeners()` y `hasDefaultListeners()`, que es como `index.php` decide si vale la
pena disparar.

### Los tres eventos del framework

| Constante | Cuándo se dispara |
| :-- | :-- |
| `EVENT_INIT_ROUTES_NAME` | En `index.php:868`, **al terminar de registrar todas las rutas** |
| `EVENT_ADD_DYNAMIC_TRANSLATIONS_NAME` | En `add-dynamic-translations.php:106`, tras cargar las traducciones dinámicas desde base |
| `EVENT_CLI_ROUTE_NOT_FOUND_NAME` | En `index.php:942`, cuando una acción de CLI no resuelve |

### Los cuatro eventos de ciclo de vida de los mappers

`BaseEntityMapper` emite, **con la clase del mapper como contexto**:

| Evento | Dónde | Cuándo |
| :-- | :-- | :-- |
| `saving` / `saved` | `BaseEntityMapper.php:123, 126` | Antes y después de `save()` |
| `updating` / `updated` | `BaseEntityMapper.php:139, 142` | Antes y después de `update()` |

```php
BaseEventDispatcher::listen('updated', function ($mapper) {
    //…
}, NewsMapper::class);          //<-- el contexto es la clase, no una cadena libre
```

**Su único usuario en todo el proyecto es `SystemApprovalManager.php:55`**, que escucha `updated`
para mover el estado de una aprobación. `saving`, `saved` y `updating` **no los escucha nadie**.

> **ADVERTENCIA, y no es menor**: `saved` y `updated` se disparan cuando `save()`/`update()`
> devuelven `true`, y eso significa **«la sentencia se ejecutó»**, no «cambió una fila» — ver
> [06-orm-mappers.md](./06-orm-mappers.md). Así que **`updated` salta también cuando no cambió
> nada**, y el escuchador de aprobaciones actúa igual.

### Dónde se registran los oyentes

En `app/core/extensions/` (los del framework) y `app/config/extensions/` (los del clon), que el bucle de extensiones de
`final-configurations.php` incluye **enteros
y por directorio**, justo antes de empezar a manejar rutas. El archivo previsto para esto es
`event-listeners.php`.

## Errores y logs

- `CustomSlimErrorHandler` (en `Core/CustomErrorsHandlers/`) genera la respuesta de
  error, en JSON si el request lo pide.
- `log_exception($e)` es el helper para registrar excepciones.
- Logs en `src/app/logs` (`LOG_ERRORS_PATH`), backups en `logs/olds`
  (`LOG_ERRORS_BACKUP_PATH`). Desde 7.0.6 existe además un `error.plain.log` de
  lectura fácil.
- La vista de log de errores del panel es la ruta `admin-error-log` (solo roles
  superiores).

## Revocar una sesión (ADR 0026, fase A, 2026-09-24)

Hasta el 2026-09-24 una credencial valía hasta caducar: no había forma de echar a nadie antes. Hoy hay **dos marcas de
tiempo**, y la regla es la misma en las dos: **un token vale si nació DESPUÉS de la marca.**

| Nivel | Dónde vive | Qué hace |
| :-- | :-- | :-- |
| Un usuario | columna `sessionsValidFrom` de `pcsphp_users` (`DATETIME NULL`) | Ponerla a «ahora» cierra **todas** las sesiones de ese usuario. `NULL` = sin revocaciones |
| Todo el sistema | configuración `session_minimum_date` | Moverla echa fuera a **todo el mundo** |

- **La comprobación del usuario vive en `src/index.php`**, dentro del `if ($user !== null)`, **no** en
  `SessionToken::isActiveSession()`: ese método es estático y no conoce al usuario, y ahí el usuario **ya está leído de
  la base**, así que no cuesta ninguna consulta.
- **La regla está escrita UNA vez: `SessionToken::isCreatedAfterMarks(?int $tokenCreated, ?string
  $userSessionsValidFrom): bool`** (bloque CD, 2026-09-30). `true` solo si hay fecha entera, es posterior a la marca
  global y, si el usuario tiene marca, posterior también a la suya. **Mismo segundo: `false`.** Una marca de usuario
  que no es una fecha niega. La usan los dos sitios de `index.php` que deciden; `isActiveSession()` conserva su propia
  comparación con la marca global, con el mismo operador (`>`).
- **TRAMPA CUBIERTA · la lista `$ignoreExpiredForRoutesName` de `index.php`** renueva un token caducado en las rutas
  que nombre (de fábrica, sin ninguna ruta real: solo el marcador de la plantilla). Fabrica un token con fecha de AHORA, así que la revocación se decide ANTES, con el
  `iat` del token viejo. Quien mueva esa pregunta después de `generateToken()` reabre la puerta trasera: lo caza la
  sección X de `core/session-revocation`. La firma del token caducado SÍ se comprueba (`BaseToken::decode()` verifica
  antes de mirar el tiempo; `$ignore_expired` solo salta `exp`).
- **Alcanza también a la identidad suplantada**: root que suplanta a alguien con la sesión revocada no entra como él,
  porque la sustitución de `$user` ocurre antes.
- **Sin una fecha de creación entera, se niega.** Es la misma trampa de `getCreated()` que ya trataba
  `isActiveSession()`: puede devolver algo que no es un entero, y entonces no hay nada que comparar.
- **ACOPLAMIENTO MEDIDO (2026-10-02, `#769`): la revocación leía su dato del bloque que ESCRIBÍA EL REGISTRO.** La
  decisión de revocar tomaba `$expiredSessionDataToJSON['decodeToken']`, un array que construía el §4 para volcarlo a
  disco. Al sacar ese registro a su clase, la variable desaparecía y **la comprobación de revocación habría decidido
  con `null`**. **Y el daño es el CONTRARIO del que parece** (medido el 2026-10-02, `#773`):
  `isCreatedAfterMarks(null, …)` devuelve `false` en su primera línea, así que sin dato **se niega siempre**, y lo que
  se rompe es la renovación de las rutas de excepción, **en silencio**. La puerta trasera llegaría el día que alguien
  invierta ese `return false`. **Lo cazó `bin/phpstan`**
  (`variable.undefined`), no una prueba. **Y medido el 2026-10-02 (`#771`): con la variable presente pero NULA,
  `core/session-revocation` pasa entera (93/93).** O sea que esa decisión **no tiene prueba para el dato vacío**: la
  red es el análisis estático, y solo porque la variable desaparecía. Hoy el token caducado se decodifica siempre en `$expiredToken`, al margen de
  que el registro esté encendido, y la revocación lee de ahí. **Lección: una guarda no puede colgar de una variable
  que existe solo porque algo se estaba registrando.**
- **El registro de caducidades ya no vive en `index.php`**: `PiecesPHP\Core\Logs\ExpiredSessionsLog`, apagado por
  omisión (`log_expired_sessions`), una línea por caducidad, con rotación por tamaño y **sin el token** —la clase no
  lo recibe, así que no puede escribirlo—. Ver `source-docs` y el ADR 0039.

**Dos constantes que se parecen y NO son lo mismo:**

- `SessionToken::DEFAULT_MINIMUM_DATE_CREATED` = `1990-01-01`: centinela histórico de «sin marca». **La congela la
  suite `core/session-isolated`**, que la usa para documentar que la sesión aislada tiene su propia fecha. Quien la
  cambie rompe esa suite (`#513`).
- `SessionToken::MINIMUM_DATE_DEFAULT` = `2026-03-02 00:00:00`: el valor por defecto de la **marca global**, el que
  antes vivía como literal en `src/app/config/roles.php`. Una configuración inválida —vacía, un número, un texto que no
  es fecha— cae a este valor y avisa una vez. **Una fecha del futuro SÍ vale**: es justo el «echar a todos».

**Lo que esto NO hace todavía:** la marca por usuario **no distingue dispositivos**, así que «cerrar esta sesión» y
«cerrar todas las mías» son hoy la misma orden. La sesión suelta necesita un identificador por sesión y su almacén:
es la fase B del ADR 0026.

**Y los tres `setMinimumDateCreated(new \DateTime())` de `index.php` se retiraron** (`#513`): no revocaban nada —la
estática muere con el proceso— y lo que cortaba la petición era el `$isActiveSession = false` de al lado, que se queda.

**Puerta que salta sola:** una columna nueva en `pcsphp_users` hace fallar `verify-integrity` hasta que se declara si
viaja al navegador (`files/dev/login-user-data-declared.json`). `sessionsValidFrom` está en `excluded`: es una
condición de acceso, no un dato del usuario.

