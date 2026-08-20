# 04 — Configuración

Todo vive en **`src/app/config/`**. La configuración se lee y escribe con los
helpers globales `get_config($name)` / `set_config($name, $value)` (y
`Config::get_config()` / `Config::set_config()`).

> Orden de precedencia efectivo: `config.php` → `Config::init()` →
> **configuraciones guardadas en base de datos** (`AppConfigModel`, si
> `APP_CONFIGURATION_MODULE`) → `final-configurations-includes/`.
> Es decir, lo que el usuario cambia en el panel gana sobre el archivo.

## Archivos

### `critical-definitions.php`
Primer archivo del arranque, **sin acceso a helpers**. Define la constante array
`CRITICAL_CONSTANTS`. Hoy: `['ORGANIZATIONS_MODULE' => true]`.

### `config.php`
Configuración maestra. Contiene:

- Zona horaria (`date_default_timezone_set('America/Bogota')`).
- `domain`, `domain_protocol`, `base_domain_path`, `base_domain_url`, `base_url`
  (derivados de `$_SERVER`).
- `default_lang` (`es`), `cache_stamp_render_files`.
- `title_app`, `owner`, `keywords`, `description`, `developer`.
- **Paleta de marca**: `main_brand_color`, `second_brand_color`, `font_color_one/two`,
  `menu_color_background|mark|font`, `meta_theme_color`, `bg_tools_buttons`,
  `body_gradient`, `font_family_global`, `font_family_sidebars`. Estos valores se
  convierten en variables CSS (ver [09-frontend-assets.md](./09-frontend-assets.md)).
- **Secretos**: `app_key` (usada por `BaseToken` y `BaseHashEncryption`),
  `CronJobKey`, `mailjet`, `OpenAIApiKey`, `MistralAIApiKey`, `GroqAPIKey`,
  `SurveyJSKey`, `Azure`, `osTicketAPI(Key)`, `LabsMobileAPIKey`.
  En el repo están como `TODO:secret` / placeholders.
- `statics_path` → `src/statics`.
- `GEO_IP.custom_directory`.
- Un listener de ejemplo sobre `BaseEventDispatcher::EVENT_INIT_ROUTES_NAME`.

### `database.php`
Perfiles de conexión multi-grupo:
`$config['database'][GRUPO][driver|db|user|password|host|charset]`.
El grupo por defecto es `'default'`. El archivo bifurca con `is_local()` para
separar credenciales de desarrollo y producción. Charset `utf8mb4`.

### `constants.php`
**Banderas de activación de módulos** (`define(...)`) + constantes varias. Las más
importantes:

| Constante | Módulo/uso |
| :-- | :-- |
| `APP_CONFIGURATION_MODULE` | Configuraciones editables desde BD/panel |
| `LOCATIONS_ENABLED`, `LOCATIONS_LANG_GROUP` | Ubicaciones |
| `IMPORTS_MODULE_ENABLED` | Importadores |
| `PIECES_PHP_DYNAMIC_IMAGES_ENABLE` | Imágenes dinámicas |
| `ACTIVE_TIMER` | Temporizadores |
| `FILE_MANAGER_MODULE` | Gestor de archivos (elFinder) |
| `PUBLICATIONS_MODULE`, `NEWS_MODULE`, `IMAGES_REPOSITORY` | Contenidos |
| `DOCUMENTS_MODULE_ENABLE` | Documentos |
| `FORMS_MODULE_ENABLE` (+ `_CATEGORIES_`, `_DOCUMENTS_TYPES_`) | Formularios |
| `EVENTS_LOG_MODULE` | Log de acciones |
| `NEWSLETTER_MODULE` | Suscriptores |
| `API_MODULE`, `API_CRONJOBS`, `API_USERS`, `API_REPORTS` | API |
| `API_TRANSLATION_MODULE`, `API_AI_TRANSLATIONS_ACTIVE` | Traducción por API/IA |
| `ORGANIZATIONS_MODULE` | = `CRITICAL_CONSTANTS['ORGANIZATIONS_MODULE']` |
| `SYSTEM_APPROVALS_MODULE` | Aprobaciones |
| `CONTENT_NAVIGATION_HUB_MODULE` | Hub de contenidos |
| `HOME_IMAGE_BANNER` | Banner de inicio |

Otras: `ADMIN_PATH_VIEWS = 'panel'`, `ADMIN_AREA_PATH_JS`,
`CONNECT_AS_ANOTHER_USER_ID_COOKIE_NAME`/`_GET_PARAM_NAME`,
`ROOT_ORIGINAL_ID_CONFIG_NAME`, `ROOT_ID_AS_CONNECT_CONFIG_NAME`,
grupos de idioma (`ADMIN_MENU_LANG_GROUP`, `GENERAL_LANG_GROUP`, `LANG_GROUP`,
`GLOBAL_LANG_GROUP`, `MAIL_TEMPLATES_LANG_GROUP`, `USER_LOGIN_LANG_GROUP`,
`LOGIN_REPORT_LANG_GROUP`, `MAILING_GENERAL_LANG_GROUP`,
`SUPPORT_FORM_ADMIN_LANG_GROUP`, `CROPPER_ADAPTER_LANG_GROUP`),
constantes de IA (`AI_OPENAI`, `AI_MISTRAL`, `AI_MODELS`, `TRANSLATION_AI_LIST`) y
datos de proyecto (`PHONE_AREA_CODES`, `NATIONALITIES`, `CURRENCIES`).

