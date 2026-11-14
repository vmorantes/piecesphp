<?php

/**
 * McpToolRegistry.php
 */

namespace PiecesPHP\Core\Mcp;

use PiecesPHP\Core\Roles;

/**
 * McpToolRegistry - El registro de las herramientas del servidor MCP del panel (ADR 0075). Cada módulo registra las suyas
 * desde su propio Routes; un clon quita (remove()) o sustituye (replace()) las que no quiera, como con las tarjetas del
 * inicio.
 *
 * @package     PiecesPHP\Core\Mcp
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class McpToolRegistry
{
    /**
     * @var array<string,McpTool>
     */
    private static $tools = [];

    /**
     * @param McpTool $tool
     * @return void
     * @throws \InvalidArgumentException si la key ya está registrada
     */
    public static function register(McpTool $tool): void
    {
        if (array_key_exists($tool->key(), self::$tools)) {
            throw new \InvalidArgumentException("Ya hay una herramienta MCP con la key «{$tool->key()}».");
        }
        self::$tools[$tool->key()] = $tool;
    }

    /**
     * @param string $key
     * @return bool false si no estaba
     */
    public static function remove(string $key): bool
    {
        if (!array_key_exists($key, self::$tools)) {
            return false;
        }
        unset(self::$tools[$key]);
        return true;
    }

    /**
     * Sustituye una herramienta registrada por otra con la misma key, en su sitio del orden de registro.
     *
     * @param McpTool $tool
     * @return void
     * @throws \InvalidArgumentException si no hay ninguna con esa key
     */
    public static function replace(McpTool $tool): void
    {
        if (!array_key_exists($tool->key(), self::$tools)) {
            throw new \InvalidArgumentException("No hay ninguna herramienta MCP con la key «{$tool->key()}» que sustituir.");
        }
        self::$tools[$tool->key()] = $tool;
    }

    /**
     * En el orden de registro.
     *
     * @return array<string,McpTool>
     */
    public static function all(): array
    {
        return self::$tools;
    }

    /**
     * @param string $key
     * @return McpTool|null
     */
    public static function get(string $key): ?McpTool
    {
        return self::$tools[$key] ?? null;
    }

    /**
     * Las herramientas cuya ruta concede el rol: un módulo apagado no registra su ruta, y su herramienta no se ve.
     *
     * @param int $userType
     * @return array<string,McpTool>
     */
    public static function visibleFor(int $userType): array
    {
        return array_filter(self::$tools, fn(McpTool $tool): bool => Roles::hasPermissions($tool->permissionRoute(), $userType, true));
    }
}
