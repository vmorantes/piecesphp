<?php

/**
 * InvalidRoutes.php
 */

namespace PiecesPHP\Core\Routing;

/**
 * InvalidRoutes - Las rutas cuyo patrón no analiza, descartadas fuera de local (P57).
 *
 * FastRoute analiza TODOS los patrones juntos en el primer despacho: una ruta mal escrita tumba la web y bin/cli
 * enteros, y el error no dice cuál es. En local la ruta mala revienta al registrarse, con su nombre y su archivo; en
 * producción se apunta aquí, no se registra, y el resto de la aplicación sigue en pie.
 *
 * @package     PiecesPHP\Core\Routing
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class InvalidRoutes
{
    /**
     * @var array<int,array{name: string, pattern: string, error: string, declaredIn: string}>
     */
    private static array $routes = [];

    /**
     * @param string $name
     * @param string $pattern
     * @param string $error
     * @param string $declaredIn archivo:línea que declaró la ruta
     * @return void
     */
    public static function add(string $name, string $pattern, string $error, string $declaredIn): void
    {
        self::$routes[] = [
            'name' => $name,
            'pattern' => $pattern,
            'error' => $error,
            'declaredIn' => $declaredIn,
        ];
    }

    /**
     * @return array<int,array{name: string, pattern: string, error: string, declaredIn: string}>
     */
    public static function all(): array
    {
        return self::$routes;
    }

    /**
     * @return void
     */
    public static function clear(): void
    {
        self::$routes = [];
    }
}
