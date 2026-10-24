<?php

/**
 * FormsRoutes.php
 */

namespace Forms;

use Forms\Categories\CategoriesLang;
use Forms\Categories\CategoriesRoutes;
use Forms\Categories\Controllers\CategoriesController;
use Forms\DocumentTypes\DocumentTypesRoutes;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Menu\MenuItem;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;

/**
 * FormsRoutes.
 *
 * @package     Forms
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class FormsRoutes
{

    const ENABLE = FORMS_MODULE_ENABLE;

    /**
     * @var boolean
     */
    private static $init = false;

    /**
     * @param RouteGroup $groupAdministration
     * @return RouteGroup[] Con los índices groupAdministration
     */
    public static function routes(RouteGroup $groupAdministration)
    {
        if (self::ENABLE) {

            CategoriesRoutes::routes($groupAdministration);
            DocumentTypesRoutes::routes($groupAdministration);

            \PiecesPHP\Core\Routing\InvocationStrategy::appendBeforeCallMethod(function () {
                self::init();
            });

        }
        return [
            'groupAdministration' => $groupAdministration,
        ];
    }

    /**
     * @return void|null
     */
    public static function init()
    {

        if (!self::$init) {

            $currentUser = getLoggedFrameworkUser();

            if ($currentUser === null) {
                return null;
            }

            $currentUserType = (int) $currentUser->type;

            /**
             * @category AddToBackendSidebarMenu
             * @var MenuGroupCollection $sidebar
             */
            $sidebar = get_sidebar_menu();

            $sidebar->addItem(new MenuGroup([
                'name' => __(ADMIN_MENU_LANG_GROUP, 'Formularios'),
                'icon' => 'clipboard list',
                'items' => [
                    new MenuItem([
                        'text' => __(CategoriesLang::LANG_GROUP, 'Categorías'),
                        'href' => CategoriesController::routeName('list'),
                        'visible' => CategoriesController::allowedRoute('list'),
                    ]),
                ],
                'position' => MenuGroupCollection::BAND_CONTENTS + 30,
            ]));

        }

        self::$init = true;

    }

}
