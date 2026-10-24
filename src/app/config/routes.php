<?php

/**
 * @pcsphp-config ambos
 * Qué conviene editar aquí: el registro de las rutas de tus módulos, en la sección del clon, antes de la zona pública: el orden importa, de más específico a menos.
 */
/**
 * Rutas
 *
 * En este archivo se definen las rutas de la aplicación.
 *
 * El sistema de rutas usa Slim 3.*
 *
 * Nótese que la forma de agregar las rutas no es la convencional en slim, sino que se usan
 * las clases PiecesPHP\Core\Route y PiecesPHP\Core\RouteGroup. Esto es así para que a PiecesPHP se le
 * facilite un manejo automático más sencillo de sus funciones de permisos basados en roles y de usuarios.
 *
 * Ejemplo:
 *
 * Grupo:
 *
 * string $name
 * $grupo = new PiecesPHP\Core\RouteGroup($name)
 *
 * Ruta:
 *
 * string $route La ruta.
 *
 * string $controller El nombre de la clase seguido de dos puntos y el nombre de método. Ej: '\App\MiClase:index'
 *
 * [string $name = uniqid()] Nombre con el que se registrá la ruta
 * [string $method = 'GET'] Método aceptado por la ruta (método HTTP ej.: GET|POST|PUT...)
 *
 * [bool $require_login = false] Si la ruta necesita de estar logueado o no. Esto si se está usando el sistema de usuarios de PiecesPHP
 *
 * [int[]|string[] $roles_allowed Roles = []] Roles que pueden acceder a la vista si es necesario estar logueado.
 * Forma alternativa al archivo roles.php de permitir el acceso a una vista.
 *
 * new PiecesPHP\Core\Route($route, $controller, $name, $method, $requireLogin, $alias, $rolesAllowed, $defaultParamsValues)
 *
 * Agregar ruta
 *
 * $grupo->register([$ruta, ...])
 *
 */

use API\APIRoutes;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Settings\SettingsRoutes;
use PiecesPHP\UserSystem\Controllers\AvatarController;
use PiecesPHP\Tokens\Controllers\GenericTokenController;
use PiecesPHP\UserSystem\Controllers\LoginAttemptsController;
use App\Controller\PublicAreaController;
use PiecesPHP\UserSystem\Controllers\TimerController;
use PiecesPHP\App\Locations\Controllers\Locations;
use PiecesPHP\Components\ComponentProviderRoutes;
use Directory\DirectoryRoutes;
use DataImportExportUtility\DataImportExportUtilityRoutes;
use Documents\DocumentsRoutes;
use EventsLog\LogsRoutes;
use PiecesPHP\FileManager\FileManagerRoutes;
use Forms\FormsRoutes;
use PiecesPHP\GoogleReCaptchaV3\GoogleReCaptchaV3Routes;
use MySpace\MySpaceRoutes;
use Newsletter\NewsletterRoutes;
use News\NewsRoutes;
use Organizations\OrganizationsRoutes;
use PiecesPHP\BuiltIn\Banner\BuiltInBannerRoutes;
use PiecesPHP\BuiltIn\Helpers\HelpersSystemRoutes;
use PiecesPHP\Core\Route as PiecesRoute;
use PiecesPHP\Core\RouteGroup as PiecesRouteGroup;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\Core\Test;
use PiecesPHP\LocalizationSystem\LocalizationSystemFeaturesRoutes;
use PiecesPHP\SystemStatus\SystemStatusRoutes;
//Alias: `LogsRoutes` es el del registro de acciones (EventsLog).
use PiecesPHP\Logs\LogsRoutes as LogsViewerRoutes;
use PiecesPHP\Geo\GeoRoutes;
use PiecesPHP\UserSystem\UserSystemFeaturesRoutes;
use Publications\PublicationsRoutes;
use ReportsManage\ReportsManageRoutes;
use SystemApprovals\SystemApprovalsRoutes;
use PiecesPHP\Settings\Controllers\SiteFilesController;
use Terminal\Controllers\TerminalController;

//── Del framework: no lo edites ──
$prefix_lang = get_config('prefix_lang');
$slim_app = get_router();
PiecesRouteGroup::setRouter($slim_app);

//──── GRUPOS DE RUTAS ───────────────────────────────────────────────────────────────────

//Nota: Declarar de más específico a menos específico, tanto los grupos como las rutas independientes dentro de los grupos.
//El orden lo fija el primer register() de cada grupo: los servicios, antes que los comodines de la zona pública.

//── Servicios: los llama la terminal, el navegador o la API, no una persona en el panel ──
$terminalGroup = new PiecesRouteGroup($prefix_lang . '/terminal'); //Rutas de terminal
$coreGroup = new PiecesRouteGroup($prefix_lang . '/core'); //API y mensajes de idioma
$componentsProvider = new PiecesRouteGroup($prefix_lang . '/components-provider'); //Componentes HTML
$servidor_estaticos = new PiecesRouteGroup($prefix_lang . '/statics/'); //Servido personalizado de archivos estáticos
$token_handler = new PiecesRouteGroup($prefix_lang . '/tokens'); //Sistema de tokens

//── Backoffice: los subgrupos de admin/ antes que admin/ ──
$adminConfigurations = new PiecesRouteGroup($prefix_lang . '/admin/configurations'); //Configuraciones
$adminUsers = new PiecesRouteGroup($prefix_lang . '/admin/users/'); //Usuarios, con sesión
$adminLocations = new PiecesRouteGroup($prefix_lang . '/admin/locations'); //Ubicaciones, la gestión
$adminOrganizations = new PiecesRouteGroup($prefix_lang . '/admin/organizations'); //Organizaciones, la gestión
$adminTickets = new PiecesRouteGroup($prefix_lang . '/admin/tickets'); //Sistema de tickets
$adminTiming = new PiecesRouteGroup($prefix_lang . '/admin/timing'); //Temporizadores
$adminAvatars = new PiecesRouteGroup($prefix_lang . '/admin/avatars'); //Sistema de usuarios-avatares
$zona_administrativa = new PiecesRouteGroup($prefix_lang . '/admin'); //Zona administrativa

