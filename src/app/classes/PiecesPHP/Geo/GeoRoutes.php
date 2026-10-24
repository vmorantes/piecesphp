<?php

/**
 * GeoRoutes.php
 */

namespace PiecesPHP\Geo;

use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\Geo\Controllers\GeoController;

/**
 * GeoRoutes - Datos geográficos: el geovisor y sus fuentes registradas (GeoSourceRegistry). Las fuentes las registra
 * cada módulo desde su propio Routes.
 *
 * @package     PiecesPHP\Geo
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class GeoRoutes
{
    const ENABLE = true;

    /**
     * @param RouteGroup $groupAdministration
     * @return RouteGroup
     */
    public static function routes(RouteGroup $groupAdministration)
    {
        if (self::ENABLE) {
            GeoLang::injectLang();
            $groupAdministration = GeoController::routes($groupAdministration);
            self::staticResolver($groupAdministration);
        }
        return $groupAdministration;
    }

    /**
     * Los estáticos de la pantalla del geovisor (Statics/: su hoja y su JS).
     *
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
            new Route('geo/statics/[{params:.*}]', $callableHandler, GeoRoutes::class),
        ]);
    }
}
