<?php

/**
 * MySpaceController.php
 */

namespace MySpace\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use ContentNavigationHub\Controllers\ContentNavigationHubController;
use Documents\Mappers\DocumentsMapper;
use ImagesRepository\Mappers\ImagesRepositoryMapper;
use MySpace\MySpaceLang;
use MySpace\MySpaceRoutes;
use News\Controllers\NewsController;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;
use Publications\Controllers\PublicationsController;
use SystemApprovals\Util\SystemApprovalManager;

/**
 * MySpaceController.
 *
 * @package     MySpace\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class MySpaceController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'my-space';
    /**
     * @var string
     */
    protected static $baseRouteName = 'my-space-admin';

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

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(MySpaceRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(MySpaceRoutes::staticRoute(self::BASE_CSS_DIR . '/my-space.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function mySpaceView(Request $request, Response $response)
    {

        $currentUser = getLoggedFrameworkUser();
        $currentUserID = $currentUser->id;
        $currentUserType = $currentUser->type;
        $noBaseView = [
            UsersModel::TYPE_USER_GENERAL,
            UsersModel::TYPE_USER_ADMIN_ORG,
            UsersModel::TYPE_USER_COMUNICACIONES,
        ];

        if (!in_array($currentUserType, $noBaseView)) {

            set_title(__(self::LANG_GROUP, 'Mi espacio'));

            set_custom_assets([
                NewsController::pathFrontNewsAdapter(),
                MySpaceRoutes::staticRoute(self::BASE_JS_DIR . '/my-space.js'),
            ], 'js');

            set_custom_assets([
                MySpaceRoutes::staticRoute(self::BASE_CSS_DIR . '/base.css'),
                MySpaceRoutes::staticRoute(self::BASE_CSS_DIR . '/others.css'),
                MySpaceRoutes::staticRoute(self::BASE_CSS_DIR . '/news.css'),
                MySpaceRoutes::staticRoute(self::BASE_CSS_DIR . '/my-space.css'),
            ], 'css');

            $currentUser = getLoggedFrameworkUser();
            $qtyDocuments = DocumentsMapper::countAll();
            $qtyImages = ImagesRepositoryMapper::countAll();

            $data = [];
            $data['langGroup'] = self::LANG_GROUP;
            $data['subtitle'] = $currentUser->fullName;
            $data['qtyDocuments'] = $qtyDocuments;
            $data['qtyImages'] = $qtyImages;
            $data['newsAjaxURL'] = NewsController::routeName('ajax-all');

            $this->helpController->render('panel/layout/header', [
                'bodyClasses' => [
                    'gradient-base',
                ],
                'containerClasses' => [],
            ]);
            $this->render('my-space', $data);
            $this->helpController->render('panel/layout/footer');

        } else {

            $isApproved = SystemApprovalManager::getInstance()->isApproved(UsersModel::class, $currentUserID);

            if ($isApproved) {

                if ($currentUserType == UsersModel::TYPE_USER_COMUNICACIONES) {
                    return (new PublicationsController())->listView($request, $response);
                } else {
                    return (new ContentNavigationHubController())->applicationCallsListView($request, $response);
                }

            } else {

                if (in_array($currentUserType, UsersModel::TYPES_USER_SHOULD_HAVE_PROFILE)) {
                    return $response->withRedirect(MyProfileController::routeName('my-profile'));
                } else {
                    $data = [];
                    $data['langGroup'] = self::LANG_GROUP;
                    $data['subtitle'] = $currentUser->fullName;
                    $this->helpController->render('panel/layout/header', [
                        'bodyClasses' => [
                            'gradient-base',
                        ],
                        'containerClasses' => [],
                    ]);
                    $this->render('my-space-empty', $data);
                    $this->helpController->render('panel/layout/footer');
                }

            }

        }

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function userSecurity(Request $request, Response $response)
    {

        set_title(__(AdminPanelController::LANG_GROUP, 'Opciones de seguridad'));

        set_custom_assets([
            MySpaceRoutes::staticRoute(self::BASE_JS_DIR . '/user-security.js'),
        ], 'js');

        set_custom_assets([
            MySpaceRoutes::staticRoute(self::BASE_CSS_DIR . '/user-security.css'),
        ], 'css');

        import_apexcharts();
        import_qrcodejs();

        $currentUser = getLoggedFrameworkUser();

        $data = [];
        $data['langGroup'] = UserSystemFeaturesLang::LANG_GROUP;
        $data['subtitle'] = $currentUser->fullName;

        $this->helpController->render('panel/layout/header', [
            'bodyClasses' => [
                'gradient-base',
            ],
            'containerClasses' => [],
        ]);
        $this->render('user-security', $data);
        $this->helpController->render('panel/layout/footer');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function exampleResources(Request $request, Response $response)
    {

        set_title(__(self::LANG_GROUP, 'Recursos de ejemplo'));

        set_custom_assets([
            //Base
            MySpaceRoutes::staticRoute(self::BASE_JS_DIR . '/example-resources.js'),
        ], 'js');

        set_custom_assets([
            //Base
            MySpaceRoutes::staticRoute(self::BASE_CSS_DIR . '/example-resources.css'),
        ], 'css');

        import_dialog_pcs();
        import_apexcharts();
        import_qrcodejs();

        $currentUser = getLoggedFrameworkUser();

        $data = [];
        $data['langGroup'] = self::LANG_GROUP;
        $data['subtitle'] = $currentUser->fullName;

        $this->helpController->render('panel/layout/header', [
            'bodyClasses' => [
                'gradient-base',
            ],
            'containerClasses' => [],
        ]);
        $this->render('example-resources', $data);
        $this->helpController->render('panel/layout/footer');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function iframesSources(Request $request, Response $response)
    {
        $source = $request->getAttribute('source', null);
        $source = is_string($source) ? $source : '';
        $refererURL = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
        $refererURL = is_string($refererURL) && mb_strlen($refererURL) > 0 ? $refererURL : null;

        if ($source == 'mail-users-template') {
            $this->render('resources/mail-sample', [
                'refererURL' => $refererURL,
            ]);
        } elseif ($source == 'survey-js-creator') {
            $this->render('resources/survey-js-creator', []);
        } elseif ($source == 'survey-js-form') {
            $this->render('resources/survey-js-form', []);
        } else {
            throw new NotFoundException($request, $response);
        }

        return $response;
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
        $onlySupers = [
            UsersModel::TYPE_USER_ROOT,
        ];

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //HTML
            new Route(
                "{$startRoute}[/]",
                $classname . ':mySpaceView',
                self::$baseRouteName . '-my-space',
                'GET',
                true,
                null,
                $allRoles
            ),
            new Route(
                "{$startRoute}/user-security[/]",
                $classname . ':userSecurity',
                self::$baseRouteName . '-user-security',
                'GET',
                true,
                null,
                $allRoles
            ),
            new Route(
                "{$startRoute}/example-resources[/]",
                $classname . ':exampleResources',
                self::$baseRouteName . '-example-resources',
                'GET',
                true,
                null,
                $onlySupers
            ),
            new Route(
                "{$startRoute}/iframe-sources/{source}[/]",
                $classname . ':iframesSources',
                self::$baseRouteName . '-iframe-sources',
                'GET',
                true,
                null,
                $onlySupers
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
