<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\AdminPanelController;
use App\Controller\AppConfigController;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Roles;
use PiecesPHP\UserSystem\UserDataPackage;

$role = Roles::getCurrentRole();
$currentUserType = !is_null($role) ? $role['code'] : null;

$userOptions = new MenuGroupCollection([
    'items' => [
        new MenuGroup([
            'name' => __(AdminPanelController::LANG_GROUP, 'Datos de perfil'),
            'icon' => 'id card outline',
            'href' => get_route('users-form-profile') . '?onlyProfile=yes',
            'asLink' => true,
        ]),
        new MenuGroup([
            'name' => __(AdminPanelController::LANG_GROUP, 'Imagen de perfil'),
            'icon' => 'user edit',
            'href' => get_route('users-form-profile') . '?onlyImage=yes',
            'asLink' => true,
        ]),
        new MenuGroup([
            'name' => __(AdminPanelController::LANG_GROUP, 'Acerca de'),
            'icon' => 'desktop',
            'href' => get_route('about-framework'),
            'visible' => Roles::hasPermissions('about-framework', $currentUserType),
            'asLink' => true,
        ]),
        new MenuGroup([
            'name' => __(ADMIN_MENU_LANG_GROUP, 'Soporte técnico'),
            'icon' => 'question',
            'href' => '#',
            'attributes' => [
                'support-button-js' => '',
            ],
            'visible' => Roles::hasPermissions('tickets-create', $currentUserType) && false,
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

$canViewCustomize = array_reduce([
    AppConfigController::allowedRoute('logos-favicons'),
    AppConfigController::allowedRoute('backgrounds'),
    AppConfigController::allowedRoute('generals'),
    AppConfigController::allowedRoute('seo'),
], function ($a, $b) {
    return $a || $b;
}, false);

$canViewConfiguration = array_reduce([
    AppConfigController::allowedRoute('generals-sitemap-create'),
    AppConfigController::allowedRoute('email'),
    AppConfigController::allowedRoute('os-ticket'),
    AppConfigController::allowedRoute('generals-cache-clean'),
    Roles::hasPermissions('configurations-routes', $currentUserType),
    AppConfigController::allowedRoute('crontab'),
    AppConfigController::allowedRoute('generals'),
], function ($a, $b) {
    return $a || $b;
}, false);

$canViewUsersManage = array_reduce([
    Roles::hasPermissions('users-selection-create', $currentUserType),
    Roles::hasPermissions('users-list', $currentUserType),
    Roles::hasPermissions('importer-form', $currentUserType),
], function ($a, $b) {
    return $a || $b;
}, false);

$canViewRecord = array_reduce([
    Roles::hasPermissions('admin-error-log', $currentUserType),
    Roles::hasPermissions('informes-acceso', $currentUserType),
], function ($a, $b) {
    return $a || $b;
}, false);
$adminOptionsGroups = [
    __(ADMIN_MENU_LANG_GROUP, 'Personalización de plataforma') => [
        'visible' => $canViewCustomize,
        'collection' => new MenuGroupCollection([
            'items' => [
                new MenuGroup([
                    'name' => __(AppConfigController::LANG_GROUP, 'Imágenes de marca'),
                    'icon' => 'image',
                    'href' => AppConfigController::routeName('logos-favicons'),
                    'visible' => AppConfigController::allowedRoute('logos-favicons'),
                    'asLink' => true,
                ]),
                new MenuGroup([
                    'name' => __(AppConfigController::LANG_GROUP, 'Personalización de fondos'),
                    'icon' => 'images',
                    'href' => AppConfigController::routeName('backgrounds'),
                    'visible' => AppConfigController::allowedRoute('backgrounds'),
                    'asLink' => true,
                ]),
                new MenuGroup([
                    'name' => __(AppConfigController::LANG_GROUP, 'Colores'),
                    'icon' => 'fill drip',
                    'href' => AppConfigController::routeName('generals'),
                    'visible' => AppConfigController::allowedRoute('generals'),
                    'asLink' => true,
                ]),
                new MenuGroup([
                    'name' => __(AppConfigController::LANG_GROUP, 'Ajustes SEO'),
                    'icon' => 'cog',
                    'href' => AppConfigController::routeName('seo'),
                    'visible' => AppConfigController::allowedRoute('seo'),
                    'asLink' => true,
                ]),
            ],
        ]),
    ],
    __(ADMIN_MENU_LANG_GROUP, 'Configuración plataforma') => [
        'visible' => $canViewConfiguration,
        'collection' => new MenuGroupCollection([
            'items' => [
                new MenuGroup([
                    'name' => __(AppConfigController::LANG_GROUP, 'Actualizar sitemap'),
                    'icon' => 'sitemap',
                    'href' => '#',
                    'attributes' => [
                        'sitemap-update-trigger' => '',
                        'data-url' => AppConfigController::routeName('generals-sitemap-create'),
                    ],
                    'visible' => AppConfigController::allowedRoute('generals-sitemap-create'),
                    'asLink' => true,
                ]),
                new MenuGroup([
                    'name' => __(AppConfigController::LANG_GROUP, 'Email SMTP'),
                    'icon' => 'envelope outline',
                    'href' => AppConfigController::routeName('email'),
                    'visible' => AppConfigController::allowedRoute('email'),
                    'asLink' => true,
                ]),
                new MenuGroup([
                    'name' => __(AppConfigController::LANG_GROUP, 'OsTicket'),
                    'icon' => 'file alternate outline',
                    'href' => AppConfigController::routeName('os-ticket'),
                    'visible' => AppConfigController::allowedRoute('os-ticket'),
                    'asLink' => true,
                ]),
                new MenuGroup([
                    'name' => __(AppConfigController::LANG_GROUP, 'Seguridad'),
                    'icon' => 'lock',
                    'href' => AppConfigController::routeName('security'),
                    'visible' => AppConfigController::allowedRoute('security'),
                    'asLink' => true,
                ]),
                new MenuGroup([
                    'name' => __(AppConfigController::LANG_GROUP, 'Limpiar caché'),
                    'icon' => 'eraser',
                    'href' => '#',
                    'visible' => AppConfigController::allowedRoute('generals-cache-clean'),
                    'attributes' => [
                        'clear-cache-update-trigger' => '',
                        'data-url' => AppConfigController::routeName('generals-cache-clean'),
                    ],
                    'asLink' => true,
                ]),
                new MenuGroup([
                    'name' => __(AppConfigController::LANG_GROUP, 'Rutas y permisos'),
                    'icon' => 'shield alternate',
                    'href' => get_route('configurations-routes', [], true),
                    'visible' => Roles::hasPermissions('configurations-routes', $currentUserType),
                    'asLink' => true,
                ]),
                new MenuGroup([
                    'name' =>  __(AppConfigController::LANG_GROUP, 'Crontab'),
                    'icon' => 'clock',
                    'href' => AppConfigController::routeName('crontab'),
                    'visible' => AppConfigController::allowedRoute('crontab'),
                    'asLink' => true,
                ]),
            ],
        ]),
    ],
    __(ADMIN_MENU_LANG_GROUP, 'Gestión de usuarios') => [
        'visible' => $canViewUsersManage,
        'collection' => new MenuGroupCollection([
            'items' => [
                new MenuGroup([
                    'name' => __(AdminPanelController::LANG_GROUP, 'Agregar usuario'),
                    'icon' => 'user plus',
                    'href' => get_route('users-selection-create'),
                    'visible' => Roles::hasPermissions('users-selection-create', $currentUserType),
                    'asLink' => true,
                ]),
                new MenuGroup([
                    'name' => __(AdminPanelController::LANG_GROUP, 'Gestionar usuarios'),
                    'icon' => 'users cog',
                    'href' => get_route('users-list'),
                    'visible' => Roles::hasPermissions('users-list', $currentUserType),
                    'asLink' => true,
                ]),
                new MenuGroup([
                    'name' => __(AdminPanelController::LANG_GROUP, 'Importar usuarios'),
                    'icon' => 'upload',
                    'href' => get_route('importer-form', ['type' => 'users'], true),
                    'visible' => Roles::hasPermissions('importer-form', $currentUserType),
                    'asLink' => true,
                ]),
            ],
        ]),
    ],
    __(ADMIN_MENU_LANG_GROUP, 'Registro') => [
        'visible' => $canViewRecord,
        'collection' => new MenuGroupCollection([
            'items' => [
                new MenuGroup([
                    'name' => __(AppConfigController::LANG_GROUP, 'Log de errores'),
                    'icon' => 'times',
                    'href' => get_route('admin-error-log', [], true),
                    'attributes' => [
                        'target' => '_blank',
                    ],
                    'visible' => Roles::hasPermissions('admin-error-log', $currentUserType),
                    'asLink' => true,
                ]),
                new MenuGroup([
                    'name' => __(AdminPanelController::LANG_GROUP, 'Intentos de ingresos'),
                    'icon' => 'sign in alternate',
                    'href' => get_route('informes-acceso') . '?attempts=yes',
                    'visible' => Roles::hasPermissions('informes-acceso', $currentUserType),
                    'asLink' => true,
                ]),
                new MenuGroup([
                    'name' => __(AdminPanelController::LANG_GROUP, 'Usuario sin ingresos'),
                    'icon' => 'user times',
                    'href' => get_route('informes-acceso') . '?not-logged=yes',
                    'visible' => Roles::hasPermissions('informes-acceso', $currentUserType),
                    'asLink' => true,
                ]),
                new MenuGroup([
                    'name' => __(AdminPanelController::LANG_GROUP, 'Registro de ingresos'),
                    'icon' => 'chart bar outline',
                    'href' => get_route('informes-acceso') . '?logged=yes',
                    'visible' => Roles::hasPermissions('informes-acceso', $currentUserType),
                    'asLink' => true,
                ]),
            ],
        ]),
    ],
];

$withAdminOptions = false;

foreach($adminOptionsGroups as $title => $config){
    if($config['visible']){
        $withAdminOptions = true;
        break;
    }
}

/**
 * @var UserDataPackage
 */
$currentUser = getLoggedFrameworkUser();
/**
 * @var bool
 */
$hasAvatar = $currentUser->hasAvatar;
/**
 * @var string
 */
$avatar = $currentUser->avatar;

?>

<div class="ui-pcs topbar-toggle user-options <?= !$withAdminOptions ? 'is-unique' : ''; ?>">
    <div class="icon">
        <i class="user icon"></i>
    </div>
</div>

<?php if($withAdminOptions): ?>
<div class="ui-pcs topbar-toggle admin-options">
    <div class="icon">
        <i class="th icon"></i>
    </div>
</div>
<?php endif; ?>

<div class="ui-pcs topbar-options user-options">

    <div class="close">
        <i class="times circle outline icon"></i>
    </div>

    <div class="user-info">

        <div class="avatar">
            <?php if($hasAvatar): ?>
            <img src="<?= $avatar; ?>">
            <?php else: ?>
            <div class="icon">
                <i class="icon user outline"></i>
            </div>
            <?php endif; ?>
        </div>

        <div class="names">
            <span><?= $currentUser->firstname . ' ' . $currentUser->secondname; ?></span>
            <span><?= $currentUser->firstLastname . ' ' . $currentUser->secondLastname; ?></span>
        </div>

        <div class="text">
            <div class="meta">
                <?= $currentUser->username; ?>
            </div>
            <div class="meta">
                <?= $currentUser->email; ?>
            </div>
            <div class="meta">
                <?= $currentUser->getTypeText(); ?>
            </div>
        </div>

    </div>

    <div class="items">
        <?php foreach($userOptions->getItems() as $userOption): ?>
        <?php if($userOption->asLink()):?>
        <a class="item<?= $userOption->isCurrent() ? " current" : ''; ?>" href="<?= $userOption->getHref(); ?>" <?= $userOption->getAttributes(true); ?>>
            <div class="figure"><i class="icon <?= $userOption->getIcon(false); ?>"></i></div>
            <div class="text"><?= $userOption->getName(); ?></div>
        </a>
        <?php endif;?>
        <?php endforeach; ?>
    </div>

</div>

<?php if($withAdminOptions): ?>
<div class="ui-pcs topbar-options admin-options">

    <div class="close">
        <i class="times circle outline icon"></i>
    </div>

    <div class="items">
        <?php foreach($adminOptionsGroups as $title => $config): ?>

        <?php /** @var bool */ $visible = $config['visible']; ?>
        <?php /** @var MenuGroup[] */ $collection = $config['collection']->getItems(); ?>
        <?php if($visible): ?>

        <div class="section-title"><?= $title; ?></div>

        <?php foreach($collection as $adminOption): ?>

        <?php if($adminOption->asLink()):?>
        <a class="item<?= $adminOption->isCurrent() ? " current" : ''; ?>" href="<?= $adminOption->getHref() === '#' ? 'javascript:void(0);' : $adminOption->getHref(); ?>" <?= $adminOption->getAttributes(true); ?>>
            <div class="figure"><i class="icon <?= $adminOption->getIcon(false); ?>"></i></div>
            <div class="text"><?= $adminOption->getName(); ?></div>
        </a>
        <?php endif;?>

        <?php endforeach; ?>

        <?php endif;?>

        <?php endforeach; ?>
    </div>

</div>
<?php endif; ?>
