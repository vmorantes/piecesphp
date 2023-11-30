<?php

/**
 * DocumentTypesRoutes.php
 */

namespace Forms\DocumentTypes;

use Forms\DocumentTypes\Controllers\DocumentTypesController;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\CSSVariables;

/**
 * DocumentTypesRoutes.
 *
 * @package     Forms\DocumentTypes
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class DocumentTypesRoutes
{

    const ENABLE = FORMS_MODULE_DOCUMENTS_TYPES_ENABLE && DOCUMENTS_MODULE_ENABLE && FORMS_MODULE_ENABLE;

    /**
     * @param RouteGroup $groupAdministration
     * @return RouteGroup[] Con los índices groupAdministration
     */
    public static function routes(RouteGroup $groupAdministration)
    {
        if (self::ENABLE) {

            $sqlCreate = [
                (new \PiecesPHP\Core\Database\SchemeCreator(new \Forms\DocumentTypes\Mappers\DocumentTypesMapper()))->getSQL(),
            ];
            //header('Content-Type: text/sql');echo implode("\r\n", $sqlCreate);exit;

            $groupAdministration = DocumentTypesController::routes($groupAdministration);

            self::staticResolver($groupAdministration);

            DocumentTypesLang::injectLang();

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
            new Route('forms/documents-types/statics-resolver/globals-vars.css', $cssGlobalVariables, DocumentTypesRoutes::class . '-global-vars'),
            new Route('forms/documents-types/statics-resolver/[{params:.*}]', $callableHandler, DocumentTypesRoutes::class),
        ];

        $group->register($routeStatics);

    }

}
