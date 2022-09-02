<?php

/**
 * DocumentsRoutes.php
 */

namespace Documents;

use Documents\Controllers\DocumentsController;
use Forms\DocumentTypes\DocumentTypesRoutes;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Menu\MenuItem;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestResponsePiecesPHP;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\CSSVariables;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * DocumentsRoutes.
 *
 * @package     Documents
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class DocumentsRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    const ENABLE = DOCUMENTS_MODULE_ENABLE && DocumentTypesRoutes::ENABLE;

    /**
     * @param RouteGroup $groupAdministration
     * @param RouteGroup $groupPublic
     * @return RouteGroup[] Con los índices groupAdministration y groupPublic
     */
    public static function routes(RouteGroup $groupAdministration)
    {
        if (self::ENABLE) {

            $sqlCreate = [
                (new \PiecesPHP\Core\Database\SchemeCreator(new \Documents\Mappers\DocumentsMapper()))->getSQL(),
            ];
            //header('Content-Type: text/sql');echo implode("\r\n", $sqlCreate);exit;

            $groupAdministration = DocumentsController::routes($groupAdministration);

            self::staticResolver($groupAdministration);

            DocumentsLang::injectLang();

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

            $sidebar->addItem(new MenuGroup(
                [
                    'name' => __(DocumentsLang::LANG_GROUP, 'Documentos'),
                    'icon' => 'open folder',
                    'position' => 3,
                    'items' => [
                        new MenuItem([
                            'text' => __(DocumentsLang::LANG_GROUP, 'Gestionar'),
                            'visible' => DocumentsController::allowedRoute('list'),
                            'href' => DocumentsController::routeName('list'),
                        ]),
                        new MenuItem([
                            'text' => __(DocumentsLang::LANG_GROUP, 'Explorar'),
                            'visible' => DocumentsController::allowedRoute('explorer'),
                            'href' => DocumentsController::routeName('explorer'),
                        ]),
                    ],
                ]
            ));

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
            new Route('document/statics-resolver/globals-vars.css', $cssGlobalVariables, DocumentsRoutes::class . '-global-vars'),
            new Route('document/statics-resolver/[{params:.*}]', $callableHandler, DocumentsRoutes::class),
        ];

        $group->register($routeStatics);

    }

}
