<?php

/**
 * @pcsphp-config ambos
 * Qué conviene editar aquí: el registro de las rutas de tus módulos, en la sección del clon, antes de la zona pública: el orden importa, de más específico a menos.
 */
/**
 * Grupos de rutas y registro de los módulos, siempre con Route y RouteGroup: el nombre de la ruta es su permiso.
 * Formato y orden: source-docs/project/docs/piecesphp/content/configuration.md, «routes.php».
 */

use API\APIRoutes;
use App\Controller\PublicAreaController;
use DataImportExportUtility\DataImportExportUtilityRoutes;
use Directory\DirectoryRoutes;
use Documents\DocumentsRoutes;
use EventsLog\LogsRoutes;
use Forms\FormsRoutes;
use MySpace\MySpaceRoutes;
use Newsletter\NewsletterRoutes;
use News\NewsRoutes;
use Organizations\OrganizationsRoutes;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\App\Locations\Controllers\Locations;
use PiecesPHP\BuiltIn\Banner\BuiltInBannerRoutes;
use PiecesPHP\BuiltIn\Helpers\HelpersSystemRoutes;
use PiecesPHP\Components\ComponentProviderRoutes;
use PiecesPHP\Core\ProjectStart;
use PiecesPHP\Core\Route as PiecesRoute;
use PiecesPHP\Core\RouteGroup as PiecesRouteGroup;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\Core\Test;
use PiecesPHP\FileManager\FileManagerRoutes;
use PiecesPHP\Geo\GeoRoutes;
use PiecesPHP\GoogleReCaptchaV3\GoogleReCaptchaV3Routes;
use PiecesPHP\LocalizationSystem\LocalizationSystemFeaturesRoutes;
use PiecesPHP\Logs\LogsRoutes as LogsViewerRoutes;
use PiecesPHP\MailCatalog\MailCatalogRoutes;
use PiecesPHP\Mcp\McpRoutes;
use PiecesPHP\Notifications\NotificationsRoutes;
use PiecesPHP\Settings\Controllers\SiteFilesController;
use PiecesPHP\Settings\SettingsRoutes;
use PiecesPHP\SystemStatus\SystemStatusRoutes;
use PiecesPHP\Tokens\Controllers\GenericTokenController;
//Alias: `LogsRoutes` es el del registro de acciones (EventsLog).
use PiecesPHP\UserSystem\Controllers\AvatarController;
use PiecesPHP\UserSystem\Controllers\LoginAttemptsController;
use PiecesPHP\UserSystem\Controllers\TimerController;
use PiecesPHP\UserSystem\UserSystemFeaturesRoutes;
use Publications\PublicationsRoutes;
use ReportsManage\ReportsManageRoutes;
use SystemApprovals\SystemApprovalsRoutes;
use Terminal\Controllers\TerminalController;

//── Del framework: no lo edites ──
$langPrefix = get_config('prefix_lang');
//El panel cuelga de $config['admin_path'] (P399; vacío aún no está soportado). public_area lo aplica cada módulo al registrarse.
$adminPrefix = ProjectStart::adminPrefix($langPrefix, ProjectStart::adminPath());
$slim_app = get_router();
PiecesRouteGroup::setRouter($slim_app);

//──── GRUPOS DE RUTAS ───────────────────────────────────────────────────────────────────

//Nota: Declarar de más específico a menos específico, tanto los grupos como las rutas independientes dentro de los grupos.
//El orden lo fija el primer register() de cada grupo: los servicios, antes que los comodines de la zona pública.

//── Servicios: los llama la terminal, el navegador o la API, no una persona en el panel ──
$terminalGroup = new PiecesRouteGroup($langPrefix . '/terminal'); //Rutas de terminal
$coreGroup = new PiecesRouteGroup($langPrefix . '/core'); //API y mensajes de idioma
$componentsGroup = new PiecesRouteGroup($langPrefix . '/components-provider'); //Componentes HTML
$staticsGroup = new PiecesRouteGroup($langPrefix . '/statics/'); //Servido personalizado de archivos estáticos
$tokensGroup = new PiecesRouteGroup($langPrefix . '/tokens'); //Sistema de tokens

//── Backoffice: los subgrupos de admin/ antes que admin/ ──
$adminConfigurationsGroup = new PiecesRouteGroup($adminPrefix . '/configurations'); //Configuraciones
$adminUsersGroup = new PiecesRouteGroup($adminPrefix . '/users/'); //Usuarios, con sesión
$adminLocationsGroup = new PiecesRouteGroup($adminPrefix . '/locations'); //Ubicaciones, la gestión
$adminOrganizationsGroup = new PiecesRouteGroup($adminPrefix . '/organizations'); //Organizaciones, la gestión
$adminTicketsGroup = new PiecesRouteGroup($adminPrefix . '/tickets'); //Sistema de tickets
$adminTimingGroup = new PiecesRouteGroup($adminPrefix . '/timing'); //Temporizadores
$adminAvatarsGroup = new PiecesRouteGroup($adminPrefix . '/avatars'); //Sistema de usuarios-avatares
$adminGroup = new PiecesRouteGroup($adminPrefix); //Zona administrativa

