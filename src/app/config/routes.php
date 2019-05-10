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
use App\Controller\AdminPanelController as Panel;
use App\Controller\AvatarController;
use App\Controller\BlackboardNewsController;
use App\Controller\ImporterController;
use App\Controller\LoginAttemptsController;
use App\Controller\MessagesController;
use App\Controller\RecoveryPasswordController;
use App\Controller\TimerController;
use App\Controller\UserProblemsController;
use App\Controller\UsersController as UsersController;
use App\Locations\Controllers\Locations;
use PiecesPHP\Core\Route as PiecesRoute;
use PiecesPHP\Core\RouteGroup as PiecesRouteGroup;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\Core\Test;

$prefix_lang = get_config('prefix_lang');
$slim_app = get_config('slim_app');
PiecesRouteGroup::setRouter($slim_app);

//──── GRUPOS DE RUTAS ───────────────────────────────────────────────────────────────────
$mensajeria = new PiecesRouteGroup($prefix_lang . '/messages'); //Mensajería
$zona_administrativa = new PiecesRouteGroup($prefix_lang . '/admin'); //Zona administrativa
$importadores = new PiecesRouteGroup($prefix_lang . '/importers'); //Importadores
$sistema_tablero_noticias = new PiecesRouteGroup($prefix_lang . '/blackboard-news/'); //Servido personalizado de archivos estáticos
$sistema_usuarios = new PiecesRouteGroup($prefix_lang . '/users/'); //Sistema de usuarios
$sistema_avatares = new PiecesRouteGroup($prefix_lang . '/avatars'); //Sistema de usuarios
$servidor_estaticos = new PiecesRouteGroup($prefix_lang . '/statics/'); //Servido personalizado de archivos estáticos
$timing = new PiecesRouteGroup($prefix_lang . '/timing'); //Temporizadores
$tickets = new PiecesRouteGroup($prefix_lang . '/tickets'); //Sistema de tickets
$locations = new PiecesRouteGroup($prefix_lang . '/locations'); //Ubicaciones

//──── REGISTRAR RUTAS ───────────────────────────────────────────────────────────────────

//Temporizador
$timing->register(TimerController::routes());

//Informes de inicio de sesión
LoginAttemptsController::routes($zona_administrativa);

//Ubicaciones
Locations::routes($locations);

//Tablero de noticias
BlackboardNewsController::routes($sistema_tablero_noticias);

//Tickets
$tickets->register(Panel::getRoutes());

//Mensajería
MessagesController::routes($mensajeria);

//Importadores
ImporterController::routes($importadores);

$zona_administrativa->register(
    [
        //──── GENERALES ─────────────────────────────────────────────────────────────────────────
        //Vista principal de la zona administrativa
        new PiecesRoute('[/]', Panel::class . ':indexView', 'admin', 'GET', true),
        //──── USUARIOS ──────────────────────────────────────────────────────────────────────────
        //Listado de usuarios
        new PiecesRoute('/usuarios/list[/]', Panel::class . ':listadoUsersView', 'listado-usuarios', 'GET', true),
        //Vista de creación de usuario
        new PiecesRoute('/usuarios/crear[/]', Panel::class . ':formUserView', 'form-usuarios', 'GET', true),
        //Vista de edición de usuario
        new PiecesRoute('/usuarios/editar/{id}[/]', Panel::class . ':formUserView', 'form-editar-usuarios', 'GET', true),
        //Vista de perfil de usuario
        new PiecesRoute('/perfil[/]', Panel::class . ':formUserView', 'profile', 'GET', true),
        //──── ERRORES ────────────────────────────────────────────────────────────────────────
        //Log
        new PiecesRoute('/error-log[/]', Panel::class . ':errorLog', 'admin-error-log', 'GET', true),
    ]
);

$sistema_usuarios->register(
    [
        //──── GET ───────────────────────────────────────────────────────────────────────────────
        new PiecesRoute('login[/]', UsersController::class . ':loginForm', 'login-form'),
        new PiecesRoute('logout[/]', UsersController::class . ':logout', 'logout'),
        new PiecesRoute('recovery[/]', RecoveryPasswordController::class . ':recoveryPasswordForm', 'recovery-form'),
        new PiecesRoute('recovery/{url_token}[/]', RecoveryPasswordController::class . ':newPasswordCreate', 'new-password-create'),
        new PiecesRoute('user-forget[/]', UserProblemsController::class . ':userForgetForm', 'user-forget-form'),
        new PiecesRoute('user-blocked[/]', UserProblemsController::class . ':userBlockedForm', 'user-blocked-form'),
        new PiecesRoute('user-not-exists[/]', UserProblemsController::class . ':userNotExistsForm', 'user-not-exists-form'),
        new PiecesRoute('problems[/]', UserProblemsController::class . ':userProblemsList', 'user-problems-list'),
        //──── POST ──────────────────────────────────────────────────────────────────────────────
        new PiecesRoute('login[/]', UsersController::class . ':login', 'login-request', 'POST'),
        new PiecesRoute('register[/]', UsersController::class . ':register', 'register-request', 'POST'),
        new PiecesRoute('edit[/]', UsersController::class . ':edit', 'user-edit-request', 'POST'),
        new PiecesRoute('recovery[/]', RecoveryPasswordController::class . ':recoveryPasswordRequest', 'recovery-password-request', 'POST'),
        new PiecesRoute('recovery-code[/]', RecoveryPasswordController::class . ':recoveryPasswordRequestCode', 'recovery-password-request-code', 'POST'),
        new PiecesRoute('create-password-code[/]', RecoveryPasswordController::class . ':newPasswordCreateCode', 'new-password-create-code', 'POST'),
        new PiecesRoute('user-forget-code[/]', UserProblemsController::class . ':generateCode', 'user-forget-request-code', 'POST'),
        new PiecesRoute('user-blocked-code[/]', UserProblemsController::class . ':generateCode', 'user-blocked-request-code', 'POST'),
        new PiecesRoute('get-username[/]', UserProblemsController::class . ':resolveProblem', 'user-forget-get', 'POST'),
        new PiecesRoute('unblock-user[/]', UserProblemsController::class . ':resolveProblem', 'user-blocked-resolve', 'POST'),
        new PiecesRoute('user-not-exists[/]', UserProblemsController::class . ':sendMailUserNotExists', 'user-not-exists-send', 'POST'),
    ]
);

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

$tests = new PiecesRouteGroup($prefix_lang . '/overview'); //Muestra de algunas funciones
$tests->active(true); //Grupo activo/inactivo
$tests->register(
    [
        //──── GET ───────────────────────────────────────────────────────────────────────────────
        new PiecesRoute('[/]', Test::class . ':index', 'home-test'),
        new PiecesRoute('/image-generator/{w}/{h}[/]', Test::class . ':generateImage', 'image-gen'),
        new PiecesRoute('/overview-1[/]', Test::class . ':overviewFront', 'front-test'),
        new PiecesRoute('/overview-2[/]', Test::class . ':overviewBack', 'back-test'),
    ]
);
