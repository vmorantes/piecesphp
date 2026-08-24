# 07 — Módulos

Todo el código de negocio vive en `src/app/classes/<Modulo>/`, con
`src/app/classes` como raíz PSR-4 (ver `config/autoloads.php`). El namespace raíz
de cada módulo **es el nombre de su carpeta**.

> **Referencia canónica: `Publications`.** Es el módulo más completo y mantenido;
> cubre zona admin y pública, sub-entidad, adjuntos, traducción de campos, caché y
> aprobaciones. Cuando dudes de un patrón, míralo ahí primero.
> `News` se usa abajo como ejemplo por ser el **caso mínimo** (solo-admin), no por
> ser el mejor: está marcado «por renovar».

## Anatomía estándar (caso mínimo: módulo solo-admin)

```
src/app/classes/News/
├── NewsRoutes.php          ⭐ Punto de entrada del módulo
├── NewsLang.php            Inyector de traducciones (extiende PiecesPHP\LangInjector)
├── Controllers/
│   ├── NewsController.php          Controlador principal (extiende AdminPanelController)
│   ├── NewsCategoryController.php  Sub-entidad
│   └── HelperController.php        Utilidades compartidas del módulo
├── Mappers/
│   ├── NewsMapper.php
│   ├── NewsCategoryMapper.php
│   └── NewsReadedMapper.php
├── Views/
│   ├── news/{list.php, forms/{add,edit}.php, public/util/item.php}
│   └── categories/{list.php, forms/{add,edit}.php}
├── Statics/
│   ├── sass/news.scss      Fuente
│   ├── css/news.css        Compilado (+ .css.map, .gitignore)
│   └── js/{NewsAdapter.js, news/{list,add-form,edit-form,delete-config}.js,
│            categories/{...}}
├── Exceptions/{SafeException.php, DuplicateException.php}
└── lang/{es.php, en.php, fr.php, ...}  + lang/files/
```

Variantes que aparecen en otros módulos: `Util/`, `Enums/`, `Managers/`,
`Queries/`, `Adapters/`, `ORM/`, `SubMappers/`, `lang/lang-public/` (traducciones de
la zona pública), `Views/mailing/`.

### Caso completo: `Publications`

Añade sobre el esqueleto anterior todo lo que un módulo puede necesitar:

```
src/app/classes/Publications/
├── PublicationsRoutes.php      routes(RouteGroup $admin, RouteGroup $public)
├── Controllers/
│   ├── PublicationsController.php          admin (extiende AdminPanelController)
│   ├── PublicationsCategoryController.php  sub-entidad con CRUD propio
│   └── PublicationsPublicController.php    público (extiende BaseController)
├── Mappers/
│   ├── PublicationMapper.php
│   ├── PublicationCategoryMapper.php
│   └── AttachmentPublicationMapper.php     relación 1-N de adjuntos
├── Util/
│   ├── AttachmentPackage.php               empaquetado de adjuntos
│   └── FieldTranslationUtility.php         traducción de campos de BD
├── Views/publications/{list, forms/, public/{list,single,util/}}
├── Statics/js/publications/{...,public/{listing,detail}.js}
└── lang/{6 idiomas} + lang/lang-public/{6 idiomas}
```

Además usa `CacheControllersManager` para cachear respuestas y se integra con
aprobaciones vía `SystemApprovals\Util\Packages\PublicationsApprovalHandler`.

## `<Modulo>Routes` — el contrato

Es la única clase que `config/routes.php` conoce. Responsabilidades:

```php
class NewsRoutes
{
    const ENABLE = NEWS_MODULE;           // bandera desde config/constants.php
    private static $init = false;

    public static function routes(RouteGroup $groupAdministration /*, RouteGroup $groupPublic */)
    {
        if (self::ENABLE) {
            // 1. rutas, estáticos, lang y menú. El DDL ya NO se vuelca desde aquí:
            //    se saca con `bin/cli scheme-create module=<Nombre>`
            // 2. delegar en los controladores
            $groupAdministration = NewsController::routes($groupAdministration);
            $groupAdministration = NewsCategoryController::routes($groupAdministration);
            // 3. rutas de estáticos del módulo
            self::staticResolver($groupAdministration);
            // 4. traducciones
            NewsLang::injectLang();
            // 5. inicialización diferida (menú, config de front)
            InvocationStrategy::appendBeforeCallMethod(fn() => self::init());
        }
        return ['groupAdministration' => $groupAdministration];
    }

    public static function init()      // añade su entrada al sidebar
    public static function staticRoute(string $segment = '')  // URL de un estático del módulo
    protected static function staticResolver(RouteGroup $group)
}
```

`staticRoute()` delega en el servicio DI `staticRouteModulesResolver`, y
`staticResolver()` registra dos rutas: `<modulo>/statics/globals-vars.css`
(variables CSS globales) y `<modulo>/statics/[{params:.*}]` (servido con
`ServerStatics::compileScssServe`, que compila SASS al vuelo).

## `<Modulo>Controller` — el patrón

