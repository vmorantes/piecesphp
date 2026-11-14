<?php

/**
 * AICapabilityRegistry.php
 */

namespace PiecesPHP\AI;

/**
 * AICapabilityRegistry - El registro de las capacidades de IA (ADR 0074). Cada módulo registra las suyas desde su propio
 * Routes; un proyecto quita (remove()) o sustituye (replace()) las que no quiera, desde su configuración.
 *
 * @package     PiecesPHP\AI
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class AICapabilityRegistry
{
    /**
     * @var array<string,AICapability>
     */
    private static $capabilities = [];

    /**
     * @param AICapability $capability
     * @return void
     * @throws \InvalidArgumentException si la key ya está registrada
     */
    public static function register(AICapability $capability): void
    {
        if (array_key_exists($capability->key(), self::$capabilities)) {
            throw new \InvalidArgumentException("Ya hay una capacidad de IA con la key «{$capability->key()}».");
        }
        self::$capabilities[$capability->key()] = $capability;
    }

    /**
     * @param string $key
     * @return bool false si no estaba
     */
    public static function remove(string $key): bool
    {
        if (!array_key_exists($key, self::$capabilities)) {
            return false;
        }
        unset(self::$capabilities[$key]);
        return true;
    }

    /**
     * Sustituye una capacidad registrada por otra con la misma key, en su sitio del orden de registro.
     *
     * @param AICapability $capability
     * @return void
     * @throws \InvalidArgumentException si no hay ninguna con esa key
     */
    public static function replace(AICapability $capability): void
    {
        if (!array_key_exists($capability->key(), self::$capabilities)) {
            throw new \InvalidArgumentException("No hay ninguna capacidad de IA con la key «{$capability->key()}» que sustituir.");
        }
        self::$capabilities[$capability->key()] = $capability;
    }

    /**
     * En el orden de registro.
     *
     * @return array<string,AICapability>
     */
    public static function all(): array
    {
        return self::$capabilities;
    }

    /**
     * @param string $key
     * @return AICapability|null
     */
    public static function get(string $key): ?AICapability
    {
        return self::$capabilities[$key] ?? null;
    }

    /**
     * SOLO PARA PRUEBAS: vacía el registro en memoria y devuelve lo que había, para reponerlo después.
     *
     * @internal
     * @param array<string,AICapability>|null $restore
     * @return array<string,AICapability>
     */
    public static function swapForTesting(?array $restore = null): array
    {
        $previous = self::$capabilities;
        self::$capabilities = $restore ?? [];
        return $previous;
    }
}
