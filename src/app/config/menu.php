<?php

/**
 * menu.php
 */

/**
 * Menús.
 * En este este archivo se pueden definir elementos útiles para generar menús
 */

use PiecesPHP\Core\Config;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Menu\MenuItem;
use PiecesPHP\Core\Menu\MenuItemCollection;
use PiecesPHP\Core\Roles;
use PiecesPHP\UserSystem\UserDataPackage;

$role = Roles::getCurrentRole();
$current_type_user = !is_null($role) ? $role['code'] : null;
$user = getLoggedFrameworkUser();

$headerDropdown = new MenuItemCollection([
    'items' => [
    ],
]);

$sidebar = new MenuGroupCollection([
    'items' => [
        new MenuGroup([
            'name' => __(ADMIN_MENU_LANG_GROUP, 'Inicio'),
            'visible' => Roles::hasPermissions('admin', $current_type_user),
            'asLink' => true,
            'href' => get_route('admin'),
            'icon' => 'address card',
        ]),
        new MenuGroup([
            'name' => __('bi-dynamic-images', 'Imágenes'),
            'visible' => \PiecesPHP\BuiltIn\DynamicImages\EntryPointController::allowedRoute('options'),
            'asLink' => true,
            'href' => \PiecesPHP\BuiltIn\DynamicImages\EntryPointController::routeName('options', [], true),
            'icon' => 'images',
        ]),
        new MenuGroup([
            'name' => __(ADMIN_MENU_LANG_GROUP, 'Ubicaciones'),
            'visible' => Roles::hasPermissions('locations', $current_type_user),
            'asLink' => true,
            'href' => get_route('locations', [], true),
            'icon' => 'map marker alternate',
        ]),
        new MenuGroup([
            'name' => __(ADMIN_MENU_LANG_GROUP, 'Mensajes'),
            'attributes' => [
                'unread-threads' => get_route('messages-threads-status', [], true),
            ],
            'visible' => Roles::hasPermissions('messages-inbox', $current_type_user),
            'asLink' => true,
            'href' => get_route('messages-inbox', [], true),
        ]),
    ],
]);

//Idiomas
$alternativesURL = Config::get_config('alternatives_url');
$hasManyLangs = !empty($alternativesURL);

if ($hasManyLangs) {
    $langsItem = new MenuGroup([
        'name' => __(ADMIN_MENU_LANG_GROUP, 'Idiomas'),
        'position' => 300,
        'icon' => 'language',
    ]);

    foreach ($alternativesURL as $lang => $url) {

        $langItem = new MenuItem([
            'text' => __('lang', $lang),
            'visible' => true,
            'href' => $url,
        ]);

        $langsItem->addItem($langItem);

    }

    $sidebar->addItem($langsItem);
}

//Añadir menús a la configuración global
$config['menus']['sidebar'] = $sidebar;
