<?php
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
 * [string $route_alias = null] Un alias para la ruta.
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
use App\Controller\AdminPanelController;
use App\Controller\AppConfigController;
use App\Controller\AvatarController;
use App\Controller\GenericTokenController;
use App\Controller\ImporterController;
use App\Controller\LoginAttemptsController;
use App\Controller\MessagesController;
use App\Controller\PublicAreaController;
use App\Controller\TimerController;
use App\Locations\Controllers\Locations;
use App\Presentations\PresentationsRoutes;
use Components\ComponentProviderRoutes;
use DataImportExportUtility\DataImportExportUtilityRoutes;
use Documents\DocumentsRoutes;
use EventsLog\LogsRoutes;
use FileManager\FileManagerRoutes;
use Forms\FormsRoutes;
use GoogleReCaptchaV3\GoogleReCaptchaV3Routes;
use ImagesRepository\ImagesRepositoryRoutes;
use MySpace\MySpaceRoutes;
use Newsletter\NewsletterRoutes;
use News\NewsRoutes;
use Persons\PersonsRoutes;
use PiecesPHP\BuiltIn\DynamicImages\DynamicImagesRoutes;
use PiecesPHP\Core\Route as PiecesRoute;
use PiecesPHP\Core\RouteGroup as PiecesRouteGroup;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\Core\Test;
use Publications\PublicationsRoutes;
use Terminal\Controllers\TerminalController;

$prefix_lang = get_config('prefix_lang');
$slim_app = get_config('slim_app');
PiecesRouteGroup::setRouter($slim_app);

//──── GRUPOS DE RUTAS ───────────────────────────────────────────────────────────────────

//Nota: Declarar de más específico a menos específico, tanto los grupos como las rutas independientes dentro de los grupos

$terminalGroup = new PiecesRouteGroup($prefix_lang . '/terminal'); //Rutas de terminal
$coreGroup = new PiecesRouteGroup($prefix_lang . '/core'); //Varias
$componentsProvider = new PiecesRouteGroup($prefix_lang . '/components-provider'); //Componentes HTML
$zona_administrativa = new PiecesRouteGroup($prefix_lang . '/admin'); //Zona administrativa
$configurations = new PiecesRouteGroup($prefix_lang . '/configurations'); //Configuraciones
$sistema_usuarios = new PiecesRouteGroup($prefix_lang . '/users/'); //Sistema de usuarios
$tickets = new PiecesRouteGroup($prefix_lang . '/tickets'); //Sistema de tickets
$timing = new PiecesRouteGroup($prefix_lang . '/timing'); //Temporizadores
$mensajeria = new PiecesRouteGroup($prefix_lang . '/messages'); //Mensajería
$locations = new PiecesRouteGroup($prefix_lang . '/locations'); //Ubicaciones
$importadores = new PiecesRouteGroup($prefix_lang . '/importers'); //Importadores
$sistema_avatares = new PiecesRouteGroup($prefix_lang . '/avatars'); //Sistema de usuarios-avatares
$servidor_estaticos = new PiecesRouteGroup($prefix_lang . '/statics/'); //Servido personalizado de archivos estáticos
$token_handler = new PiecesRouteGroup($prefix_lang . '/tokens'); //Sistema de tokens
$zona_publica = new PiecesRouteGroup($prefix_lang); //Zona pública

//──── REGISTRAR RUTAS ───────────────────────────────────────────────────────────────────

//Rutas para solicitudes desde la terminal
TerminalController::routes($terminalGroup);

//Proveedor de componentes HTML
ComponentProviderRoutes::routes($componentsProvider, $componentsProvider);

//Rutas básicas de la zona administrativa
AdminPanelController::routes($zona_administrativa);

//Gestor de archivos
FileManagerRoutes::routes($zona_administrativa);

//Personalización de configuraciones
AppConfigController::routes($configurations);

//Informes de inicio de sesión
LoginAttemptsController::routes($zona_administrativa);

//Sistema de usuarios
AdminPanelController::usersRoutes($sistema_usuarios);

//Tickets
AdminPanelController::ticketsRoutes($tickets);

//Temporizador
TimerController::routes($timing);

//Mensajería
MessagesController::routes($mensajeria);

//Ubicaciones
Locations::routes($locations);

//Importadores
ImporterController::routes($importadores);

//Manejador de tokens
GenericTokenController::routes($token_handler);

//Imágenes
DynamicImagesRoutes::routes($zona_administrativa);

//Google ReCaptcha V3
GoogleReCaptchaV3Routes::routes(new PiecesRouteGroup($prefix_lang . '/recaptcha'));

//Utilidad de importación y exportación
DataImportExportUtilityRoutes::routes($zona_administrativa);

//API
APIRoutes::routes($coreGroup);

$sistema_avatares->register(
    [
        //──── GET ───────────────────────────────────────────────────────────────────────────────
        new PiecesRoute('/get[/]', AvatarController::class . ':avatar', 'avatars', 'GET', true, null),
        //──── POST ──────────────────────────────────────────────────────────────────────────────
        new PiecesRoute('/push[/]', AvatarController::class . ':register', 'push-avatars', 'POST', true),
    ]
);

$servidor_estaticos->register(
    [
        //──── GET ───────────────────────────────────────────────────────────────────────────────
        new PiecesRoute('[{params:.*}]', ServerStatics::class . ':serve', 'statics-files'),
    ]
);

//──── RUTAS OPCIONALES ──────────────────────────────────────────────────────────────────

$generacion_imagenes = new PiecesRouteGroup($prefix_lang . '/img-gen/'); //Generación de imágenes
$generacion_imagenes->active(true); //Grupo activo/inactivo
$generacion_imagenes->register(
    [
        //──── GET ───────────────────────────────────────────────────────────────────────────────
        new PiecesRoute('{w}/{h}[/]', Test::class . ':generateImage', 'img-gen'),
    ]
);

//Módulo de presentaciones
PresentationsRoutes::routes($zona_administrativa, $zona_publica);

//Publicaciones
PublicationsRoutes::routes($zona_administrativa, $zona_publica);

//Newsletter
NewsletterRoutes::routes($zona_administrativa, $zona_publica);

//Noticias internas
NewsRoutes::routes($zona_administrativa);

//Registro fotográfico
ImagesRepositoryRoutes::routes($zona_administrativa);

//Formularios
FormsRoutes::routes($zona_administrativa);

//Personas
PersonsRoutes::routes($zona_administrativa);

//Log de acciones
LogsRoutes::routes($zona_administrativa);

//Documentos
DocumentsRoutes::routes($zona_administrativa);

//Mi espacio
MySpaceRoutes::routes($zona_administrativa);

//Rutas básicas de la zona pública
PublicAreaController::routes($zona_publica);
