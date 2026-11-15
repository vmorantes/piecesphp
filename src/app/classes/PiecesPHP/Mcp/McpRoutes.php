<?php

/**
 * McpRoutes.php
 */

namespace PiecesPHP\Mcp;

use PiecesPHP\AdminPanel\PanelSearchSource;
use PiecesPHP\AI\AICapability;
use PiecesPHP\AI\AICapabilityRegistry;
use PiecesPHP\Core\Mcp\McpTool;
use PiecesPHP\Core\Mcp\McpToolRegistry;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Mcp\Controllers\McpController;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * McpRoutes - El servidor MCP del panel (ADR 0075): su ruta y las herramientas del propio panel. Las de cada módulo las
 * registra ese módulo en su Routes, con McpToolRegistry::register().
 *
 * @package     PiecesPHP\Mcp
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class McpRoutes
{
    const ENABLE = true;

    /**
     * La herramienta que busca en el panel: lo mismo que el buscador de la barra (Ctrl K).
     */
    const TOOL_PANEL_FIND = 'panel_find';

    /**
     * La cuota por omisión del asistente: tokens al mes. Lo que guarde quien administra en Configuración › IA manda.
     */
    const ASSISTANT_DEFAULT_QUOTA = 200000;

    /**
     * @param RouteGroup $groupAdministration
     * @return RouteGroup
     */
    public static function routes(RouteGroup $groupAdministration)
    {
        if (self::ENABLE) {
            McpLang::injectLang();
            $groupAdministration = McpController::routes($groupAdministration);
            if (McpToolRegistry::get(self::TOOL_PANEL_FIND) === null) {
                McpToolRegistry::register(self::panelFindTool());
            }
            //El asistente, en el centro de mando de la IA (ADR 0074): apagado hasta que alguien lo encienda en Configuración › IA,
            //y con cuota: sin ella, una pregunta podría gastar sin límite.
            if (AICapabilityRegistry::get(Assistant::CAPABILITY) === null) {
                AICapabilityRegistry::register(new AICapability(Assistant::CAPABILITY, fn(): string => __(McpLang::LANG_GROUP, 'Asistente del panel'), AICapability::PROVIDER_CHAT, false, [], self::ASSISTANT_DEFAULT_QUOTA, AICapability::PERIOD_MONTHLY, null, true));
            }
        }
        return $groupAdministration;
    }

    /**
     * @return McpTool
     */
    public static function panelFindTool(): McpTool
    {
        return new McpTool(
            self::TOOL_PANEL_FIND,
            __(McpLang::LANG_GROUP, 'Buscar en el panel'),
            __(McpLang::LANG_GROUP, 'Busca pantallas del panel de administración por su nombre o su sección, igual que el buscador de la barra (Ctrl K). Úsala cuando haya que decir dónde se hace algo o dar el enlace a una pantalla. Devuelve, para cada coincidencia, el título, la sección (módulos, configuración o cuenta) y la dirección completa. Solo incluye las pantallas que el usuario que pregunta puede abrir.'),
            [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 100, 'description' => __(McpLang::LANG_GROUP, 'El texto que buscar en el título o la sección; no distingue mayúsculas ni tildes.')],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 20, 'description' => __(McpLang::LANG_GROUP, 'Cuántas coincidencias como mucho; por omisión, 20.')],
                ],
                'required' => ['query'],
                'additionalProperties' => false,
            ],
            'admin',
            function (UserDataPackage $user, array $arguments): array {
                $results = array_map(fn(array $entry): array => [
                    'title' => $entry['title'],
                    'category' => $entry['category'],
                    'url' => preg_match('#^[a-z][a-z0-9+.-]*://#i', $entry['url']) === 1 ? $entry['url'] : baseurl(ltrim($entry['url'], '/')),
                ], PanelSearchSource::find($user, (string) $arguments['query'], (int) ($arguments['limit'] ?? 20)));
                return ['total' => count($results), 'results' => $results];
            }
        );
    }
}