//── Pública: lo que se usa sin sesión ──
$organizations = new PiecesRouteGroup($prefix_lang . '/organizations'); //Organizaciones, sus estáticos
$sistema_usuarios = new PiecesRouteGroup($prefix_lang . '/users/'); //Acceso, recuperación y bloqueo
$locations = new PiecesRouteGroup($prefix_lang . '/locations'); //Ubicaciones, las consultas públicas
$zona_publica = new PiecesRouteGroup($prefix_lang); //Zona pública

//──── REGISTRAR RUTAS ───────────────────────────────────────────────────────────────────

//──── SERVICIOS ─────────────────────────────────────────────────────────────────────────

$servidor_estaticos->register(
    [
        //──── GET ───────────────────────────────────────────────────────────────────────────────
        new PiecesRoute('[{params:.*}]', ServerStatics::class . ':serve', 'statics-files'),
    ]
);

if (requestIsSameDomain()) {
    //Generación de imágenes y otras rutas de prueba. Solo se genera imagen si es mismo dominio.
    Test::registerRoutes($prefix_lang);
}

//Rutas para solicitudes desde la terminal
TerminalController::routes($terminalGroup);

//Proveedor de componentes HTML
ComponentProviderRoutes::routes($componentsProvider, $componentsProvider);

//Manejador de tokens
GenericTokenController::routes($token_handler);

//Google ReCaptcha V3
GoogleReCaptchaV3Routes::routes(new PiecesRouteGroup($prefix_lang . '/recaptcha'));

//API
APIRoutes::routes($coreGroup);

//──── BACKOFFICE ────────────────────────────────────────────────────────────────────────

$adminAvatars->register(
    [
        //──── POST ──────────────────────────────────────────────────────────────────────────────
        new PiecesRoute('/push[/]', AvatarController::class . ':register', 'push-avatars', 'POST', true),
    ]
);

//Módulo de organizaciones
OrganizationsRoutes::routes($adminOrganizations, $organizations);

//Rutas básicas de la zona administrativa
AdminPanelController::routes($zona_administrativa);

//Gestor de archivos
FileManagerRoutes::routes($zona_administrativa);

//Personalización de configuraciones
SettingsRoutes::routes($adminConfigurations);

//Informes de inicio de sesión
LoginAttemptsController::routes($zona_administrativa);

//Sistema de usuarios
AdminPanelController::usersRoutes($adminUsers, $sistema_usuarios);
UserSystemFeaturesRoutes::routes($adminUsers, $sistema_usuarios);

//Tickets
AdminPanelController::ticketsRoutes($adminTickets);

//Temporizador
TimerController::routes($adminTiming);

//Ubicaciones
Locations::routes($adminLocations, $locations);

//Utilidad de importación y exportación
DataImportExportUtilityRoutes::routes($zona_administrativa);

//Utilidad de localización (idiomas): los mensajes, a los servicios; los estáticos, aquí
LocalizationSystemFeaturesRoutes::routes($zona_administrativa, $coreGroup);

//Avisos del sistema y mantenimiento
SystemStatusRoutes::routes($zona_administrativa);

//Registros: una vista con las pestañas que aportan los módulos
LogsViewerRoutes::routes($zona_administrativa);

//Datos geográficos: el geovisor y las fuentes que le registran los módulos
GeoRoutes::routes($zona_administrativa);

//Imágenes
BuiltInBannerRoutes::routes($zona_administrativa, $zona_publica);

//Publicaciones
PublicationsRoutes::routes($zona_administrativa, $zona_publica);

//Newsletter
NewsletterRoutes::routes($zona_administrativa, $zona_publica);

//Noticias internas
NewsRoutes::routes($zona_administrativa);


//Formularios
FormsRoutes::routes($zona_administrativa);

//Log de acciones
LogsRoutes::routes($zona_administrativa);

//Documentos
DocumentsRoutes::routes($zona_administrativa);

//Mi espacio
MySpaceRoutes::routes($zona_administrativa);
//Perfiles de organización: en la zona administrativa, con las URL que tenían en Mi espacio
OrganizationsRoutes::profileRoutes($zona_administrativa);

//Aprobaciones
SystemApprovalsRoutes::routes($zona_administrativa);

//Directorio: el listado de personas y organizaciones, y sus perfiles
DirectoryRoutes::routes($zona_administrativa);


//Reportes
ReportsManageRoutes::routes($zona_administrativa);

//Helpers
HelpersSystemRoutes::routes($zona_administrativa);

//── Del clon ──
//Tus módulos: TusRutas::routes($zona_administrativa, $zona_publica);

//──── PÚBLICA ───────────────────────────────────────────────────────────────────────────

//── Del framework: no lo edites ──

//robots.txt, humans.txt y llms.txt, en la raíz. Los grupos se guardan por prefijo: con el de la zona pública vacío, un
//`new PiecesRouteGroup('')` perdería sus rutas en silencio. Van antes que la zona pública, que acaba en una ruta comodín.
SiteFilesController::routes($prefix_lang === '' ? $zona_publica : new PiecesRouteGroup(''));

//Rutas básicas de la zona pública
PublicAreaController::routes($zona_publica);
