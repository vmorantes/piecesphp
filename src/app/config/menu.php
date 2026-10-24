<?php

/**
 * @pcsphp-config ambos
 * Qué conviene editar aquí: tus grupos del menú lateral, en la sección del clon, con `$sidebar->addItem(...)`. Los del framework, no.
 */

/**
 * menu.php
 */

/**
 * Menús.
 * En este este archivo se pueden definir elementos útiles para generar menús
 */

use PiecesPHP\UserSystem\ORM\UsersModel;
use Directory\Controllers\DirectoryController;
use Organizations\Controllers\MyOrganizationProfileController;
use MySpace\Controllers\MyProfileController;
use MySpace\MySpaceRoutes;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Geo\Controllers\GeoController;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Menu\MenuItem;
use PiecesPHP\Core\Menu\MenuItemCollection;
use PiecesPHP\Core\Roles;
use PiecesPHP\UserSystem\UserDataPackage;

$role = Roles::getCurrentRole();
$current_type_user = !is_null($role) ? $role['code'] : null;

//── Del framework: no lo edites ──

$headerDropdown = new MenuItemCollection([
    'items' => [
    ],
]);

/**
 * @category AddToBackendSidebarMenu
 */
$externalUsers = [
    UsersModel::TYPE_USER_GENERAL,
    UsersModel::TYPE_USER_ADMIN_ORG,
];

$sidebar = new MenuGroupCollection([]);

if (!in_array($current_type_user, $externalUsers)) {
    $sidebar = new MenuGroupCollection([
        'items' => [
            new MenuGroup([
                'name' => __(ADMIN_MENU_LANG_GROUP, 'Inicio'),
                'visible' => Roles::hasPermissions('admin', $current_type_user),
                'asLink' => true,
                'href' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName(''),
                'icon' => 'home',
                'position' => MenuGroupCollection::BAND_HOME,
            ]),
            new MenuGroup([
                'name' => __(ADMIN_MENU_LANG_GROUP, 'Mi espacio'),
                'visible' => MySpaceRoutes::ENABLE,
                'icon' => 'user circle',
                'position' => MenuGroupCollection::BAND_MY_SPACE,
                'items' => [
                    new MenuItem([
                        'text' => __(ADMIN_MENU_LANG_GROUP, 'Mi perfil'),
                        'href' => MyProfileController::routeName('my-profile', [], true),
                        'visible' => MyProfileController::allowedRoute('my-profile'),
                    ]),
                    new MenuItem([
                        'text' => __(ADMIN_MENU_LANG_GROUP, 'Mi organización'),
                        'href' => MyOrganizationProfileController::routeName('my-organization-profile', [], true),
                        'visible' => MyOrganizationProfileController::allowedRoute('my-organization-profile') && in_array($current_type_user, OrganizationMapper::PROFILE_EDITOR),
                    ]),
                ],
            ]),
            new MenuGroup([
                'name' => __(ADMIN_MENU_LANG_GROUP, 'Directorio'),
                'icon' => 'address book',
                'position' => MenuGroupCollection::BAND_PEOPLE + 20,
                'asLink' => true,
                'href' => DirectoryController::routeName('list', [], true),
                'visible' => DirectoryController::allowedRoute('list'),
            ]),
            new MenuGroup([
                'name' => __(ADMIN_MENU_LANG_GROUP, 'Mapa de contenidos'),
                'icon' => 'map outline',
                'position' => MenuGroupCollection::BAND_PEOPLE + 21,
                'asLink' => true,
                'href' => GeoController::routeName('viewer', [], true),
                'visible' => GeoController::allowedRoute('viewer'),
            ]),
            new MenuGroup([
                'name' => __(ADMIN_MENU_LANG_GROUP, 'Ubicaciones'),
                'visible' => Roles::hasPermissions('locations', $current_type_user),
                'asLink' => true,
                'href' => \PiecesPHP\App\Locations\Controllers\Locations::routeName('', [], true),
                'icon' => 'map marker alternate',
                'position' => MenuGroupCollection::BAND_DATA,
            ]),
        ],
    ]);
} else {

    $sidebar = new MenuGroupCollection([
        'items' => [
            new MenuGroup([
                'name' => __(ADMIN_MENU_LANG_GROUP, 'Inicio'),
                'visible' => Roles::hasPermissions('admin', $current_type_user),
                'asLink' => true,
                'href' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName(''),
                'icon' => 'home',
                'position' => MenuGroupCollection::BAND_HOME,
            ]),
            new MenuGroup([
                'name' => __(ADMIN_MENU_LANG_GROUP, 'Directorio'),
                'icon' => 'address book',
                'position' => MenuGroupCollection::BAND_PEOPLE + 30,
                'asLink' => true,
                'href' => DirectoryController::routeName('list', [], true),
                'visible' => DirectoryController::allowedRoute('list'),
            ]),
            new MenuGroup([
                'name' => __(ADMIN_MENU_LANG_GROUP, 'Mapa de actores y contenidos'),
                'icon' => 'map outline',
                'position' => MenuGroupCollection::BAND_PEOPLE + 40,
                'asLink' => true,
                'href' => GeoController::routeName('viewer', [], true),
                'visible' => GeoController::allowedRoute('viewer'),
            ]),
        ],
    ]);
}

//Idiomas
$alternativesURL = Config::get_config('alternatives_url');
$hasManyLangs = !empty($alternativesURL);

//NOTE: Este es opcional, se oculta por defecto en favor del modal de cambio de idioma
if ($hasManyLangs && false) {
    $langsItem = new MenuGroup([
        'name' => __(ADMIN_MENU_LANG_GROUP, 'Idiomas'),
        'position' => MenuGroupCollection::BAND_LANGUAGES,
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

//── Del clon ──
//Tus grupos: $sidebar->addItem(new MenuGroup([...]));

//── Del framework: no lo edites ──
//Añadir menús a la configuración global
set_config('menus', [
    'sidebar' => $sidebar,
]);
