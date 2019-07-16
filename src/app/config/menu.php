<?php

/**
 * menu.php
 */

/**
 * Menús.
 * En este este archivo se pueden definir elementos útiles para generar menús
 */

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
            'name' => 'Inicio',
            'icon' => 'home',
            'visible' => Roles::hasPermissions('admin', $current_type_user),
            'asLink' => true,
            'href' => get_route('admin'),
        ]),
        new MenuGroup([
            'name' => 'Artículos',
            'icon' => 'newspaper',
            'visible' => Roles::hasPermissions('piecesphp-built-in-articles-list', $current_type_user),
            'asLink' => true,
            'href' => get_route('piecesphp-built-in-articles-list', [], true),
        ]),
        new MenuGroup([
            'name' => 'Categorías',
            'icon' => 'tags',
            'visible' => Roles::hasPermissions('piecesphp-built-in-articles-categories-list', $current_type_user),
            'asLink' => true,
            'href' => get_route('piecesphp-built-in-articles-categories-list', [], true),
        ]),
        new MenuGroup([
            'name' => 'Ubicaciones',
            'icon' => 'marker',
            'visible' => Roles::hasPermissions('locations', $current_type_user),
            'asLink' => true,
            'href' => get_route('locations', [], true),
        ]),
        new MenuGroup([
            'name' => 'Noticias',
            'icon' => 'newspaper',
            'visible' => Roles::hasPermissions('blackboard-news-list', $current_type_user),
            'asLink' => true,
            'href' => get_route('blackboard-news-list', [], true),
        ]),
        new MenuGroup([
            'name' => 'Gestionar usuarios',
            'icon' => 'user',
            'visible' => Roles::hasPermissions('listado-usuarios', $current_type_user),
            'asLink' => true,
            'href' => get_route('listado-usuarios'),
        ]),
        new MenuGroup([
            'name' => 'Informes de ingreso',
            'icon' => 'file',
            'visible' => Roles::hasPermissions('informes-acceso', $current_type_user),
            'asLink' => true,
            'href' => get_route('informes-acceso'),
        ]),
        new MenuGroup([
            'name' => 'Importar usuarios',
            'icon' => 'upload',
            'visible' => Roles::hasPermissions('importer-form', $current_type_user),
            'asLink' => true,
            'href' => get_route('importer-form', ['type' => 'users'], true),
        ]),
        new MenuGroup([
            'name' => __('general', 'messages'),
            'attributes' => [
                'unread-threads' => get_route('messages-threads-status', [], true),
            ],
            'icon' => 'envelope',
            'visible' => Roles::hasPermissions('messages-inbox', $current_type_user),
            'asLink' => true,
            'href' => get_route('messages-inbox', [], true),
        ]),
        new MenuGroup([
            'name' => 'Opciones de usuario',
            'icon' => 'user cog',
            'visible' => true,
            'asLink' => true,
            'href' => get_route('profile'),
        ]),
        new MenuGroup([
            'name' => 'Personalización',
            'icon' => 'pencil alternate',
            'visible' => Roles::hasPermissions('configurations-customization', $current_type_user),
            'asLink' => true,
            'href' => get_route('configurations-customization', [], true),
        ]),
        new MenuGroup([
            'name' => 'Configuraciones',
            'icon' => 'cogs',
            'visible' => Roles::hasPermissions('configurations-generals', $current_type_user),
            'asLink' => true,
            'href' => get_route('configurations-generals', [], true),
        ]),
        new MenuGroup([
            'name' => 'Log de errores',
            'icon' => 'file alternate outline',
            'visible' => Roles::hasPermissions('admin-error-log', $current_type_user),
            'asLink' => true,
            'href' => get_route('admin-error-log'),
        ]),
        new MenuGroup([
            'name' => 'Soporte técnico',
            'icon' => 'question',
            'visible' => Roles::hasPermissions('tickets-create', $current_type_user),
            'asLink' => true,
            'attributes' => [
                'support-button-js' => '',
            ],
        ]),
        new MenuGroup([
            'name' => 'Rutas y permisos',
            'icon' => 'shield alternate',
            'visible' => Roles::hasPermissions('configurations-routes', $current_type_user),
            'asLink' => true,
            'href' => get_route('configurations-routes', [], true),
        ]),
        new MenuGroup([
            'name' => __('general', 'logout'),
            'icon' => 'share',
            'visible' => true,
            'asLink' => false,
			'attributes' => [
				'pcsphp-users-logout' => '',
			],
        ]),
    ],
]);
