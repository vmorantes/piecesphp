<?php

/**
 * AppEnvironment.php
 */

namespace PiecesPHP\Core;

/**
 * AppEnvironment - Si la instalación es local o de producción.
 *
 * Sale de src/app/config/environment.php (no versionado), que devuelve 'local' o 'production'. Sin el archivo, ilegible
 * o con otro valor, es PRODUCCIÓN: la cabecera Host la manda el cliente y no decide nada (P58).
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class AppEnvironment
{
    const LOCAL = 'local';
    const PRODUCTION = 'production';

    const FILE_RELATIVE_PATH = 'app/config/environment.php';

    /**
     * @var string|null El entorno ya leído
     */
    private static $environment = null;

    /**
     * @var bool|null Si el archivo existía al leerlo
     */
    private static $configured = null;

    /**
     * @var string|null Archivo fijado por una prueba
     */
    private static $testPath = null;

    /**
     * 'local' o 'production'.
     *
     * @return string
     */
    public static function get(): string
    {
        if (self::$environment === null) {
            self::load();
        }
        return (string) self::$environment;
    }

    /**
     * Si existe el archivo de entorno. Sin él, la instalación funciona como producción.
     *
     * @return bool
     */
    public static function isConfigured(): bool
    {
        if (self::$configured === null) {
            self::load();
        }
        return (bool) self::$configured;
    }

    /**
     * Lee el archivo. Se llama en el arranque, antes de database.php, y se puede repetir.
     *
     * @return void
     */
    public static function load(): void
    {
        $path = self::$testPath ?? self::defaultPath();
        self::$configured = is_file($path);
        $value = null;
        if (self::$configured) {
            try {
                $value = (static fn(string $file) => include $file)($path);
            } catch (\Throwable) {
                $value = null;
            }
        }
        self::$environment = $value === self::LOCAL ? self::LOCAL : self::PRODUCTION;
    }

    /**
     * Solo para pruebas: lee otro archivo (null vuelve al real) y relee.
     *
     * @param string|null $path
     * @return void
     */
    public static function useForTesting(?string $path): void
    {
        self::$testPath = $path;
        self::load();
    }

    /**
     * @return string
     */
    private static function defaultPath(): string
    {
        return rtrim(str_replace('\\', '/', dirname(__DIR__, 5)), '/') . '/' . self::FILE_RELATIVE_PATH;
    }
}
