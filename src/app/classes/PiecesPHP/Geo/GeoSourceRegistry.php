<?php

/**
 * GeoSourceRegistry.php
 */

namespace PiecesPHP\Geo;

use PiecesPHP\Core\Roles;

/**
 * GeoSourceRegistry - El registro de las fuentes del geovisor. Cada módulo registra las suyas desde su propio Routes;
 * un clon quita (remove()) o sustituye (replace()) las que no quiera, desde su configuración.
 *
 * @package     PiecesPHP\Geo
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class GeoSourceRegistry
{
    /**
     * @var array<string,GeoSource>
     */
    private static $sources = [];

    /**
     * @param GeoSource $source
     * @return void
     * @throws \InvalidArgumentException si la key ya está registrada o si su tarjeta no es un archivo legible
     */
    public static function register(GeoSource $source): void
    {
        if (array_key_exists($source->key(), self::$sources)) {
            throw new \InvalidArgumentException("Ya hay una fuente del geovisor con la key «{$source->key()}».");
        }
        self::assertCardView($source);
        self::$sources[$source->key()] = $source;
    }

    /**
     * @param string $key
     * @return bool false si no estaba
     */
    public static function remove(string $key): bool
    {
        if (!array_key_exists($key, self::$sources)) {
            return false;
        }
        unset(self::$sources[$key]);
        return true;
    }

    /**
     * Sustituye una fuente registrada por otra con la misma key, en su sitio del orden de registro.
     *
     * @param GeoSource $source
     * @return void
     * @throws \InvalidArgumentException si no hay ninguna con esa key o si su tarjeta no es un archivo legible
     */
    public static function replace(GeoSource $source): void
    {
        if (!array_key_exists($source->key(), self::$sources)) {
            throw new \InvalidArgumentException("No hay ninguna fuente del geovisor con la key «{$source->key()}» que sustituir.");
        }
        self::assertCardView($source);
        self::$sources[$source->key()] = $source;
    }

    /**
     * En orden de registro.
     *
     * @return array<string,GeoSource>
     */
    public static function all(): array
    {
        return self::$sources;
    }

    /**
     * @param string $key
     * @return GeoSource|null
     */
    public static function get(string $key): ?GeoSource
    {
        return self::$sources[$key] ?? null;
    }

    /**
     * Las fuentes cuya ruta concede el rol: un módulo apagado no registra su ruta, y su fuente no se ve.
     *
     * @param int $userType
     * @return array<string,GeoSource>
     */
    public static function visibleFor(int $userType): array
    {
        return array_filter(self::$sources, fn(GeoSource $source): bool => Roles::hasPermissions($source->permissionRoute(), $userType, true));
    }

    /**
     * La vista de la página la incluye: una tarjeta que no se puede leer rompería la pantalla entera.
     *
     * @param GeoSource $source
     * @return void
     * @throws \InvalidArgumentException si su tarjeta no es un archivo legible
     */
    private static function assertCardView(GeoSource $source): void
    {
        $cardView = $source->cardView();
        if (!is_file($cardView) || !is_readable($cardView)) {
            throw new \InvalidArgumentException("La fuente del geovisor «{$source->key()}» no tiene una tarjeta legible en «{$cardView}».");
        }
    }

    /**
     * SOLO PARA PRUEBAS: vacía el registro en memoria y devuelve lo que había, para reponerlo después.
     *
     * @internal
     * @param array<string,GeoSource>|null $restore
     * @return array<string,GeoSource>
     */
    public static function swapForTesting(?array $restore = null): array
    {
        $previous = self::$sources;
        self::$sources = $restore ?? [];
        return $previous;
    }
}
