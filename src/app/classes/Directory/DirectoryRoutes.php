<?php

/**
 * DirectoryRoutes.php
 */

namespace Directory;

use Directory\Controllers\AllProfilesController;
use Directory\Controllers\DirectoryController;
use Directory\Controllers\ProfileController;
use Directory\Geo\PersonsGeoSource;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\CSSVariables;
use PiecesPHP\Geo\GeoSourceRegistry;

/**
 * DirectoryRoutes.
 *
 * @package     Directory
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class DirectoryRoutes
{

    const ENABLE = true;

    /**
     * @param RouteGroup $groupAdministration
     * @return RouteGroup[] Con el índice groupAdministration
     */
    public static function routes(RouteGroup $groupAdministration)
    {
        if (self::ENABLE) {

            $groupAdministration = DirectoryController::routes($groupAdministration);
            $groupAdministration = ProfileController::routes($groupAdministration);
            $groupAdministration = AllProfilesController::routes($groupAdministration);

            self::staticResolver($groupAdministration);

            DirectoryLang::injectLang();

            GeoSourceRegistry::register(new PersonsGeoSource());

        }

        return [
            'groupAdministration' => $groupAdministration,
        ];
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

        /**
         * @param Request $request
         * @param Response $response
         * @return Response
         */
        $cssGlobalVariables = function (Request $request, Response $response) {
            $css = CSSVariables::instance('global');
            return $css->toResponse($request, $response, false);
        };

        $routeStatics = [
            new Route('directory/statics/globals-vars.css', $cssGlobalVariables, DirectoryRoutes::class . '-global-vars'),
            new Route('directory/statics/[{params:.*}]', $callableHandler, DirectoryRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
