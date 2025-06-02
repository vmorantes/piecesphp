<?php

/**
 * menu.php
 */

/**
 * Menús.
 * En este este archivo se pueden definir elementos útiles para generar menús
 */

use ApplicationCalls\Controllers\ApplicationCallsController;
use ContentNavigationHub\Controllers\ContentNavigationHubController;
use MySpace\Controllers\MyOrganizationProfileController;
use MySpace\Controllers\MyProfileController;
use Organizations\Mappers\OrganizationMapper;
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

/**
 * @category AddToBackendSidebarMenu
 */
$sidebar = new MenuGroupCollection([
    'items' => [
        new MenuGroup([
            'name' => __(ADMIN_MENU_LANG_GROUP, 'Inicio'),
            'visible' => Roles::hasPermissions('admin', $current_type_user),
            'asLink' => true,
            'href' => get_route('admin'),
            'icon' => 'home',
            'position' => 0,
        ]),
        new MenuGroup([
            'name' => __(ADMIN_MENU_LANG_GROUP, 'Gestión'),
            'visible' => true,
            'icon' => 'edit',
            'position' => 0,
            'items' => [
                new MenuItem([
                    'text' => __(ADMIN_MENU_LANG_GROUP, 'Mi perfil'),
                    'href' => MyProfileController::routeName('my-profile'),
                    'visible' => MyProfileController::allowedRoute('my-profile'),
                ]),
                new MenuItem([
                    'text' => __(ADMIN_MENU_LANG_GROUP, 'Mi organización'),
                    'href' => MyOrganizationProfileController::routeName('my-organization-profile'),
                    'visible' => MyOrganizationProfileController::allowedRoute('my-organization-profile') && in_array($current_type_user, OrganizationMapper::PROFILE_EDITOR),
                ]),
                new MenuItem([
                    'text' => __(ADMIN_MENU_LANG_GROUP, 'Convocatorias'),
                    'href' => ApplicationCallsController::routeName('list'),
                    'visible' => ApplicationCallsController::allowedRoute('list'),
                ]),
            ],
        ]),
        new MenuGroup([
            'name' => __(ADMIN_MENU_LANG_GROUP, 'Consultar'),
            'visible' => true,
            'icon' => 'search',
            'position' => 0,
            'items' => [
                new MenuItem([
                    'text' => __(ADMIN_MENU_LANG_GROUP, 'Listado de actores'),
                    'href' => ContentNavigationHubController::routeName('profiles-list'),
                    'visible' => ContentNavigationHubController::allowedRoute('profiles-list'),
                ]),
                new MenuItem([
                    'text' => __(ADMIN_MENU_LANG_GROUP, 'Mapa de actores'),
                    'href' => ContentNavigationHubController::routeName('profiles-map'),
                    'visible' => ContentNavigationHubController::allowedRoute('profiles-map'),
                ]),
                new MenuItem([
                    'text' => __(ADMIN_MENU_LANG_GROUP, 'Convocatorias'),
                    'href' => ContentNavigationHubController::routeName('application-calls-list'),
                    'visible' => ContentNavigationHubController::allowedRoute('application-calls-list'),
                ]),
            ],
        ]),
        new MenuGroup([
            'name' => __(ADMIN_MENU_LANG_GROUP, 'Ubicaciones'),
            'visible' => Roles::hasPermissions('locations', $current_type_user),
            'asLink' => true,
            'href' => get_route('locations', [], true),
            'icon' => 'map marker alternate',
            'position' => 1800,
        ]),
        new MenuGroup([
            'name' => __(ADMIN_MENU_LANG_GROUP, 'Mensajes'),
            'attributes' => [
                'unread-threads' => get_route('messages-threads-status', [], true),
            ],
            'visible' => Roles::hasPermissions('messages-inbox', $current_type_user),
            'asLink' => true,
            'href' => get_route('messages-inbox', [], true),
            'icon' => 'envelope outline',
            'position' => 2000,
        ]),
    ],
]);

//Idiomas
$alternativesURL = Config::get_config('alternatives_url');
$hasManyLangs = !empty($alternativesURL);

//NOTE: Este es opcional, se oculta por defecto en favor del modal de cambio de idioma
if ($hasManyLangs && false) {
    $langsItem = new MenuGroup([
        'name' => __(ADMIN_MENU_LANG_GROUP, 'Idiomas'),
        'position' => 9999,
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
set_config('menus', [
    'sidebar' => $sidebar,
]);
