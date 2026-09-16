# 02 — Estructura de directorios

## Raíz del repositorio

```
.agents/            Skills y contexto para agentes de IA (este directorio)
bin/                Ejecutables y herramientas de desarrollo
databases/          Scripts SQL (estructura, datos, vistas, funciones, locations)
files/              Recursos que acompañan al framework; files/dev/, solo datos de instrumentos (ver abajo)
node_modules/       Dependencias front (no versionado en despliegue)
secure-keys/        Llaves/secretos leídos con getKeyFromSecureKeys()
source-docs/        Fuentes MkDocs de la documentación del proyecto y de la API (source-docs/api/)
src/                RAÍZ DE LA APLICACIÓN WEB (document root de Apache)
tasks/              TasksManager.php — tareas post-install/post-update de Composer
CHANGELOG.md        Historial de versiones (muy detallado)
IGNORE.md           Notas del autor (no versionado)
PHPStanResult*.txt  Salida del análisis estático
package.json        Dependencias y scripts de front
permissions-and-property.sh  Ajuste de permisos/propiedad en despliegue
```

### `files/` y `files/dev/` (ADR 0006)

Una sola razón de ser por carpeta:

- **`files/`**: recursos que acompañan al framework y usa quien lo clona.
- **`files/dev/`**: solo datos que escriben o leen instrumentos. **Ningún documento para
  personas**: esos van a `source-docs/`, o a `.agents/` si son para agentes o para el PO.

Lo que se añada a `files/dev/` tiene que tener un instrumento que lo escriba o lo lea.

| Ruta | Qué es |
| :-- | :-- |
| `files/Webflow/` | Esqueleto e instrucciones (`Intrucciones.md`) para integrar un export de Webflow |
| `files/CliScripts/` | Guiones PHP sueltos. Resuelven la raíz con `__DIR__ . '/../../'`: **no se cambian de profundidad** |
| `files/TraduccionesPublicas.json` | Contiene `{}` y nadie lo nombra literalmente. Sin verificar si se carga por un nombre compuesto |
| `files/dev/` | Datos de instrumentos: tabla siguiente |

`files/dev/`, medido el 2026-09-14 buscando el nombre de cada archivo en `bin/` y `src/` con
`git grep -l`. La columna dice quién lo usa, no si lo escribe o lo lee:

| Archivo | Quién lo usa |
| :-- | :-- |
| `deprecated-functions.json` | `bin/cli verify-integrity` |
| `forbidden-routes.json` | `bin/tools/forbidden-routes.php`, `bin/walk-routes`, `verify-integrity` |
| `ignored-returns-baseline.json` | `bin/censo-retornos-ignorados` |
| `integrity-signatures.json` | `verify-integrity` (firmas de funciones y métodos) |
| `narrative-comments.json` | `verify-integrity` (comentarios narrativos) |
| `orphan-lang-keys-baseline.json` | `bin/censo-claves-huerfanas` |
| `phpstan-universe.json` | `verify-integrity` (universo de PHPStan) |
| `public-routes-in-guarded-modules.json` | `verify-integrity` |
| `reading-forms-baseline.json` | `bin/censo-formas-de-lectura` |
| `shared-toolchain.json` | `verify-integrity` (instrumental común de los cinco repositorios) |
| `sql-concat-baseline.json`, `sql-concat-declared.json` | `bin/censo-sql-concatenado`. El segundo, también la suite `UnitTest-SqlPlaceholders` |
| `volatile-state.json` | `bin/walk-attribute`, `verify-integrity` (tablas volátiles) |
| `route-inventory.json` (ignorado) | `RouteInventoryTask` de `bin/cli`, `bin/walk-routes`, `bin/walk-attribute`, `verify-integrity` |
| `last-restore.json` (ignorado) | `DbRestoreTask` de `bin/cli`, `bin/walk-attribute` |
| `snapshots/` (contenido ignorado) | La foto de E3, `bin/cli snapshot` (18, T44). Solo se versiona su `.gitignore` |

