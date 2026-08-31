<?php

/**
 * AllProfilesController.php
 */

namespace MySpace\Controllers;

use App\Controller\AdminPanelController;
use App\Model\AvatarModel;
use App\Model\UsersModel;
use MySpace\MySpaceLang;
use MySpace\MySpaceRoutes;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

/**
 * AllProfilesController.
 *
 * @package     MySpace\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class AllProfilesController extends AdminPanelController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $URLDirectory = 'all-profiles';
    /**
     * @var string
     */
    protected static $baseRouteName = 'all-profiles-admin';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_JS_DIR = 'js';
    const BASE_CSS_DIR = 'css';
    const LANG_GROUP = MySpaceLang::LANG_GROUP;
    const ONLY_TYPES = [
        UsersModel::TYPE_USER_GENERAL,
        UsersModel::TYPE_USER_ADMIN_ORG,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());
        add_global_asset(MySpaceRoutes::staticRoute('globals-vars.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dataTables(Request $request, Response $response)
    {

        $whereString = null;
        $havingString = null;
        $and = 'AND';

        $allowedUserTypes = implode(',', self::ONLY_TYPES);

        $where = [];
        $having = [
            "systemApprovalStatus = '" . SystemApprovalsMapper::STATUS_APPROVED . "'",
            "AND userType IS NULL OR userType IN ({$allowedUserTypes})",
        ];

        if (false) {
            $beforeOperator = !empty($having) ? $and : '';
            $critery = "FIELD = VALUE";
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        if (!empty($having)) {
            $havingString = trim(implode(' ', $having));
        }

        $tableUserProfile = UserProfileMapper::TABLE;
        $selectFieldsUserProfiles = implode(",", UserProfileMapper::fieldsToSelect());
        $selectForUserProfiles = "SELECT {$selectFieldsUserProfiles} FROM {$tableUserProfile} HAVING userStatus != " . UsersModel::STATUS_USER_INACTIVE . " AND userStatus != " . UsersModel::STATUS_USER_DELETED;
        $tableUserProfile = 'userQuery';
        $selectFieldsUserProfiles = implode(",", [
            "{$tableUserProfile}.belongsTo AS id",
            "{$tableUserProfile}.fullname AS name",
            "{$tableUserProfile}.fullLocation",
            "{$tableUserProfile}.createdAt",
            "'USER' AS type",
            "NULL AS image",
            "type AS userType",
            "systemApprovalStatus",
        ]);
        $selectForUserProfiles = "SELECT {$selectFieldsUserProfiles} " . " FROM ({$selectForUserProfiles}) " . "AS {$tableUserProfile}";

        $initialOrgID = OrganizationMapper::INITIAL_ID_GLOBAL; //Ignorar organización de base
        $tableOrganization = OrganizationMapper::TABLE;
        $selectFieldsOrganizations = implode(",", OrganizationMapper::fieldsToSelect());
        $selectForOrganizations = "SELECT {$selectFieldsOrganizations} FROM {$tableOrganization} WHERE id != {$initialOrgID}";
        $tableOrganization = 'organizationsQuery';
        $selectFieldsOrganizations = implode(",", [
            "{$tableOrganization}.id",
            "{$tableOrganization}.name",
            "{$tableOrganization}.fullLocation",
            "{$tableOrganization}.createdAt",
            "'ORGANIZATION' AS type",
            "logo AS image",
            "NULL AS userType",
            "systemApprovalStatus",
        ]);
        $selectForOrganizations = "SELECT {$selectFieldsOrganizations} " . " FROM ({$selectForOrganizations}) " . "AS {$tableOrganization}";

        $table = 'derivatedTable';
        $mainSQL = "{$selectForUserProfiles} UNION ALL {$selectForOrganizations}";

        $selectFields = [
            'id',
            'name',
            'fullLocation',
            'type',
            'createdAt',
            'image',
            'systemApprovalStatus',
            'userType',
        ];

        $columnsOrder = [
            'name',
            'fullLocation',
        ];

        $customOrder = [
            'createdAt' => 'DESC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::processFromQuery([
            'tableName' => $table,
            'fakeTable' => $mainSQL,
            'where_string' => $whereString,
            'having_string' => $havingString,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'request' => $request,
            'on_set_data' => function ($e) {
                $buttons = [];

                $isOrg = $e->type == 'ORGANIZATION';
                $profileLink = $isOrg ? OrganizationProfileController::routeName('profile', ['organizationID' => $e->id]) : ProfileController::routeName('profile', ['userID' => $e->id]);

                if (mb_strlen($profileLink) > 0) {
                    $profileText = __(self::LANG_GROUP, 'Ir');
                    $profileButton = "<a href='{$profileLink}' class='ui button brand-color icon'>&nbsp;<i class='icon arrow right'></i>&nbsp;</a>";
                    $buttons[] = $profileButton;
                }

                $buttons = implode('', $buttons);
                $columns = [];

                $avatar = $e->image;
                if ($isOrg) {
                    if ($avatar == null) {
                        $avatar = 'statics/images/default-avatar-org.jpg';
                    }
                } else {
                    $avatar = AvatarModel::getAvatar($e->id);
                    $avatar = !is_null($avatar) ? $avatar : baseurl('statics/images/default-avatar.png');
                }

                $orgClass = $isOrg ? 'org' : '';
                $avatar = "<div class='avatar {$orgClass}'><img src='{$avatar}' /></div>";
                $name = "<div class='name'>{$e->name}</div>";


                $columns[] = "<div class='user-info'>{$avatar} {$name}</div>";
                $columns[] = $e->fullLocation;
                $columns[] = $buttons;
                return $columns;
            },

        ]);

        return $response->withJson($result->getValues());
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

        //Rutas
        $routes = [];

        $groupSegmentURL = $group->getGroupSegment();

        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = ($lastIsBar ? '' : '/') . self::$URLDirectory;

        $classname = self::class;

        /**
         * @var array<string>
         */
        $allRoles = array_keys(UsersModel::TYPES_USERS);
        $list = $allRoles;

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────

            //JSON
            new Route( //Datos para datatables
                "{$startRoute}/datatables[/]",
                $classname . ':dataTables',
                self::$baseRouteName . '-datatables',
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
