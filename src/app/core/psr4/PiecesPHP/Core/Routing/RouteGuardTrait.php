<?php

/**
 * RouteGuardTrait.php
 */

namespace PiecesPHP\Core\Routing;

/**
 * RouteGuardTrait - Visibilidad de una ruta en menús y botones.
 *
 * Separado de `RouteNamingTrait` porque no todo controlador que nombra rutas expone un
 * guardián: seis de los cuarenta y cuatro tienen `routeName()` y no `allowedRoute()`.
 *
 * Descansa por completo en `routeName()`, que devuelve cadena vacía cuando el usuario no
 * tiene permiso. **Por eso una URL escrita a mano se salta el control de acceso**: no pasa
 * por aquí.
 *
 * @package     PiecesPHP\Core\Routing
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
trait RouteGuardTrait
{

    /**
     * Verificar si una ruta es permitida
     *
     * @param string $name
     * @param array $params
     * @return bool
     */
    public static function allowedRoute(string $name, array $params = [])
    {
        $route = self::routeName($name, $params, true);
        $allow = (string) $route !== '';
        return $allow;
    }

}
