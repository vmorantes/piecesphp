<?php

/**
 * OrganizationsRoutes.php
 */

namespace Organizations;

use App\Model\UsersModel;
use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
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

            $sqlCreate = [
                (new \PiecesPHP\Core\Database\SchemeCreator(new OrganizationMapper()))->getSQL(),
            ];
            $showSQL = false;
            //$showSQL = true;
            if ($showSQL) {
                header('Content-Type: text/sql');
                echo strReplaceTemplate(implode("\r\n", $sqlCreate), [
                    'createdBy` int' => 'createdBy` bigint',
                    'modifiedBy` int' => 'modifiedBy` bigint',
                ]);
                exit;
            }

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
             * @var MenuGroupCollection $sidebar
             */
            $sidebar = get_config('menus')['sidebar'];

            $isRoot = $currentUser->type == UsersModel::TYPE_USER_ROOT;
            if ($isRoot) {
                $sidebar->addItem(new MenuGroup([
                    'name' => __(OrganizationsLang::LANG_GROUP, 'Gestión de organizaciones'),
                    'icon' => 'list',
                    'href' => OrganizationsController::routeName('list'),
                    'visible' => OrganizationsController::allowedRoute('list'),
                    'position' => 10,
                    'asLink' => true,
                ]));
            } else {
                $sidebar->addItem(new MenuGroup([
                    'name' => __(OrganizationsLang::LANG_GROUP, 'Gestión de la organización'),
                    'icon' => 'list',
                    'items' => [
                        new MenuItem([
                            'text' => __(OrganizationsLang::LANG_GROUP, 'Organización'),
                            'href' => OrganizationsController::routeName('forms-edit', [
                                'id' => getLoggedFrameworkUser()->organization,
                            ]),
                            'visible' => OrganizationsController::allowedRoute('forms-edit', [
                                'id' => getLoggedFrameworkUser()->organization,
                            ]),
                        ]),
                        new MenuItem([
                            'text' => __(OrganizationsLang::LANG_GROUP, 'Usuarios'),
                            'href' => get_route('users-list'),
                            'visible' => Roles::hasPermissions('users-list', $currentUserType),
                        ]),
                        new MenuItem([
                            'text' => __(OrganizationsLang::LANG_GROUP, 'Agregar usuarios'),
                            'href' => get_route('users-selection-create'),
                            'visible' => Roles::hasPermissions('users-selection-create', $currentUserType),
                        ]),
                    ],
                    'position' => 10,
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
            new Route('organizations/statics/globals-vars.css', $cssGlobalVariables, OrganizationsRoutes::class . '-global-vars'),
            new Route('organizations/statics/[{params:.*}]', $callableHandler, OrganizationsRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
