<?php

/**
 * CategoriesRoutes.php
 */

namespace Forms\Categories;

use Forms\Categories\Controllers\CategoriesController;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\CSSVariables;

/**
 * CategoriesRoutes.
 *
 * @package     Forms\Categories
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class CategoriesRoutes
{

    const ENABLE = FORMS_MODULE_CATEGORIES_ENABLE && FORMS_MODULE_ENABLE;

    /**
     * @param RouteGroup $groupAdministration
     * @param RouteGroup $groupPublic
     * @return RouteGroup[] Con los índices groupAdministration y groupPublic
     */
    public static function routes(RouteGroup $groupAdministration)
    {
        if (self::ENABLE) {

            $sqlCreate = [
                (new \PiecesPHP\Core\Database\SchemeCreator(new \Forms\Categories\Mappers\CategoriesMapper()))->getSQL(),
            ];
            //header('Content-Type: text/sql');echo implode("\r\n", $sqlCreate);exit;

            $groupAdministration = CategoriesController::routes($groupAdministration);

            self::staticResolver($groupAdministration);

            CategoriesLang::injectLang();

        }

        return [
            'groupAdministration' => $groupAdministration,
        ];
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
            new Route('formularios/categorias-forms/statics-resolver/globals-vars.css', $cssGlobalVariables, CategoriesRoutes::class . '-global-vars'),
            new Route('formularios/categorias-forms/statics-resolver/[{params:.*}]', $callableHandler, CategoriesRoutes::class),
        ];

        $group->register($routeStatics);

    }

}
