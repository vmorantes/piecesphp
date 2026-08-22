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
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
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

    use ControllerRoutingTrait;

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
     * @return Response
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