Quedan en la raíz a propósito, y no en `files/dev/`, los `PHPStanResult.*`: son el instrumental
común de los cinco repositorios (ADR 0006).

### `bin/`

| Item | Qué es |
| :-- | :-- |
| `cli` | Atajo bash: `cd src && php index.php cli --local $@` |
| `phpstan`, `phpstan.neon`, `phpstan.services.neon` | Análisis estático |
| `phpstan-process-result.php` | Genera `PHPStanResult.Summary.txt` |
| `rector` | Refactor automatizado |
| `package-css` | Utilidad de empaquetado CSS |
| `pieces-completion.bash` / `.zsh` | Autocompletado de comandos CLI |
| `node/` | Scripts node (ej. `copyDependencies.sh`, usado por `npm run deps`) |
| `Preview/` | Espejo de la estructura de `src/app` usado como referencia/preview |
| `tools/` | Proyecto composer aparte con herramientas (se autocarga si existe) |

## `src/` — la aplicación

```
src/
├── index.php        Front Controller único (web y CLI). ~1030 líneas.
├── .htaccess        Rewrite, CORS/CSP/HSTS, bloqueo de archivos sensibles, gzip
├── composer.json    Dependencias PHP + autoload PSR-4
├── gulpfile.js      Tareas de build de front
├── robots.txt, sitemap.xml, permissions.sh
├── adminer/         Adminer embebido (se elimina en despliegue)
├── tmp/             Temporales
├── vendor/          Dependencias Composer
├── statics/         Recursos públicos servidos vía ServerStatics
└── app/             Lógica de la aplicación
```

### `src/app/`

```
app/
├── cache/       Caché (escribible)
├── classes/     ⭐ MÓDULOS PSR-4 — aquí vive casi todo el código de negocio
├── config/      Configuración de la instancia
├── core/        Núcleo del framework
├── lang/        Traducciones globales + dinámicas + faltantes
├── logs/        Logs de errores (escribible)
├── model/       Modelos "de sistema" (App\Model\*)
├── view/        Vistas y layouts de sistema
└── controller/  Controladores "de sistema" (App\Controller\*)
```

#### `app/core/`

```
core/
├── bootstrap.php                 Arranque: CLI, errores, autoloads, config, constantes
├── autoload.php                  Autoloader propio
├── custom-autoloads-config.php   Procesa config/autoloads.php
├── Config.php                    Clase Config (config + i18n + rutas base)
├── AppHelpers.php                ⭐ ~100 KB de funciones globales (helpers)
├── Utilities.php                 Utilidades tempranas (is_local(), etc.)
├── psr4/PiecesPHP/               Namespace PiecesPHP\Core y PiecesPHP\Terminal
├── system-controllers/           Controladores internos del framework
└── system-views/                 Vistas internas (errores, etc.)
```

**`core/psr4/PiecesPHP/Core/` (clases raíz)**: `BaseController`, `BaseModel`,
`BaseEntityMapper`, `BaseEventDispatcher`, `BaseHashEncryption`, `BaseToken`,
`BaseMongoModel`, `FlashMessages`, `Mailer`, `MailjetHandler`, `PDFManager`,
`Roles`, `Route`, `RouteGroup`, `ServerStatics`, `SessionDataHandler`,
`SessionToken`, `SessionTokenIsolated`, `StringManipulate`.

**Subnamespaces**: `Cache`, `ConfigHelpers`, `CustomErrorsHandlers`, `Database`
(+ `Export`, `Meta`), `Email`, `Exceptions`, `Forms`, `Helpers/Directories`,
`Http` (`HttpClient`, `FreezeRequest`), `Importer`, `Menu`, `Pagination`,
`PHPStan`, `Routing` (+ `Slim3Compatibility`), `Sitemap`, `Utilities`,
`Validation`.

**`core/psr4/PiecesPHP/Terminal/`**: `CliActions`, `CronJobTask`, `QueueTask`,
`QueueHandlerResponse`, `Tasks/{Interfaces,Abstracts}`.

