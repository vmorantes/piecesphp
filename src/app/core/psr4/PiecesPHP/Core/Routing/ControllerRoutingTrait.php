<?php

/**
 * ControllerRoutingTrait.php
 */

namespace PiecesPHP\Core\Routing;

use PiecesPHP\Core\Roles;

/**
 * ControllerRoutingTrait - Nombre, URL y visibilidad de las rutas de un controlador.
 *
 * Es TRAIT y no clase base porque los controladores no comparten padre: los de zona
 * pública extienden `BaseController` y los de panel `AdminPanelController`. Un trait
 * compone en cualquier jerarquía, y dentro de él `self::` sigue resolviendo a la clase
 * que lo usa, así que `self::$baseRouteName` sigue siendo la del módulo.
 *
 * Los tres métodos van juntos porque **no se pueden separar**: `routeName()` llama SIEMPRE
 * a `_allowedRoute()`, y `allowedRoute()` no hace más que preguntarle a `routeName()` si
 * devolvió cadena. Repartirlos en dos traits era una frontera inventada.
 *
 * `_allowedRoute()` trae implementación permisiva por defecto para que quien no tenga reglas
 * extra no escriba nada, y quien sí las tenga escriba SOLO ese método — que es exactamente
 * la parte que hay que pensar al clonar un módulo.
 *
 * DOS PREGUNTAS DE SEGURIDAD ABIERTAS, preexistentes, que ahora viven aquí y por tanto en
 * todas partes. NO se tocan sin decisión explícita; están escritas en T26 del documento
 * `.agents/context/18-siguientes-ventanas.md`:
 *
 *   1. `routeName()` **autoriza para construir una cadena**: llama a
 *      `Roles::hasPermissions()` en los 606 sitios que generan una URL.
 *   2. **Sin usuario, CONCEDE.** El `else` de abajo pone `$allowed = true`, y
 *      `getLoggedFrameworkUser()` devuelve `null` también cuando el constructor de
 *      `UserDataPackage` lanza. Un fallo al construir al usuario se trata como anónimo.
 *
 * @package     PiecesPHP\Core\Routing
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
trait ControllerRoutingTrait
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
     * Verificar si una ruta es permitida
     *
     * Descansa por completo en `routeName()`, que devuelve cadena vacía cuando el usuario no
     * tiene permiso.
     *
     * **22 controladoras instalan `DefaultAccessControlModules`**, cuyo veredicto de acceso es
     * `routeName()` devolviendo cadena no vacía. En esos módulos este método NO decide
     * visibilidad: **DECIDE EL ACCESO**. Fuera de ellos sí decide solo visibilidad. Ver T148.
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
