<?php

/**
 * HelpersSystemRoutes.php
 */

namespace PiecesPHP\BuiltIn\Helpers;

use PiecesPHP\BuiltIn\Helpers\Controllers\GenericContentController;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\CSSVariables;

/**
 * HelpersSystemRoutes.
 *
 * @package     PiecesPHP\BuiltIn\Helpers;
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class HelpersSystemRoutes
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
    public static function routes(RouteGroup $groupAdministration, )
    {
        if (self::ENABLE) {

            $groupAdministration = GenericContentController::routes($groupAdministration);

            self::staticResolver($groupAdministration);

            HelpersSystemLang::injectLang();

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
             * @var MenuGroupCollection $sidebar
             */
            $sidebar = get_config('menus')['sidebar'];

            $sidebar->addItem(new MenuGroup([
                'name' => __(HelpersSystemLang::LANG_GROUP, 'Imagen principal'),
                'icon' => 'images',
                'asLink' => true,
                'href' => GenericContentController::routeName('forms-home-image'),
                'visible' => GenericContentController::allowedRoute('forms-home-image'),
                'position' => MenuGroupCollection::BAND_CONTENTS + 40,
            ]));

            //«Variables de entorno» ya no está en el menú lateral: sus dos pantallas viven en Configuración › Integraciones
            //(SettingsController::panelGroups(), P134 a).

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
            new Route('helpers-system/statics/globals-vars.css', $cssGlobalVariables, HelpersSystemRoutes::class . '-global-vars'),
            new Route('helpers-system/statics/[{params:.*}]', $callableHandler, HelpersSystemRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
