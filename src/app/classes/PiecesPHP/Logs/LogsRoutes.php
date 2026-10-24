<?php

/**
 * LogsRoutes.php
 */

namespace PiecesPHP\Logs;

use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\Logs\Controllers\LogsController;

/**
 * LogsRoutes - La vista «Registros»: una pantalla con las pestañas que aportan los módulos (LogTabRegistry).
 *
 * @package     PiecesPHP\Logs
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class LogsRoutes
{
    const ENABLE = true;

    /**
     * @param RouteGroup $groupAdministration
     * @return RouteGroup
     */
    public static function routes(RouteGroup $groupAdministration)
    {
        if (self::ENABLE) {
            //Antes de registrar la pestaña: su título se traduce al registrarse.
            LogsLang::injectLang();
            $groupAdministration = LogsController::routes($groupAdministration);
            LogsController::registerLogTab();
            LogsController::registerDashboardCard();
            self::staticResolver($groupAdministration);
        }
        return $groupAdministration;
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
     * @return RouteGroup
     */
    protected static function staticResolver(RouteGroup $group)
    {
        $callableHandler = function (Request $request, Response $response, array $args) {
            $server = new ServerStatics();
            return $server->serve($request, $response, $args, __DIR__ . '/Statics');
        };
        //No es «logs/statics/»: esa la tiene el registro de acciones (EventsLog\LogsRoutes).
        $group->register([
            new Route('logs-viewer/statics/[{params:.*}]', $callableHandler, self::class),
        ]);
        return $group;
    }
}
