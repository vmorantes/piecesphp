<?php

/**
 * ShopRoutes.php
 */

namespace PiecesPHP\BuiltIn\Shop;

use PiecesPHP\BuiltIn\Shop\Brand\Controllers\BrandController;
use PiecesPHP\BuiltIn\Shop\Category\Controllers\CategoryController;
use PiecesPHP\BuiltIn\Shop\Product\Controllers\ProductController;
use PiecesPHP\BuiltIn\Shop\SubCategory\Controllers\SubCategoryController;
use PiecesPHP\Core\RouteGroup;

/**
 * ShopRoutes.
 *
 * @package     PiecesPHP\BuiltIn\Shop
 * @author      Tejido Digital S.A.S.
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @link        http://www.tejidodigital.com/
 * @copyright   Copyright (c) 2019
 */
class ShopRoutes
{
    /**
     * routes
     *
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        if (PIECES_PHP_SHOP_ENABLE) {

            $group = EntryPointController::routes($group);

            $group = CategoryController::routes($group);

            $group = SubCategoryController::routes($group);

            $group = BrandController::routes($group);

			$group = ProductController::routes($group);
			
        }

        return $group;
    }
}
