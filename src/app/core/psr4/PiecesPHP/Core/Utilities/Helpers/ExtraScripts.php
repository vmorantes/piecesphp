<?php

/**
 * ExtraScripts.php
 */
namespace PiecesPHP\Core\Utilities\Helpers;

/**
 * ExtraScripts
 *
 * Clase para generar scripts en el header
 *
 * @category    Helpers
 * @package     PiecesPHP\Core\Utilities\Helpers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class ExtraScripts
{

    /**
     * La opción que guarda las entradas (JSON), escrita por la pantalla «Scripts» de Integraciones.
     */
    const CONFIG_NAME = 'injected_scripts';

    const ZONE_PANEL = 'panel';
    const ZONE_PUBLIC = 'public';
    const ZONE_BOTH = 'both';
    const ZONES = [self::ZONE_PANEL, self::ZONE_PUBLIC, self::ZONE_BOTH];

    const POSITION_HEAD = 'head';
    const POSITION_BODY_START = 'body_start';
    const POSITION_BODY_END = 'body_end';
    const POSITIONS = [self::POSITION_HEAD, self::POSITION_BODY_START, self::POSITION_BODY_END];

    /**
     * Lo puesto con setScripts(); se suma a lo público de la cabecera.
     * @var string
     */
    protected static $scripts = '';

    /**
     * Sustituye el código que getScripts() añade a las entradas guardadas.
     *
     * @param string $value
     * @return void
     */
    public static function setScripts(string $value)
    {
        self::$scripts = $value;
    }

    /**
     * La forma antigua: lo mismo que getScriptsFor('public', 'head') más lo puesto con setScripts().
     *
     * @return string
     */
    public static function getScripts()
    {
        return self::getScriptsFor(self::ZONE_PUBLIC, self::POSITION_HEAD) . self::wrap(self::$scripts);
    }

    /**
     * El código de las entradas activas de esa zona (o de las dos) y ese punto de la página, en el orden guardado.
     *
     * @param string $zone panel|public
     * @param string $position head|body_start|body_end
     * @return string Vacía si la zona o el punto no existen.
     */
    public static function getScriptsFor(string $zone, string $position): string
    {
        if (!in_array($zone, [self::ZONE_PANEL, self::ZONE_PUBLIC], true) || !in_array($position, self::POSITIONS, true)) {
            return '';
        }
        $code = [];
        foreach (self::entries() as $entry) {
            if ($entry['active'] && $entry['position'] === $position && in_array($entry['zone'], [$zone, self::ZONE_BOTH], true)) {
                $code[] = $entry['code'];
            }
        }
        return self::wrap(implode("\r\n", $code));
    }

    /**
     * Las entradas guardadas, sin las que no tienen forma válida.
     *
     * Un JSON roto da ninguna entrada, sin excepción: la llaman todas las cabeceras, y una que cae tumba el sitio.
     *
     * @return array<int,array{label:string,zone:string,position:string,active:bool,code:string}>
     */
    public static function entries(): array
    {
        $stored = get_config(self::CONFIG_NAME);
        if (is_string($stored)) {
            $stored = json_decode($stored, true);
        }
        if (!is_array($stored)) {
            return [];
        }
        $entries = [];
        foreach ($stored as $entry) {
            $normalized = self::normalizeEntry($entry);
            if ($normalized !== null) {
                $entries[] = $normalized;
            }
        }
        return $entries;
    }

    /**
     * Una entrada con su forma, o null si algún campo no la tiene.
     *
     * @param mixed $entry Array u objeto: la columna JSON de la configuración se lee como objetos.
     * @return array{label:string,zone:string,position:string,active:bool,code:string}|null
     */
    public static function normalizeEntry($entry): ?array
    {
        if (is_object($entry)) {
            $entry = get_object_vars($entry);
        }
        if (!is_array($entry)) {
            return null;
        }
        $label = $entry['label'] ?? null;
        $zone = $entry['zone'] ?? null;
        $position = $entry['position'] ?? null;
        $active = $entry['active'] ?? null;
        $code = $entry['code'] ?? null;
        if (!is_string($label) || trim($label) === '' || !in_array($zone, self::ZONES, true) || !in_array($position, self::POSITIONS, true) || !is_bool($active) || !is_string($code)) {
            return null;
        }
        return [
            'label' => trim($label),
            'zone' => $zone,
            'position' => $position,
            'active' => $active,
            'code' => $code,
        ];
    }

    /**
     * @param string $code
     * @return string
     */
    private static function wrap(string $code): string
    {
        if (mb_strlen(trim($code)) === 0) {
            return '';
        }
        return "\r\n<!-- Extra scripts -->\r\n" . $code . "\r\n<!-- Close Extra scripts -->\r\n";
    }
}
