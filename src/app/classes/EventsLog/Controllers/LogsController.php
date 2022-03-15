<?php

/**
 * LogsController.php
 */

namespace EventsLog\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use EventsLog\LogsLang;
use EventsLog\LogsRoutes;
use EventsLog\Mappers\LogsMapper;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use Slim\Http\Request as Request;
use Slim\Http\Response as Response;

/**
 * LogsController.
 *
 * @package     EventsLog\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class LogsController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'actions-logs';
    /**
     * @var string
     */
    protected static $baseRouteName = 'actions-logs-admin';
    /**
     * @var string
     */
    protected static $title = 'Registro de actividad';
    /**
     * @var string
     */
    protected static $pluralTitle = 'Registro de actividad';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_VIEW_DIR = 'log';
    const BASE_JS_DIR = 'js/log';
    const BASE_CSS_DIR = 'css';
    const LANG_GROUP = LogsLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct(false); //No cargar ningún modelo automáticamente.

        self::$title = __(self::LANG_GROUP, self::$title);
        self::$pluralTitle = __(self::LANG_GROUP, self::$pluralTitle);

        $this->model = (new LogsMapper())->getModel();
        set_title(self::$title);

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(LogsRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(LogsRoutes::staticRoute(self::BASE_CSS_DIR . '/log.css'), 'css');

    }

    /**
     * @return void
     */
    public function listView()
    {

        $processTableLink = self::routeName('datatables');

        $title = __(self::LANG_GROUP, "Últimos eventos");
        set_title($title);

        $data = [];
        $data['processTableLink'] = $processTableLink;
        $data['langGroup'] = self::LANG_GROUP;
        $data['title'] = $title;

        set_custom_assets([
            LogsRoutes::staticRoute(self::BASE_JS_DIR . '/list.js'),
        ], 'js');

        $this->helpController->render('panel/layout/header');
        self::view('list', $data);
        $this->helpController->render('panel/layout/footer');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dataTables(Request $request, Response $response)
    {

        $whereString = null;

        $where = [];

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        $selectFields = LogsMapper::fieldsToSelect();

        $columnsOrder = [
            'textMessageReplacement',
            'createdAtFormat',
        ];

        $customOrder = [
            'createdAt' => 'DESC',
            'textMessageReplacement' => 'ASC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([

            'where_string' => $whereString,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new LogsMapper(),
            'request' => $request,
            'on_set_data' => function ($e) {

                //$mapper = LogsMapper::objectToMapper($e);

                $columns = [];

                $columns[] = $e->textMessageReplacement;
                $columns[] = ucfirst($e->createdAtFormat);
                return $columns;
            },

        ]);

        return $response->withJson($result->getValues());
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
        return (new static )->render(self::BASE_VIEW_DIR . '/' . trim($name, '/'), $data, $mode, $format);
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

        $allow = strlen($route) > 0;

        if ($allow) {

            if ($name == 'SAMPLE') { //do something
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

        $simpleName = $name;

        if (!is_null($name)) {
            $name = trim($name);
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$baseRouteName . $name : self::$baseRouteName;

        $allowed = false;
        $current_user = get_config('current_user');

        if ($current_user !== false) {
            $allowed = Roles::hasPermissions($name, (int) $current_user->type);
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

        $allRoles = array_keys(UsersModel::TYPES_USERS);

        $permisos_listado = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN,
        ];

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //HTML
            new Route( //Vista del listado
                "{$startRoute}/list[/]",
                $classname . ':listView',
                self::$baseRouteName . '-list',
                'GET',
                true,
                null,
                $permisos_listado
            ),

            //JSON
            new Route( //Datos para datatables
                "{$startRoute}/datatables[/]",
                $classname . ':dataTables',
                self::$baseRouteName . '-datatables',
                'GET',
                true,
                null,
                $permisos_listado
            ),
        ];

        $group->register($routes);

        return $group;
    }
}
