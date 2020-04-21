<?php

use App\Model\AppConfigModel;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\SessionToken;

require __DIR__ . '/app/core/bootstrap.php';

/**Assets globales */
require_once basepath("app/config/assets.php");

/**Rutas */

//Containers
require_once basepath("app/config/containers.php");

//Instancia del enrutador
set_config(
    'slim_container',
    new \Slim\Container($container_configurations)
);

if (get_config('control_access_login') === true) {
    add_global_assets([
        base_url('statics/core/js/user-system/system.users.jquery.js'),
        base_url('statics/core/js/user-system/PiecesPHPSystemUserHelper.js'),
        base_url('statics/core/js/user-system/main_system_user.js'),
    ], 'js');
}

if (APP_CONFIGURATION_MODULE) {
    $default_configurations_values = [
        'title_app' => 'Nombre Plataforma',
        'osTicketAPI' => 'http://ayuda.tejidodigital.com',
        'osTicketAPIKey' => '93F0F2E3DCD3B79A686A3157620CFF24',
        'favicon' => 'statics/images/favicon.png',
        'favicon-back' => 'statics/images/favicon-back.png',
        'logo' => 'statics/images/logo.png',
        'logo-login' => 'statics/images/logo-login.png',
        'logo-sidebar-top' => 'statics/images/logo-sidebar-top.png',
        'logo-sidebar-bottom' => 'statics/images/logo-sidebar-bottom.png',
        'logo-mailing' => 'statics/images/logo-mailing.png',
        'backgrounds' => [
            'statics/login-and-recovery/images/login/bg1.jpg',
            'statics/login-and-recovery/images/login/bg2.jpg',
            'statics/login-and-recovery/images/login/bg3.jpg',
            'statics/login-and-recovery/images/login/bg4.jpg',
            'statics/login-and-recovery/images/login/bg5.jpg',
        ],
        'mail' => [
            'auto_tls' => true,
            'protocol' => 'ssl',
            'host' => 'smtp.zoho.com',
            'auth' => true,
            'user' => 'correo@correo.com',
            'password' => '123456',
            'port' => 465,
        ],
        'owner' => 'Sample Inc.',
        'description' => 'Descripción de la página.',
        'keywords' => [
            'A',
            'B',
            'C D',
        ],
        'meta_theme_color' => '#2692D0',
        'open_graph_image' => 'statics/images/open_graph.jpg',
    ];
    ksort($default_configurations_values);
    AppConfigModel::initializateConfigurations($default_configurations_values);
}

$app = new \Slim\App(get_config('slim_container'));

