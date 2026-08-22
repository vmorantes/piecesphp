<?php

/**
 * MySpaceController.php
 */

namespace MySpace\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use ContentNavigationHub\ContentNavigationHubRoutes;
use ContentNavigationHub\Controllers\ContentNavigationHubController;
use Documents\Mappers\DocumentsMapper;
use ImagesRepository\Mappers\ImagesRepositoryMapper;
use MySpace\MySpaceLang;
use MySpace\MySpaceRoutes;
use News\Controllers\NewsController;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;
use Publications\Controllers\PublicationsController;
use Publications\PublicationsRoutes;
use ReportsManage\Controllers\ReportsManageController;
use ReportsManage\ReportsManageRoutes;
use SystemApprovals\SystemApprovalsRoutes;
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

    use ControllerRoutingTrait;

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
     * @return Response
     */
    public function mySpaceView(Request $request, Response $response)
    {

        $currentUser = getLoggedFrameworkUserOrFail();
        $currentUserID = $currentUser->id;
        $currentUserType = $currentUser->type;
        $noBaseView = [
            UsersModel::TYPE_USER_GENERAL,
            UsersModel::TYPE_USER_ADMIN_ORG,
            UsersModel::TYPE_USER_COMUNICACIONES,
            UsersModel::TYPE_USER_GOOGLE_PLAY,
        ];

        if (!in_array($currentUserType, $noBaseView) && ReportsManageRoutes::ENABLE) {

            return (new ReportsManageController())->genericReportView($request, $response);

        } else {

            $normalSpace = false;
            $isApproved = SystemApprovalManager::getInstance()->isApproved(UsersModel::class, $currentUserID);

            if ($isApproved) {

                if ($currentUserType == UsersModel::TYPE_USER_COMUNICACIONES && PublicationsRoutes::ENABLE) {
                    return (new PublicationsController())->listView($request, $response);
                } elseif ($currentUserType != UsersModel::TYPE_USER_ROOT && ContentNavigationHubRoutes::ENABLE) {
                    return (new ContentNavigationHubController())->applicationCallsListView($request, $response);
                } else {
                    $normalSpace = true;
                }

            }

            if ($normalSpace) {

                if (in_array($currentUserType, UsersModel::TYPES_USER_SHOULD_HAVE_PROFILE) && SystemApprovalsRoutes::ENABLE) {

                    return $response->withRedirect(MyProfileController::routeName('my-profile'));

                } else {

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

                    $currentUser = getLoggedFrameworkUserOrFail();
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
                    //$this->render('my-space-empty', $data);
                    $this->helpController->render('panel/layout/footer');

                }

            }

        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function userSecurity(Request $request, Response $response)
    {

        set_title(__(AdminPanelController::ADMIN_LANG_GROUP, 'Opciones de seguridad'));

        set_custom_assets([
            MySpaceRoutes::staticRoute(self::BASE_JS_DIR . '/user-security.js'),
        ], 'js');

        set_custom_assets([
            MySpaceRoutes::staticRoute(self::BASE_CSS_DIR . '/user-security.css'),
        ], 'css');

        import_apexcharts();
        import_qrcodejs();

        $currentUser = getLoggedFrameworkUserOrFail();

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
        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
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

        $currentUser = getLoggedFrameworkUserOrFail();

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
        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
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