//── Pública: lo que se usa sin sesión ──
$organizationsGroup = new PiecesRouteGroup($langPrefix . '/organizations'); //Organizaciones, sus estáticos
$usersGroup = new PiecesRouteGroup($langPrefix . '/users/'); //Acceso, recuperación y bloqueo
$locationsGroup = new PiecesRouteGroup($langPrefix . '/locations'); //Ubicaciones, las consultas públicas
$publicGroup = new PiecesRouteGroup($langPrefix); //Zona pública

//──── REGISTRAR RUTAS ───────────────────────────────────────────────────────────────────

//──── SERVICIOS ─────────────────────────────────────────────────────────────────────────

$staticsGroup->register(
    [
        //──── GET ───────────────────────────────────────────────────────────────────────────────
        new PiecesRoute('[{params:.*}]', ServerStatics::class . ':serve', 'statics-files'),
    ]
);

if (requestIsSameDomain()) {
    //Generación de imágenes y otras rutas de prueba. Solo se genera imagen si es mismo dominio.
    Test::registerRoutes($langPrefix);
}

//Rutas para solicitudes desde la terminal
TerminalController::routes($terminalGroup);

//Proveedor de componentes HTML
ComponentProviderRoutes::routes($componentsGroup, $componentsGroup);

//Manejador de tokens
GenericTokenController::routes($tokensGroup);

//Google ReCaptcha V3
GoogleReCaptchaV3Routes::routes(new PiecesRouteGroup($langPrefix . '/recaptcha'));

//API
APIRoutes::routes($coreGroup);

//──── BACKOFFICE ────────────────────────────────────────────────────────────────────────

$adminAvatarsGroup->register(
    [
        //──── POST ──────────────────────────────────────────────────────────────────────────────
        new PiecesRoute('/push[/]', AvatarController::class . ':register', 'push-avatars', 'POST', true),
    ]
);

//Módulo de organizaciones
OrganizationsRoutes::routes($adminOrganizationsGroup, $organizationsGroup);

//Rutas básicas de la zona administrativa
AdminPanelController::routes($adminGroup);

//Gestor de archivos
FileManagerRoutes::routes($adminGroup);

//Personalización de configuraciones
SettingsRoutes::routes($adminConfigurationsGroup);

//Informes de inicio de sesión
LoginAttemptsController::routes($adminGroup);

//Sistema de usuarios
AdminPanelController::usersRoutes($adminUsersGroup, $usersGroup);
UserSystemFeaturesRoutes::routes($adminUsersGroup, $usersGroup);

//Tickets
AdminPanelController::ticketsRoutes($adminTicketsGroup);

//Temporizador
TimerController::routes($adminTimingGroup);

//Ubicaciones
Locations::routes($adminLocationsGroup, $locationsGroup);

//Utilidad de importación y exportación
DataImportExportUtilityRoutes::routes($adminGroup);

//Utilidad de localización (idiomas): los mensajes, a los servicios; los estáticos, aquí
LocalizationSystemFeaturesRoutes::routes($adminGroup, $coreGroup);

//Avisos del sistema y mantenimiento
SystemStatusRoutes::routes($adminGroup);

//Correos del sitio: el banco de pruebas de correo
MailCatalogRoutes::routes($adminGroup);

//Registros: una vista con las pestañas que aportan los módulos
LogsViewerRoutes::routes($adminGroup);
NotificationsRoutes::routes($adminGroup);

//El servidor MCP del panel (ADR 0075): las herramientas, con la sesión y los permisos de quien pregunta
McpRoutes::routes($adminGroup);

//Datos geográficos: el geovisor y las fuentes que le registran los módulos
GeoRoutes::routes($adminGroup);

//Imágenes
BuiltInBannerRoutes::routes($adminGroup, $publicGroup);

//Publicaciones
PublicationsRoutes::routes($adminGroup, $publicGroup);

//Newsletter
NewsletterRoutes::routes($adminGroup, $publicGroup);

//Noticias internas
NewsRoutes::routes($adminGroup);

//Formularios
FormsRoutes::routes($adminGroup);

//Log de acciones
LogsRoutes::routes($adminGroup);

//Documentos
DocumentsRoutes::routes($adminGroup);

//Mi espacio
MySpaceRoutes::routes($adminGroup);
//Perfiles de organización: en la zona administrativa, con las URL que tenían en Mi espacio
OrganizationsRoutes::profileRoutes($adminGroup);

//Aprobaciones
SystemApprovalsRoutes::routes($adminGroup);

//Directorio: el listado de personas y organizaciones, y sus perfiles
DirectoryRoutes::routes($adminGroup);

//Reportes
ReportsManageRoutes::routes($adminGroup);

//Helpers
HelpersSystemRoutes::routes($adminGroup);

//Archivos para buscadores, antes que tus módulos: su comodín los taparía; con prefijo vacío, en la zona pública (H371).
SiteFilesController::routes($langPrefix === '' ? $publicGroup : new PiecesRouteGroup(''));

//── Del clon ──
//Tus módulos: TusRutas::routes($adminGroup, $publicGroup);

//──── PÚBLICA ───────────────────────────────────────────────────────────────────────────

//── Del framework: no lo edites ──

//Rutas básicas de la zona pública
PublicAreaController::routes($publicGroup);
