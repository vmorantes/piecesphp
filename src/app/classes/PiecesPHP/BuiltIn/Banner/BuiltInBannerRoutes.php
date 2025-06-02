<?php

/**
 * BuiltInBannerRoutes.php
 */

namespace PiecesPHP\BuiltIn\Banner;

use PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController;
use PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerPublicController;
use PiecesPHP\BuiltIn\Banner\Mappers\BuiltInBannerMapper;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\CSSVariables;

/**
 * BuiltInBannerRoutes.
 *
 * @package     PiecesPHP\BuiltIn\Banner
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class BuiltInBannerRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    const ENABLE = PIECES_PHP_DYNAMIC_IMAGES_ENABLE;

    /**
     * @param RouteGroup $groupAdministration
     * @param RouteGroup $groupPublic
     * @return RouteGroup[] Con los índices groupAdministration y groupPublic
     */
    public static function routes(RouteGroup $groupAdministration, RouteGroup $groupPublic)
    {
        if (self::ENABLE) {

            $sqlCreate = [
                (new \PiecesPHP\Core\Database\SchemeCreator(new BuiltInBannerMapper()))->getSQL(),
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

            $groupAdministration = BuiltInBannerController::routes($groupAdministration);
            $groupPublic = BuiltInBannerPublicController::routes($groupPublic);

            self::staticResolver($groupAdministration);

            BuiltInBannerLang::injectLang();

            \PiecesPHP\Core\Routing\InvocationStrategy::appendBeforeCallMethod(function () {
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
             * @category AddToBackendSidebarMenu
             * @var MenuGroupCollection $sidebar
             */
            $sidebar = get_sidebar_menu();

            $sidebar->addItem(new MenuGroup([
                'name' => __(BuiltInBannerLang::LANG_GROUP, 'Banners'),
                'icon' => 'images',
                'asLink' => true,
                'href' => BuiltInBannerController::routeName('list'),
                'visible' => BuiltInBannerController::allowedRoute('list'),
                'position' => 110,
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
            new Route('built-in-banner/statics/globals-vars.css', $cssGlobalVariables, BuiltInBannerRoutes::class . '-global-vars'),
            new Route('built-in-banner/statics/[{params:.*}]', $callableHandler, BuiltInBannerRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
