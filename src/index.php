<?php

use PiecesPHP\Core\BaseToken;
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

$app = new \Slim\App(get_config('slim_container'));

//Acciones antes de mostrar una ruta
$app->add(function (\Slim\Http\Request $request, \Slim\Http\Response $response, callable $next) {

    $route = $request->getAttribute('route');

    if (empty($route)) {
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    $name_route = $route->getName(); //Nombre de la ruta
    $methods = $route->getMethods(); //Métodos que acepta la ruta solicitada
    $arguments = $route->getArguments(); //Argumentos pasados en la url

    //Control de acceso por login
    $control_access_login = get_config('control_access_login');

    //Verifica si el control automático de acceso por login está activado
    if ($control_access_login) {

        $info_route = get_route_info($name_route); //Información de la ruta actual

        //Verifica si la ruta requiere estar logueado
        if ($info_route['require_login']) {

            //Acciones en caso de no estar logueado
            if (!SessionToken::isActiveSession()) {

                if ($name_route != 'login-form') {

                    if ($request->isXhr()) {

                        $url_login = remove_last_char_on('/', get_route('login-form'));
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
                        set_flash_message('session_errors', [__('errors', 'not_login'), __('general', 'should_be_logged')]);
                        set_flash_message('requested_uri', get_current_url());
                        return $response->withRedirect(get_route('login-form'));
                    }

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

    //Verifica si el control automático de acceso por login está activado
    if ($control_access_login) {

        //Verifica que esté logueado
        if (SessionToken::isActiveSession()) {

            if ($name_route == 'login-form') {
                return $response->withRedirect($admin_url);
            }

        }
    }

    //Control de permisos por roles
    $roles_control = get_config('roles');
    $active_roles_control = isset($roles_control['active']) ? $roles_control['active'] : false;
    $user = null;
    $current_role = null;
    $has_permissions = null;

    //Verifica si hay una sesion activa
    if (SessionToken::isActiveSession()) {

        $jwt = SessionToken::getSession(); //JWT de la sesion
        $user = BaseToken::getData($jwt); //Información del usuario
        $user = $user instanceof \stdClass ? (new \App\Model\UsersModel())->getModel()->select()->where(['id' => $user->id])->row() : $user;

        if ($user instanceof \stdClass) {
            set_config('current_user', $user);
            Roles::setCurrentRole($user->type); //Se establece el rol
            $current_role = Roles::getCurrentRole(); //Se obtiene el rol
        }

        //Definición de menús
        require_once basepath("app/config/menu.php");
        if (isset($config['menus']) && is_array($config['menus'])) {
            set_config('menus', $config['menus']);
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

    return $next($request, $response);
});

set_config('upload_dir', basepath('statics/uploads'));
set_config('upload_dir_url', baseurl('statics/uploads'));
set_config('slim_app', $app);

//Definición de rutas
require_once basepath("app/config/routes.php");

/** Activar enrutador */
get_config('slim_app')->run();
