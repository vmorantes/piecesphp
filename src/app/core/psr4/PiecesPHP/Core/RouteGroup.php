<?php

/**
 * RouteGroup.php
 */
namespace PiecesPHP\Core;

use Exception;
use PiecesPHP\Core\Route;
use Slim\App;

/**
 * RouteGroup - Esquema de grupo de rutas
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class RouteGroup
{

    /**
     * @var App
     */
    protected static $router = null;
    /**
     * @var static[]
     */
    protected static $groups = [];
    /**
     * @var bool
     */
    protected static $isInit = false;
    /**
     * @var string
     */
    protected $routeGroup = '';
    /**
     * $routes
     *
     * @var Route[]
     */
    protected $routes = [];
    /**
     * @var App
     */
    protected $instanceRouter = null;
    /**
     * @var array<callable>|array<string>
     */
    protected $middlewares = [];
    /**
     * @var bool
     */
    protected $active = true;

    /**
     * @param string $routeGroup
     * @param App $router
     * @param bool $useClassRouter
     */
    public function __construct(string $routeGroup, App $router = null, bool $useClassRouter = true)
    {
        $routerDefined = false;

        $this->routeGroup = $routeGroup;
        $this->instanceRouter = $router;

        if ($useClassRouter) {

            if (self::$router instanceof App) {
                $this->instanceRouter = self::$router;
                $routerDefined = true;
            }

        } else {

            if ($router instanceof App) {
                $this->instanceRouter = $router;
                $routerDefined = true;
            }

        }

        if (!$routerDefined) {
            throw new Exception("No hay ningún enrutador definido para el grupo {$this->routeGroup}.");
        }

    }

    /**
     * @param Route[] $routes
     * @param App $router
     * @return static
     */
    public function register($routes)
    {

        $groupSegmentURL = rtrim($this->routeGroup, '/');

        foreach ($routes as $route) {

            if ($route instanceof Route) {

                $segment = $route->routeSegment();

                if ($segment === '[/]') {

                    $segment = "{$groupSegmentURL}{$segment}";

                } elseif (mb_strlen($segment) > 0) {

                    if ($segment[0] == '/') {
                        $segment = mb_substr($segment, 1, mb_strlen($segment) - 1);
                    }

                    $segment = "{$groupSegmentURL}/{$segment}";

                } else {
                    $segment = "{$groupSegmentURL}[/]";
                }

                $route->routeSegment($segment);
                $this->routes[] = $route;
            }

        }

        if (!array_key_exists($this->routeGroup, self::$groups)) {
            self::$groups[$this->routeGroup] = $this;
        }

        return $this;
    }

    /**
     * @param callable|string $callable
     * @return static
     */
    public function addMiddleware($callable)
    {
        if ($this->active) {
            if (is_callable($callable) || is_string($callable)) {
                $this->middlewares[] = $callable;
            }
        }
        return $this;
    }

    /**
     * Alias de addMiddleware
     * @param callable|string $callable
     * @return static
     */
    public function withMiddleware($callable)
    {
        return $this->addMiddleware($callable);
    }

    /**
     * @return string
     */
    public function getGroupSegment()
    {
        return $this->routeGroup;
    }

    /**
     * @param bool $active
     * @return static
     */
    public function active(bool $active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return void
     */
    public static function initRoutes()
    {

        if (!self::$isInit) {

            foreach (self::$groups as $group) {

                $router = $group->instanceRouter;

                if ($group->active) {

                    $routes = $group->routes;

                    foreach ($routes as $route) {

                        foreach ($group->middlewares as $mw) {
                            $route->addMiddleware($mw);
                        }

                        $route->register($router);

                    }

                }

            }

            self::$isInit = true;

        }

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
