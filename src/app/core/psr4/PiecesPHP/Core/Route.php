<?php

/**
 * Route.php
 */
namespace PiecesPHP\Core;

/**
 * Route - Esquema de ruta
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class Route
{

    /**
     * $router
     *
     * @var \Slim\App
     */
    public static $router = null;
    /**
     * $route
     *
     * @var string
     */
    protected $route = '';
    /**
     * $method
     *
     * @var string
     */
    protected $method = 'GET';
    /**
     * $name
     *
     * @var string
     */
    protected $name = '';
    /**
     * $alias
     *
     * @var string
     */
    protected $alias = '';
    /**
     * $controller
     *
     * @var string|callable
     */
    protected $controller = '';
    /**
     * $requireLogin
     *
     * @var bool
     */
    protected $requireLogin = false;
    /**
     * $params
     *
     * @var array
     */
    protected $params = [];
    /**
     * $rolesAllowed
     *
     * @var string[]
     */
    protected $rolesAllowed = [];

    /**
     * __construct
     *
     * @param string $route
     * @param string|callable $controller
     * @param string $name
     * @param string $method
     * @param bool $requireLogin
     * @param string $alias
     * @param string[] $rolesAllowed
     * @param array $defaultParamsValues
     * @return static
     */
    public function __construct(string $route, $controller, string $name = null, string $method = 'GET', bool $requireLogin = false, string $alias = null, array $rolesAllowed = [], array $defaultParamsValues = [])
    {

        if (!is_string($controller) && !is_callable($controller)) {
            throw new \TypeError('El argumento $controller debe ser un string o un objeto callable.');
        }
        $this->route = $route;
        $this->controller = $controller;
        $this->name = $name == null ? uniqid() : $name;
        $this->method = $method;
        $this->requireLogin = $requireLogin;
        $this->alias = $alias == null ? $name : $alias;
        $this->rolesAllowed = $rolesAllowed;

        $params = [];
        preg_match_all('/\{[a-z|A-Z|0-9|_|-]*\}/', $this->route, $params);
        $this->params = [];
        if (count($params) > 0) {
            foreach ($params[0] as $param) {
                $param = str_replace(['{', '}'], '', $param);
                $this->params[$param] = null;
            }
        }
        $this->setParametersValues($defaultParamsValues);
    }

    /**
     * setParametersValues
     *
     * @param mixed array
     * @return static
     */
    public function setParametersValues(array $defaultParamsValues = [])
    {
        foreach ($defaultParamsValues as $name => $value) {
            $this->setParameterValue($name, $value);
        }
        return $this;
    }

    /**
     * setParameterValue
     *
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function setParameterValue(string $name, $value = null)
    {
        if (array_key_exists($name, $this->params)) {
            $value = is_scalar($value) ? $value : null;
            $this->params[$name] = $value;
        }
        return $this;
    }

    /**
     * register
     *
     * @param \Slim\App $router
     * @return void
     */
    public function register(\Slim\App $router = null)
    {

        $route_info = get_route_info($this->name);

        $router = $router === null ? static::$router : $router;

        if ($route_info === null) {
            register_routes([
                [
                    'route' => $this->route,
                    'controller' => $this->controller,
                    'method' => $this->method,
                    'name' => $this->name,
                    'alias' => $this->alias,
                    'require_login' => $this->requireLogin,
                    'roles_allowed' => $this->rolesAllowed,
                    'parameters' => $this->params,
                ],
            ], $router);
        } else {
            throw new \Exception("La ruta $this->name ya existe.");
        }

    }

    /**
     * setRouter
     *
     * @param \Slim\App $router
     * @return void
     */
    public static function setRouter(\Slim\App $router)
    {
        self::$router = $router;
    }
}
