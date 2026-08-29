<?php

/**
 * ContentNavigationHubController.php
 */

namespace ContentNavigationHub\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use ContentNavigationHub\ContentNavigationHubLang;
use ContentNavigationHub\ContentNavigationHubRoutes;
use MySpace\Controllers\AllProfilesController;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;

/**
 * ContentNavigationHubController.
 *
 * @package     ContentNavigationHub\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class ContentNavigationHubController extends AdminPanelController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $URLDirectory = 'content-hub';
    /**
     * @var string
     */
    protected static $baseRouteName = 'content-navigation-hub-admin';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    protected static ?ContentNavigationHubController $renderInstance = null;

    const BASE_VIEW_DIR = '';
    const BASE_JS_DIR = 'js';
    const BASE_CSS_DIR = 'css';
    const LANG_GROUP = ContentNavigationHubLang::LANG_GROUP;

    const ENABLE_CACHE = true;

    public function __construct()
    {
        parent::__construct();

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(ContentNavigationHubRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(ContentNavigationHubRoutes::staticRoute(self::BASE_CSS_DIR . '/content-navigation-hub.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function profileListView(Request $request, Response $response)
    {

        $title = __(self::LANG_GROUP, 'Listado de actores');
        $description = '';

        set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

        $processTableLink = AllProfilesController::routeName('datatables');

        $data = [];
        $data['langGroup'] = self::LANG_GROUP;
        $data['title'] = $title;
        $data['description'] = $description;
        $data['processTableLink'] = $processTableLink;
        $data['breadcrumbs'] = get_breadcrumbs([
            __(self::LANG_GROUP, 'Inicio') => [
                'url' => get_route('admin'),
            ],
            $title,
        ]);

        set_custom_assets([
            ContentNavigationHubRoutes::staticRoute(self::BASE_CSS_DIR . '/profiles-list.css'),
        ], 'css');

        set_custom_assets([
            ContentNavigationHubRoutes::staticRoute(self::BASE_JS_DIR . '/profiles/list.js'),
        ], 'js');

        $this->helpController->render('panel/layout/header');
        $this->render('profiles/list', $data);
        $this->helpController->render('panel/layout/footer');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function contentsMapView(Request $request, Response $response)
    {

        remove_imported_asset('locations');
        $title = __(ADMIN_MENU_LANG_GROUP, 'Mapa de actores y contenidos');
        $description = '';

        set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

        $data = [];
        $data['langGroup'] = self::LANG_GROUP;

        set_custom_assets([
            ContentNavigationHubRoutes::staticRoute(self::BASE_CSS_DIR . '/contents-map.css'),
        ], 'css');

        set_custom_assets([
            ContentNavigationHubRoutes::staticRoute(self::BASE_JS_DIR . '/contents/map.js'),
        ], 'js');

        $this->helpController->render('panel/layout/header', [
            'bodyClasses' => [
                'contents-map',
            ],
        ]);
        $this->render('contents/map', $data);
        $this->helpController->render('panel/layout/footer');

    }

    /**
     * @inheritDoc
     */
    public function render(string $name = "index", array $data = [], bool $mode = true, bool $format = false)
    {
        $name = mb_strlen(self::BASE_VIEW_DIR) > 0 ? self::BASE_VIEW_DIR . '/' . trim($name, '/') : trim($name, '/');
        return parent::render($name, $data, $mode, $format);
    }

    /**
     * @inheritDoc
     */
    public static function view(string $name = "index", array $data = [], bool $mode = true, bool $format = false)
    {
        if (self::$renderInstance === null) {
            self::$renderInstance = new ContentNavigationHubController();
        }
        return self::$renderInstance->render($name, $data, $mode, $format);
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
        $list = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_ADMIN_ORG,
            UsersModel::TYPE_USER_GENERAL,
            UsersModel::TYPE_USER_INSTITUCIONAL,
        ];
        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //HTML
            new Route( //Vista del listado: Convocatorias
                "{$startRoute}/profiles-list[/]",
                $classname . ':profileListView',
                self::$baseRouteName . '-profiles-list',
                'GET',
                true,
                null,
                $list
            ),
            new Route( //Vista del mapa de contenidos
                "{$startRoute}/contents-map[/]",
                $classname . ':contentsMapView',
                self::$baseRouteName . '-contents-map',
                'GET',
                true,
                null,
                $list
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
