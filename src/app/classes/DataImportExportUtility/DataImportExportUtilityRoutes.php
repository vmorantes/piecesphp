<?php

/**
 * DataImportExportUtilityRoutes.php
 */

namespace DataImportExportUtility;

use DataImportExportUtility\Controllers\DataImportExportUtilityController;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoutePiecesPHP as Request;
use PiecesPHP\Core\Routing\ResponseRoutePiecesPHP as Response;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\CSSVariables;

/**
 * DataImportExportUtilityRoutes.
 *
 * @package     DataImportExportUtility
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class DataImportExportUtilityRoutes
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

            $groupAdministration = DataImportExportUtilityController::routes($groupAdministration);

            self::staticResolver($groupAdministration);

            DataImportExportUtilityLang::injectLang();

            $groupAdministration->addMiddleware(function (\PiecesPHP\Core\Routing\RequestRoutePiecesPHP $request, $handler) {
                $response = $handler->handle($request);
                return $response;
            });

            \PiecesPHP\Core\Routing\InvocationStrategyPiecesPHP::appendBeforeCallMethod(function () {
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
                'name' => __(DataImportExportUtilityLang::LANG_GROUP, 'Import/Export'),
                'asLink' => true,
                'icon' => 'file excel',
                'href' => DataImportExportUtilityController::routeName('show-routes'),
                'visible' => DataImportExportUtilityController::allowedRoute('show-routes'),
                'position' => 100,
            ]));

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
            new Route('data-import-export-utility/statics/globals-vars.css', $cssGlobalVariables, DataImportExportUtilityRoutes::class . '-global-vars'),
            new Route('data-import-export-utility/statics/[{params:.*}]', $callableHandler, DataImportExportUtilityRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
