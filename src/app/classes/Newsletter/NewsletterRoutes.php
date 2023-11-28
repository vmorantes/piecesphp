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
use PiecesPHP\Core\Routing\RequestRoutePiecesPHP as Request;
use PiecesPHP\Core\Routing\ResponseRoutePiecesPHP as Response;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\CSSVariables;

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

    const ENABLE = NEWSLETTER_MODULE;

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
             * @var MenuGroupCollection $sidebar
             */
            $sidebar = get_config('menus')['sidebar'];

            $sidebar->addItem(new MenuGroup([
                'name' => __(NewsletterLang::LANG_GROUP, 'Suscriptores'),
                'icon' => 'bell',
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
            new Route('newsletter/statics/globals-vars.css', $cssGlobalVariables, NewsletterRoutes::class . '-global-vars'),
            new Route('newsletter/statics/[{params:.*}]', $callableHandler, NewsletterRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
