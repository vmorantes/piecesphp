<?php

/**
 * MySpaceRoutes.php
 */

namespace MySpace;

use MySpace\Controllers\AllProfilesController;
use MySpace\Controllers\MyOrganizationProfileController;
use MySpace\Controllers\MyProfileController;
use MySpace\Controllers\MySpaceController;
use MySpace\Controllers\OrganizationProfileController;
use MySpace\Controllers\ProfileController;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Menu\MenuItem;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\CSSVariables;

/**
 * MySpaceRoutes.
 *
 * @package     MySpace
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class MySpaceRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    const ENABLE = true;

    /**
     * @param RouteGroup $groupAdministration
     * @return RouteGroup[] Con los índices groupAdministration
     */
    public static function routes(RouteGroup $groupAdministration)
    {
        if (self::ENABLE) {

            $groupAdministration = MySpaceController::routes($groupAdministration);
            $groupAdministration = MyProfileController::routes($groupAdministration);
            $groupAdministration = ProfileController::routes($groupAdministration);
            $groupAdministration = MyOrganizationProfileController::routes($groupAdministration);
            $groupAdministration = OrganizationProfileController::routes($groupAdministration);
            $groupAdministration = AllProfilesController::routes($groupAdministration);

            self::staticResolver($groupAdministration);

            MySpaceLang::injectLang();

            \PiecesPHP\Core\Routing\InvocationStrategy::appendBeforeCallMethod(function () {
                self::init();
            });

        }

        return [
            'groupAdministration' => $groupAdministration,
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

            $sidebar->addItem(new MenuGroup(
                [
                    'name' => __(MySpaceLang::LANG_GROUP, 'Ejemplos de elementos'),
                    'icon' => 'ol list',
                    'position' => 9999,
                    'items' => [
                        new MenuItem([
                            'text' => __(MySpaceLang::LANG_GROUP, 'Varios'),
                            'visible' => MySpaceController::allowedRoute('example-resources'),
                            'href' => MySpaceController::routeName('example-resources'),
                        ]),
                    ],
                ]
            ));

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
            return $server->compileScssServe($request, $response, $args, __DIR__ . '/Statics', [], self::staticRoute());
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
            new Route('my-space/statics/globals-vars.css', $cssGlobalVariables, MySpaceRoutes::class . '-global-vars'),
            new Route('my-space/statics/[{params:.*}]', $callableHandler, MySpaceRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
