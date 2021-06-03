<?php

/**
 * NewsletterRoutes.php
 */

namespace Newsletter;

use Newsletter\Controllers\NewsletterController;
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
 * NewsletterRoutes.
 *
 * @package     Newsletter
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class NewsletterRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    const ENABLE = false;

    /**
     * @param RouteGroup $groupAdministration
     * @param RouteGroup $groupPublic
     * @return RouteGroup[] Con los índices groupAdministration y groupPublic
     */
    public static function routes(RouteGroup $groupAdministration, RouteGroup $groupPublic)
    {
        if (self::ENABLE) {

            //echo (new \PiecesPHP\Core\Database\SchemeCreator(new \Newsletter\Mappers\NewsletterSuscriberMapper()))->getSQL();exit;

            $groupAdministration = NewsletterController::routes($groupAdministration);

            self::staticResolver($groupAdministration);

            NewsletterLang::injectLang();

            $groupAdministration->addMiddleware(function (\Slim\Http\Request $request, \Slim\Http\Response $response, callable $next) {

                return $next($request, $response);
            });

            RequestResponsePiecesPHP::appendBeforeCallMethod(function () {
                self::init();
            });

        }

        return [
            'groupAdministration' => $groupAdministration,
            'groupPublic' => $groupPublic,
        ];
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
                'name' => __(NewsletterLang::LANG_GROUP, 'Suscriptores'),
                'asLink' => true,
                'href' => NewsletterController::routeName('list'),
                'visible' => NewsletterController::allowedRoute('list'),
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
            $css = CSSVariables::instance('global');
            return $css->toResponse($request, $response, false);
        };

        $routeStatics = [
            new Route('newsletter/statics/globals-vars.css', $cssGlobalVariables, NewsletterRoutes::class . '-global-vars'),
            new Route('newsletter/statics/[{params:.*}]', $callableHandler, NewsletterRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
