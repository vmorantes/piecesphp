<?php

/**
 * PersonsRoutes.php
 */

namespace Persons;

use Persons\Controllers\PersonsController;
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
 * PersonsRoutes.
 *
 * @package     Persons
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class PersonsRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    const ENABLE = PERSONS_MODULE;

    /**
     * @param RouteGroup $groupAdministration
     * @param RouteGroup $groupPublic
     * @return RouteGroup[] Con los índices groupAdministration y groupPublic
     */
    public static function routes(RouteGroup $groupAdministration)
    {
        if (self::ENABLE) {

            $sqlCreate = [
                (new \PiecesPHP\Core\Database\SchemeCreator(new \Persons\Mappers\PersonsMapper()))->getSQL(),
            ];
            //header('Content-Type: text/sql');echo implode("\r\n", $sqlCreate);exit;

            $groupAdministration = PersonsController::routes($groupAdministration);

            self::staticResolver($groupAdministration);

            PersonsLang::injectLang();

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
                'name' => __(PersonsLang::LANG_GROUP, 'Personas'),
                'asLink' => true,
                'href' => PersonsController::routeName('list'),
                'visible' => PersonsController::allowedRoute('list'),
                'position' => 2,
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
        $route = get_route(self::class);
        return is_string($route) ? append_to_url(str_replace('/[{params:.*}]', '', $route), $segment) : $segment;
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
            new Route('personas/statics-resolver/globals-vars.css', $cssGlobalVariables, PersonsRoutes::class . '-global-vars'),
            new Route('personas/statics-resolver/[{params:.*}]', $callableHandler, PersonsRoutes::class),
        ];

        $group->register($routeStatics);

    }

}
