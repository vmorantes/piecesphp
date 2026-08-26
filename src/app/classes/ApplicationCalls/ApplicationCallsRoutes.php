<?php

/**
 * ApplicationCallsRoutes.php
 */

namespace ApplicationCalls;

use ApplicationCalls\Controllers\ApplicationCallsController;
use ApplicationCalls\Controllers\ApplicationCallsPublicController;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\CSSVariables;

/**
 * ApplicationCallsRoutes.
 *
 * @package     ApplicationCalls
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class ApplicationCallsRoutes
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

            $groupAdministration = ApplicationCallsController::routes($groupAdministration);
            $groupPublic = ApplicationCallsPublicController::routes($groupPublic);

            self::staticResolver($groupAdministration);

            ApplicationCallsLang::injectLang();

            \PiecesPHP\Core\Routing\InvocationStrategy::appendBeforeCallMethod(function () {
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

            /**
             * @category AddToBackendSidebarMenu
             * @var MenuGroupCollection $sidebar
             */
            $sidebar = get_sidebar_menu();

            //$sidebar->addItem(new MenuGroup([
            //    'name' => __(ApplicationCallsLang::LANG_GROUP, 'Gestionar contenidos'),
            //    'icon' => 'bullhorn',
            //    'href' => ApplicationCallsController::routeName('list'),
            //    'visible' => ApplicationCallsController::allowedRoute('list'),
            //    'asLink' => true,
            //    'position' => 30,
            //]));

        }

        self::$init = true;

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
            new Route('application-calls/statics/globals-vars.css', $cssGlobalVariables, ApplicationCallsRoutes::class . '-global-vars'),
            new Route('application-calls/statics/[{params:.*}]', $callableHandler, ApplicationCallsRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
