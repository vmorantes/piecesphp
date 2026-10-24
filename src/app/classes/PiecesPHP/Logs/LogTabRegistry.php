<?php

/**
 * LogTabRegistry.php
 */

namespace PiecesPHP\Logs;

use PiecesPHP\Core\Roles;

/**
 * LogTabRegistry - El registro de las pestañas de «Registros». Cada módulo registra la suya desde su propio Routes.
 *
 * @package     PiecesPHP\Logs
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class LogTabRegistry
{
    /**
     * @var array<string,LogTab>
     */
    private static $tabs = [];

    /**
     * @param LogTab $tab
     * @return void
     * @throws \InvalidArgumentException si la key ya está registrada
     */
    public static function register(LogTab $tab): void
    {
        if (array_key_exists($tab->key(), self::$tabs)) {
            throw new \InvalidArgumentException("Ya hay una pestaña de registros con la key «{$tab->key()}».");
        }
        self::$tabs[$tab->key()] = $tab;
    }

    /**
     * Por posición y, a igual posición, por orden de registro.
     *
     * @return array<string,LogTab>
     */
    public static function all(): array
    {
        $tabs = self::$tabs;
        $order = array_flip(array_keys($tabs));
        uasort($tabs, fn(LogTab $a, LogTab $b): int => [$a->position(), $order[$a->key()]] <=> [$b->position(), $order[$b->key()]]);
        return $tabs;
    }

    /**
     * @param string $key
     * @return LogTab|null
     */
    public static function get(string $key): ?LogTab
    {
        return self::$tabs[$key] ?? null;
    }

    /**
     * Las pestañas cuya ruta concede el rol: un módulo apagado no registra su ruta, y su pestaña no se ve.
     *
     * @param int $userType
     * @return array<string,LogTab>
     */
    public static function visibleFor(int $userType): array
    {
        return array_filter(self::all(), fn(LogTab $tab): bool => Roles::hasPermissions($tab->permissionRoute(), $userType, true));
    }

    /**
     * SOLO PARA PRUEBAS: vacía el registro en memoria y devuelve lo que había, para reponerlo después.
     *
     * @internal
     * @param array<string,LogTab>|null $restore
     * @return array<string,LogTab>
     */
    public static function swapForTesting(?array $restore = null): array
    {
        $previous = self::$tabs;
        self::$tabs = $restore ?? [];
        return $previous;
    }
}
