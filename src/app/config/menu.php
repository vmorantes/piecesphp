<?php

/**
 * menu.php
 */

/**
 * Menús.
 * En este este archivo se pueden definir elementos útiles para generar menús
 */

use ApplicationCalls\Controllers\ApplicationCallsController;
use ApplicationCalls\Mappers\ApplicationCallsMapper;
use App\Model\UsersModel;
use ContentNavigationHub\ContentNavigationHubRoutes;
use ContentNavigationHub\Controllers\ContentNavigationHubController;
use InterestResearchAreas\Controllers\InterestResearchAreasController;
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
                'href' => get_route('admin'),
                'icon' => 'home',
                'position' => 0,
            ]),
            new MenuGroup([
                'name' => __(ADMIN_MENU_LANG_GROUP, 'Gestión'),
                'visible' => ContentNavigationHubRoutes::ENABLE,
                'icon' => 'edit',
                'position' => 0,
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
                    new MenuItem([
                        'text' => __(ADMIN_MENU_LANG_GROUP, 'Contenidos'),
                        'href' => ApplicationCallsController::routeName('list', [], true),
                        'visible' => ApplicationCallsController::allowedRoute('list'),
                    ]),
                    new MenuItem([
                        'text' => __(ADMIN_MENU_LANG_GROUP, 'Áreas de investigación'),
                        'href' => InterestResearchAreasController::routeName('list', [], true),
                        'visible' => InterestResearchAreasController::allowedRoute('list'),
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
                        'href' => ContentNavigationHubController::routeName('profiles-list', [], true),
                        'visible' => ContentNavigationHubController::allowedRoute('profiles-list'),
                    ]),
                    new MenuItem([
                        'text' => __(ADMIN_MENU_LANG_GROUP, 'Mapa de contenidos'),
                        'href' => ContentNavigationHubController::routeName('contents-map', [], true),
                        'visible' => ContentNavigationHubController::allowedRoute('contents-map'),
                    ]),
                    new MenuItem([
                        'text' => __(ADMIN_MENU_LANG_GROUP, 'Contenidos'),
                        'href' => ContentNavigationHubController::routeName('application-calls-list', [], true),
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
} else {

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
                'name' => __(ADMIN_MENU_LANG_GROUP, 'Usuarios generales'),
                'icon' => 'edit',
                'position' => 0,
                'asLink' => true,
                'href' => ContentNavigationHubController::routeName('profiles-list', [], true),
                'visible' => ContentNavigationHubController::allowedRoute('profiles-list'),
            ]),
            new MenuGroup([
                'name' => __(ADMIN_MENU_LANG_GROUP, 'Proyectos'),
                'visible' => ContentNavigationHubRoutes::ENABLE,
                'icon' => 'search',
                'position' => 0,
                'items' => [
                    new MenuItem([
                        'text' => __(ADMIN_MENU_LANG_GROUP, 'Consultar proyectos'),
                        'href' => ContentNavigationHubController::routeName('application-calls-list-by-type', [
                            'type' => ApplicationCallsMapper::CONTENT_TYPE_BILATERAL_PROJECT,
                        ], true),
                        'visible' => ContentNavigationHubController::allowedRoute('application-calls-list-by-type', [
                            'type' => ApplicationCallsMapper::CONTENT_TYPE_BILATERAL_PROJECT,
                        ]),
                    ]),
                    new MenuItem([
                        'text' => __(ADMIN_MENU_LANG_GROUP, 'Agregar proyectos'),
                        'href' => ApplicationCallsController::routeName('forms-add', [], true) . '?p',
                        'visible' => ApplicationCallsController::allowedRoute('forms-add'),
                    ]),
                ],
            ]),
            new MenuGroup([
                'name' => __(ADMIN_MENU_LANG_GROUP, 'Oportunidades'),
                'visible' => ContentNavigationHubRoutes::ENABLE,
                'icon' => 'search',
                'position' => 0,
                'items' => [
                    new MenuItem([
                        'text' => __(ADMIN_MENU_LANG_GROUP, 'Consultar oportunidades'),
                        'href' => ContentNavigationHubController::routeName('application-calls-list-by-type', [
                            'type' => ApplicationCallsMapper::CONTENT_TYPE_FUNDING_OPPORTUNITY,
                        ], true),
                        'visible' => ContentNavigationHubController::allowedRoute('application-calls-list-by-type', [
                            'type' => ApplicationCallsMapper::CONTENT_TYPE_BILATERAL_PROJECT,
                        ]),
                    ]),
                    new MenuItem([
                        'text' => __(ADMIN_MENU_LANG_GROUP, 'Agregar oportunidades'),
                        'href' => ApplicationCallsController::routeName('forms-add', [], true) . '?o',
                        'visible' => ApplicationCallsController::allowedRoute('forms-add'),
                    ]),
                ],
            ]),
            new MenuGroup([
                'name' => __(ADMIN_MENU_LANG_GROUP, 'Mapa de actores y contenidos'),
                'icon' => 'map outline',
                'position' => 0,
                'asLink' => true,
                'href' => ContentNavigationHubController::routeName('contents-map', [], true),
                'visible' => ContentNavigationHubController::allowedRoute('contents-map'),
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