> **Para desactivar un módulo, se pone su constante en `false`.** La clase
> `<Modulo>Routes` la lee como `const ENABLE = X_MODULE;` y no registra nada.

### `roles.php`
Sistema de permisos. Define `$config['roles']['active']`,
`$config['roles']['types']` (code / name / all / allowed_routes),
`$config['roles']['baseInitialSegmentedPermissions']`
(`generals`, `administratives`, `superiors`), `control_access_login` y `admin_url`.
También fija la fecha mínima de validez de los tokens de sesión con
`SessionToken::setMinimumDateCreated(...)` — **subir esa fecha invalida todas las
sesiones activas.** Ver [05-routing-y-permisos.md](./05-routing-y-permisos.md).

### `routes.php`
Orquestador central. Crea los `RouteGroup` (prefijados con `get_config('prefix_lang')`)
y delega en `XRoutes::routes(...)` / `XController::routes(...)` de cada módulo.
Grupos definidos: `/terminal`, `/core`, `/components-provider`, `/organizations`,
`/admin`, `/configurations`, `/users/`, `/tickets`, `/timing`, `/locations`,
`/importers`, `/avatars`, `/statics/`, `/tokens`, y la zona pública (raíz).
**Se declara de más específico a menos específico.**

### `containers.php`
Definición de servicios del DI (`$container_configurations`):

- `foundHandler` — se ejecuta al resolver una ruta; construye las **variables CSS
  globales** (`CSSVariables::instance('global')`) a partir de la paleta de `config.php`.
- `notFoundHandler` / `forbiddenHandler` — pantallas 404 / 403.
- `staticRouteModulesResolver` — resuelve la URL base de los estáticos de cada módulo.
- `params` — parámetros de request.
- `cors` — cabeceras CORS (se aplica si `API_MODULE`).

### `assets.php`
Registro de librerías front:
`$assets['nombre']['css'|'js'|'plugins'] = [...]`. Consumido por los helpers
`import_*()` de `AppHelpers.php`.

### `autoloads.php`
Autoloaders adicionales. Actualmente uno solo, y es clave:

```php
return [ ['psr4' => true, 'path' => app_basepath('classes')] ];
```

→ **`src/app/classes` es la raíz PSR-4 de todos los módulos**. Un namespace
`News\Controllers\NewsController` se resuelve a
`src/app/classes/News/Controllers/NewsController.php`.
(Composer solo mapea `PiecesPHP\Core\` → `app/core/psr4/PiecesPHP/Core` y
`PiecesPHP\ComposerTasks\` → `tasks/`.)

### `lang.php`
Idiomas soportados y estrategia de detección. Ver [08-i18n.md](./08-i18n.md).

### `menu.php`
Construcción del sidebar administrativo con `MenuGroupCollection`, `MenuGroup`,
`MenuItemCollection`, `MenuItem`. Cada entrada trae `visible` calculado con
`Roles::hasPermissions()` o `Controller::allowedRoute()`, y `href` con
`Controller::routeName()`. Los módulos **añaden sus propias entradas** desde
`<Modulo>Routes::init()` usando `get_sidebar_menu()->addItem(new MenuGroup([...]))`
con una `position` numérica.

### `cookies.php`
Configuración de cookies (`Secure`, `HttpOnly`, `SameSite`). Helpers:
`setCookieByConfig()`, `getCookie()`.

### `functions.php`
Funciones globales específicas de la instancia (helpers de DataTables, selectores
de usuario, etc.).

### `final-configurations.php` + `final-configurations-includes/`
Se cargan al final del arranque, después de las rutas:

| Archivo | Para qué |
| :-- | :-- |
| `add-dynamic-translations.php` | Inyecta traducciones dinámicas (desde BD) |
| `api-keys.php` | Llaves de servicios externos (Mapbox, GeoIP, reCAPTCHA…) |
| `cli-actions.php` | Acciones CLI personalizadas (`CliActions::make()`) |
| `cronjobs.php` | Tareas programadas (`CronJobTask::make()`) |
| `event-listeners.php` | `BaseEventDispatcher::listen()` |
| `mailing.php` | SMTP y remitentes |
| `protected-files.php` | `ProtectFileMiddleware::protect()` |
| `queues.php` | Handlers de cola (`QueueTask::make()`) |
| `patches_composer_dependencies.php` | Parches a dependencias |
| `set-additional-configurations.php` | Ajustes finales / parches de configuración |

**Estos son los puntos de extensión preferidos**: si necesitas añadir un cronjob,
una cola, un listener o una acción CLI, va aquí, no en el núcleo.

## Secretos

- `getKeyFromSecureKeys()` (helper de `AppHelpers.php`) lee del directorio raíz
  `secure-keys/`. Úsalo en lugar de hardcodear llaves nuevas en `config.php`.
- `app_key` es el secreto raíz: firma tokens (`BaseToken`) y cifrado
  (`BaseHashEncryption`). Cambiarla invalida sesiones y tokens existentes.
