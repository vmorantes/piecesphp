<?php

/**
 * RouteNamingTrait.php
 */

namespace PiecesPHP\Core\Routing;

use PiecesPHP\Core\Roles;

/**
 * RouteNamingTrait - Nombre y URL de las rutas de un controlador.
 *
 * Es TRAIT y no clase base porque los controladores no comparten padre: los de zona
 * pública extienden `BaseController` y los de panel `AdminPanelController`. Un trait
 * compone en cualquier jerarquía, y dentro de él `self::` sigue resolviendo a la clase
 * que lo usa, así que `self::$baseRouteName` sigue siendo la del módulo.
 *
 * `_allowedRoute()` vive aquí con implementación permisiva por defecto, y no en
 * `RouteGuardTrait`, por una razón de funcionamiento: `routeName()` lo llama SIEMPRE, así
 * que quien use este trait sin declararlo tendría un fatal. Con el valor por defecto,
 * quien no quiera reglas extra no escribe nada — y quien sí las quiera escribe SOLO ese
 * método, que es exactamente la parte que hay que pensar al clonar un módulo.
 *
 * @package     PiecesPHP\Core\Routing
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
trait RouteNamingTrait
{

    /**
     * Obtener URL de una ruta
     *
     * @param string $name
     * @param array $params
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(?string $name = null, array $params = [], bool $silentOnNotExists = false)
    {

        $simpleName = $name ?? '';

        if (!is_null($name)) {
            $name = trim($name);
            $name = $name !== '' ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$baseRouteName . $name : self::$baseRouteName;

        $allowed = false;
        $current_user = getLoggedFrameworkUser();

        if ($current_user !== null) {
            $allowed = Roles::hasPermissions($name, $current_user->type);
        } else {
            $allowed = true;
        }

        $route = '';

        if ($allowed) {
            $route = get_route(
                $name,
                $params,
                $silentOnNotExists
            );
            $route = !is_string($route) ? '' : $route;
        }

        $allow = self::_allowedRoute($simpleName, $route, $params);

        return $allow ? $route : '';
    }

    /**
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * Punto de variación del módulo: aquí y en ningún otro sitio van las reglas de negocio
     * que decidan ocultar una ruta que los roles sí permiten. El valor por defecto no
     * añade ninguna.
     *
     * Es `protected` y no `private` porque es un punto de extensión: los 32 módulos que hoy
     * lo declaran usan `private` y siguen ganando sobre este por ser declaración de clase.
     *
     * @param string $name
     * @param string $route
     * @param array $params
     * @return bool
     */
    protected static function _allowedRoute(string $name, string $route, array $params = [])
    {
        return true;
    }

}
