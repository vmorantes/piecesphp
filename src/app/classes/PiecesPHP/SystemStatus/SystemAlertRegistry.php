<?php

/**
 * SystemAlertRegistry.php
 */

namespace PiecesPHP\SystemStatus;

use PiecesPHP\Settings\ORM\SettingsModel;

/**
 * SystemAlertRegistry - El registro único de avisos del sistema: alimenta el aviso flotante y la página de avisos.
 *
 * Lo oculto se guarda en la configuración (SettingsModel, clave 'system_alerts_hidden').
 *
 * @package     PiecesPHP\SystemStatus
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class SystemAlertRegistry
{
    const HIDDEN_CONFIG = 'system_alerts_hidden';

    /**
     * @var array<string,SystemAlert>
     */
    private static $alerts = [];

    /**
     * @var array<string,callable():bool> Otras formas de «oculto» que un aviso ya tenía antes del registro
     */
    private static $legacyHidden = [];

    /**
     * @var array<string,callable():void> Cómo se deshace esa marca anterior al mostrar desde el registro
     */
    private static $legacyShow = [];

    /**
     * @param SystemAlert $alert
     * @param (callable():bool)|null $legacyHidden Una marca de «oculto» anterior al registro que se sigue respetando
     * @param (callable():void)|null $legacyShow Cómo se quita esa marca cuando root lo muestra desde la página
     * @return void
     * @throws \InvalidArgumentException si la key ya está registrada
     */
    public static function register(SystemAlert $alert, ?callable $legacyHidden = null, ?callable $legacyShow = null): void
    {
        if (array_key_exists($alert->key(), self::$alerts)) {
            throw new \InvalidArgumentException("Ya hay un aviso registrado con la key «{$alert->key()}».");
        }
        self::$alerts[$alert->key()] = $alert;
        if ($legacyHidden !== null) {
            self::$legacyHidden[$alert->key()] = $legacyHidden;
        }
        if ($legacyShow !== null) {
            self::$legacyShow[$alert->key()] = $legacyShow;
        }
    }

    /**
     * @return array<string,SystemAlert>
     */
    public static function all(): array
    {
        return self::$alerts;
    }

    /**
     * @param string $key
     * @return SystemAlert|null
     */
    public static function get(string $key): ?SystemAlert
    {
        return self::$alerts[$key] ?? null;
    }

    /**
     * Los avisos activos para ese tipo de usuario que no están ocultos.
     *
     * @param int $userType
     * @return SystemAlert[]
     */
    public static function visibleFor(int $userType): array
    {
        $visible = [];
        foreach (self::$alerts as $key => $alert) {
            if (in_array($userType, $alert->audience(), true) && !self::isHidden($key) && self::isActive($alert)) {
                $visible[] = $alert;
            }
        }
        return $visible;
    }

    /**
     * Lo mismo que visibleFor() pero solo los que van como aviso flotante, y filtrando ANTES de evaluar: se llama en
     * cada página del panel, y un aviso caro (recorrer un árbol) no puede pagarse en cada petición.
     *
     * @param int $userType
     * @return SystemAlert[]
     */
    public static function nagsFor(int $userType): array
    {
        $nags = [];
        $hidden = null;
        foreach (self::$alerts as $key => $alert) {
            if (!$alert->showAsNag() || !in_array($userType, $alert->audience(), true)) {
                continue;
            }
            //La lista de ocultos se lee una vez, y solo si algún aviso llega hasta aquí.
            $hidden ??= self::hidden();
            if (!self::isHidden($key, $hidden) && self::isActive($alert)) {
                $nags[] = $alert;
            }
        }
        return $nags;
    }

    /**
     * Un aviso que lanza al evaluarse se registra y cuenta como NO activo: un aviso roto no tumba el panel.
     *
     * @param SystemAlert $alert
     * @return bool
     */
    public static function isActive(SystemAlert $alert): bool
    {
        try {
            return $alert->evaluateActive();
        } catch (\Throwable $e) {
            log_exception($e);
            return false;
        }
    }

    /**
     * @param bool $fromDatabase Sin la foto de la petición: para leer, modificar y escribir (ADR 0080)
     * @return string[]
     */
    public static function hidden(bool $fromDatabase = false): array
    {
        $raw = $fromDatabase ? SettingsModel::readFromDatabase(self::HIDDEN_CONFIG) : SettingsModel::getConfigValue(self::HIDDEN_CONFIG);
        $value = json_decode((string) json_encode($raw), true);
        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }

    /**
     * @param string $key
     * @param string[]|null $hidden La lista de hidden() ya leída; null la lee
     * @return bool
     */
    public static function isHidden(string $key, ?array $hidden = null): bool
    {
        if (in_array($key, $hidden ?? self::hidden(), true)) {
            return true;
        }
        $legacy = self::$legacyHidden[$key] ?? null;
        return $legacy !== null && (bool) $legacy();
    }

    /**
     * @param string $key
     * @return bool false si el aviso no existe o no es ocultable
     */
    public static function hide(string $key): bool
    {
        $alert = self::get($key);
        if ($alert === null || !$alert->isDismissible()) {
            return false;
        }
        $hidden = array_values(array_unique(array_merge(self::hidden(true), [$key])));
        return (bool) SettingsModel::setConfigValue(self::HIDDEN_CONFIG, $hidden);
    }

    /**
     * @param string $key
     * @return bool false si el aviso no existe o no es ocultable
     */
    public static function show(string $key): bool
    {
        $alert = self::get($key);
        if ($alert === null || !$alert->isDismissible()) {
            return false;
        }
        $legacyShow = self::$legacyShow[$key] ?? null;
        if ($legacyShow !== null) {
            $legacyShow();
        }
        $hidden = array_values(array_diff(self::hidden(true), [$key]));
        return (bool) SettingsModel::setConfigValue(self::HIDDEN_CONFIG, $hidden);
    }

    /**
     * SOLO PARA PRUEBAS: vacía el registro en memoria y devuelve lo que había, para reponerlo después.
     *
     * @internal
     * @param array{0:array<string,SystemAlert>,1:array<string,callable>,2:array<string,callable>}|null $restore
     * @return array{0:array<string,SystemAlert>,1:array<string,callable>,2:array<string,callable>}
     */
    public static function swapForTesting(?array $restore = null): array
    {
        $previous = [self::$alerts, self::$legacyHidden, self::$legacyShow];
        [self::$alerts, self::$legacyHidden, self::$legacyShow] = $restore ?? [[], [], []];
        return $previous;
    }
}