> Fuera de `psr4/` hay clases del namespace raíz `PiecesPHP\` cargadas por el
> autoloader propio: `Cli`, `TerminalData`, `LangInjector`, `CSSVariables`, `Test`…

#### `app/config/`

`assets.php`, `autoloads.php`, `config.php`, `constants.php`, `containers.php`,
`cookies.php`, `critical-definitions.php`, `database.php`, `functions.php`,
`lang.php`, `menu.php`, `roles.php`, `routes.php`, `final-configurations.php` y
`final-configurations-includes/`. Ver [04-configuracion.md](./04-configuracion.md).

#### `app/classes/` — los módulos

Cada subcarpeta es un módulo autocontenido en su propio namespace raíz
(autoload PSR-4 con `app/classes` como base, vía `config/autoloads.php`).
Ver [07-modulos.md](./07-modulos.md) para el inventario y la anatomía.

#### `app/model/` y `app/controller/` (namespace `App\Model` / `App\Controller`)

Piezas de sistema que no llegaron a ser módulos:

- Modelos: `UsersModel`, `AppConfigModel`, `AvatarModel`, `TokenModel`,
  `RecoveryPasswordModel`, `LoginAttemptsModel`, `TicketsLogModel`,
  `TimeOnPlatformModel`, `UserProblemsModel`.
- Controladores: `AdminPanelController` (⭐ base de casi todos los controladores
  administrativos), `PublicAreaController`, `UsersController`, `AppConfigController`,
  `AvatarController`, `TokenController`, `GenericTokenController`,
  `RecoveryPasswordController`, `LoginAttemptsController`, `ContactFormsController`,
  `TimerController`, `UserProblemsController`.

#### `app/view/`

```
view/
├── layout/       Layout general
├── panel/        Zona administrativa (ADMIN_PATH_VIEWS = 'panel')
│   ├── layout/, pages/, built-in/
├── pages/        Páginas públicas (+ generic-views/)
├── usuarios/     Vistas del sistema de usuarios (forms por tipo, mails, problems)
├── webflow/      Plantillas exportadas desde Webflow (layout/, pages/)
└── mailing/      Plantillas de correo
```

Las vistas **de módulo** no viven aquí: viven en
`app/classes/<Modulo>/Views/` y el controlador las apunta con
`$this->setInstanceViewDir(__DIR__ . '/../Views/')`.

#### `app/lang/`

Archivos por idioma en la raíz (`es.php`, `en.php`, `default.php`) más subcarpetas por
grupo temático
(`usersModule`, `adminZone`, `sidebarAdminZone`, `app_config`, `loginReport`,
`mailingGeneral`, `public`, `about-framework`, `files`, `FileValidator`,
`replace-generic-titles`), y las carpetas operativas
`dynamic-translations/` (traducciones editables desde el panel) y
`missing-lang-messages/` (registro de etiquetas sin traducir).

### `src/statics/`

```
admin-area/       JS/CSS de la zona administrativa (ADMIN_AREA_PATH_JS)
core/             JS del núcleo (incluye core/js/user-system/*)
css/ sass/ js/    Estilos y scripts globales (css se genera desde sass)
features/         Recursos por característica
fonts/ images/    Tipografías e imágenes
plugins/          Librerías de terceros
login-and-recovery/  Recursos de login/recuperación
filemanager/      Área de elFinder (escribible)
uploads/          Subidas de usuarios (escribible)
server-delegated/ Archivos gestionados por lógica interna (escribible)
wf/               Recursos Webflow
```

## Directorios que deben ser escribibles

`src/app/logs`, `src/app/cache`, `src/app/lang/dynamic-translations`,
`src/app/lang/missing-lang-messages`, `src/tmp`, `dumps`, `src/statics/uploads`,
`src/statics/css` (si se compila SASS en servidor), `src/statics/server-delegated`,
`src/statics/filemanager`. Recomendado `2775` dirs / `664` files con SetGID.
