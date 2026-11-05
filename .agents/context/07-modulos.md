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
└── lang/{es.php, en.php}  + lang/files/
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
`ServerStatics::serve`, que sirve el archivo ya compilado por gulp, delegándolo al
servidor web cuando la extensión lo permite).

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

## Toda pantalla nueva lleva su capítulo del manual (ADR 0072)

Una ruta GET con sesión bajo `/admin/` que no sea de las familias de `files/dev/manual-not-screens.json` es una
**pantalla**, y la comprobación 61 de `verify-integrity` la cuenta si ningún capítulo de `src/app/manual/` la cita en
`rutas:`. La cifra es un trinquete (`files/dev/manual-coverage-baseline.json`): una pantalla nueva sin capítulo la
hace caer. El capítulo lo escribe el arquitecto **midiendo la pantalla** (`#2365`: uno escrito de memoria nombró
menús que no existen); se genera con `bin/manual` y lo generado se commitea. Guía: «El manual de usuario del panel», en la de desarrolladores.

## Inventario de módulos (`src/app/classes/`)

| Módulo | Namespace | Qué hace |
| :-- | :-- | :-- |
| **API** | `API` | Endpoints REST (`APIRoutes`, `APIController`, `Adapters/`, `Exceptions/`). Se monta bajo `/core`. Controlado por `API_MODULE` y flags `API_*` |

**Alta pública con organización nueva (P37, `334fcf57`/`9e32f67e`).** `APIController` ya NO fabrica una petición para
crear la organización: llama a `OrganizationsController::createOrganization(array $values): ?OrganizationMapper`, que es
la misma rama que usa la acción HTTP. Trampa que lo rompía (T120): la petición fabricada no llevaba el atributo `route`,
`isEditRoute()` lanzaba, el `catch` respondía sin `orgID` y la línea siguiente leía esa clave sin comprobarla. Reglas
vivas: si la organización falla, no se crea usuario; si el usuario falla, la organización creada se retira con el
`DELETED` del módulo; el NIT lleva marcador (`NIT_WITHOUT_INFORMATION_PREFIX`) porque `save()` lo trata como único; y el
`-10` solo es defecto de `TYPES_USER_DONT_REQUIRE_ORGANIZATION` (P39). Suite `core/api-register` (18), provocada.
**Sin cerrar:** `src/app/config/containers.php:94-100` escribe el `-10` a cualquier usuario sin organización en cada
petición, y `UsersImportDefinition.php:201-205` se lo pone a los tipos que la requieren (decidido y probado). Ver
`../docs/pendientes.md`, punto 73.
| **App** | `App\Locations`, `App\Presentations` | `Locations` = ubicaciones (países/estados/ciudades/puntos) con sus mappers, vistas y lang |
| **Components** | `Components` | Proveedor de componentes HTML reutilizables (`ComponentProviderRoutes`) |
| **ContentNavigationHub** | `ContentNavigationHub` | Solo el mapa de contenidos (`content-navigation-hub-admin-contents-map`). Desaparece en la 9 con el geovisor (ADR 0059): no le añadas nada |
| **Directory** | `Directory` | El Directorio: el listado de personas y organizaciones (`directory-admin-list`), sus datos (`all-profiles-admin-*`) y el perfil público de una persona (`profile-admin-*`) |
| **DataImportExportUtility** | `DataImportExportUtility` | Utilidad genérica de importación/exportación (`ExportHandlers/`) |
| **Documents** | `Documents` | Gestión de documentos |
| **EventsLog** | `EventsLog` | Log de acciones del sistema (tabla `actions_log`) |
| **FileManager** | `FileManager` | Gestor de archivos sobre elFinder |
| **Forms** | `Forms\Categories`, `Forms\DocumentTypes` | Formularios: categorías y tipos de documento |
| **GeoJSONManager** | `GeoJSONManager` | Gestión de capas GeoJSON (usa `piecesphp/geojson`) |
| **GoogleReCaptchaV3** | `GoogleReCaptchaV3` | Integración reCAPTCHA v3 |
| **Importers** | `Importers` | Importadores de datos (`Managers/`, `Controller/`) |
| **MySpace** | `MySpace` | «Mi espacio», solo lo del usuario: Mi perfil, seguridad (2FA) y Mis sesiones (P135 a). No es el inicio del panel ni aloja lo de otros |
| **News** | `News` | Noticias internas + categorías + marcado de leídas |
| **Newsletter** | `Newsletter` | Suscriptores de newsletter |
| **Organizations** | `Organizations` | Organizaciones (gate: `ORGANIZATIONS_MODULE`) y sus perfiles: Mi organización (`my-organization-profile-admin-*`) y el perfil público (`profile-organization-admin-*`). **Trampa:** esas rutas se registran en la zona administrativa con `OrganizationsRoutes::profileRoutes()`, no en el grupo del módulo (cambiaría sus URL), y existen aunque el módulo esté apagado; sus estáticos y su idioma se cargan siempre. **Con privilegios superiores, la organización es SIEMPRE la de la URL** (`organizationForSuperUser()`), en la vista y en el guardado: tomar la del encargado escribía en otra (`#1446`). Mi organización guarda con `OrganizationsController::saveOrganization(…, true)`, nunca con `action()` (receta 4). El contacto del perfil público es el de la ORGANIZACIÓN (`phone`, `informativeEmail`, `websiteLink`, `linkedinLink`), y el perfil se pinta sin encargado |
| **Persons** | `Persons` | Solo estáticos actualmente (módulo pendiente de renovar) |
| **PiecesPHP** | `PiecesPHP\*` | ⭐ Módulos del propio framework, ver abajo |
| **Publications** | `Publications` | Publicaciones + categorías + adjuntos (admin y público) |
| **ReportsManage** | `ReportsManage` | Gestión de reportes (`Queries/`) |
| **SystemApprovals** | `SystemApprovals` | Flujo de aprobaciones transversal (inyecta `systemApprovalStatus` en todos los mappers) + middleware |
| **Terminal** | `Terminal` | `TerminalController`, `QueueJobMapper` y `Tasks/` (tareas CLI) |