//Acciones antes de mostrar una ruta
$app->add(function (\Slim\Http\Request $request, \Slim\Http\Response $response, callable $next) {

    //──── Idiomas ───────────────────────────────────────────────────────────────────────────
    $allowedLangs = Config::get_allowed_langs();
    $currenLang = Config::get_lang();
    $alternativesURL = [];

    foreach ($allowedLangs as $lang) {

        if ($currenLang != $lang) {

            $alternativesURL[$lang] = get_lang_url($currenLang, $lang);

        }

    }

    Config::set_config('alternatives_url', $alternativesURL);

    //──── Validaciones de sesión y redirecciones ────────────────────────────────────────────

    $route = $request->getAttribute('route');

    if (empty($route)) {
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $JWT = SessionToken::getJWTReceived();
    $name_route = $route->getName(); //Nombre de la ruta
    $methods = $route->getMethods(); //Métodos que acepta la ruta solicitada
    $arguments = $route->getArguments(); //Argumentos pasados en la url

    //Control de acceso por login
    $control_access_login = get_config('control_access_login');

    //Verifica validez del JWT
    $isActiveSession = SessionToken::isActiveSession($JWT);

    //Verifica si el control automático de acceso por login está activado
    if ($control_access_login) {

        $info_route = get_route_info($name_route); //Información de la ruta actual

        //Verifica si la ruta requiere estar logueado
        if ($info_route['require_login']) {

            //Acciones en caso de no estar logueado
            if (!$isActiveSession) {

                if ($name_route != 'users-form-login') {

                    if ($request->isXhr()) {

                        $url_login = remove_last_char_on('/', get_route('users-form-login'));
                        $referer = $request->getHeader('HTTP_REFERER');
                        $referer = isset($referer[0]) ? $referer[0] : '';
                        $referer = remove_last_char_on('/', $referer);

                        if ($referer != $url_login) {
                            return $response->withJson([
                                'error' => 'RESTRICTED_AREA',
                                'message' => __('errors', 'RESTRICTED_AREA'),
                            ]);
                        }

                    } else {
                        set_flash_message('requested_uri', get_current_url());
                        return $response->withRedirect(get_route('users-form-login'));
                    }

                }
            }

        }

        //Redirección al area administrativa desde formulario de logueo en caso de haber una session
        $login_redirect = get_config('admin_url');
        $relative_url = $login_redirect !== false ? (isset($login_redirect['relative']) ? $login_redirect['relative'] : true) : true;
        $relative_url = !is_bool($relative_url) ? true : $relative_url;
        $admin_url = $login_redirect !== false ? (isset($login_redirect['url']) ? $login_redirect['url'] : '') : '';
        if ($relative_url) {
            $admin_url = baseurl($admin_url);
        }

        $admin_url = convert_lang_url($admin_url, get_config('default_lang'), get_config('app_lang'));

        //Verifica que esté logueado
        if ($isActiveSession) {

            if ($name_route == 'users-form-login') {
                return $response->withRedirect($admin_url);
            }

        }

        //Control de permisos por roles
        $roles_control = get_config('roles');
        $active_roles_control = isset($roles_control['active']) ? $roles_control['active'] : false;
        $user = null;
        $current_role = null;
        $has_permissions = null;

        //Verifica si hay una sesion activa
        if ($isActiveSession) {

            $user = BaseToken::getData($JWT); //Información del usuario
            $user = $user instanceof \stdClass ? (new \App\Model\UsersModel())->getModel()->select()->where(['id' => $user->id])->row() : $user;

            if ($user instanceof \stdClass) {
                set_config('current_user', $user);
                Roles::setCurrentRole($user->type); //Se establece el rol
                $current_role = Roles::getCurrentRole(); //Se obtiene el rol
            }

        }

        //Verifica si está activada la comprobación automática de roles
        if ($current_role !== null && $active_roles_control === true) {

            $has_permissions = Roles::hasPermissions($name_route, $current_role['name']);

        }

        //Acciones en caso de no tener permisos
        if ($has_permissions !== null && !$has_permissions && $info_route['require_login']) {
            return (function ($request, $response) {

                $response = $response->withStatus(403);

                if (!$request->isXhr()) {
                    $controller = new PiecesPHP\Core\BaseController(false);
                    $controller->render('pages/403');
                } else {
                    $response = $response->withJson("403 Forbidden");
                }

                return $response;

            })($request, $response);
        }

    }

    //Definición de menús
    $silentModeRolesSetted = Roles::getSilentMode();
    Roles::setSilentMode(true);
    require_once basepath("app/config/menu.php");
    if (isset($config['menus']) && is_array($config['menus'])) {
        set_config('menus', $config['menus']);
    }
    Roles::setSilentMode($silentModeRolesSetted);

    if (APP_CONFIGURATION_MODULE) {
        //Configuraciones de la aplicación tomadas desde la base de datos
        $configurations = AppConfigModel::getConfigurations();

        foreach ($configurations as $name => $value) {
            set_config($name, $value);
        }

        $mail_config = get_config('mail');
        if (!is_scalar($mail_config)) {
            $mail_config = json_encode($mail_config);
            $mail_config = json_decode($mail_config, true);
            set_config('mail', $mail_config);
        }

        if (strlen(get_title()) == 0) {
            set_title(AppConfigModel::getConfigValue('title_app'));
        }
    }

    return $next($request, $response);
});

set_config('upload_dir', basepath('statics/uploads'));
set_config('upload_dir_url', baseurl('statics/uploads'));
set_config('slim_app', $app);

//Definición de rutas
require_once basepath("app/config/routes.php");

//Configuraciones finales
require_once basepath("app/config/final-configurations.php");

/** Activar enrutador */
get_config('slim_app')->run();
