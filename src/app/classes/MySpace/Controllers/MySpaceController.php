<?php

/**
 * MySpaceController.php
 */

namespace MySpace\Controllers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Documents\Mappers\DocumentsMapper;
use EventsLog\Mappers\LogsMapper;
use MySpace\MySpaceLang;
use MySpace\MySpaceRoutes;
use News\Controllers\NewsController;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\Controllers\UsersController;
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

                    $data = [];
                    $data['langGroup'] = self::LANG_GROUP;
                    $data['subtitle'] = $currentUser->fullName;
                    $data['qtyDocuments'] = $qtyDocuments;
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
     * Cierra TODAS las sesiones del usuario, incluida la de esta petición (ADR 0026).
     *
     * El sujeto sale de la sesión, JAMÁS del cuerpo. No emite token nuevo: un token robado no puede echar al dueño y
     * quedarse con una sesión fresca. Durante una suplantación no se puede usar: cerraría las sesiones del suplantado.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function revokeMySessions(Request $request, Response $response)
    {
        $langGroup = UserSystemFeaturesLang::LANG_GROUP;
        $currentUser = getLoggedFrameworkUserOrFail();

        if (self::isImpersonating((int) $currentUser->id)) {
            return $response->withStatus(403)->withJson([
                'success' => false,
                'name' => __($langGroup, 'Cerrar sesiones'),
                'message' => __($langGroup, 'No se pueden cerrar las sesiones de un usuario mientras se actúa como él.'),
            ]);
        }

        $revoked = (new UsersModel())->revokeSessions((int) $currentUser->id);

        if (!$revoked) {
            return $response->withStatus(500)->withJson([
                'success' => false,
                'name' => __($langGroup, 'Cerrar sesiones'),
                'message' => __($langGroup, 'No se pudieron cerrar las sesiones.'),
            ]);
        }

        LogsMapper::addLog(LogsMapper::MSG_REVOKE_OWN_SESSIONS, [
            '%username%' => $currentUser->username,
        ], 'id', (string) $currentUser->id, UsersModel::TABLE);

        self::forgetSessionCookie();

        return $response->withJson([
            'success' => true,
            'name' => __($langGroup, 'Cerrar sesiones'),
            'message' => __($langGroup, 'Se cerraron todas sus sesiones. Tendrá que volver a ingresar.'),
            'values' => [
                'redirect' => true,
                'redirect_to' => UsersController::routeName('form-login'),
            ],
        ]);
    }

    /**
     * Root actuando como otro usuario: `index.php` guarda el id original de root y deja en la sesión al suplantado.
     *
     * @param int $currentUserID
     * @return bool
     */
    public static function isImpersonating(int $currentUserID): bool
    {
        $rootOriginalID = get_config(ROOT_ORIGINAL_ID_CONFIG_NAME);
        return Validator::isInteger($rootOriginalID) && (int) $rootOriginalID !== $currentUserID;
    }

    /**
     * Caduca la cookie de sesión en las dos formas en que la pone el cliente: la del host y la del dominio.
     *
     * @return void
     */
    private static function forgetSessionCookie(): void
    {
        $name = SessionToken::tokenName();
        $host = isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST']) ? explode(':', $_SERVER['HTTP_HOST'])[0] : '';
        setcookie($name, '', ['expires' => 1, 'path' => '/']);
        if ($host !== '') {
            setcookie($name, '', ['expires' => 1, 'path' => '/', 'domain' => $host]);
        }
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

            //──── POST ──────────────────────────────────────────────────────────────────────────────
            new Route(
                "{$startRoute}/revoke-my-sessions[/]",
                $classname . ':revokeMySessions',
                self::$baseRouteName . '-revoke-my-sessions',
                'POST',
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
