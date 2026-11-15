<?php

/**
 * NotificationsRoutes.php
 */

namespace PiecesPHP\Notifications;

use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\Notifications\Controllers\NotificationsController;
use PiecesPHP\Notifications\Sources\NewsNotificationSource;
use PiecesPHP\Notifications\Sources\TableNotificationSource;

/**
 * NotificationsRoutes - El canal de notificaciones por usuario (ADR 0073): sus rutas en JSON y sus fuentes. La de
 * noticias, solo con NEWS_MODULE; un proyecto añade la suya con Notifications::register().
 *
 * @package     PiecesPHP\Notifications
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class NotificationsRoutes
{
    const ENABLE = true;

    /**
     * @param RouteGroup $groupAdministration
     * @return RouteGroup
     */
    public static function routes(RouteGroup $groupAdministration)
    {
        if (self::ENABLE) {
            NotificationsLang::injectLang();
            if (Notifications::source(TableNotificationSource::KEY) === null) {
                Notifications::register(new TableNotificationSource());
            }
            if (NEWS_MODULE && Notifications::source(NewsNotificationSource::KEY) === null) {
                Notifications::register(new NewsNotificationSource());
            }
            $groupAdministration = NotificationsController::routes($groupAdministration);
            self::staticResolver($groupAdministration);
        }
        return $groupAdministration;
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
            new Route('notifications/statics/[{params:.*}]', $callableHandler, self::class),
        ]);
        return $group;
    }
}
