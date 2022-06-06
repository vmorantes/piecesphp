<?php

/**
 * NewsRoutes.php
 */

namespace News;

use News\Controllers\NewsCategoryController;
use News\Controllers\NewsController;
use News\Mappers\NewsCategoryMapper;
use News\Mappers\NewsMapper;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestResponsePiecesPHP;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\CSSVariables;
use Slim\Http\Request;
use Slim\Http\Response;

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

            $groupAdministration = NewsController::routes($groupAdministration);
            $groupAdministration = NewsCategoryController::routes($groupAdministration);

            self::staticResolver($groupAdministration);

            NewsLang::injectLang();

            $groupAdministration->addMiddleware(function (\Slim\Http\Request $request, \Slim\Http\Response $response, callable $next) {

                return $next($request, $response);
            });

            RequestResponsePiecesPHP::appendBeforeCallMethod(function () {
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
                'name' => __(NewsLang::LANG_GROUP, 'Noticias'),
                'icon' => 'file alternate outline',
                'href' => NewsController::routeName('list'),
                'visible' => NewsController::allowedRoute('list'),
                'asLink' => true,
                'position' => 3,
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
            new Route('news/statics/globals-vars.css', $cssGlobalVariables, NewsRoutes::class . '-global-vars'),
            new Route('news/statics/[{params:.*}]', $callableHandler, NewsRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
