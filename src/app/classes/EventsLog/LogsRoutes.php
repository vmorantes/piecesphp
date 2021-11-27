<?php

/**
 * LogsRoutes.php
 */

namespace EventsLog;

use EventsLog\Controllers\LogsController;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestResponsePiecesPHP;
use PiecesPHP\Core\ServerStatics;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * LogsRoutes.
 *
 * @package     Logs
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class LogsRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    const ENABLE = false;

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        if (self::ENABLE) {

            //header('Content-Type: text/sql');echo (new \PiecesPHP\Core\Database\SchemeCreator(new \EventsLog\Mappers\LogsMapper()))->getSQL();exit;

            $group = LogsController::routes($group);

            self::staticResolver($group);

            LogsLang::injectLang();

            $group->addMiddleware(function (\Slim\Http\Request $request, \Slim\Http\Response $response, callable $next) {

                return $next($request, $response);
            });

            RequestResponsePiecesPHP::appendBeforeCallMethod(function () {
                self::init();
            });

        }

        return $group;
    }

    /**
     * @return void
     */
    public static function init()
    {

        if (!self::$init) {

            $currentUser = get_config('current_user');

            if (!($currentUser instanceof \stdClass)) {
                return null;
            }

            $currentUserType = (int) $currentUser->type;

            /**
             * @var MenuGroupCollection $sidebar
             */
            $sidebar = get_config('menus')['sidebar'];

            $sidebar->addItem(new MenuGroup([
                'name' => __(LogsLang::LANG_GROUP, 'Últimos movimientos'),
                'asLink' => true,
                'visible' => LogsController::allowedRoute('list'),
                'href' => LogsController::routeName('list'),
                'position' => 30,
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
        return append_to_url(str_replace('/[{params:.*}]', '', get_route(self::class)), $segment);
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
            return $server->compileScssServe($request, $response, $args, __DIR__ . '/Statics');
        };

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         */
        $cssGlobalVariables = function (Request $request, Response $response, array $args) {

            $css = [
                "--emphasis-color:" . '#282828' . ";",
                "--over-emphasis-color:" . 'white' . ";",
            ];

            $css = implode("\n", $css);

            $css = ":root {\n{$css}\n}";

            $lastModification = \DateTime::createFromFormat('d-m-Y h:i A', '01-05-2021 04:22 PM');
            $headersAndStatus = generateCachingHeadersAndStatus($request, $lastModification, $css);

            foreach ($headersAndStatus['headers'] as $header => $value) {
                $response = $response->withHeader($header, $value);
            }

            return $response
                ->write($css)
                ->withStatus($headersAndStatus['status'])
                ->withHeader('Content-Type', 'text/css');

        };

        $routeStatics = [
            new Route('logs/statics/globals-vars.css', $cssGlobalVariables, LogsRoutes::class . '-global-vars'),
            new Route('logs/statics/[{params:.*}]', $callableHandler, LogsRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
