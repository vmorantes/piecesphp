<?php

/**
 * AdminPanelController.php
 */

namespace App\Controller;

use App\Controller\UsersController;
use App\Model\AvatarModel;
use App\Model\TicketsLogModel;
use App\Model\UsersModel;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Utilities\OsTicket\OsTicketAPI;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * AdminPanelController.
 *
 * Controlador del panel administrativo
 *
 * @package     App\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class AdminPanelController extends \PiecesPHP\Core\BaseController
{

    /**
     * $user
     *
     * Usuario logueado
     *
     * @var \stdClass
     */
    protected $user = null;

    /**
     * $modelUsers
     *
     * @var \PiecesPHP\Core\BaseModel
     */
    protected $modelUsers;

    /**
     * __construct
     *
     * @return static
     */
    public function __construct()
    {
        parent::__construct(false); //No cargar ningún modelo automáticamente
        $this->init();
    }

    /**
     * indexView
     *
     * Vista principal
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return void
     */
    public function indexView(Request $req, Response $res, array $args)
    {
        if (BLACKBOARD_NEWS_ENABLED) {
            add_global_asset(BLACKBOARD_NEWS_PATH_JS . '/main.js', 'js');
            $this->render('panel/layout/header');
            $this->render('panel/pages/main');
            $this->render('panel/layout/footer');
        } else {
            $this->render('panel/layout/header');
            $this->render('pages/sample');
            $this->render('panel/layout/footer');
        }

        return $res;
    }

    /**
     * errorLog
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function errorLog(Request $req, Response $res, array $args)
    {
        $file = LOG_ERRORS_PATH . '/error.log.json';
        $log = file_exists($file) ? file_get_contents($file) : '';
        $log = json_decode($log, true);
        if (json_last_error() != \JSON_ERROR_NONE || !is_array($log)) {
            $log = [];
        }
        return $res->withJson($log);
    }

    /**
     * sendTicket
     *
     * Enviar mensaje al soporte
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function sendTicket(Request $req, Response $res, array $args)
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

        $operation_name = __('adminZone', 'Crear ticket de soporte');
        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $message_create = __('adminZone', 'Ticket de soporte creado.');
        $message_unknow_error = __('adminZone', 'Ha ocurrido un error inesperado.');
        $message_unexpected_or_missing_params = __('adminZone', 'Información faltante o inesperada.');

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
                'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0',
            ];
            $logRequest->type = __('adminZone', 'Ticket soporte dentro del panel administrativo (osTicket).');
            $logRequest->save();

            if ($success) {
                $result->setValue('reload', true);
                $result
                    ->setMessage($message_create)
                    ->operation($operation_name)
                    ->setSuccess(true);
            } else {
                $result
                    ->setMessage($message_unknow_error)
                    ->operation($operation_name);
            }
        } else {
            $result
                ->setMessage($message_unexpected_or_missing_params)
                ->operation($operation_name);
        }

        return $res->withJson($result);
    }

    /**
     * routes
     *
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $groupSegmentURL = $group->getGroupSegment();
        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';
        $classname = self::class;

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

        //──── POST ─────────────────────────────────────────────────────────────────────────

        return $group;
    }

    /**
     * usersRoutes
     *
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function usersRoutes(RouteGroup $group)
    {
        return UsersController::routes($group);
    }

    /**
     * ticketsRoutes
     *
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function ticketsRoutes(RouteGroup $group)
    {
        $groupSegmentURL = $group->getGroupSegment();
        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';
        $classname = self::class;
        $all_roles = array_keys(UsersModel::TYPES_USERS);

        //──── GET ─────────────────────────────────────────────────────────────────────────

        //──── POST ─────────────────────────────────────────────────────────────────────────

        $group->register([
            new Route(
                "{$startRoute}create[/]",
                $classname . ':sendTicket',
                'tickets-create',
                'POST',
                false,
                null,
                $all_roles
            ),
        ]);

        return $group;
    }

    /**
     * init
     *
     * @return void
     */
    protected function init()
    {
        $api_url = get_config('osTicketAPI');
        $api_key = get_config('osTicketAPIKey');

        OsTicketAPI::setBaseURL($api_url);
        OsTicketAPI::setBaseAPIKey($api_key);

        $view_data = [];

        $this->user = get_config('current_user');

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
        /* Librerías de la aplicación */
        import_app_libraries([
            'adminStyle',
        ]);

        add_global_asset(base_url(ADMIN_AREA_PATH_JS . '/main.js'), 'js');

        if (MESSAGES_ENABLED) {
            if (get_current_url() != get_route('messages-inbox') && REFRESH_MESSAGES_STATUS) {
                add_global_asset(MESSAGES_PATH_JS . '/unread.js', 'js');
            }
        }

        if (LOCATIONS_ENABLED) {
            add_global_asset(LOCATIONS_PATH_JS . '/locations-config.js', 'js');
        }
    }

    /**
     * showPermissionsRoles
     *
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