### Submódulos dentro de `classes/PiecesPHP/`

| Ruta | Qué es |
| :-- | :-- |
| `PiecesPHP/UserSystem/` | Sistema de usuarios: perfiles (`UserProfileMapper`; `SubMappers/` se borró en el bloque AC al quedar vacío), autenticación OTP/TOTP, `UserDataPackage`, rutas y vistas. **Un usuario «Eliminado» (`STATUS_USER_DELETED`) no se borra nunca: se oculta** (PO, 2026-10-16, P170). Todo lo que LISTA, BUSCA, ELIGE o muestra un PERFIL lo excluye (filtro `status != STATUS_USER_DELETED`, o `UsersModel::existsAndNotDeleted(int $id)` al validar un id); su perfil público da 404 a todos; la exportación de usuarios lo omite. Lo que lo cita por RELACIÓN (autor, registros, historial de ingresos, sesiones, aprobaciones) sigue con su nombre. `UserDataPackage` NO mira el estado. Censo de `#1463`, prueba `core/deleted-user-gone`. Los inactivos van aparte: el listado de usuarios les da su pestaña; Directorio y mapa los excluyen |
| `PiecesPHP/LocalizationSystem/` | Gestión de idiomas y traducciones desde el panel (`Packages/`, `Util/`) |
| `PiecesPHP/BuiltIn/Banner/` | Banners de inicio (`HOME_IMAGE_BANNER`) |
| `PiecesPHP/BuiltIn/Helpers/` | Contenidos genéricos (`GenericContentController`, `GenericContentPseudoMapper`) |
| `PiecesPHP/RoutingUtils/` | `DefaultAccessControlModules` — middleware de control de acceso por módulo |

## Módulos de sistema

Fuera de `classes/` quedan solo dos controladores, en `App\Controller` (`src/app/controller/`):
`PublicAreaController` (el área pública) y `ContactFormsController`. No hay `src/app/model/`.

El resto de lo que es del sistema vive en `classes/PiecesPHP/`: el panel (`AdminPanel/`, con
`AdminPanelController`), usuarios, avatares, recuperación de contraseña, intentos de acceso, temporizador,
problemas de usuario y tokens (`UserSystem/Controllers/`), y las configuraciones (`Settings/`). Tickets no
tiene controlador: queda `UserSystem/ORM/TicketsLogModel.php` y la configuración de osTicket.

`AdminPanelController` es la clase base de casi todos los controladores de módulo y
aporta: usuario en sesión (`$this->user`), variables globales de vista, breadcrumbs,
sidebar y las rutas base del panel (`admin`, tickets, usuarios).
