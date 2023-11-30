<?php

/**
 * ComponentProviderRoutes.php
 */

namespace Components;

use Components\Controllers\ComponentProvider;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoutePiecesPHP as Request;
use PiecesPHP\Core\Routing\ResponseRoutePiecesPHP as Response;
use PiecesPHP\Core\ServerStatics;

/**
 * ComponentProviderRoutes.
 *
 * @package     Components
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class ComponentProviderRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    const ENABLE = true;

    /**
     * @param RouteGroup $groupAdministration
     * @param RouteGroup $groupPublic
     * @return RouteGroup[] Con los índices groupAdministration y groupPublic
     */
    public static function routes(RouteGroup $groupAdministration, RouteGroup $groupPublic)
    {
        if (self::ENABLE) {

            $groupAdministration = ComponentProvider::routes($groupAdministration);
            self::staticResolver($groupAdministration);

            ComponentProviderLang::injectLang();

            \PiecesPHP\Core\Routing\InvocationStrategyPiecesPHP::appendBeforeCallMethod(function () {
                self::init();
            });

        }

        return [
            'groupAdministration' => $groupAdministration,
            'groupPublic' => $groupPublic,
        ];
    }

    /**
     * @return void|null
     */
    public static function init()
    {

        if (!self::$init) {

            $currentUser = getLoggedFrameworkUser();

            if ($currentUser === null) {
                return null;
            }

            $currentUserType = (int) $currentUser->type;

        }

        self::$init = true;

    }

    /**
     * @param string $segment
     * @return string
     */
    public static function staticRoute(string $segment = '')
    {
        if (self::ENABLE) {
            $route = get_route(self::class);
            return is_string($route) ? append_to_url(str_replace('/[{params:.*}]', '', $route), $segment) : $segment;
        } else {
            return '';
        }
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

        $routeStatics = [
            new Route('components-provider/statics/[{params:.*}]', $callableHandler, ComponentProviderRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
