<?php

/**
 * RouterPiecesPHP.php
 */
namespace PiecesPHP\Core\Routing;

use Slim\App as RouterDependency;

/**
 * RouterPiecesPHP
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2023
 */
class RouterPiecesPHP extends RouterDependency
{

    /**
     * @return RouterPiecesPHP
     */
    public static function createRouter(?DependenciesInjectorPiecesPHP $container = null)
    {
        return new RouterPiecesPHP(new ResponseRouteFactoryPiecesPHP(), $container);
    }
}
