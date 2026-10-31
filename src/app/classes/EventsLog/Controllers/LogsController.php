<?php

/**
 * LogsController.php
 */

namespace EventsLog\Controllers;

use EventsLog\LogsLang;
use EventsLog\LogsRoutes;
use EventsLog\Mappers\LogsMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItemGroup;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItemGroup;
use PiecesPHP\Core\Database\ORM\Statements\HavingSegment;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\Helpers\RowActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserHierarchy;

/**
 * LogsController.
 *
 * @package     EventsLog\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class LogsController extends AdminPanelController
{

    use ControllerRoutingTrait;

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
        parent::__construct();

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
     * El registro de acciones, recortado por la jerarquía de P133 (UserHierarchy): root, todo; los demás, lo suyo y lo
     * de rangos menores (el de organización, de la suya). Cuenta quien actuó de verdad (actorFields()).
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function listView(Request $request, Response $response)
    {
        $title = __(self::LANG_GROUP, 'Registro de actividad');
        set_title($title);

        set_custom_assets([
            LogsRoutes::staticRoute(self::BASE_JS_DIR . '/list.js'),
        ], 'js');

        $labels = LogsMapper::messageLabels();
        $types = [];
        foreach (array_keys(LogsMapper::MESSAGES) as $type) {
            $types[$type] = $labels[$type] ?? $type;
        }
        asort($types);

        $currentUser = getLoggedFrameworkUser();
        $isRoot = $currentUser !== null && (int) $currentUser->type === UsersModel::TYPE_USER_ROOT;
        $isOrganizationAdmin = $currentUser !== null && (int) $currentUser->type === UsersModel::TYPE_USER_ADMIN_ORG;

        $this->helpController->render('panel/layout/header');
        self::view('list', [
            'langGroup' => self::LANG_GROUP,
            'title' => $title,
            'description' => __(self::LANG_GROUP, 'Quién hizo qué en el panel, y cuándo.'),
            'breadcrumbs' => [
                __(self::LANG_GROUP, 'Inicio') => [
                    'url' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName(''),
                ],
                $title,
            ],
            'helpController' => $this->helpController,
            'processTableLink' => self::routeName('datatables'),
            'types' => $types,
            'scope' => $isRoot ? '' : ($isOrganizationAdmin
                ? __(self::LANG_GROUP, 'Solo sus acciones y las de los usuarios de menor rango de su organización.')
                : __(self::LANG_GROUP, 'Solo sus acciones y las de los usuarios de menor rango.')),
        ]);
        $this->helpController->render('panel/layout/footer');

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dataTables(Request $request, Response $response)
    {
        $table = LogsMapper::TABLE;
        $selectFields = array_merge(LogsMapper::fieldsToSelect(), self::actorFields());

        //Todo filtro, por marcador: nada de la petición entra en el SQL como texto.
        $where = [];
        $from = self::dateFilter($request->getQueryParam('from', ''));
        if ($from !== null) {
            $where[] = new WhereItem("{$table}.createdAt", WhereItem::GREATER_OR_EQUAL_OPERATOR, $from . ' 00:00:00', WhereItem::AND_OPERATOR);
        }
        $to = self::dateFilter($request->getQueryParam('to', ''));
        if ($to !== null) {
            $where[] = new WhereItem("{$table}.createdAt", WhereItem::LESS_OR_EQUAL_OPERATOR, $to . ' 23:59:59', WhereItem::AND_OPERATOR);
        }
        $type = $request->getQueryParam('type', '');
        if (is_string($type) && array_key_exists($type, LogsMapper::MESSAGES)) {
            $where[] = new WhereItem("{$table}.textMessage", WhereItem::EQUAL_OPERATOR, LogsMapper::MESSAGES[$type], WhereItem::AND_OPERATOR);
        }
        $whereSegment = null;
        if ($where !== []) {
            $whereSegment = new WhereSegment();
            $whereSegment->addGroup(new WhereItemGroup($where));
        }

        $havingSegment = new HavingSegment();
        $hasHaving = false;
        $visibility = UserHierarchy::visibilityGroup(getLoggedFrameworkUser(), 'effectiveActorID', 'effectiveActorRank', 'effectiveActorOrganization');
        if ($visibility !== null) {
            $havingSegment->addGroup($visibility);
            $hasHaving = true;
        }
        $user = $request->getQueryParam('user', '');
        if (is_string($user) && trim($user) !== '') {
            $havingSegment->addGroup(new HavingItemGroup([
                new HavingItem('createdByUser', HavingItem::LIKE_OPERATOR, '%' . mb_substr(trim($user), 0, 100) . '%', HavingItem::AND_OPERATOR),
            ]));
            $hasHaving = true;
        }

        $columnsOrder = [
            "{$table}.id",
            'moduleName',
            'textMessageReplacement',
            'createdByUser',
            'ip',
            'createdAt',
            "{$table}.id",
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $detailLabel = __(self::LANG_GROUP, 'Ver detalle');
        $result = DataTablesHelper::process([
            'where_segment' => $whereSegment,
            'having_segment' => $hasHaving ? $havingSegment : null,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => [
                'id' => 'DESC',
            ],
            'mapper' => new LogsMapper(),
            'request' => $request,
            'on_set_data' => function ($e) use ($detailLabel) {
                $columns = [];
                $columns[] = $e->idPadding;
                $columns[] = escape_html((string) $e->moduleName);
                //Plantillas de texto plano con el usuario dentro, que cambia cualquiera: se escapa el resultado.
                $columns[] = escape_html((string) $e->textMessageReplacement);
                //`createdBy` no basta: con suplantación es el suplantado, y sin nadie conectado es
                //un 1 que no significa el principal. `meta.actor` dice quién actuaba de verdad.
                $columns[] = escape_html(LogsMapper::actorLabel(
                    isset($e->actorKind) && is_string($e->actorKind) ? $e->actorKind : null,
                    isset($e->actorUser) && is_string($e->actorUser) ? $e->actorUser : null,
                    isset($e->createdByUser) && is_string($e->createdByUser) ? $e->createdByUser : null
                ));
                $columns[] = escape_html((string) $e->ip);
                $columns[] = escape_html(ucfirst((string) $e->createdAtFormat));
                $columns[] = RowActions::cell(
                    RowActions::action($detailLabel, '#', 'eye', ['data-actions-log-detail' => self::routeName('detail', ['id' => (int) $e->id])])
                );
                return $columns;
            },
        ]);

        return $response->withJson($result->getValues());
    }

    /**
     * Una acción, con su `meta` legible. Responde JSON: la vista lo pinta como TEXTO.
     *
     * Se comprueba a sí misma con la jerarquía (P133): una acción que no se puede ver no se abre pidiéndola por id.
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     * @return Response
     */
    public function detail(Request $request, Response $response, array $args = [])
    {
        $id = $args['id'] ?? null;
        $notFound = fn() => $response->withJson(['error' => __(self::LANG_GROUP, 'No existe esa acción.')], 404);
        if (!is_string($id) || preg_match('/^[1-9][0-9]{0,18}$/', $id) !== 1) {
            return $notFound();
        }
        $table = LogsMapper::TABLE;
        $model = LogsMapper::model();
        $model->resetAll();
        $fields = array_merge(LogsMapper::fieldsToSelect(), self::actorFields());
        $model->select($fields)->where(new WhereSegment([new WhereItem("{$table}.id", WhereItem::EQUAL_OPERATOR, (int) $id)]))->execute();
        $rows = $model->result();
        $row = is_array($rows) && isset($rows[0]) && $rows[0] instanceof \stdClass ? $rows[0] : null;
        //Una acción que no se puede ver responde IGUAL que una que no existe: no se delata cuáles hay.
        $visible = $row !== null && UserHierarchy::canSee(
            getLoggedFrameworkUser(),
            is_numeric($row->effectiveActorID ?? null) ? (int) $row->effectiveActorID : null,
            is_numeric($row->effectiveActorType ?? null) ? (int) $row->effectiveActorType : null,
            is_numeric($row->effectiveActorOrganization ?? null) ? (int) $row->effectiveActorOrganization : null
        );
        if ($row === null || !$visible) {
            return $notFound();
        }
        $meta = is_string($row->meta ?? null) ? json_decode($row->meta, true) : null;
        $variables = is_string($row->textMessageVariables ?? null) ? json_decode($row->textMessageVariables, true) : null;
        $type = array_search($row->textMessage, LogsMapper::MESSAGES, true);
        return $response->withJson([
            'id' => (int) $row->id,
            'type' => is_string($type) ? (LogsMapper::messageLabels()[$type] ?? $type) : '',
            'message' => (string) $row->textMessageReplacement,
            'user' => LogsMapper::actorLabel(
                isset($row->actorKind) && is_string($row->actorKind) ? $row->actorKind : null,
                isset($row->actorUser) && is_string($row->actorUser) ? $row->actorUser : null,
                isset($row->createdByUser) && is_string($row->createdByUser) ? $row->createdByUser : null
            ),
            'date' => ucfirst((string) $row->createdAtFormat),
            'ip' => (string) ($row->ip ?? ''),
            'country' => (string) ($row->geolocationByIp ?? ''),
            'module' => (string) ($row->moduleName ?? ''),
            'reference' => trim(implode(' ', array_filter([(string) ($row->referenceSource ?? ''), (string) ($row->referenceColumn ?? ''), (string) ($row->referenceValue ?? '')]))),
            'variables' => is_array($variables) ? $variables : [],
            'meta' => is_array($meta) ? $meta : [],
        ], null, \JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * Quién actuó de verdad, para la jerarquía (P133): con suplantación, root (`meta.actor.id`), no el suplantado de
     * `createdBy`; una acción del sistema no es de nadie (rango de root, así que solo root la ve). Solo constantes.
     *
     * @return string[]
     */
    public static function actorFields(): array
    {
        $table = LogsMapper::TABLE;
        $tableUser = UsersModel::TABLE;
        $kind = "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$." . LogsMapper::META_ACTOR . ".kind'))";
        $actorID = "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$." . LogsMapper::META_ACTOR . ".id'))";
        $effective = "(CASE WHEN {$kind} = '" . LogsMapper::ACTOR_IMPERSONATION . "' THEN {$actorID}"
            . " WHEN {$kind} = '" . LogsMapper::ACTOR_SYSTEM . "' THEN NULL ELSE {$table}.createdBy END)";
        $type = "SELECT {$tableUser}.type FROM {$tableUser} WHERE {$tableUser}.id = {$effective}";
        return [
            "{$effective} AS effectiveActorID",
            "({$type}) AS effectiveActorType",
            "(SELECT {$tableUser}.organization FROM {$tableUser} WHERE {$tableUser}.id = {$effective}) AS effectiveActorOrganization",
            UserHierarchy::rankSQL($type) . ' AS effectiveActorRank',
        ];
    }

    /**
     * Un día como lo da el calendario del panel (DD-MM-AAAA) a AAAA-MM-DD.
     *
     * @param mixed $value
     * @return string|null
     */
    protected static function dateFilter($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $date = \DateTime::createFromFormat('!d-m-Y', trim($value));
        return $date !== false && $date->format('d-m-Y') === trim($value) ? $date->format('Y-m-d') : null;
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
        return (new LogsController)->render(self::BASE_VIEW_DIR . '/' . trim($name, '/'), $data, $mode, $format);
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

        //Los administradores, recortados por la jerarquía de P133 (actorFields()).
        $permisos_listado = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_ADMIN_ORG,
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
            new Route( //Una acción, con su meta
                "{$startRoute}/detail/{id}[/]",
                $classname . ':detail',
                self::$baseRouteName . '-detail',
                'GET',
                true,
                null,
                $permisos_listado
            ),
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
