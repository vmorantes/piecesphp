<?php

/**
 * ApplicationCallsRoutes.php
 */

namespace ApplicationCalls;

use ApplicationCalls\Controllers\ApplicationCallsController;
use ApplicationCalls\Controllers\ApplicationCallsPublicController;
use ApplicationCalls\Mappers\ApplicationCallsMapper;
use ApplicationCalls\Mappers\AttachmentApplicationCallsMapper;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\CSSVariables;

/**
 * ApplicationCallsRoutes.
 *
 * @package     ApplicationCalls
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class ApplicationCallsRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    const ENABLE = true;

    /**
     * @param RouteGroup $groupAdministration
     * @param RouteGroup $groupPublic
     * @return RouteGroup[] Con los índices groupAdministration y groupPublic
     */
    public static function routes(RouteGroup $groupAdministration, RouteGroup $groupPublic)
    {
        if (self::ENABLE) {

            $sqlCreate = [
                (new \PiecesPHP\Core\Database\SchemeCreator(new ApplicationCallsMapper()))->getSQL(),
                (new \PiecesPHP\Core\Database\SchemeCreator(new AttachmentApplicationCallsMapper()))->getSQL(),
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

            $groupAdministration = ApplicationCallsController::routes($groupAdministration);
            $groupPublic = ApplicationCallsPublicController::routes($groupPublic);

            self::staticResolver($groupAdministration);

            ApplicationCallsLang::injectLang();

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

            //$sidebar->addItem(new MenuGroup([
            //    'name' => __(ApplicationCallsLang::LANG_GROUP, 'Gestionar convocatorias'),
            //    'icon' => 'bullhorn',
            //    'href' => ApplicationCallsController::routeName('list'),
            //    'visible' => ApplicationCallsController::allowedRoute('list'),
            //    'asLink' => true,
            //    'position' => 30,
            //]));

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
            new Route('application-calls/statics/globals-vars.css', $cssGlobalVariables, ApplicationCallsRoutes::class . '-global-vars'),
            new Route('application-calls/statics/[{params:.*}]', $callableHandler, ApplicationCallsRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
