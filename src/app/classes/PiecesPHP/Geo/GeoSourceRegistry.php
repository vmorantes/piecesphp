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
     * @throws \InvalidArgumentException si la key ya está registrada
     */
    public static function register(GeoSource $source): void
    {
        if (array_key_exists($source->key(), self::$sources)) {
            throw new \InvalidArgumentException("Ya hay una fuente del geovisor con la key «{$source->key()}».");
        }
        //Sin tarjeta legible se anota y se registra igual: la vista la omite (hasCardView()) y la página no se cae.
        if (!self::hasCardView($source)) {
            log_exception(new \InvalidArgumentException("La fuente del geovisor «{$source->key()}» no tiene una tarjeta legible en «{$source->cardView()}»: se registra sin tarjeta."));
        }
        self::$sources[$source->key()] = $source;
    }

    /**
     * Si la tarjeta de una fuente es un archivo legible: la vista solo incluye las que lo son.
     *
     * @param GeoSource $source
     * @return bool
     */
    public static function hasCardView(GeoSource $source): bool
    {
        $cardView = $source->cardView();
        return is_file($cardView) && is_readable($cardView);
    }

    /**
     * El color que declara una fuente (GeoSourceColor), si tiene forma de color CSS; si no, null y manda su variable.
     * Va a un atributo style y al JS: nada que pueda cerrar la declaración.
     *
     * @param GeoSource $source
     * @return string|null
     */
    public static function colorValue(GeoSource $source): ?string
    {
        if (!$source instanceof GeoSourceColor) {
            return null;
        }
        $color = trim($source->colorValue());
        $forma = '/^(#[0-9a-fA-F]{3,8}|var\(--[A-Za-z0-9_-]+\)|(rgb|rgba|hsl|hsla|hwb|lab|lch|oklab|oklch|color|color-mix)\([^;{}<>"\'\\\\]*\)|[a-zA-Z]+)$/';
        return preg_match($forma, $color) === 1 ? $color : null;
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
        if (!self::hasCardView($source)) {
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
