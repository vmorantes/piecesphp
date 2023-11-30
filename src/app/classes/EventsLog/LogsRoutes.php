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
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\ServerStatics;

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

    const ENABLE = EVENTS_LOG_MODULE;

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

            \PiecesPHP\Core\Routing\InvocationStrategy::appendBeforeCallMethod(function () {
                self::init();
            });

        }

        return $group;
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
                'name' => __(LogsLang::LANG_GROUP, 'Últimos movimientos'),
                'icon' => 'history',
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

            $css = [
                "--main-brand-color:" . '#282828' . ";",
                "--color-text-over-main-brand-color:" . 'white' . ";",
            ];

            $css = implode("\n", $css);

            $css = ":root {\n{$css}\n}";

            $lastModification = new \DateTime('2022-01-01 00:00:00');
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
