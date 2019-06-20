<?php

/**
 * RouteGroup.php
 */
namespace PiecesPHP\Core;

use PiecesPHP\Core\Route;

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
     * $router
     *
     * @var \Slim\App
     */
    protected static $router = null;
    /**
     * $routeGroup
     *
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
     * $instanceRouter
     *
     * @var \Slim\App
     */
    protected $instanceRouter = null;
    /**
     * $lastRouteGroupInstance
     *
     * @var \Slim\Interfaces\RouteGroupInterface
     */
    protected $lastRouteGroupInstance = null;
    /**
     * $generalMiddleware
     *
     * @var callable|string
     */
    protected $generalMiddleware = null;
    /**
     * $active
     *
     * @var bool
     */
    protected $active = true;

    /**
     * __construct
     *
     * @param string $routeGroup
     * @param \Slim\App $router
     * @param bool $useClassRouter
     * @return static
     */
    public function __construct(string $routeGroup, \Slim\App $router = null, bool $useClassRouter = true)
    {
        $routerDefined = false;

        $this->routeGroup = $routeGroup;
        $this->instanceRouter = $router;

        if ($useClassRouter) {

            if (self::$router instanceof \Slim\App) {
                $this->instanceRouter = self::$router;
                $routerDefined = true;
            }

        } else {

            if ($router instanceof \Slim\App) {
                $this->instanceRouter = $router;
                $routerDefined = true;
            }

        }

        if (!$routerDefined) {
            throw new Exception("No hay ningún enrutador definido para el grupo {$this->routeGroup}.");
        }

    }

    /**
     * register
     *
     * @param Route[] $routes
     * @param \Slim\App $router
     * @return static
     */
    public function register($routes)
    {

        $router = $this->instanceRouter;

        $routes = !is_array($routes) ? [$routes] : $routes;

        if ($this->active) {

            $set_routes_callable = function () use ($routes) {
                foreach ($routes as $route) {
                    if ($route instanceof Route) {
                        $route->register($this);
                        $routes[] = $route;
                    }
                }
            };

            $this->lastRouteGroupInstance = $router->group($this->routeGroup, $set_routes_callable);

            if ($this->generalMiddleware !== null) {
                $this->lastRouteGroupInstance->add($this->generalMiddleware);
            }

        }

        return $this;
    }

    /**
     * addMiddleware
     *
     * @param callable|string $callable
     * @return static
     */
    public function addMiddleware($callable)
    {
        if ($this->active) {
            if ($this->lastRouteGroupInstance !== null) {
                $this->lastRouteGroupInstance->add($callable);
            }
        }
        return $this;
    }

    /**
     * withMiddleware
     *
     * @param callable|string $callable
     * @return static
     */
    public function withMiddleware($callable)
    {
        if (is_callable($callable) || is_string($callable)) {
            $this->generalMiddleware = $callable;
        }
        return $this;
    }

    /**
     * active
     *
     * @param bool $active
     * @return static
     */
    public function active(bool $active)
    {
        $this->active = $active;
        return $this;
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

    /**
     * getGroupSegment
     *
     * @return string
     */
    public function getGroupSegment()
    {
        return $this->routeGroup;
    }
}
