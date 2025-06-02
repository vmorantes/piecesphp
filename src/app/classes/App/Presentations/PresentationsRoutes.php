<?php

/**
 * PresentationsRoutes.php
 */

namespace App\Presentations;

use App\Model\UsersModel;
use App\Presentations\Controllers\PresentationsCategoryController;
use App\Presentations\Controllers\PresentationsController;
use App\Presentations\Controllers\PresentationsPublicController;
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
 * PresentationsRoutes.
 *
 * @package     App\Presentations
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class PresentationsRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    const APP_PRESENTATIONS_ENABLE = APP_PRESENTATIONS_ENABLE;

    /**
     * @param RouteGroup $groupAdministration
     * @param RouteGroup $groupPublic
     * @return RouteGroup[] Con los índices groupAdministration y groupPublic
     */
    public static function routes(RouteGroup $groupAdministration, RouteGroup $groupPublic)
    {
        if (self::APP_PRESENTATIONS_ENABLE) {

            $groupAdministration = PresentationsController::routes($groupAdministration);
            $groupAdministration = PresentationsCategoryController::routes($groupAdministration);
            $groupPublic = PresentationsPublicController::routes($groupPublic);

            self::staticResolver($groupAdministration);

            PresentationsLang::injectLang();

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

            if ($currentUserType === UsersModel::TYPE_USER_ROOT) {

                $sidebar->addItem(new MenuGroup([
                    'name' => __(PresentationsLang::LANG_GROUP, 'Presentaciones de entrenamiento'),
                    'icon' => 'file powerpoint outline',
                    'items' => [
                        new MenuItem([
                            'text' => __(PresentationsLang::LANG_GROUP, 'Listado (Administación)'),
                            'href' => PresentationsController::routeName('list'),
                            'visible' => PresentationsController::allowedRoute('list'),
                        ]),
                        new MenuItem([
                            'text' => __(PresentationsLang::LANG_GROUP, 'Listado para usuarios'),
                            'href' => PresentationsPublicController::routeName('list'),
                            'visible' => PresentationsPublicController::allowedRoute('list'),
                        ]),
                    ],
                    'position' => 1900,
                ]));

            } else {

                $sidebar->addItem(new MenuGroup(
                    [
                        'name' => __(PresentationsLang::LANG_GROUP, 'Presentaciones'),
                        'icon' => 'file powerpoint outline',
                        'visible' => PresentationsPublicController::allowedRoute('list'),
                        'href' => PresentationsPublicController::routeName('list'),
                        'asLink' => true,
                        'position' => 1900,
                    ]
                ));

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
        if (self::APP_PRESENTATIONS_ENABLE) {
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

        $cssGlobalVariables = function (Request $request, Response $response) {
            $css = CSSVariables::instance('global');
            $css->setVariable('readable-color', 'white', false);
            return $css->toResponse($request, $response, false);
        };

        $routeStatics = [
            new Route('presentations/statics-resolver/globals-vars.css', $cssGlobalVariables, PresentationsRoutes::class . '-global-vars'),
            new Route('presentations/statics-resolver/[{params:.*}]', $callableHandler, PresentationsRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
