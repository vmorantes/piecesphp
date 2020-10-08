<?php

/**
 * Route.php
 */
namespace PiecesPHP\Core;

use Slim\App;
use TypeError;

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
     * @var App
     */
    public static $router = null;
    /**
     * @var string
     */
    protected $route = '';
    /**
     * @var string
     */
    protected $method = 'GET';
    /**
     * @var string
     */
    protected $name = '';
    /**
     * @var string
     */
    protected $alias = '';
    /**
     * @var string|callable
     */
    protected $controller = '';
    /**
     * @var array<callable>|array<string>
     */
    protected $middlewares = [];
    /**
     * @var bool
     */
    protected $requireLogin = false;
    /**
     * @var array
     */
    protected $params = [];
    /**
     * @var string[]
     */
    protected $rolesAllowed = [];

    /**
     * @param string $route
     * @param string|callable $controller
     * @param string $name
     * @param string $method
     * @param bool $requireLogin
     * @param string $alias
     * @param string[] $rolesAllowed
     * @param array $defaultParamsValues
     * @param array<callable>|array<string> $middlewares
     */
    public function __construct(
        string $route,
        $controller,
        string $name = null,
        string $method = 'GET',
        bool $requireLogin = false,
        string $alias = null,
        array $rolesAllowed = [],
        array $defaultParamsValues = [],
        array $middlewares = []
    ) {

        if (!is_string($controller) && !is_callable($controller)) {
            throw new \TypeError('El argumento $controller debe ser un string o un objeto callable.');
        }
        $this->controller($controller);
        $this->name($name == null ? uniqid() : $name);
        $this->method($method);
        $this->requireLogin($requireLogin);
        $this->alias($alias == null ? $name : $alias);
        $this->rolesAllowed($rolesAllowed);
        $this->routeSegment($route);
        $this->setParametersValues($defaultParamsValues);
        foreach ($middlewares as $mw) {
            $this->addMiddleware($mw);
        }
    }

    /**
     * @param string $value
     * @param bool $asArray
     * @return static|string|string[]
     */
    public function method(string $value = null, bool $asArray = false)
    {
        $property = 'method';

        if ($value !== null) {
            $this->$property = $value;
        } else {
            return $asArray ? explode('|', $this->$property) : $this->$property;
        }

        return $this;
    }

    /**
     * @param string[] $methods
     * @return static
     */
    public function methodFromArray(array $methods)
    {

        foreach ($methods as $key => $method) {

            $method = trim($method);

            if (mb_strlen($method) > 0) {
                $methods[$key] = mb_strtoupper($method);
            } else {
                unset($methods[$key]);
            }

        }

        $this->method(implode('|', $methods));

        return $this;

    }

    /**
     * @param string $value
     * @return static|string
     */
    public function name(string $value = null)
    {
        $property = 'name';

        if ($value !== null) {
            $this->$property = $value;
        } else {
            return $this->$property;
        }

        return $this;
    }

    /**
     * @param string $value
     * @return static|string
     */
    public function alias(string $value = null)
    {
        $property = 'alias';

        if ($value !== null) {
            $this->$property = $value;
        } else {
            return $this->$property;
        }

        return $this;
    }

    /**
     * @param bool $value
     * @return static|bool
     */
    public function requireLogin(bool $value = null)
    {
        $property = 'requireLogin';

        if ($value !== null) {
            $this->$property = $value;
        } else {
            return $this->$property;
        }

        return $this;
    }

    /**
     * @param array $value
     * @return static|array
     */
    public function rolesAllowed(array $value = null)
    {
        $property = 'rolesAllowed';

        if ($value !== null) {
            $this->$property = $value;
        } else {
            return $this->$property;
        }

        return $this;
    }

    /**
     * @param string $value
     * @return static|string
     */
    public function routeSegment(string $value = null)
    {

        if ($value !== null) {

            $this->route = str_replace(' ', '', $value);

            $params = [];
            preg_match_all('/\{[a-z|A-Z|0-9|_|-]*\}/', $this->route, $params);
            $this->params = [];
            if (count($params) > 0) {
                foreach ($params[0] as $param) {
                    $param = str_replace(['{', '}'], '', $param);
                    $this->params[$param] = null;
                }
            }

        } else {
            return $this->route;
        }

        return $this;
    }

    /**
     * @param callable|string $value
     * @return static|callable|string
     */
    public function controller($value = null)
    {
        $property = 'controller';

        if ($value !== null) {

            if (is_scalar($value)) {
                $setter = function (string $value) {return $value;};
            } else {
                $setter = function (callable $value) {return $value;};
            }

            $this->$property = ($setter)($value);

        } else {
            return $this->$property;
        }

        return $this;
    }

    /**
     * @param array $defaultParamsValues
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
     * @return array
     */
    public function getParameters()
    {
        return $this->params;
    }

    /**
     * @param callable|string $middleware
     * @return static
     */
    public function addMiddleware($middleware)
    {

        if (is_scalar($middleware)) {
            $setter = function (string $value) {return $value;};
        } else {
            $setter = function (callable $value) {return $value;};
        }

        $this->middlewares[] = ($setter)($middleware);

        return $this;
    }

    /**
     * @return array<callable>|array<string>
     */
    public function middlewares()
    {
        return $this->middlewares;
    }

    /**
     * @param App $router
     * @return void
     */
    public function register(App $router = null)
    {

        $route_info = get_route_info($this->name);

        $router = $router === null ? static::$router : $router;

        if ($route_info === null) {
            register_route($this->toArray(), $router);
        } else {
            throw new \Exception("La ruta $this->name ya existe.");
        }

    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'route' => $this->routeSegment(),
            'controller' => $this->controller(),
            'method' => $this->method(),
            'name' => $this->name(),
            'route_alias' => $this->alias(),
            'require_login' => $this->requireLogin(),
            'roles_allowed' => $this->rolesAllowed(),
            'parameters' => $this->getParameters(),
            'middlewares' => $this->middlewares(),
        ];
    }

    /**
     * @param array $route
     * @param string $route[route]
     * @param string|callable $route[controller]
     * @param string|string[] $route[method]
     * @param string|null $route[name]
     * @param string|null $route[route_alias]
     * @param bool $route[require_login]
     * @param array $route[roles_allowed]
     * @param array $route[parameters]
     * @param array<string|callable> $route[middlewares]
     * @return static
     */
    public static function instanceFromArray(array $route)
    {

        $requiredParams = [
            'route',
            'method',
        ];

        array_map(function ($name) use ($route) {

            if (!isset($route[$name])) {
                throw new TypeError("El parámetro '{$name}' es obligatorio.");
            }

        }, $requiredParams);

        $routeSegment = $route['route'];
        $controller = $route['controller'];
        $method = $route['method'];
        $name = isset($route['name']) ? $route['name'] : null;
        $alias = isset($route['route_alias']) ? $route['route_alias'] : null;
        $requireLogin = isset($route['require_login']) ? $route['require_login'] === true : false;
        $rolesAllowed = isset($route['roles_allowed']) ? $route['roles_allowed'] : [];
        $rolesAllowed = is_array($rolesAllowed) ? $rolesAllowed : [$rolesAllowed];
        $parameters = isset($route['parameters']) ? $route['parameters'] : [];
        $parameters = is_array($parameters) ? $parameters : [$parameters];
        $middlewares = isset($route['middlewares']) ? $route['middlewares'] : [];
        $middlewares = is_array($middlewares) ? $middlewares : [$middlewares];

        $instance = new Route($routeSegment, $controller);

        if (is_array($method)) {
            $instance->methodFromArray($method);
        } else {
            $instance->method($method);
        }

        $instance->name($name);
        $instance->alias($alias);
        $instance->requireLogin($requireLogin);
        $instance->rolesAllowed($rolesAllowed);
        $instance->setParametersValues($parameters);

        foreach ($middlewares as $mw) {
            $instance->addMiddleware($mw);
        }

        return $instance;

    }

    /**
     * @param App $router
     * @return void
     */
    public static function setRouter(App $router)
    {
        self::$router = $router;
    }
}
