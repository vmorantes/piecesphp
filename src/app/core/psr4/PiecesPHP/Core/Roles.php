<?php

/**
 * Roles.php
 */

namespace PiecesPHP\Core;

use PiecesPHP\Core\Exceptions\RoleDuplicateException;
use PiecesPHP\Core\Exceptions\RoleMalformedException;
use PiecesPHP\Core\Exceptions\RoleNotExistsException;
use \Slim\Http\Request as Request;

/**
 * Roles.
 *
 * Gestor de roles de usuarios
 *
 * Los roles registrados son inmutables
 *
 * Está pensado para controlar los permisos basado en los nombres de ruta de una aplicación Slim
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class Roles
{
    const IDENTIFIER_TYPE_NAME = 'IDENTIFIER_TYPE_NAME';
    const IDENTIFIER_TYPE_CODE = 'IDENTIFIER_TYPE_CODE';
    /**
     * $roles
     *
     * @var array
     */
    protected static $roles = [];
    /**
     * $currentRole
     *
     * @var array
     */
    protected static $currentRole = null;
    /**
     * $silentMode
     *
     * @var array
     */
    protected static $silentMode = false;

    /**
     * setCurrentRole
     *
     * @param mixed $name_or_code
     * @return void
     * @throws RoleNotExistsException
     */
    public static function setCurrentRole($name_or_code)
    {

        $roles = self::getRoles();
        $current_role = null;

        foreach ($roles as $role) {

            if ($role['name'] == $name_or_code || $role['code'] == $name_or_code) {

                $current_role = $role;

            }
        }

        if ($current_role !== null) {
            self::$currentRole = $current_role;
        } else {
            throw new RoleNotExistsException();
        }
    }

    /**
     * registerRole
     *
     * Registra un nuevo rol
     *
     * @param string $name Nombre del rol
     * @param int $code Código del rol
     * @param Array<string> $allowed_routes Nombres de rutas permitidas
     * @param bool $all_access Si es true se le da acceso total
     * @return void
     * @throws RoleDuplicateException Si code o name están repetidos
     */
    public static function registerRole(string $name, int $code = null, array $allowed_routes, bool $all_access = false)
    {

        $duplicate = false;

        $roles_currents = self::getRoles();

        foreach ($roles_currents as $role) {
            if ($role['name'] == $name || $role['code'] == $code) {
                throw new RoleDuplicateException();
            }
        }

        if (!$duplicate) {
            self::$roles[] = [
                'code' => is_null($code) ? uniqid('pcs_roles') : $code,
                'name' => $name,
                'all' => $all_access,
                'allowed_routes' => array_filter($allowed_routes, function ($allowed) {
                    if (is_string($allowed)) {
                        return true;
                    } else {
                        return false;
                    }
                }),
            ];
        }
    }

    /**
     * registerRoles
     *
     * Registra varios roles
     *
     * Cada rol debe ser:
     *
     * [
     * string 'name'=>'...',
     * int 'code' (opcional)=>0,
     * array 'allowed_routes'=>[strings...],
     * bool 'all' (opcional)=>false
     * ]
     *
     * @param array $roles Roles
     * @return void
     * @throws RoleMalformedException
     */
    public static function registerRoles(array $roles)
    {
        foreach ($roles as $role) {

            $all = isset($role['all']) ? (is_bool($role['all']) ? $role['all'] : null) : false;
            $code = isset($role['code']) ? (is_integer($role['code']) ? $role['code'] : false) : null;

            if (!isset($role['name']) || !isset($role['allowed_routes']) || $all === null || $code === false) {
                throw new RoleMalformedException();
            }

            $name = $role['name'];
            $allowed_routes = $role['allowed_routes'];

            self::registerRole($name, $code, $allowed_routes, $all);

        }
    }

    /**
     * getCurrentRole
     *
     * @return array
     */
    public static function getCurrentRole()
    {
        return self::$currentRole;
    }

    /**
     * getRolesIdentifiers
     *
	 * @param bool $i18n
     * Devuelve los roles registrados de la forma:
     *
     * [
     *     'name'=>'code',
     *     ...
     * ]
     *
     * @return array
     */
    public static function getRolesIdentifiers(bool $i18n = false): array
    {
        $roles = [];

        foreach (self::$roles as $role) {
            $roles[($i18n ? __('usersModule', $role['name']) : $role['name'])] = $role['code'];
        }

        return $roles;
    }

    /**
     * getRoles
     *
     * Devuelve los roles registrados
     *
     * @return array
     */
    public static function getRoles(): array
    {
        return self::$roles;
    }

    /**
     * getRole
     *
     * Devuelve el role que coincida o null si no existe
     *
     * @return array|null
     */
    public static function getRole($name_or_code)
    {
        $roles = self::getRoles();

        foreach ($roles as $role) {
            if ($role['name'] == $name_or_code || $role['code'] == $name_or_code) {
                return $role;
            }
        }

        return null;
    }

    /**
     * getSilentMode
     *
     * Si es true no lanzará RoleNotExistsException en hasPermissions
     *
     * @return bool
     */
    public static function getSilentMode()
    {
        return self::$silentMode;
    }

    /**
     * addPermission
     *
     * @param string $name_route
     * @param mixed $identifier
     * @param mixed $type
     * @return void
     * @throws RoleNotExistsException
     */
    public static function addPermission(string $name_route, $identifier, $type = self::IDENTIFIER_TYPE_CODE)
    {
        if ($type == self::IDENTIFIER_TYPE_NAME) {
            $identifier = (string) $identifier;
        } elseif ($type == self::IDENTIFIER_TYPE_CODE) {
            $identifier = (int) $identifier;
        }

        $role = self::getRole($identifier);

        if (!is_null($role)) {
            $name = $role['name'];
            $allowed_routes = $role['allowed_routes'];
            $allowed_routes[] = $name_route;
            $allowed_routes = array_unique($allowed_routes);
            $role['allowed_routes'] = $allowed_routes;

            foreach (self::$roles as $index => $value) {
                if ($value['name'] == $name) {
                    self::$roles[$index] = $role;
                    break;
                }
            }

        } else {
            throw new RoleNotExistsException();
        }

    }

    /**
     * addPermission
     *
     * @param string[] $name_route
     * @param mixed $identifier
     * @param mixed $type
     * @return void
     * @throws RoleNotExistsException
     */
    public static function addPermissions(array $routes, $identifier, $type = self::IDENTIFIER_TYPE_CODE)
    {
        foreach ($routes as $route) {
            if (is_string($route)) {
                self::addPermission($route, $identifier, $type);
            }
        }
    }

    /**
     * setSilentMode
     *
     * Si es true no lanzará RoleNotExistsException en hasPermissions
     *
     * @param bool $mode
     * @return void
     */
    public static function setSilentMode(bool $mode)
    {
        self::$silentMode = $mode;
    }

    /**
     * hasPermissions
     *
     * Verifica los permisos de un rol
     *
     * @param \Slim\Http\Request|string $request_route La ruta solicita. Puede ser un objeto Request de slim o el
     * nombre de la ruta como string
     * @param mixed $id_role El code o name del rol a examinar
     * @param bool $silent_mode Si lanza RoleNotExistsException o no
     * @return bool true si tiene permisos, false si no
     * @throws TypeError Además de las razones comunes, puede lanzar esta exepción si $request_route no es \Slim\Http\Request|string
     * @throws RoleNotExistsException Si el role no existe
     */
    public static function hasPermissions($request_route, $id_role, bool $silent_mode = false): bool
    {

        $role_exists = false;

        $allowed = false;

        $require_login = false;

        $name_route = '';

        if ($request_route instanceof \Slim\Http\Request) {

            $request_route = $request_route->getAttribute('route')->getName();

        } else if (!is_string($request_route)) {

            throw new \TypeError(__('errors', 'type_param_not_allowed'));

        }

        $routes = get_routes();
        $route_exists = array_key_exists($request_route, $routes);

        if ($route_exists) {

            $route = get_route_info($request_route);
            $name_route = $route['name'];
            $require_login = $route['require_login'];
            $roles_allowed = $route['roles_allowed'];
            $require_login = !$require_login ? count($roles_allowed) > 0 : $require_login;

        } else {

            return false;

        }

        $roles = self::getRoles();

        foreach ($roles as $role) {

            $role_allowed_routes = $role['allowed_routes'];
            $role_name = $role['name'];
            $role_code = $role['code'];
            $role_allow_all = $role['all'];
            $role_exists = $role_name === $id_role || $role_code === $id_role;

            $route_exists_on_role = !in_array($name_route, $role_allowed_routes);

            if ($route_exists_on_role) {

                if ($require_login) {

                    if ($role_exists) {

                        if ($role_allow_all === true) {

                            $allowed = true;
                            break;

                        } else {

                            foreach ($role_allowed_routes as $route) {

                                if ($route == $name_route) {
                                    $allowed = true;
                                    break;
                                }

                            }

                            break;

                        }
                    }

                } else {

                    if ($role_exists) {

                        $allowed = true;
                        break;

                    }

                }

            }

            if ($role_exists) {

                if ($role_allow_all === true) {

                    $allowed = true;
                    break;

                } else {

                    foreach ($role_allowed_routes as $route) {
                        if ($route == $name_route) {
                            $allowed = true;
                            break;
                        }
                    }

                    break;

                }
            }
        }

        if ($role_exists) {
            return $allowed;
        } else {
            if ($silent_mode || self::getSilentMode()) {
                return false;
            } else {
                throw new RoleNotExistsException();
            }
        }

    }

    /**
     * hasRoles
     *
     * @return bool
     */
    public static function hasRoles(): bool
    {
        return count(self::$roles) > 0;
    }

}
