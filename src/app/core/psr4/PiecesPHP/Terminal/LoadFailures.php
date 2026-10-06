<?php

/**
 * LoadFailures.php
 */

namespace PiecesPHP\Terminal;

/**
 * LoadFailures
 *
 * Los archivos de suites (`local-tests/`) y de tareas (`Terminal/Tasks/`) que no se pudieron cargar al arrancar.
 *
 * Un archivo roto se salta para que no tumbe `bin/cli` entero, pero NUNCA en silencio: una suite que no carga no
 * registra su acción y `gates` no la vería. Por eso se anota aquí, `bin/cli` lo avisa por STDERR, `gates` lo cuenta
 * como fallo y `verify-integrity` lo publica.
 *
 * Límite: un error de compilación que no es excepción (redeclarar una clase) sigue siendo fatal y no llega aquí.
 *
 * @package     PiecesPHP\Terminal
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class LoadFailures
{
    const TYPE_SUITE = 'suite';
    const TYPE_TASK = 'task';

    /**
     * @var array<int,array{file: string, type: string, class: string, message: string, line: int}>
     */
    protected static array $failures = [];

    /**
     * @var array<string,int>
     */
    protected static array $loaded = [
        self::TYPE_SUITE => 0,
        self::TYPE_TASK => 0,
    ];

    /**
     * Incluye el archivo; si lanza, lo anota y devuelve false.
     *
     * @param string $path Ruta absoluta
     * @param string $type self::TYPE_SUITE o self::TYPE_TASK
     * @return bool
     */
    public static function includeFile(string $path, string $type): bool
    {
        try {
            include_once $path;
            self::$loaded[$type] = (self::$loaded[$type] ?? 0) + 1;
            return true;
        } catch (\Throwable $e) {
            self::add($path, $type, $e);
            return false;
        }
    }

    /**
     * @param string $path
     * @param string $type
     * @param \Throwable $e
     * @return void
     */
    public static function add(string $path, string $type, \Throwable $e): void
    {
        self::$failures[] = [
            'file' => self::relative($path),
            'type' => $type,
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
        ];
    }

    /**
     * @param string|null $type Filtra por tipo; null, todos
     * @return array<int,array{file: string, type: string, class: string, message: string, line: int}>
     */
    public static function all(?string $type = null): array
    {
        if ($type === null) {
            return self::$failures;
        }
        return array_values(array_filter(self::$failures, fn($f) => $f['type'] === $type));
    }

    /**
     * Cuántos archivos del tipo se cargaron sin fallo.
     *
     * @param string $type
     * @return int
     */
    public static function loadedCount(string $type): int
    {
        return self::$loaded[$type] ?? 0;
    }

    /**
     * Una línea legible por fallo: «<archivo>: <clase>: <mensaje> (línea N)».
     *
     * @param array{file: string, type: string, class: string, message: string, line: int} $failure
     * @return string
     */
    public static function describe(array $failure): string
    {
        return "{$failure['file']}: {$failure['class']}: {$failure['message']} (línea {$failure['line']})";
    }

    /**
     * @param string $path
     * @return string
     */
    protected static function relative(string $path): string
    {
        $base = rtrim(str_replace('\\', '/', basepath('')), '/') . '/';
        $path = str_replace('\\', '/', $path);
        return str_starts_with($path, $base) ? 'src/' . substr($path, strlen($base)) : $path;
    }
}
