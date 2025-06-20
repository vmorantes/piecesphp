<?php

/**
 * ReportsManageController.php
 */

namespace ReportsManage\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use MySpace\MySpaceLang;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use ReportsManage\Queries\ReportsManageQueries;
use ReportsManage\ReportsManageLang;
use ReportsManage\ReportsManageRoutes;

/**
 * ReportsManageController.
 *
 * @package     ReportsManage\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class ReportsManageController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'reports-manage';
    /**
     * @var string
     */
    protected static $baseRouteName = 'reports-manage-admin';
    /**
     * @var string
     */
    protected static $title = 'Reportes';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_VIEW_DIR = 'reports-manage';
    const BASE_JS_DIR = 'js/reports-manage';
    const BASE_CSS_DIR = 'css/reports-manage';
    const LANG_GROUP = ReportsManageLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();

        self::$title = __(self::LANG_GROUP, self::$title);

        set_title(self::$title);

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(ReportsManageRoutes::staticRoute('globals-vars.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function genericReportView(Request $request, Response $response)
    {

        set_title(__(self::LANG_GROUP, 'Reportes'));

        $currentUser = getLoggedFrameworkUser();
        $subtitle = $currentUser->getMapper()->getFullName();

        $data = [];
        $data['langGroup'] = MySpaceLang::LANG_GROUP;
        $data['subtitle'] = $subtitle;

        import_apexcharts();

        set_custom_assets([
            ReportsManageRoutes::staticRoute(self::BASE_CSS_DIR . '/generic-report.css'),
        ], 'css');
        set_custom_assets([
            ReportsManageRoutes::staticRoute(self::BASE_JS_DIR . '/generic-report.js'),
        ], 'js');

        $this->helpController->render('panel/layout/header', [
            'bodyClasses' => [
                'gradient-base',
            ],
            'containerClasses' => [],
        ]);
        $this->render('generic-report-view', $data);
        $this->helpController->render('panel/layout/footer');

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function render(string $name = "index", array $data = [], bool $mode = true, bool $format = false)
    {
        return parent::render(self::BASE_VIEW_DIR . '/' . trim($name, '/'), $data, $mode, $format);
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
        $routes = [];

        $groupSegmentURL = $group->getGroupSegment();

        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = ($lastIsBar ? '' : '/') . self::$URLDirectory;

        $classname = self::class;

        /**
         * @var array<string>
         */
        $allRoles = array_keys(UsersModel::TYPES_USERS);

        //Permisos
        $allAccess = ReportsManageQueries::ROLES_WITH_REPORTS;

        //Listado
        $genericReport = array_unique(array_merge($allAccess, []));

        $routes = [
            //GET
            new Route(
                "{$startRoute}[/]",
                $classname . ':genericReportView',
                self::$baseRouteName . '-generic-report-view',
                'GET',
                true,
                null,
                $genericReport
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
