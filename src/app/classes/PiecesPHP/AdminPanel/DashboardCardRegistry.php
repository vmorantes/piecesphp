<?php

/**
 * DashboardCardRegistry.php
 */

namespace PiecesPHP\AdminPanel;

use PiecesPHP\Core\Roles;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * DashboardCardRegistry - El registro de las tarjetas del inicio del panel. Cada módulo registra las suyas desde su
 * propio Routes; un clon quita (remove()) o sustituye (replace()) las que no quiera, desde su configuración.
 *
 * Lo plegado se guarda por usuario, en su `meta` (como la última visita de NewErrorsAlert).
 *
 * @package     PiecesPHP\AdminPanel
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class DashboardCardRegistry
{
    /**
     * La clave de `meta` del usuario donde vive lo que plegó y desplegó: {key: true|false}.
     */
    const META_COLLAPSED = 'dashboardCardsCollapsed';

    /**
     * @var array<string,DashboardCard>
     */
    private static $cards = [];

    /**
     * @param DashboardCard $card
     * @return void
     * @throws \InvalidArgumentException si la key ya está registrada
     */
    public static function register(DashboardCard $card): void
    {
        if (array_key_exists($card->key(), self::$cards)) {
            throw new \InvalidArgumentException("Ya hay una tarjeta del inicio con la key «{$card->key()}».");
        }
        self::$cards[$card->key()] = $card;
    }

    /**
     * @param string $key
     * @return bool false si no estaba
     */
    public static function remove(string $key): bool
    {
        if (!array_key_exists($key, self::$cards)) {
            return false;
        }
        unset(self::$cards[$key]);
        return true;
    }

    /**
     * Sustituye una tarjeta registrada por otra con la misma key, en su sitio del orden de registro.
     *
     * @param DashboardCard $card
     * @return void
     * @throws \InvalidArgumentException si no hay ninguna con esa key
     */
    public static function replace(DashboardCard $card): void
    {
        if (!array_key_exists($card->key(), self::$cards)) {
            throw new \InvalidArgumentException("No hay ninguna tarjeta del inicio con la key «{$card->key()}» que sustituir.");
        }
        self::$cards[$card->key()] = $card;
    }

    /**
     * Por posición y, a igual posición, por orden de registro.
     *
     * @return array<string,DashboardCard>
     */
    public static function all(): array
    {
        $cards = self::$cards;
        $order = array_flip(array_keys($cards));
        uasort($cards, fn(DashboardCard $a, DashboardCard $b): int => [$a->position(), $order[$a->key()]] <=> [$b->position(), $order[$b->key()]]);
        return $cards;
    }

    /**
     * @param string $key
     * @return DashboardCard|null
     */
    public static function get(string $key): ?DashboardCard
    {
        return self::$cards[$key] ?? null;
    }

    /**
     * Las tarjetas cuya ruta concede el rol: un módulo apagado no registra su ruta, y su tarjeta no se ve.
     *
     * @param int $userType
     * @return array<string,DashboardCard>
     */
    public static function visibleFor(int $userType): array
    {
        return array_filter(self::all(), fn(DashboardCard $card): bool => Roles::hasPermissions($card->permissionRoute(), $userType, true));
    }

    /**
     * Si está plegada para ese usuario: lo que él eligió o, si nunca eligió, como nace la tarjeta.
     *
     * @param DashboardCard $card
     * @param int $userID
     * @return bool
     */
    public static function isCollapsed(DashboardCard $card, int $userID): bool
    {
        if (!$card->collapsible()) {
            return false;
        }
        $chosen = self::collapsedOf($userID);
        return array_key_exists($card->key(), $chosen) ? $chosen[$card->key()] : $card->collapsedByDefault();
    }

    /**
     * @param string $key
     * @param int $userID
     * @param bool $collapsed
     * @return bool false si la tarjeta no existe o no se puede plegar
     */
    public static function setCollapsed(string $key, int $userID, bool $collapsed): bool
    {
        $card = self::get($key);
        if ($card === null || !$card->collapsible()) {
            return false;
        }
        $chosen = self::collapsedOf($userID);
        $chosen[$key] = $collapsed;
        return UsersModel::setMetaValue($userID, self::META_COLLAPSED, (string) json_encode($chosen));
    }

    /**
     * SOLO PARA PRUEBAS: vacía el registro en memoria y devuelve lo que había, para reponerlo después.
     *
     * @internal
     * @param array<string,DashboardCard>|null $restore
     * @return array<string,DashboardCard>
     */
    public static function swapForTesting(?array $restore = null): array
    {
        $previous = self::$cards;
        self::$cards = $restore ?? [];
        return $previous;
    }

    /**
     * @param int $userID
     * @return array<string,bool>
     */
    private static function collapsedOf(int $userID): array
    {
        $raw = UsersModel::metaValue($userID, self::META_COLLAPSED);
        $decoded = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : null);
        if (!is_array($decoded)) {
            return [];
        }
        $chosen = [];
        foreach ($decoded as $key => $value) {
            if (is_string($key) && is_bool($value)) {
                $chosen[$key] = $value;
            }
        }
        return $chosen;
    }
}
