<?php

/**
 * DataImportExportUtilityRoutes.php
 */

namespace DataImportExportUtility;

use DataImportExportUtility\Controllers\DataImportExportUtilityController;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
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

    const ENABLE = false;

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

            $sidebar->addItem(new MenuGroup([
                'name' => __(DataImportExportUtilityLang::LANG_GROUP, 'Import/Export'),
                'asLink' => true,
                'icon' => 'file excel',
                'href' => DataImportExportUtilityController::routeName('show-routes'),
                'visible' => DataImportExportUtilityController::allowedRoute('show-routes'),
                'position' => 8000,
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
            new Route('data-import-export-utility/statics/globals-vars.css', $cssGlobalVariables, DataImportExportUtilityRoutes::class . '-global-vars'),
            new Route('data-import-export-utility/statics/[{params:.*}]', $callableHandler, DataImportExportUtilityRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
