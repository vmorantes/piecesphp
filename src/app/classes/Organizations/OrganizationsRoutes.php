<?php

/**
 * OrganizationsRoutes.php
 */

namespace Organizations;

use PiecesPHP\UserSystem\ORM\UsersModel;
use Organizations\Controllers\OrganizationsController;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Menu\MenuItem;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\CSSVariables;

/**
 * OrganizationsRoutes.
 *
 * @package     Organizations
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class OrganizationsRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    const ENABLE = ORGANIZATIONS_MODULE;

    /**
     * @param RouteGroup $groupAdministration
     * @param RouteGroup $groupPublic
     * @return RouteGroup[] Con los índices groupAdministration y groupPublic
     */
    public static function routes(RouteGroup $groupAdministration, RouteGroup $groupPublic)
    {
        if (self::ENABLE) {

            $groupAdministration = OrganizationsController::routes($groupAdministration);

            self::staticResolver($groupAdministration);

            OrganizationsLang::injectLang();

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

            $dontRequireOrganization = in_array($currentUserType, UsersModel::TYPES_USER_DONT_REQUIRE_ORGANIZATION);

            if ($dontRequireOrganization) {
                $sidebar->addItem(new MenuGroup([
                    'name' => __(OrganizationsLang::LANG_GROUP, 'Gestión de organizaciones'),
                    'icon' => 'list',
                    'href' => OrganizationsController::routeName('list'),
                    'visible' => OrganizationsController::allowedRoute('list'),
                    'position' => 80,
                    'asLink' => true,
                ]));
            } else {
                $sidebar->addItem(new MenuGroup([
                    'name' => __(OrganizationsLang::LANG_GROUP, 'Gestión de la organización'),
                    'icon' => 'list',
                    'items' => array_merge(
                        //Sin organización no hay a cuál enlazar: el usuario no recibe la entrada (pendientes.md 379.1).
                        $currentUser->organization !== null ? [
                            new MenuItem([
                                'text' => __(OrganizationsLang::LANG_GROUP, 'Organización'),
                                'href' => OrganizationsController::routeName('forms-edit', [
                                    'id' => $currentUser->organization,
                                ]),
                                'visible' => OrganizationsController::allowedRoute('forms-edit', [
                                    'id' => $currentUser->organization,
                                ]),
                            ]),
                        ] : [],
                        [
                            new MenuItem([
                                'text' => __(OrganizationsLang::LANG_GROUP, 'Usuarios'),
                                'href' => \PiecesPHP\UserSystem\Controllers\UsersController::routeName('list'),
                                'visible' => Roles::hasPermissions('users-list', $currentUserType),
                            ]),
                            new MenuItem([
                                'text' => __(OrganizationsLang::LANG_GROUP, 'Agregar usuarios'),
                                'href' => \PiecesPHP\UserSystem\Controllers\UsersController::routeName('selection-create'),
                                'visible' => Roles::hasPermissions('users-selection-create', $currentUserType),
                            ]),
                        ]
                    ),
                    'position' => 80,
                ]));
            }

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
            new Route('organizations/statics/globals-vars.css', $cssGlobalVariables, OrganizationsRoutes::class . '-global-vars'),
            new Route('organizations/statics/[{params:.*}]', $callableHandler, OrganizationsRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
