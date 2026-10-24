<?php

/**
 * SettingsRoutes.php
 */

namespace PiecesPHP\Settings;

use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\Settings\Controllers\SettingsController;

/**
 * SettingsRoutes.
 *
 * Las rutas de la configuración y sus estáticos (Statics/: las hojas y el JavaScript de sus pantallas), como las de los
 * demás módulos (MySpaceRoutes).
 *
 * @package     PiecesPHP\Settings
 */
class SettingsRoutes
{

    const ENABLE = true;

    /**
     * @param RouteGroup $group El de la configuración (/admin/configurations).
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        if (self::ENABLE) {
            $group = SettingsController::routes($group);
            self::staticResolver($group);
        }
        return $group;
    }

    /**
     * @param string $segment
     * @return string
     */
    public static function staticRoute(string $segment = '')
    {
        return get_router()->getContainer()->get('staticRouteModulesResolver')(self::class, $segment, __DIR__ . '/Statics', self::ENABLE);
    }

    /**
     * @param RouteGroup $group
     * @return void
     */
    protected static function staticResolver(RouteGroup $group)
    {

        $lastIsBar = last_char($group->getGroupSegment()) == '/';
        $startRoute = $lastIsBar ? '' : '/';

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         */
        $callableHandler = function (Request $request, Response $response, array $args) {
            $server = new ServerStatics();
            return $server->serve($request, $response, $args, __DIR__ . '/Statics');
        };

        $group->register([
            new Route("{$startRoute}statics/[{params:.*}]", $callableHandler, SettingsRoutes::class),
        ]);

    }

}
