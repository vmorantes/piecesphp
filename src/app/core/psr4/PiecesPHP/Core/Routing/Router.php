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

    /**
     * @return Router
     */
    public static function createRouter(?DependenciesInjector $container = null)
    {
        return new Router(new ResponseRouteFactory(), $container);
    }
}
