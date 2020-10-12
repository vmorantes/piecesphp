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
use App\Controller\AdminPanelController;
use App\Controller\AppConfigController;
use App\Controller\AvatarController;
use App\Controller\BlackboardNewsController;
use App\Controller\GenericTokenController;
use App\Controller\ImporterController;
use App\Controller\LoginAttemptsController;
use App\Controller\MessagesController;
use App\Controller\PublicAreaController;
use App\Controller\TimerController;
use App\Locations\Controllers\Locations;
use App\Presentations\PresentationsRoutes;
use PiecesPHP\BuiltIn\Article\Controllers\ArticleController;
use PiecesPHP\BuiltIn\Article\Controllers\ArticleControllerPublic;
use PiecesPHP\BuiltIn\DynamicImages\DynamicImagesRoutes;
use PiecesPHP\BuiltIn\Shop\ShopRoutes;
use PiecesPHP\Core\Route as PiecesRoute;
use PiecesPHP\Core\RouteGroup as PiecesRouteGroup;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\Core\Test;

$prefix_lang = get_config('prefix_lang');
$slim_app = get_config('slim_app');
PiecesRouteGroup::setRouter($slim_app);

//──── GRUPOS DE RUTAS ───────────────────────────────────────────────────────────────────

//Nota: Declarar de más específico a menos específico, tanto los grupos como las rutas independientes dentro de los grupos

$zona_administrativa = new PiecesRouteGroup($prefix_lang . '/admin'); //Zona administrativa
$configurations = new PiecesRouteGroup($prefix_lang . '/configurations'); //Zona administrativa
$sistema_usuarios = new PiecesRouteGroup($prefix_lang . '/users/'); //Sistema de usuarios
$tickets = new PiecesRouteGroup($prefix_lang . '/tickets'); //Sistema de tickets
$timing = new PiecesRouteGroup($prefix_lang . '/timing'); //Temporizadores
$mensajeria = new PiecesRouteGroup($prefix_lang . '/messages'); //Mensajería
$locations = new PiecesRouteGroup($prefix_lang . '/locations'); //Ubicaciones
$sistema_tablero_noticias = new PiecesRouteGroup($prefix_lang . '/blackboard-news/'); //Servido personalizado de archivos estáticos
$importadores = new PiecesRouteGroup($prefix_lang . '/importers'); //Importadores
$articles = new PiecesRouteGroup($prefix_lang . '/articles'); //Blog
$sistema_avatares = new PiecesRouteGroup($prefix_lang . '/avatars'); //Sistema de usuarios
$servidor_estaticos = new PiecesRouteGroup($prefix_lang . '/statics/'); //Servido personalizado de archivos estáticos
$token_handler = new PiecesRouteGroup($prefix_lang . '/tokens'); //Servido personalizado de archivos estáticos
$zona_publica = new PiecesRouteGroup($prefix_lang); //Zona pública

//──── REGISTRAR RUTAS ───────────────────────────────────────────────────────────────────

//Rutas básicas de la zona administrativa
AdminPanelController::routes($zona_administrativa);

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

//Tablero de noticias
BlackboardNewsController::routes($sistema_tablero_noticias);

//Importadores
ImporterController::routes($importadores);

//Blog
ArticleController::routes($zona_administrativa);
ArticleControllerPublic::routes($articles);

//Manejador de tokens
GenericTokenController::routes($token_handler);

//Tienda
ShopRoutes::routes($zona_administrativa);

//Imágenes
DynamicImagesRoutes::routes($zona_administrativa);

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

//Rutas básicas de la zona pública
PublicAreaController::routes($zona_publica);
