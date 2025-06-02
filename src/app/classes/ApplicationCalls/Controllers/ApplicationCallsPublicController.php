<?php

/**
 * ApplicationCallsPublicController.php
 */

namespace ApplicationCalls\Controllers;

use ApplicationCalls\ApplicationCallsLang;
use ApplicationCalls\ApplicationCallsRoutes;
use ApplicationCalls\Mappers\ApplicationCallsMapper;
use App\Model\AvatarModel;
use App\Model\UsersModel;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Utilities\OsTicket\OsTicketAPI;

/**
 * ApplicationCallsPublicController.
 *
 * @package     ApplicationCalls\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class ApplicationCallsPublicController extends \PiecesPHP\Core\BaseController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'application-calls';

    /**
     * @var string
     */
    protected static $baseRouteName = 'application-calls';

    /**
     * @var BaseController
     */
    protected $helpController = null;

    /**
     * Usuario logueado
     *
     * @var \stdClass
     */
    protected $user = null;

    const BASE_VIEW_DIR = ApplicationCallsController::BASE_VIEW_DIR;
    const BASE_JS_DIR = ApplicationCallsController::BASE_JS_DIR;
    const BASE_CSS_DIR = ApplicationCallsController::BASE_CSS_DIR;
    const LANG_GROUP = ApplicationCallsLang::LANG_GROUP;
    const LANG_GROUP_PUBLIC = ApplicationCallsLang::LANG_GROUP_PUBLIC;

    public function __construct()
    {
        parent::__construct(false); //No cargar ningún modelo automáticamente.
        $this->init();

        $this->model = (new ApplicationCallsMapper())->getModel();
        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        import_jquery();
        import_izitoast();
        import_semantic();
        import_app_libraries();
        import_app_front_libraries();

        add_global_asset(ApplicationCallsRoutes::staticRoute('globals-vars.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function all(Request $request, Response $response)
    {
        return (new ApplicationCallsController)->all($request, $response);
    }

    /**
     * @param string $name
     * @param array $data
     * @param bool $mode
     * @param bool $format
     * @return void|string
     */
    public static function view(string $name, array $data = [], bool $mode = true, bool $format = true)
    {
        return (new ApplicationCallsPublicController)->render($name, $data, $mode, $format);
    }

    /**
     * @inheritDoc
     */
    public function render(string $name = "index", array $data = [], bool $mode = true, bool $format = false)
    {
        return parent::render(self::BASE_VIEW_DIR . '/' . trim($name, '/'), $data, $mode, $format);
    }

    /**
     * @param string $name
     * @param array $params
     * @return bool
     */
    public static function allowedRoute(string $name, array $params = [])
    {

        $route = self::routeName($name, $params, true);
        $allow = strlen($route) > 0;

        if ($allow) {

            if ($name == 'SAMPLE') { //do something
            }

        }

        return $allow;
    }

    /**
     * @param string $name
     * @param array $params
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(string $name = null, array $params = [], bool $silentOnNotExists = false)
    {
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

        if ($allowed) {
            $routeResult = get_route(
                $name,
                $params,
                $silentOnNotExists
            );
            return is_string($routeResult) ? $routeResult : '';
        } else {
            return '';
        }
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
        $all_roles = array_keys(UsersModel::TYPES_USERS);

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //JSON
            new Route( //JSON con todos los elementos
                "{$startRoute}/all[/]",
                $classname . ':all',
                self::$baseRouteName . '-ajax-all',
                'GET'
            ),

        ];

        $group->register($routes);

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
            $this->user->avatar = AvatarModel::getAvatar((int) $this->user->id);
            $this->user->hasAvatar = !is_null($this->user->avatar);
            unset($this->user->password);
        }

        $this->setVariables($view_data);

    }

}
