<?php

/**
 * Router.php
 */
namespace PiecesPHP\Core\Routing;

use Slim\App as RouterDependency;

/**
 * Router
 *
 * @package     PiecesPHP\Core\Routing
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2023
 */
class Router extends RouterDependency
{

    protected static ?DependenciesInjector $defaultContainer = null;

    /**
     * Obtiene el contenedor de dependencias
     *
     * @return DependenciesInjector
     */
    public function getContainer(): DependenciesInjector
    {
        return parent::getContainer() ?? self::defaultContainer();
    }

    /**
     * Obtiene el contenedor de dependencias
     *
     * @return DependenciesInjector
     */
    public function getDI()
    {
        return $this->getContainer() ?? self::defaultContainer();
    }

    /**
     * @return Router
     */
    public static function createRouter(?DependenciesInjector $container = null)
    {
        return new Router(new ResponseRouteFactory(), $container ?? self::defaultContainer());
    }

    /**
     * Obtiene el contenedor de dependencias por defecto
     * @return DependenciesInjector
     */
    protected static function defaultContainer()
    {
        if (self::$defaultContainer instanceof DependenciesInjector) {
            return self::$defaultContainer;
        }

        $defaultContainer = get_config('slim_container');
        $container = $container ?? $defaultContainer;

        if (!($container instanceof DependenciesInjector)) {
            /** @var array $container_configurations */
            require_once basepath("app/config/containers.php");
            $container = new DependenciesInjector($container_configurations ?? []);
        }

        if (!($defaultContainer instanceof DependenciesInjector)) {
            set_config('slim_container', $container);
        }

        self::$defaultContainer = $container;
        return self::$defaultContainer;
    }

}