```php
class NewsController extends AdminPanelController
{
    protected static $URLDirectory  = 'news';        // segmento de URL
    protected static $baseRouteName = 'news-admin';  // prefijo de nombres de ruta
    protected static $title = 'Noticia';

    const BASE_VIEW_DIR = 'news';
    const BASE_JS_DIR   = 'js/news';
    const BASE_CSS_DIR  = 'css';
    const LANG_GROUP    = NewsLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();
        self::$title = __(self::LANG_GROUP, self::$title);
        $this->model = (new NewsMapper())->getModel();
        set_title(self::$title);
        $this->setInstanceViewDir(__DIR__ . '/../Views/');
        add_global_asset(NewsRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(NewsRoutes::staticRoute(self::BASE_CSS_DIR . '/news.css'), 'css');
    }

    // Métodos de ruta (todos reciben Request y DEBEN devolver Response):
    public function listView(Request $r, Response $s) { ... }
    public function addForm(Request $r, Response $s)  { ... }
    public function editForm(Request $r, Response $s) { ... }
    public function all(Request $r, Response $s)      { ... }   // JSON
    public function dataTables(Request $r, Response $s) { ... } // DataTablesHelper
    public function action(Request $r, Response $s)   { ... }   // add + edit (POST)
    public function toDelete(Request $r, Response $s) { ... }

    public static function routes(RouteGroup $group)  { ... }
}
```

`routeName()` y `allowedRoute()` **ya no se escriben en el controlador**: los aporta
`PiecesPHP\Core\Routing\ControllerRoutingTrait` junto con `_allowedRoute()`
(ver [05-routing-y-permisos.md](./05-routing-y-permisos.md)). En el controlador solo se
escribe `_allowedRoute()`, **y solo si el módulo tiene reglas de autorización propias**.

Dentro de las vistas y métodos, los assets se cargan con `set_custom_assets()`,
`add_global_asset()` e `import_*()` (ver [09-frontend-assets.md](./09-frontend-assets.md)).

## Inventario de módulos (`src/app/classes/`)

| Módulo | Namespace | Qué hace |
| :-- | :-- | :-- |
| **API** | `API` | Endpoints REST (`APIRoutes`, `APIController`, `Adapters/`, `Exceptions/`). Se monta bajo `/core`. Controlado por `API_MODULE` y flags `API_*` |
| **App** | `App\Locations`, `App\Presentations` | `Locations` = ubicaciones (países/estados/ciudades/puntos) con sus mappers, vistas y lang |
| **ApplicationCalls** | `ApplicationCalls` | Convocatorias (zona admin + pública) |
| **Components** | `Components` | Proveedor de componentes HTML reutilizables (`ComponentProviderRoutes`) |
| **ContentNavigationHub** | `ContentNavigationHub` | Hub centralizado de contenido navegable (contenidos, perfiles, convocatorias) |
| **DataImportExportUtility** | `DataImportExportUtility` | Utilidad genérica de importación/exportación (`ExportHandlers/`) |
| **Documents** | `Documents` | Gestión de documentos |
| **EventsLog** | `EventsLog` | Log de acciones del sistema (tabla `actions_log`) |
| **FileManager** | `FileManager` | Gestor de archivos sobre elFinder |
| **Forms** | `Forms\Categories`, `Forms\DocumentTypes` | Formularios: categorías y tipos de documento |
| **GeoJSONManager** | `GeoJSONManager` | Gestión de capas GeoJSON (usa `piecesphp/geojson`) |
| **GoogleReCaptchaV3** | `GoogleReCaptchaV3` | Integración reCAPTCHA v3 |
| **ImagesRepository** | `ImagesRepository` | Registro/repositorio fotográfico |
| **Importers** | `Importers` | Importadores de datos (`Managers/`, `Controller/`) |
| **InterestResearchAreas** | `InterestResearchAreas` | Áreas de interés/investigación |
| **MySpace** | `MySpace` | "Mi espacio": mi perfil, mi organización, recursos |
| **News** | `News` | Noticias internas + categorías + marcado de leídas |
| **Newsletter** | `Newsletter` | Suscriptores de newsletter |
| **Organizations** | `Organizations` | Organizaciones (gate: `ORGANIZATIONS_MODULE`) |
| **Persons** | `Persons` | Solo estáticos actualmente (módulo pendiente de renovar) |
| **PiecesPHP** | `PiecesPHP\*` | ⭐ Módulos del propio framework, ver abajo |
| **Publications** | `Publications` | Publicaciones + categorías + adjuntos (admin y público) |
| **ReportsManage** | `ReportsManage` | Gestión de reportes (`Queries/`) |
| **SystemApprovals** | `SystemApprovals` | Flujo de aprobaciones transversal (inyecta `systemApprovalStatus` en todos los mappers) + middleware |
| **Terminal** | `Terminal` | `TerminalController`, `QueueJobMapper` y `Tasks/` (tareas CLI) |

### Submódulos dentro de `classes/PiecesPHP/`

| Ruta | Qué es |
| :-- | :-- |
| `PiecesPHP/UserSystem/` | Sistema de usuarios: perfiles (`UserProfileMapper` + `SubMappers/`), autenticación OTP/TOTP, `UserDataPackage`, rutas y vistas |
| `PiecesPHP/LocalizationSystem/` | Gestión de idiomas y traducciones desde el panel (`Packages/`, `Util/`) |
| `PiecesPHP/BuiltIn/Banner/` | Banners de inicio (`HOME_IMAGE_BANNER`) |
| `PiecesPHP/BuiltIn/Helpers/` | Contenidos genéricos (`GenericContentController`, `GenericContentPseudoMapper`) |
| `PiecesPHP/RoutingUtils/` | `DefaultAccessControlModules` — middleware de control de acceso por módulo |

## Módulos de sistema fuera de `classes/`

`App\Controller\*` y `App\Model\*` (`src/app/controller/`, `src/app/model/`):
panel administrativo, usuarios, avatares, configuraciones, tokens, recuperación de
contraseña, intentos de login, tickets, temporizador, problemas de usuario, área
pública.

`AdminPanelController` es la clase base de casi todos los controladores de módulo y
aporta: usuario en sesión (`$this->user`), variables globales de vista, breadcrumbs,
sidebar y las rutas base del panel (`admin`, `admin-error-log`, tickets, usuarios).
