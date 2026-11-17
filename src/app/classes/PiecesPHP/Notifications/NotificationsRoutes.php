<?php

/**
 * NotificationsRoutes.php
 */

namespace PiecesPHP\Notifications;

use PiecesPHP\Core\Mcp\McpTool;
use PiecesPHP\Core\Mcp\McpToolRegistry;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\Notifications\Controllers\NotificationsController;
use PiecesPHP\Notifications\Sources\NewsNotificationSource;
use PiecesPHP\Notifications\Sources\TableNotificationSource;
use PiecesPHP\UserSystem\UserDataPackage;

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
     * La herramienta MCP de las notificaciones de quien pregunta (ADR 0075).
     */
    const TOOL_NOTIFICATIONS_MINE = 'notifications_mine';

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
            //Después de las fuentes: el enum de filtros sale de las que hay.
            if (McpToolRegistry::get(self::TOOL_NOTIFICATIONS_MINE) === null) {
                McpToolRegistry::register(self::notificationsMineTool());
            }
        }
        return $groupAdministration;
    }

    /**
     * @return McpTool
     */
    public static function notificationsMineTool(): McpTool
    {
        return new McpTool(
            self::TOOL_NOTIFICATIONS_MINE,
            __(NotificationsLang::LANG_GROUP, 'Mis notificaciones'),
            __(NotificationsLang::LANG_GROUP, 'Devuelve las notificaciones del usuario que pregunta (los avisos del sistema y las noticias internas). El total de no leídas va en unreadTotal y los totales de cada filtro en totals; page trae solo una página de notificaciones, de la más nueva a la más vieja, y su tamaño no es el total. Úsala cuando pregunte qué tiene pendiente o sin leer. Solo ve las suyas, nunca las de otra persona.'),
            [
                'type' => 'object',
                'properties' => [
                    'filter' => ['type' => 'string', 'enum' => Notifications::filters(), 'default' => Notifications::FILTER_UNREAD, 'description' => __(NotificationsLang::LANG_GROUP, 'Cuáles: todas, solo las no leídas, solo las leídas o, si hay noticias internas, solo esas; por omisión, las no leídas.')],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => Notifications::MAX_PER_PAGE, 'default' => 10, 'description' => __(NotificationsLang::LANG_GROUP, 'Cuántas como mucho; por omisión, 10.')],
                ],
                'additionalProperties' => false,
            ],
            'notifications-list',
            function (UserDataPackage $user, array $arguments): array {
                $filter = (string) ($arguments['filter'] ?? Notifications::FILTER_UNREAD);
                $page = Notifications::page((int) $user->id, $filter, 1, (int) ($arguments['limit'] ?? 10));
                //El total, arriba y con su nombre: con la página sola, el modelo contaba los elementos (10) y no el total (12).
                return [
                    'unreadTotal' => (int) ($page['counts'][Notifications::FILTER_UNREAD] ?? 0),
                    'totals' => $page['counts'],
                    'page' => ['filter' => $filter, 'size' => count($page['items']), 'hasMore' => $page['hasMore'], 'items' => $page['items']],
                ];
            }
        );
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
