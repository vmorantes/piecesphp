<?php

/**
 * Locations.php
 */

namespace App\Locations\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * Locations.
 *
 * Controlador de ubicaciones
 *
 * @package     App\Locations\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class Locations extends AdminPanelController
{
    /**
     * $prefixEntity
     *
     * @var string
     */
    protected static $prefixEntity = 'locations';

    /**
     * $title
     *
     * @var string
     */
    public static $title = 'Ubicaciones';

    /**
     * __construct
     *
     * @return static
     */
    public function __construct()
    {
        self::$title = __('locationBackend', self::$title);

        parent::__construct(false); //No cargar ningún modelo automáticamente.
        set_title(self::$title);
    }

    public function indexView(Request $request, Response $response, array $args)
    {
        $this->render('panel/layout/header');
        $this->render('panel/' . self::$prefixEntity . '/main');
        $this->render('panel/layout/footer');
    }

    /**
     * routes
     *
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $group->active(LOCATIONS_ENABLED);

        $routes = [];

        $groupSegmentURL = $group->getGroupSegment();

        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';

        $permisos_gestion = [
            UsersModel::TYPE_USER_ROOT,
        ];

        $permisos_listado = [
            UsersModel::TYPE_USER_ADMIN,
            UsersModel::TYPE_USER_ROOT,
        ];

        //General
        $routes[] = new Route("[$startRoute]", static::class . ':indexView', self::$prefixEntity, 'GET', true, null, $permisos_listado);

        $group->register($routes);

        //Country
        $group->register(
            self::genericManageRoutes($startRoute, self::$prefixEntity, Country::class, 'countries', [
                'edit' => $permisos_gestion,
                'list' => $permisos_listado,
            ])
        );
        //State
        $group->register(
            self::genericManageRoutes($startRoute, self::$prefixEntity, State::class, 'states', [
                'edit' => $permisos_gestion,
                'list' => $permisos_listado,
            ])
        );
        //City
        $group->register(
            self::genericManageRoutes($startRoute, self::$prefixEntity, City::class, 'cities', [
                'edit' => $permisos_gestion,
                'list' => $permisos_listado,
            ])
        );
        //Point
        $group->register(
            self::genericManageRoutes($startRoute, self::$prefixEntity, Point::class, 'points', [
                'edit' => $permisos_gestion,
                'list' => $permisos_listado,
            ])
        );

        return $group;
    }

    /**
     * genericManageRoutes
     *
     * @param string $startRoute
     * @param string $namePrefix
     * @param string $handler
     * @param string $uriPrefix
     * @param array $rolesAllowed
     * @return Route[]
     */
    protected static function genericManageRoutes(string $startRoute, string $namePrefix, string $handler, string $uriPrefix, array $rolesAllowed = [])
    {
        $namePrefix .= '-' . $uriPrefix;
        $startRoute .= $uriPrefix;

        $editPermissions = $rolesAllowed['edit'];
        $listPermissions = $rolesAllowed['list'];

        return [
            new Route(
                "{$startRoute}[/]",
                "{$handler}:{$uriPrefix}",
                "{$namePrefix}-ajax-all",
                'GET'
            ),
            new Route(
                "{$startRoute}/datatables[/]",
                "{$handler}:{$uriPrefix}DataTables",
                "{$namePrefix}-datatables",
                'GET',
                true,
                null,
                $listPermissions
            ),
            new Route(
                "{$startRoute}/list[/]",
                "{$handler}:list",
                "{$namePrefix}-list",
                'GET',
                true,
                null,
                $listPermissions
            ),
            new Route(
                "{$startRoute}/forms/add[/]",
                "{$handler}:addForm",
                "{$namePrefix}-forms-add",
                'GET',
                true,
                null,
                $editPermissions
            ),
            new Route(
                "{$startRoute}/action/add[/]",
                "{$handler}:action",
                "{$namePrefix}-actions-add",
                'POST',
                true,
                null,
                $editPermissions
            ),
            new Route(
                "{$startRoute}/forms/edit/{id}[/]",
                "{$handler}:editForm",
                "{$namePrefix}-forms-edit",
                'GET',
                true,
                null,
                $editPermissions
            ),
            new Route(
                "{$startRoute}/action/edit[/]",
                "{$handler}:action",
                "{$namePrefix}-actions-edit",
                'POST',
                true,
                null,
                $editPermissions
            ),
        ];
    }

}
