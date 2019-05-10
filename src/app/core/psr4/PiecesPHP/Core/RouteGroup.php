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
 * @version     v.1
 * @copyright   Copyright (c) 2018
 */
class RouteGroup
{

    /**
     * $router
     *
     * @var \Slim\App
     */
    public static $router = null;
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
     * @return static
     */
    public function __construct(string $routeGroup)
    {
        $this->routeGroup = $routeGroup;
    }

    /**
     * register
     *
     * @param Route[] $routes
     * @param \Slim\App $router
     * @return static
     */
    public function register($routes, \Slim\App $router = null)
    {

        if ($router === null) {
            $this->instanceRouter = static::$router;
        } elseif ($this->instanceRouter === null) {
            $this->instanceRouter = $router;
        }

        $router = $this->instanceRouter;
        $routes = !is_array($routes) ? [$routes] : $routes;

        if ($this->active) {

            $this->lastRouteGroupInstance = $router->group($this->routeGroup, function () use ($routes) {
                foreach ($routes as $route) {
                    if ($route instanceof Route) {
                        $route->register($this);
                        $routes[] = $route;
                    }
                }
            });

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
        $this->lastRouteGroupInstance->add($callable);
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
     * setInstanceRouter
     *
     * @param \Slim\App $router
     * @return static
     */
    public function setInstanceRouter(\Slim\App $router)
    {
        $this->instanceRouter = $router;
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
