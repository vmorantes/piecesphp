<?php

/**
 * CleanTargetRegistry.php
 */

namespace PiecesPHP\SystemStatus;

/**
 * CleanTargetRegistry - El registro de lo que «Estado y cachés» sabe borrar por separado. El núcleo registra los suyos
 * desde SystemStatusRoutes, y un módulo con caché propia, la suya desde su Routes: su botón y su fila salen solos.
 *
 * @package     PiecesPHP\SystemStatus
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class CleanTargetRegistry
{
    /**
     * @var array<string,CleanTarget>
     */
    private static $targets = [];

    /**
     * @param CleanTarget $target
     * @return void
     * @throws \InvalidArgumentException si la key ya está registrada
     */
    public static function register(CleanTarget $target): void
    {
        if (array_key_exists($target->key(), self::$targets)) {
            throw new \InvalidArgumentException("Ya hay una limpieza registrada con la key «{$target->key()}».");
        }
        self::$targets[$target->key()] = $target;
    }

    /**
     * En el orden de sus botones.
     *
     * @return array<string,CleanTarget>
     */
    public static function all(): array
    {
        $targets = self::$targets;
        uasort($targets, fn (CleanTarget $a, CleanTarget $b): int => $a->position() <=> $b->position());
        return $targets;
    }

    /**
     * Los que tienen fila en la lista, en el orden de sus filas.
     *
     * @return array<string,CleanTarget>
     */
    public static function withRows(): array
    {
        $targets = array_filter(self::$targets, fn (CleanTarget $target): bool => $target->hasRow());
        uasort($targets, fn (CleanTarget $a, CleanTarget $b): int => $a->rowPosition() <=> $b->rowPosition());
        return $targets;
    }

    /**
     * Comparación ESTRICTA y sensible a mayúsculas: un destino que no esté registrado no se toca.
     *
     * @param mixed $key
     * @return CleanTarget|null
     */
    public static function get(mixed $key): ?CleanTarget
    {
        return is_string($key) ? (self::$targets[$key] ?? null) : null;
    }

    /**
     * SOLO PARA PRUEBAS: vacía el registro en memoria y devuelve lo que había, para reponerlo después.
     *
     * @internal
     * @param array<string,CleanTarget>|null $restore
     * @return array<string,CleanTarget>
     */
    public static function swapForTesting(?array $restore = null): array
    {
        $previous = self::$targets;
        self::$targets = $restore ?? [];
        return $previous;
    }
}
