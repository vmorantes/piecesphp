<?php

/**
 * AdminPanelController.php
 */

namespace PiecesPHP\AdminPanel\Controllers;

use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\AvatarModel;
use PiecesPHP\UserSystem\ORM\TicketsLogModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use MySpace\Controllers\MySpaceController;
use MySpace\MySpaceRoutes;
use News\Controllers\NewsController;
use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Utilities\OsTicket\OsTicketAPI;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\CSSVariables;
use Publications\Controllers\PublicationsController;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

/**
 * AdminPanelController.
 *
 * Controlador del panel administrativo
 *
 * @package     App\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class AdminPanelController extends BaseController
{

    /**
     * Las tres vistas propias de este armazón viven en el módulo, y las pide ESTE ayudante, que
     * lleva el directorio del módulo. En `$this` no puede ir: lo heredarían las 42 clases que
     * extienden de aquí, y las trece que no fijan el suyo buscarían TODAS sus vistas en este
     * módulo. Medido el 2026-09-26: dos pantallas en HTTP 500 y ninguna puerta lo vio.
     *
     * Se tipa por el ancestro común porque el árbol tiene 23 clases `HelperController` distintas y
     * ninguna es covariante con otra: con un tipo concreto, 31 errores de PHPStan.
     *
     * @var BaseController
     */
    protected $moduleViews = null;

    //Va el último de los controladores del sistema: sus hijos sin trait propio heredarían el prefijo «admin».
    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $baseRouteName = 'admin';

    /**
     * Usuario logueado
     *
     * @var \stdClass
     */
    protected $user = null;

    /**
     * @var \PiecesPHP\Core\BaseModel
     */
    protected $modelUsers;

    const ADMIN_LANG_GROUP = 'adminZone';

    /**
     * @return static
     */
    public function __construct()
    {
        parent::__construct(false); //No cargar ningún modelo automáticamente
        $this->init();
        $this->moduleViews = (new HelperController($this->user, $this->getGlobalVariables()))
            ->setInstanceViewDir(__DIR__ . '/../Views/');
        set_title('Dashboard');
    }

    /**
     * Vista principal
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function indexView(Request $req, Response $res)
    {
        if (MySpaceRoutes::ENABLE) {
            $controller = new MySpaceController();
            return $controller->mySpaceView($req, $res);
        } else {

            add_global_asset('statics/core/css/dashboard.css', 'css');

            $publicationsListLink = PublicationsController::routeName('list');

            $data = [];
            $data['publicationsListLink'] = $publicationsListLink;

            $this->render('panel/layout/header', [
                'containerClasses' => [
                    'with-banner',
                ],
            ]);
            $this->moduleViews->render('panel/pages/dashboard', $data);
            $this->render('panel/layout/footer');
        }

        return $res;
    }

    /**
     * Acerca de
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function aboutFramework(Request $req, Response $res)
    {

        add_global_asset('statics/core/css/about-framework.css', 'css');

        set_title(__(AdminPanelController::ADMIN_LANG_GROUP, 'Acerca de'));

        $publicationsListLink = PublicationsController::routeName('list');

        $data = [];
        $data['publicationsListLink'] = $publicationsListLink;

        $this->render('panel/layout/header');
        $this->moduleViews->render('panel/pages/about-framework', $data);
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function errorLog(Request $req, Response $res)
    {
        $file = LOG_ERRORS_PATH . '/error.log.json';
        $log = file_exists($file) ? file_get_contents($file) : '';
        $log = json_decode($log, true);
        if (json_last_error() != \JSON_ERROR_NONE  || !is_array($log)) {
            $log = [];
        }
        return $res->withJson($log);
    }

    /**
     * Prueba del adaptador de CropperJS
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function cropperAdapterTestingView(Request $req, Response $res)
    {
        import_cropper();

        $data = [];

        $this->render('panel/layout/header');
        $this->moduleViews->render('panel/pages/test-cropper', $data);
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * Enviar mensaje al soporte
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function sendTicket(Request $req, Response $res)
    {
        $name = $req->getParsedBodyParam('name', null);
        $email = $req->getParsedBodyParam('email', null);
        $subject = $req->getParsedBodyParam('subject', null);
        $comments = $req->getParsedBodyParam('comments', null);

        $params_ok = !in_array(null, [
            $subject,
            $email,
            $name,
            $comments,
        ]);

        $operation_name = __(self::ADMIN_LANG_GROUP, 'Crear ticket de soporte');
        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $message_create = __(self::ADMIN_LANG_GROUP, 'Ticket de soporte creado.');
        $message_unknow_error = __(self::ADMIN_LANG_GROUP, 'Ha ocurrido un error inesperado.');
        $message_unexpected_or_missing_params = __(self::ADMIN_LANG_GROUP, 'Información faltante o inesperada.');

        if ($params_ok) {

            $osTicketApi = new OsTicketAPI();

            $subject = "$subject " . get_title();

            $success = $osTicketApi->createTicket($name, $email, $subject, $comments);
            $result->setValue('osTicketApi', [
                'url' => $osTicketApi->getHttpClient()->getRequestURI(),
                'reponse' => $osTicketApi->getHttpClient()->getResponseHeaders(),
            ]);

            $logRequest = new TicketsLogModel();
            $logRequest->created = new \DateTime();
            $logRequest->name = $name;
            $logRequest->email = $email;
            $logRequest->message = $comments;
            $logRequest->information = [
                'subject' => $subject,
                'email_sended' => $success,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ];
            $logRequest->type = (string) __(self::ADMIN_LANG_GROUP, 'Ticket soporte dentro del panel administrativo (osTicket).');
            $logRequest->save();

            if ($success) {
                $result->setValue('reload', true);
                $result
                    ->setMessage($message_create);
                //La operación se creó con este nombre en el constructor: si faltara, es un fallo de verdad.
                ($result->operation($operation_name) ?? throw new \LogicException('La operación del resultado no existe.'))->setSuccess(true);
            } else {
                $result
                    ->setMessage($message_unknow_error);
            }
        } else {
            $result
                ->setMessage($message_unexpected_or_missing_params);
        }

        return $res->withJson($result);
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $groupSegmentURL = $group->getGroupSegment();
        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';
        $classname = self::class;
        $allRoles = array_keys(UsersModel::TYPES_USERS);

        //──── GET ─────────────────────────────────────────────────────────────────────────

        //Generales
        $group->register([
            new Route(
                $lastIsBar ? '' : '[/]',
                self::class . ':indexView',
                'admin',
                'GET',
                true
            ),
            new Route(
                "{$startRoute}about[/]",
                self::class . ':aboutFramework',
                'admin-about-framework',
                'GET',
                true,
                null,
                $allRoles
            ),
            new Route(
                "{$startRoute}cropper-testing[/]",
                self::class . ':cropperAdapterTestingView',
                'admin-cropper-testing',
                'GET',
                true,
                null,
                [
                    UsersModel::TYPE_USER_ROOT,
                ]
            ),
        ]);

        //Errores
        $group->register([
            new Route(
                "{$startRoute}error-log[/]",
                $classname . ':errorLog',
                'admin-error-log',
                'GET',
                true
            ),
        ]);

        //Variables css globales
        $cssGlobalVariables = function (Request $request, Response $response) {
            $css = CSSVariables::instance('global');
            return $css->toResponse($request, $response, false);
        };

        $routeStatics = [
            new Route(
                "{$startRoute}/global-variables/variables.css[/]",
                $cssGlobalVariables,
                'admin-global-variables-css',
                'GET'
            ),
        ];
        $group->register($routeStatics);

        //──── POST ─────────────────────────────────────────────────────────────────────────

        return $group;
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function usersRoutes(RouteGroup $group)
    {
        return UsersController::routes($group);
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function ticketsRoutes(RouteGroup $group)
    {
        $groupSegmentURL = $group->getGroupSegment();
        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';
        $classname = self::class;
        /**
         * @var array<string>
         */
        $all_roles = array_keys(UsersModel::TYPES_USERS);

        //──── GET ─────────────────────────────────────────────────────────────────────────

        //──── POST ─────────────────────────────────────────────────────────────────────────

        $group->register([
            new Route(
                "{$startRoute}create[/]",
                $classname . ':sendTicket',
                'admin-tickets-create',
                'POST',
                true,
                null,
                $all_roles
            ),
        ]);

        return $group;
    }

    /**
     * @return void
     */
    protected function init()
    {
        $api_url = get_config('osTicketAPI');
        $api_key = get_config('osTicketAPIKey');

        OsTicketAPI::setBaseURL($api_url);
        OsTicketAPI::setBaseAPIKey($api_key);

        $view_data = [];

        $currentUser = getLoggedFrameworkUser();
        if ($currentUser !== null) {
            $this->user = $currentUser->userStdClass;
        }

        if ($this->user instanceof \stdClass) {
            $view_data['user'] = $this->user;
            $this->user->avatar = AvatarModel::getAvatar($this->user->id);
            $this->user->hasAvatar = !is_null($this->user->avatar);
        }

        $this->setVariables($view_data);

        /* JQuery */
        import_jquery();
        /* Semantic */
        import_semantic();
        /* DataTables */
        import_datatables();
        /* NProgress */
        import_nprogress();
        /* izitoast */
        import_izitoast();
        /* Cropper */
        import_cropper();
        /* Módulo de noticias */
        if (NEWS_MODULE) {
            add_global_asset(NewsController::pathFrontNewsAdapter(), 'js');
        }
        /* Librerías de la aplicación */
        import_app_libraries([
            'adminStyle',
            'attachmentPlaceholder',
        ]);
        add_global_asset(base_url(ADMIN_AREA_PATH_JS . '/main.js'), 'js');

        if (LOCATIONS_ENABLED) {
            import_locations([], true, true);
        }
    }

    /**
     * @return void
     */
    private function showPermissionsRoles()
    {
        $routes = get_routes();
        $total_routes = count($routes);
        $result = [];
        $typesUsers = UsersModel::getTypesUser();

        foreach ($typesUsers as $code => $name) {
            if (!isset($result[$name])) {
                $result[$name] = [
                    'total_routes' => $total_routes,
                    'total_allowed' => 0,
                    'total_restricted' => 0,
                    'allowed' => [],
                    'restricted' => [],
                ];
            }
            foreach (get_routes() as $route_info) {
                $route_name = $route_info['name'];
                $allowed = Roles::hasPermissions($route_name, (int) $code);
                if ($allowed) {
                    $result[$name]['allowed'][] = $route_name;
                } else {
                    $result[$name]['restricted'][] = $route_name;
                }
            }
            $result[$name]['total_allowed'] = count($result[$name]['allowed']);
            $result[$name]['total_restricted'] = count($result[$name]['restricted']);
        }
        header('content-type:application/json');
        echo json_encode($result);
        die;
    }
}
