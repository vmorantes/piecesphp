<?php

/**
 * DynamicImagesRoutes.php
 */

namespace PiecesPHP\BuiltIn\DynamicImages;

use PiecesPHP\BuiltIn\DynamicImages\Informative\Controllers\HeroController;
use PiecesPHP\Core\RouteGroup;

/**
 * DynamicImagesRoutes.
 *
 * @package     PiecesPHP\BuiltIn\DynamicImages
 * @author      Tejido Digital S.A.S.
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @link        http://www.tejidodigital.com/
 * @copyright   Copyright (c) 2019
 */
class DynamicImagesRoutes
{
    /**
     * routes
     *
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        if (PIECES_PHP_DYNAMIC_IMAGES_ENABLE) {

            $group = EntryPointController::routes($group);

            $group = HeroController::routes($group);

        }

        return $group;
    }
}
