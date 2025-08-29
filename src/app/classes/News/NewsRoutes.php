<?php

/**
 * NewsRoutes.php
 */

namespace News;

use News\Controllers\NewsCategoryController;
use News\Controllers\NewsController;
use News\Mappers\NewsCategoryMapper;
use News\Mappers\NewsMapper;
use News\Mappers\NewsReadedMapper;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\CSSVariables;

/**
 * NewsRoutes.
 *
 * @package     News
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class NewsRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    const ENABLE = NEWS_MODULE;

    /**
     * @param RouteGroup $groupAdministration
     * @return RouteGroup[] Con los índices groupAdministration
     */
    public static function routes(RouteGroup $groupAdministration)
    {
        if (self::ENABLE) {

            $sqlCreate = [
                (new \PiecesPHP\Core\Database\SchemeCreator(new NewsCategoryMapper()))->getSQL(),
                (new \PiecesPHP\Core\Database\SchemeCreator(new NewsMapper()))->getSQL(),
                (new \PiecesPHP\Core\Database\SchemeCreator(new NewsReadedMapper()))->getSQL(),
            ];
            $showSQL = false;
            //$showSQL = true;
            if ($showSQL) {
                header('Content-Type: text/sql');
                echo strReplaceTemplate(implode("\r\n", $sqlCreate), [
                    'createdBy` int' => 'createdBy` bigint',
                    'modifiedBy` int' => 'modifiedBy` bigint',
                    'readerUser` int' => 'readerUser` bigint',
                ]);
                exit;
            }

            $groupAdministration = NewsController::routes($groupAdministration);
            $groupAdministration = NewsCategoryController::routes($groupAdministration);

            self::staticResolver($groupAdministration);

            NewsLang::injectLang();

            \PiecesPHP\Core\Routing\InvocationStrategy::appendBeforeCallMethod(function () {
                self::init();
                add_to_front_configurations('NewsModuleMarkAsReadedEndpoint', NewsController::routeName('actions-mark-as-read', ['newsID' => '{ID}']));
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
                'name' => __(NewsLang::LANG_GROUP, 'Noticias internas'),
                'icon' => 'bell',
                'href' => NewsController::routeName('list'),
                'visible' => NewsController::allowedRoute('list'),
                'asLink' => true,
                'position' => 130,
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
            new Route('news/statics/globals-vars.css', $cssGlobalVariables, NewsRoutes::class . '-global-vars'),
            new Route('news/statics/[{params:.*}]', $callableHandler, NewsRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
