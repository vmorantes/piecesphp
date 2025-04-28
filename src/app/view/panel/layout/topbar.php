<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\AdminPanelController;
use App\Controller\AppConfigController;
use MySpace\Controllers\MySpaceController;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Roles;
use PiecesPHP\UserSystem\UserDataPackage;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;

$role = Roles::getCurrentRole();
$currentUserType = !is_null($role) ? $role['code'] : null;

$userOptions = new MenuGroupCollection([
    'items' => [
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
                    'name' => __(AppConfigController::LANG_GROUP, 'Seguridad e IA'),
                    'icon' => 'lock',
                    'href' => AppConfigController::routeName('security-and-ia'),
                    'visible' => AppConfigController::allowedRoute('security-and-ia'),
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

<div class="ui-pcs topbar-toggle user-options">
    <div class="current-user-info">
        <div class="image">
            <?php if ($hasAvatar) : ?>
            <img src="<?= $avatar; ?>">
            <?php else : ?>
            <i class="icon user outline"></i>
            <?php endif; ?>
        </div>
        <span><?= $currentUser->firstname . ' ' . $currentUser->firstLastname; ?></span>
    </div>
    <i class="angle down icon"></i>
</div>

<?php if ($withNews) : ?>
<div class="ui-pcs topbar-toggle notifications-options">
    <div class="icon">
        <i class="bell outline icon"></i>
    </div>
</div>
<?php endif; ?>

<?php if ($withAdminOptions) : ?>
<div class="ui-pcs topbar-toggle admin-options <?= $withAdminOptions && !$withNews ? 'is-unique' : ''; ?>">
    <div class="icon">
        <i class="cog icon"></i>
    </div>
</div>
<?php endif; ?>

<div class="topbar-content close">

    <div class="close-user">
        <div class="icon-decorated">
            <div class="bg-white">
                <i class="user outline icon"></i>
            </div>
            <span><?= __(AdminPanelController::LANG_GROUP, 'Perfil'); ?></span>
        </div>
        <i class="times icon close"></i>
    </div>

    <div class="ui-pcs topbar-options user-options">

        <div class="user-info">

            <div class="avatar">
                <?php if ($hasAvatar) : ?>
                <img src="<?= $avatar; ?>">
                <?php else : ?>
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
                    <?= $currentUser->getTypeText(); ?>
                </div>
                <div class="meta">
                    <?= $currentUser->email; ?>
                </div>
            </div>
        </div>

        <div class="change-account">
            <span edit-account><?= __(AdminPanelController::LANG_GROUP, 'Editar Cuenta'); ?></span>
            <span change-password><?= __(AdminPanelController::LANG_GROUP, 'Cambiar contraseña'); ?></span>
            <a href="<?= MySpaceController::routeName('user-security'); ?>"><?= __(AdminPanelController::LANG_GROUP, 'Opciones de seguridad'); ?></a>
        </div>

        <div class="items">
            <div class="section-title"><?= __(AdminPanelController::LANG_GROUP, 'Plataforma'); ?></div>
            <?php foreach ($userOptions->getItems() as $userOption) : ?>
            <?php if ($userOption->asLink()) : ?>
            <a class="item<?= $userOption->isCurrent() ? " current" : ''; ?>" href="<?= $userOption->getHref(); ?>" <?= $userOption->getAttributes(true); ?>>
                <div class="figure"><i class="icon <?= $userOption->getIcon(false); ?>"></i></div>
                <div class="text"><?= $userOption->getName(); ?></div>
            </a>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="footer-logo">
            <img src="<?= get_config('logo'); ?>" alt="Logo">
        </div>

    </div>
</div>

<?php if ($withNews) : ?>
<div class="topbar-content close" data-url="<?= \News\Controllers\NewsController::routeName('ajax-all'); ?>">

    <div class="close-user">
        <div class="icon-decorated">
            <div class="bg-white">
                <i class="bell outline icon"></i>
            </div>
            <span><?= __(AdminPanelController::LANG_GROUP, 'Noticias'); ?></span>
        </div>
        <i class="times icon close"></i>
    </div>

    <div news-toolbar-container class="ui-pcs topbar-options notifications-options">
    </div>
</div>
<?php endif; ?>

<?php if ($withAdminOptions) : ?>
<div class="topbar-content close">

    <div class="close-user">
        <div class="icon-decorated">
            <div class="bg-white">
                <i class="cog icon"></i>
            </div>
            <span><?= __(AdminPanelController::LANG_GROUP, 'Administrativo'); ?></span>
        </div>
        <i class="times icon close"></i>
    </div>

    <div class="ui-pcs topbar-options admin-options">

        <div class="items">
            <?php foreach ($adminOptionsGroups as $title => $config) : ?>

            <?php /** @var bool */ $visible = $config['visible']; ?>
            <?php /** @var MenuGroup[] */ $collection = $config['collection']->getItems(); ?>
            <?php if ($visible) : ?>

            <div class="section-title"><?= $title; ?></div>

            <?php foreach ($collection as $adminOption) : ?>

            <?php if($adminOption->asLink()): ?>
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
</div>
<?php endif; ?>

<div class="profile-content">

    <div class="close">
        <i close-profile class="times icon"></i>
    </div>

    <div class="user-data">

        <div class="image">
            <?php if ($hasAvatar) : ?>
            <img src="<?= $avatar; ?>" alt="Avatar">
            <?php else : ?>
            <i class="user icon"></i>
            <?php endif; ?>
        </div>

        <div class="info">
            <span><?= $currentUser->firstname . ' ' . $currentUser->secondname . ' ' . $currentUser->firstLastname . ' ' . $currentUser->secondLastname; ?></span>
            <small><?= $currentUser->getTypeText(); ?></small>
            <a action-image-profile href="javascript:void(0);"><?= __(AdminPanelController::LANG_GROUP, 'Editar foto'); ?></a>
        </div>
    </div>

    <div class="tab-options">

        <div class="item" data-tab="account">
            <i class="user outline icon"></i>
            <span><?= __(AdminPanelController::LANG_GROUP, 'Cuenta'); ?></span>
        </div>
        <div class="item" data-tab="password">
            <i class="user outline icon"></i>
            <span><?= __(AdminPanelController::LANG_GROUP, 'Contraseña'); ?></span>
        </div>
        <?php //<!-- Quitar cuando se coloque otro tab -->; ?>
        <div class=""></div>
    </div>

    <div class="body-content">

        <div class="content-view account" data-view="account">
            <form profile-information-form class="ui form" action="">
                <input type="hidden" name="id" value="<?= $currentUser->id ?>">
                <h3><?= __(AdminPanelController::LANG_GROUP, 'Información de perfil'); ?></h3>
                <div class="field">
                    <label><?= __(AdminPanelController::LANG_GROUP, 'Nombres'); ?></label>
                    <div class="two fields">
                        <div class="field">
                            <input type="text" name="firstname" placeholder="<?= __(AdminPanelController::LANG_GROUP, 'Primer nombre'); ?>" required value="<?= $currentUser->firstname ?>">
                        </div>
                        <div class="field">
                            <input type="text" name="secondname" placeholder="<?= __(AdminPanelController::LANG_GROUP, 'Segundo nombre'); ?>" value="<?= $currentUser->secondname ?>">
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label><?= __(AdminPanelController::LANG_GROUP, 'Apellidos'); ?></label>
                    <div class="two fields">
                        <div class="field">
                            <input required type="text" name="first_lastname" placeholder="<?= __(AdminPanelController::LANG_GROUP, 'Primer apellido'); ?>" value="<?= $currentUser->first_lastname ?>">
                        </div>
                        <div class="field">
                            <input type="text" name="second_lastname" placeholder="<?= __(AdminPanelController::LANG_GROUP, 'Segundo apellido'); ?>" value="<?= $currentUser->second_lastname ?>">
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label><?= __(AdminPanelController::LANG_GROUP, 'Usuario'); ?></label>
                    <input required type="text" name="username" placeholder="" value="<?= $currentUser->username ?>">
                </div>
                <div class="field">
                    <label><?= __(AdminPanelController::LANG_GROUP, 'Correo'); ?></label>
                    <input required type="email" name="email" placeholder="" value="<?= $currentUser->email ?>">
                </div>
                <div class="align-right">
                    <button class="ui button primary" type="submit"><?= __(AdminPanelController::LANG_GROUP, 'Guardar'); ?></button>
                </div>
            </form>
        </div>
        <div class="content-view password" data-view="password">
            <form user-password-form class="ui form" action="">
                <input type="hidden" name="id" value="<?= $currentUser->id ?>">

                <h3 class="no-margin"><?= __(AdminPanelController::LANG_GROUP, 'Cambiar de contraseña'); ?></h3>
                <span>Una contraseña segura debe tener 8 caracteres como mínimo, distingue mayúsculas de minúsculas</span>
                <br>
                <div class="field">
                    <label><?= __(AdminPanelController::LANG_GROUP, 'Contraseña actual'); ?></label>
                    <input type="password" name="current-password" placeholder="">
                </div>
                <div class="field">
                    <label><?= __(AdminPanelController::LANG_GROUP, 'Nueva contraseña'); ?></label>
                    <input type="password" name="password" placeholder="">
                </div>
                <div class="field">
                    <label><?= __(AdminPanelController::LANG_GROUP, 'Confirmar contraseña'); ?></label>
                    <input type="password" name="password2" placeholder="">
                </div>
                <div class="align-right">
                    <button close-profile class="ui secondary basic button" type="reset"><?= __(AdminPanelController::LANG_GROUP, 'Cancelar'); ?></button>
                    <button class="ui button primary" type="submit"><?= __(AdminPanelController::LANG_GROUP, 'Guardar'); ?></button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php if($withNews): ?>
<?php //Modal módulo noticias internas ?>
<div news-modal class="ui tiny modal">
    <div class="header"></div>
    <div class="content"></div>
    <div class="actions">
        <div class="ui cancel button primary">Ok</div>
    </div>
</div>
<?php endif; ?>

<?php
    //Modal edición de imagen de usuario
    modalImageUploaderForCropperAdminViews([
        //El contenido (si se usa simpleCropperAdapterWorkSpace o similar debe ser con el parámetro $echo en false)
        'content' => simpleCropperAdapterWorkSpace([
            'type' => 'image/*',
            'required' => false,
            'selectorAttr' => 'simple-cropper-profile',
            'referenceW' => '400',
            'referenceH' => '400',
            'image' => $avatar,
        ], false),
        //Atributos que se asignarán al modal (el contenedor principal), string
        'modalContainerAttrs' => "profile-image-modal",
        //Clases que se asignarán al modal (el contenedor principal), string
        'modalContainerClasses' => "ui tiny modal",
        //Atributos que se asignarán al elemento de contenido del modal (modal > .content), string
        'modalContentElementAttrs' => implode(' ', [
            'action-url="' . get_route('push-avatars') . '"',
            'user-id="' . getLoggedFrameworkUser()->id . '"',
        ]),
        //Clase por defecto del elemento informativo del modal (donde están el título y la descripcion, por omisión cropper-info-content), string
        'informationContentMainClass' => 'info-content',
        //Clases que se asignarán al elemento informativo del modal (donde están el título y la descripcion), string
        'informationContentClasses' => null,
        //Título del modal, string
        'titleModal' => null,
        //Descripción del modal, string
        'descriptionModal' => null,
    ]);
?>
