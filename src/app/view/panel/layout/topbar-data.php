<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
//Los datos de la barra superior, para topbar.php (lo flotante) y topbar-bar.php (la barra): se incluye, no pinta.
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Roles;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\UserSystem\Controllers\ImpersonationController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;
use Spatie\Url\Url as URLManager;

$currentUser = getLoggedFrameworkUserOrFail();
$currentUserID = $currentUser->id;
$currentUserType = $currentUser->type;
$isRoot = $currentUserType == UsersModel::TYPE_USER_ROOT;
$usersCanDeleteAccount = [
    UsersModel::TYPE_USER_ADMIN_ORG,
    UsersModel::TYPE_USER_GENERAL,
];
$asUserLoggedID = get_config(ROOT_ID_AS_CONNECT_CONFIG_NAME);
$rootOriginalID = get_config(ROOT_ORIGINAL_ID_CONFIG_NAME);
$isLoggedOtherUser = $asUserLoggedID !== $rootOriginalID;
//Las dos rutas de la suplantación (P204 a): la cookie la escribe y la borra el servidor.
$impersonationStartURL = ImpersonationController::routeName('start');
$impersonationEndURL = ImpersonationController::routeName('end');
$currentLang = Config::get_lang();
$allowedLangs = Config::get_allowed_langs();
$manyLangs = count($allowedLangs) > 1;
$alternativesURL = Config::get_config('alternatives_url');
$searchUsersURL = URLManager::fromString(\PiecesPHP\UserSystem\Controllers\UsersController::routeName('search-dropdown'));
$searchUsersURL = $searchUsersURL->withQueryParameter('search', '{query}');
$searchUsersURL = $searchUsersURL->withQueryParameter('typeResult', 'RESULT_FULLNAME_USERNAME');
$searchUsersURL = urldecode($searchUsersURL->__toString());

//El menú de usuario, en el orden del prototipo (P256): tras la cuenta, las preferencias y la suplantación; al final,
//«Acerca de» y «Cerrar sesión». La Hoja de diseño está en Configuración › Sistema (SettingsController::panelGroups()).
$userOptions = new MenuGroupCollection([
    'items' => [
        new MenuGroup([
            'name' => __(AdminPanelController::ADMIN_LANG_GROUP, 'Idiomas'),
            'icon' => 'globe',
            'href' => baseurl() . '?changeLang',
            'visible' => $manyLangs,
            'asLink' => true,
            'attributes' => [
                'change-system-lang-trigger' => '',
            ],
        ]),
        new MenuGroup([
            'name' => __(ADMIN_MENU_LANG_GROUP, 'Conectar como otro usuario'),
            'icon' => 'users',
            'href' => '#',
            'attributes' => [
                'connect-as-another-user-trigger' => '',
            ],
            'visible' => $isRoot,
            'asLink' => true,
        ]),
        new MenuGroup([
            'name' => __(ADMIN_MENU_LANG_GROUP, 'Volver a mi usuario'),
            'icon' => 'exchange alternate',
            'href' => '#',
            'attributes' => [
                'exit-as-logged-options' => '',
                'data-url' => $impersonationEndURL,
            ],
            'visible' => $isLoggedOtherUser,
            'asLink' => true,
        ]),
    ],
]);
$userOptionsEnd = new MenuGroupCollection([
    'items' => [
        new MenuGroup([
            'name' => __(AdminPanelController::ADMIN_LANG_GROUP, 'Acerca de'),
            'icon' => 'desktop',
            'href' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName('about-framework'),
            'visible' => Roles::hasPermissions('admin-about-framework', $currentUserType),
            'asLink' => true,
        ]),
        new MenuGroup([
            'name' => __(ADMIN_MENU_LANG_GROUP, 'Cerrar sesión'),
            'icon' => 'power off',
            'href' => '#',
            'attributes' => [
                'pcsphp-users-logout' => '',
            ],
            'asLink' => true,
        ]),
    ],
]);

//Configuración, con los grupos del prototipo, sale de la misma fuente que el índice (ADR 0028; P255 a): también
//Gestión de usuarios y Registros. Solo lleva a pantallas: un menú no ejecuta nada.
$adminOptionsGroups = [];
foreach (SettingsController::panelGroups() as $configurationGroup) {
    $adminOptionsGroups[$configurationGroup['name']] = [
        'icon' => $configurationGroup['icon'],
        'visible' => $configurationGroup['visible'],
        'collection' => new MenuGroupCollection([
            'items' => array_map(fn(array $item) => new MenuGroup([
                'name' => $item['name'],
                'icon' => $item['icon'],
                'href' => $item['href'],
                'visible' => $item['visible'],
                'asLink' => true,
            ]), $configurationGroup['items']),
        ]),
    ];
}

//El centro de ayuda (botón de la barra): soporte, si hay destinatarios del formulario y el usuario puede escribir, y FAQ.
$canContactSupport = Roles::hasPermissions('admin-tickets-create', $currentUserType) && AdminPanelController::supportFormRecipients() !== [];
$helpFaqURL = trim((string) get_config('help_faq_url'));
$withHelpCenter = $canContactSupport || $helpFaqURL !== '';

$withAdminOptions = false;
$withNews = NEWS_MODULE;

foreach ($adminOptionsGroups as $title => $config) {
    if ($config['visible']) {
        $withAdminOptions = true;
        break;
    }
}

/**
 * @var UserDataPackage
 */
$currentUser = getLoggedFrameworkUserOrFail();
/**
 * @var bool
 */
$hasAvatar = $currentUser->hasAvatar;
/**
 * @var string
 */
$avatar = $currentUser->avatar;
//Los avisos flotantes salen del registro de avisos del sistema (PiecesPHP\SystemStatus).
$systemAlertNags = \PiecesPHP\SystemStatus\SystemAlertRegistry::nagsFor((int) $currentUserType);
