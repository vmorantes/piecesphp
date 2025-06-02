<?php

/**
 * ProfileController.php
 */

namespace MySpace\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use MySpace\MySpaceLang;
use MySpace\MySpaceRoutes;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * ProfileController.
 *
 * @package     MySpace\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class ProfileController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'profile';
    /**
     * @var string
     */
    protected static $baseRouteName = 'profile-admin';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_JS_DIR = 'js';
    const BASE_CSS_DIR = 'css';
    const LANG_GROUP = MySpaceLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/profile');

        add_global_asset(MySpaceRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(MySpaceRoutes::staticRoute(self::BASE_CSS_DIR . '/profile.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function profileView(Request $request, Response $response, array $args = [])
    {

        $userID = array_key_exists('userID', $args) ? $args['userID'] : null;
        $userID = Validator::isInteger($userID) ? (int) $userID : null;
        $userOfProfile = null;
        try {
            $userOfProfile = new UserDataPackage($userID);
        } catch (\Exception $e) {}

        if ($userOfProfile !== null) {

            remove_imported_asset('locations');
            import_locations([], false, true);
            set_custom_assets([
                MySpaceRoutes::staticRoute(self::BASE_JS_DIR . '/profile.js'),
            ], 'js');

            $title = __(self::LANG_GROUP, 'Perfil');
            $description = '';
            set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

            $data = [];
            $data['langGroup'] = self::LANG_GROUP;
            $data['currentUser'] = getLoggedFrameworkUser();
            $data['userOfProfile'] = $userOfProfile;
            $data['title'] = $title;
            $data['description'] = $description;
            $data['breadcrumbs'] = get_breadcrumbs([
                __(self::LANG_GROUP, 'Inicio') => [
                    'url' => get_route('admin'),
                ],
                $title,
            ]);

            if ($userOfProfile->profile->isComplete()) {
                $this->helpController->render('panel/layout/header');
                if (in_array($userOfProfile->type, UsersModel::TYPES_USER_SHOULD_HAVE_PROFILE)) {
                    $this->render('profile', $data);
                }
                $this->helpController->render('panel/layout/footer');
            } else {
                $this->helpController->render('panel/layout/header');
                if (in_array($userOfProfile->type, UsersModel::TYPES_USER_SHOULD_HAVE_PROFILE)) {
                    $this->render('profile-not-completed', $data);
                }
                $this->helpController->render('panel/layout/footer');
            }

        } else {
            throw new NotFoundException($request, $response);
        }

    }

    /**
     * @inheritDoc
     */
    public function render(string $name = "index", array $data = [], bool $mode = true, bool $format = false)
    {
        return parent::render(trim($name, '/'), $data, $mode, $format);
    }

    /**
     * Verificar si una ruta es permitida
     *
     * @param string $name
     * @param array $params
     * @return bool
     */
    public static function allowedRoute(string $name, array $params = [])
    {
        $route = self::routeName($name, $params, true);
        $allow = strlen($route) > 0;
        return $allow;
    }

    /**
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * @param string $name
     * @param string $route
     * @param array $params
     * @return bool
     */
    private static function _allowedRoute(string $name, string $route, array $params = [])
    {

        $getParam = function ($paramName) use ($params) {
            $_POST = isset($_POST) && is_array($_POST) ? $_POST : [];
            $_GET = isset($_GET) && is_array($_GET) ? $_GET : [];
            $paramValue = isset($params[$paramName]) ? $params[$paramName] : null;
            $paramValue = $paramValue !== null ? $paramValue : (isset($_GET[$paramName]) ? $_GET[$paramName] : null);
            $paramValue = $paramValue !== null ? $paramValue : (isset($_POST[$paramName]) ? $_POST[$paramName] : null);
            return $paramValue;
        };

        $allow = strlen($route) > 0;

        if ($allow) {

            $currentUser = getLoggedFrameworkUser();

            if ($currentUser !== null) {

                $currentUserType = $currentUser->type;
                $currentUserID = $currentUser->id;

                if ($name == 'NOMBRE_RUTA') {
                }

            }

        }

        return $allow;
    }

    /**
     * Obtener URL de una ruta
     *
     * @param string $name
     * @param array $params
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(string $name = null, array $params = [], bool $silentOnNotExists = false)
    {

        $simpleName = !is_null($name) ? $name : '';

        if (!is_null($name)) {
            $name = trim($name);
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$baseRouteName . $name : self::$baseRouteName;

        $allowed = false;
        $current_user = getLoggedFrameworkUser();

        if ($current_user !== null) {
            $allowed = Roles::hasPermissions($name, $current_user->type);
        } else {
            $allowed = true;
        }

        $route = '';

        if ($allowed) {
            $route = get_route(
                $name,
                $params,
                $silentOnNotExists
            );
            $route = !is_string($route) ? '' : $route;
        }

        $allow = self::_allowedRoute($simpleName, $route, $params);

        return $allow ? $route : '';
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {

        //Rutas
        $routes = [];

        $groupSegmentURL = $group->getGroupSegment();

        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = ($lastIsBar ? '' : '/') . self::$URLDirectory;

        $classname = self::class;

        /**
         * @var array<string>
         */
        $allRoles = array_keys(UsersModel::TYPES_USERS);

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //HTML
            new Route(
                "{$startRoute}/{userID}[/]",
                $classname . ':profileView',
                self::$baseRouteName . '-profile',
                'GET',
                true,
                null,
                $allRoles
            ),

        ];

        $group->register($routes);

        $group->addMiddleware(function (\PiecesPHP\Core\Routing\RequestRoute $request, $handler) {
            return (new DefaultAccessControlModules(self::$baseRouteName . '-', function (string $name, array $params) {
                return self::routeName($name, $params);
            }))->getResponse($request, $handler);
        });

        return $group;
    }
}
