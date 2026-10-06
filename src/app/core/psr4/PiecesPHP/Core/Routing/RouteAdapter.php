<?php

/**
 * RouteAdapter.php
 */
namespace PiecesPHP\Core\Routing;

use FastRoute\RouteParser\Std;
use PiecesPHP\Core\Route;
use Slim\Routing\RouteCollectorProxy;
use TypeError;

/**
 * RouteAdapter - Esquema de ruta
 *
 * @package     PiecesPHP\Core\Routing
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class RouteAdapter
{

    /**
     * @var Router
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
     * @var bool Si el patrón no analiza: fuera de local la ruta NO se registra (P57)
     */
    protected $patternIsInvalid = false;

    /**
     * @param string $route
     * @param string|callable $controller
     * @param string $name
     * @param string $method
     * @param bool $requireLogin
     * @param string $alias IGNORADO desde el bloque CX: `set_route()` ya no registra una segunda
     *                       ruta. No se retira porque es el SEXTO posicional de 221 declaraciones.
     * @param array<string>|array<int> $rolesAllowed
     * @param array $defaultParamsValues
     * @param array<callable>|array<string> $middlewares
     */
    public function __construct(
        string $route,
        $controller,
        ?string $name = null,
        string $method = 'GET',
        bool $requireLogin = false,
        ?string $alias = null,
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
    public function method(?string $value = null, bool $asArray = false)
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
    public function name(?string $value = null)
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
    public function alias(?string $value = null)
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
    public function requireLogin(?bool $value = null)
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
    public function rolesAllowed(?array $value = null)
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
    public function routeSegment(?string $value = null)
    {

        if ($value !== null) {

            $this->route = str_replace(' ', '', $value);

            $paramsSetted = $this->params;
            $params = [];
            preg_match_all('/\{[a-z|A-Z|0-9|_|-]*\}/', $this->route, $params);
            $this->params = [];
            if (count($params[0] ?? []) > 0) {
                foreach ($params[0] as $param) {
                    $param = str_replace(['{', '}'], '', $param);
                    $this->params[$param] = null;
                }
            }

            foreach ($paramsSetted as $paramName => $paramValue) {
                $this->setParameterValue($paramName, $paramValue);
            }

            $this->checkPattern();

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
     * El patrón, contra el analizador de FastRoute, que es quien lo va a leer al despachar (P57).
     *
     * En local revienta aquí, con el nombre, el patrón y el archivo que la declaró: así el fallo se ve donde se
     * escribió. Fuera de local no se lanza —una ruta mal escrita no puede tumbar la instalación entera—: se apunta en
     * InvalidRoutes, se registra en el log y register() se la salta.
     *
     * @return bool
     * @throws \InvalidArgumentException en local
     */
    protected function checkPattern(): bool
    {
        $pattern = $this->route;
        $name = is_string($this->name) ? $this->name : '(sin nombre)';

        try {
            (new Std())->parse($pattern);
            $this->patternIsInvalid = false;
            return true;
        } catch (\Throwable $e) {
            $this->patternIsInvalid = true;
            $declaredIn = self::declaredIn();
            $message = "La ruta «{$name}» tiene un patrón que FastRoute no puede analizar: «{$pattern}». "
                . $e->getMessage() . ' Declarada en ' . $declaredIn . '.';

            if (function_exists('is_local') && is_local()) {
                throw new \InvalidArgumentException($message, 0, $e);
            }

            InvalidRoutes::add($name, $pattern, $e->getMessage(), $declaredIn);
            if (function_exists('log_exception')) {
                log_exception(new \InvalidArgumentException($message, 0, $e));
            }
            return false;
        }
    }

    /**
     * El archivo:línea que declaró la ruta: el primer marco fuera de PiecesPHP\Core\Routing.
     *
     * @return string
     */
    private static function declaredIn(): string
    {
        //El archivo de un marco es el de QUIEN hizo esa llamada, así que el sitio donde se escribió la ruta es el
        //ÚLTIMO marco interno del enrutador: el de más afuera ya pertenece a quien la declaró.
        $ultimoInterno = null;
        foreach (debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            $class = (string) ($frame['class'] ?? '');
            $esInterno = $class !== '' && (str_starts_with($class, 'PiecesPHP\\Core\\Routing') || $class === Route::class);
            if ($esInterno) {
                if (isset($frame['file'], $frame['line'])) {
                    $ultimoInterno = $frame['file'] . ':' . $frame['line'];
                }
                continue;
            }
            break;
        }
        return $ultimoInterno ?? 'origen desconocido';
    }

    /**
     * @param Router|RouteCollectorProxy $router
     * @return void
     */
    public function register($router = null)
    {

        $validInstancesRoutes = array_reduce([
            $router instanceof Router,
            $router instanceof RouteCollectorProxy,
        ], function ($a, $b) {
            return $a || $b;
        }, false);

        if (!$validInstancesRoutes) {
            throw new TypeError("\$router debe ser instancia de alguna de las siguientes clases: " . implode(', ', [
                Router::class,
                RouteCollectorProxy::class,
            ]));
        }

        //La ruta con el patrón roto no se registra (P57): en local ya habría reventado al fijarlo.
        if ($this->patternIsInvalid) {
            return;
        }

        $route_info = get_route_info($this->name);

        $router ??= static::$router;

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
     *      string                 $route[route]
     *      string|callable        $route[controller]
     *      string|string[]        $route[method]
     *      string|null            $route[name]
     *      string|null            $route[route_alias]
     *      bool                   $route[require_login]
     *      array                  $route[roles_allowed]
     *      array                  $route[parameters]
     *      array<string|callable> $route[middlewares]
     * @return Route
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
        $name = $route['name'] ?? null;
        $alias = $route['route_alias'] ?? null;
        $requireLogin = isset($route['require_login']) ? $route['require_login'] === true : false;
        $rolesAllowed = $route['roles_allowed'] ?? [];
        $rolesAllowed = is_array($rolesAllowed) ? $rolesAllowed : [$rolesAllowed];
        $parameters = $route['parameters'] ?? [];
        $parameters = is_array($parameters) ? $parameters : [$parameters];
        $middlewares = $route['middlewares'] ?? [];
        $middlewares = is_array($middlewares) ? $middlewares : [$middlewares];

        $instance = new RouteAdapter($routeSegment, $controller);

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
     * @param Router $router
     * @return void
     */
    public static function setRouter(Router $router)
    {
        self::$router = $router;
    }
}
