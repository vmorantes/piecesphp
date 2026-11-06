<?php

/**
 * MailCatalogRoutes.php
 */

namespace PiecesPHP\MailCatalog;

use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\MailCatalog\Controllers\MailCatalogController;

/**
 * MailCatalogRoutes - El banco de pruebas de correo (H189): «Correos del sitio», con todos los correos que el sitio envía
 * y su vista previa.
 *
 * @package     PiecesPHP\MailCatalog
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class MailCatalogRoutes
{
    const ENABLE = true;

    /**
     * @param RouteGroup $groupAdministration
     * @return array{groupAdministration:RouteGroup}
     */
    public static function routes(RouteGroup $groupAdministration): array
    {
        if (self::ENABLE) {
            //Antes que nada: los textos de las definiciones se traducen al pintarse.
            MailCatalogLang::injectLang();
            $groupAdministration = MailCatalogController::routes($groupAdministration);
            self::staticResolver($groupAdministration);
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
     * @return RouteGroup
     */
    protected static function staticResolver(RouteGroup $group)
    {
        $callableHandler = function (Request $request, Response $response, array $args) {
            $server = new ServerStatics();
            return $server->serve($request, $response, $args, __DIR__ . '/Statics');
        };
        $group->register([
            new Route('mail-catalog/statics/[{params:.*}]', $callableHandler, self::class),
        ]);
        return $group;
    }
}
