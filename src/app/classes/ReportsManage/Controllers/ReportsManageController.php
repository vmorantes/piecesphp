<?php

/**
 * ReportsManageController.php
 */

namespace ReportsManage\Controllers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\AdminPanel\DashboardCard;
use PiecesPHP\AdminPanel\DashboardCardBody;
use PiecesPHP\AdminPanel\DashboardCardRegistry;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;
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

        $title = __(self::LANG_GROUP, 'Informe general');
        set_title($title);

        $data = [];
        $data['langGroup'] = self::LANG_GROUP;
        $data['title'] = $title;
        //La misma frase que la tarjeta del inicio (registerDashboardCard()): la pantalla y la tarjeta dicen lo mismo.
        $data['description'] = __(self::LANG_GROUP, 'Las cifras de la plataforma: publicaciones, organizaciones y usuarios.');
        $data['breadcrumbs'] = [
            __(self::LANG_GROUP, 'Inicio') => [
                'url' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName(''),
            ],
            $title,
        ];

        import_apexcharts();

        set_custom_assets([
            ReportsManageRoutes::staticRoute(self::BASE_CSS_DIR . '/generic-report.css'),
        ], 'css');
        set_custom_assets([
            'statics/core/js/statistics-chart-legend.js',
            ReportsManageRoutes::staticRoute(self::BASE_JS_DIR . '/generic-report.js'),
        ], 'js');

        $this->helpController->render('panel/layout/header');
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
     * La tarjeta «Informe general» del inicio: antes el informe ERA el inicio de root, del administrador general y del
     * institucional; ahora es una tarjeta más, que un clon puede quitar, y lleva a su pantalla.
     *
     * @return void
     */
    public static function registerDashboardCard(): void
    {
        DashboardCardRegistry::register(new DashboardCard(
            'general-report',
            __(self::LANG_GROUP, 'Informe general'),
            'chart bar outline',
            self::$baseRouteName . '-generic-report-view',
            fn(UserDataPackage $user): DashboardCardBody => DashboardCardBody::action(
                self::routeName('generic-report-view'),
                __(self::LANG_GROUP, 'Las cifras de la plataforma: publicaciones, organizaciones y usuarios.'),
                __(self::LANG_GROUP, 'Ver el informe')
            ),
            50
        ));
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

    /**
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * PUNTO DE VARIACIÓN DEL MÓDULO. Aquí, y en ningún otro sitio, van las reglas de negocio
     * que oculten una ruta que los roles SÍ permiten. Está vacío a propósito: es la plantilla,
     * y su presencia dice dónde se escribe la regla el día que aparezca.
     *
     * Devolver `false` ESTRECHA lo que ya concedieron los roles; nunca ensancha. `routeName()`
     * llama a este método SIEMPRE, y `allowedRoute()` no hace más que preguntarle a
     * `routeName()` si devolvió cadena.
     *
     * @param string $name
     * @param string $route
     * @param array $params
     * @return bool
     */
    protected static function _allowedRoute(string $name, string $route, array $params = [])
    {
        return true;
    }
}
