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
use PiecesPHP\Core\Menu\MenuItemCollection;
use PiecesPHP\Core\Roles;

$role = Roles::getCurrentRole();
$current_type_user = !is_null($role) ? $role['code'] : null;

$config['menus']['header_dropdown'] = new MenuItemCollection([
    'items' => [
    ],
]);

$config['menus']['sidebar'] = new MenuGroupCollection([
    'items' => [
        new MenuGroup([
            'name' => __('sidebarAdminZone', 'Inicio'),
            'icon' => 'home',
            'visible' => Roles::hasPermissions('admin', $current_type_user),
            'asLink' => true,
            'href' => get_route('admin'),
        ]),
        new MenuGroup([
            'name' => __('bi-shop', 'Tienda'),
            'icon' => 'store',
            'visible' => Roles::hasPermissions('built-in-shop-private-entry-options', $current_type_user),
            'asLink' => true,
            'href' => get_route('built-in-shop-private-entry-options', [], true),
        ]),
        new MenuGroup([
            'name' => __('bi-dynamic-images', 'Imágenes'),
            'icon' => 'images',
            'visible' => \PiecesPHP\BuiltIn\DynamicImages\EntryPointController::allowedRoute('options'),
            'asLink' => true,
            'href' => \PiecesPHP\BuiltIn\DynamicImages\EntryPointController::routeName('options', [], true),
        ]),
        new MenuGroup([
            'name' => __('sidebarAdminZone', 'Artículos'),
            'icon' => 'newspaper',
            'visible' => Roles::hasPermissions('built-in-articles-list', $current_type_user),
            'asLink' => true,
            'href' => get_route('built-in-articles-list', [], true),
        ]),
        new MenuGroup([
            'name' => __('sidebarAdminZone', 'Categorías'),
            'icon' => 'tags',
            'visible' => Roles::hasPermissions('built-in-articles-categories-list', $current_type_user),
            'asLink' => true,
            'href' => get_route('built-in-articles-categories-list', [], true),
        ]),
        new MenuGroup([
            'name' => __('sidebarAdminZone', 'Ubicaciones'),
            'icon' => 'marker',
            'visible' => Roles::hasPermissions('locations', $current_type_user),
            'asLink' => true,
            'href' => get_route('locations', [], true),
        ]),
        new MenuGroup([
            'name' => __('sidebarAdminZone', 'Noticias'),
            'icon' => 'newspaper',
            'visible' => Roles::hasPermissions('blackboard-news-list', $current_type_user),
            'asLink' => true,
            'href' => get_route('blackboard-news-list', [], true),
        ]),
        new MenuGroup([
            'name' => __('sidebarAdminZone', 'Gestionar usuarios'),
            'icon' => 'user',
            'visible' => Roles::hasPermissions('users-list', $current_type_user),
            'asLink' => true,
            'href' => get_route('users-list'),
        ]),
        new MenuGroup([
            'name' => __('sidebarAdminZone', 'Informes de ingreso'),
            'icon' => 'file',
            'visible' => Roles::hasPermissions('informes-acceso', $current_type_user),
            'asLink' => true,
            'href' => get_route('informes-acceso'),
        ]),
        new MenuGroup([
            'name' => __('sidebarAdminZone', 'Importar usuarios'),
            'icon' => 'upload',
            'visible' => Roles::hasPermissions('importer-form', $current_type_user),
            'asLink' => true,
            'href' => get_route('importer-form', ['type' => 'users'], true),
        ]),
        new MenuGroup([
            'name' => __('sidebarAdminZone', 'Mensajes'),
            'attributes' => [
                'unread-threads' => get_route('messages-threads-status', [], true),
            ],
            'icon' => 'envelope',
            'visible' => Roles::hasPermissions('messages-inbox', $current_type_user),
            'asLink' => true,
            'href' => get_route('messages-inbox', [], true),
        ]),
        new MenuGroup([
            'name' => __('sidebarAdminZone', 'Opciones de usuario'),
            'icon' => 'user cog',
            'visible' => true,
            'asLink' => true,
            'href' => get_route('users-form-profile'),
        ]),
        new MenuGroup([
            'name' => __('sidebarAdminZone', 'Personalización'),
            'icon' => 'pencil alternate',
            'visible' => Roles::hasPermissions('configurations-customization', $current_type_user),
            'asLink' => true,
            'href' => get_route('configurations-customization', [], true),
        ]),
        new MenuGroup([
            'name' => __('sidebarAdminZone', 'Configuraciones'),
            'icon' => 'cogs',
            'visible' => Roles::hasPermissions('configurations-generals', $current_type_user),
            'asLink' => true,
            'href' => get_route('configurations-generals', [], true),
        ]),
        new MenuGroup([
            'name' => __('sidebarAdminZone', 'Log de errores'),
            'icon' => 'file alternate outline',
            'visible' => Roles::hasPermissions('admin-error-log', $current_type_user),
            'asLink' => true,
            'href' => get_route('admin-error-log'),
        ]),
        new MenuGroup([
            'name' => __('sidebarAdminZone', 'Soporte técnico'),
            'icon' => 'question',
            'visible' => Roles::hasPermissions('tickets-create', $current_type_user),
            'asLink' => true,
            'attributes' => [
                'support-button-js' => '',
            ],
        ]),
        new MenuGroup([
            'name' => __('sidebarAdminZone', 'Rutas y permisos'),
            'icon' => 'shield alternate',
            'visible' => Roles::hasPermissions('configurations-routes', $current_type_user),
            'asLink' => true,
            'href' => get_route('configurations-routes', [], true),
        ]),
        new MenuGroup([
            'name' => __('sidebarAdminZone', 'Cerrar sesión'),
            'icon' => 'share',
            'visible' => true,
            'asLink' => false,
            'attributes' => [
                'pcsphp-users-logout' => '',
            ],
        ]),
    ],
]);

$alternativesURL = Config::get_config('alternatives_url');

foreach ($alternativesURL as $lang => $url) {

    $langItem = new MenuGroup([
        'name' => __('lang', $lang),
        'icon' => 'language',
        'visible' => true,
        'asLink' => true,
        'href' => $url,
    ]);

    $config['menus']['sidebar']->addItem($langItem);

}
