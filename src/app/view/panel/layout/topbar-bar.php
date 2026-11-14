<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use MySpace\Controllers\MyProfileController;
use MySpace\Controllers\MySpaceController;
use Organizations\Controllers\MyOrganizationProfileController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\Utilities\Helpers\StatusLabel;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;
use SystemApprovals\SystemApprovalsRoutes;

require __DIR__ . '/topbar-data.php';
$fullName = $currentUser->firstname . ' ' . $currentUser->firstLastname;
$themeMode = AdminPanelController::themeModeOf((int) $currentUser->id);
//El mismo criterio que el acceso (activado y confirmado), del TOTPData que el usuario ya trae cargado: sin consulta nueva.
$twoFactorData = $currentUser->TOTPData;
$twoFactorActive = $twoFactorData !== null && $twoFactorData->twoAuthFactor === OTPSecretsUsersMapper::TWOAF_STATUS_ENABLED && (int) $twoFactorData->twoAuthFactorQRViewed === 1;
?>
<?php if ($withHelpCenter) : ?>
<button class="ui tertiary icon button" type="button" help-center-toggle aria-haspopup="menu" aria-label="<?= escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Ayuda')); ?>"><i class="question circle icon" aria-hidden="true"></i></button>
<?php endif; ?>
<?php //La campana (ADR 0073): sale con sesión; la insignia, las no leídas que trae la página, oculta con 0. ?>
<?php $notificationsLabel = __(\PiecesPHP\Notifications\NotificationsLang::LANG_GROUP, 'Notificaciones, ${n} sin leer'); ?>
<button class="ui tertiary icon button" type="button" notifications-toggle aria-haspopup="dialog" aria-expanded="false" aria-controls="notifications-popup-list" data-label-template="<?= escape_html($notificationsLabel); ?>" aria-label="<?= escape_html(str_replace('${n}', (string) $notificationsUnread, $notificationsLabel)); ?>" title="<?= escape_html(__(\PiecesPHP\Notifications\NotificationsLang::LANG_GROUP, 'Notificaciones')); ?>"><i class="bell icon" aria-hidden="true"></i><span class="ui floating circular mini red label" notifications-badge aria-hidden="true"<?= $notificationsUnread === 0 ? ' hidden' : ''; ?>><?= $notificationsUnread; ?></span></button>
<?php if ($withAdminOptions) : ?>
<button class="ui tertiary icon button" type="button" admin-options-toggle aria-haspopup="dialog" aria-expanded="false" aria-controls="admin-options-flyout" aria-label="<?= escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Configuración del sistema')); ?>" title="<?= escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Configuración del sistema')); ?>"><i class="cogs icon" aria-hidden="true"></i></button>
<?php endif; ?>
<?php if ($isLoggedOtherUser) : ?>
<button class="ui tertiary icon button" type="button" exit-as-logged-options data-url="<?= escape_html($impersonationEndURL); ?>" aria-label="<?= escape_html(__(UserSystemFeaturesLang::LANG_GROUP, 'Volver a mi cuenta')); ?>" title="<?= escape_html(__(UserSystemFeaturesLang::LANG_GROUP, 'Volver a mi cuenta')); ?>"><i class="exchange alternate icon" aria-hidden="true"></i></button>
<?php endif; ?>
<div class="mobile hidden divider" aria-hidden="true"></div>
<div class="ui dropdown" user-options-dropdown tabindex="0" aria-label="<?= escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Perfil')); ?>">
    <div class="ui nowrap flex">
        <?php if ($hasAvatar) : ?>
        <img class="ui avatar image" src="<?= $avatar; ?>" alt="">
        <?php else : ?>
        <?= ($avatarLabel)(); ?>
        <?php endif; ?>
        <span class="mobile hidden fluid ui vertical fitted flex">
            <strong class="ui small truncated text" title="<?= escape_html($fullName); ?>"><?= escape_html($fullName); ?></strong>
            <small class="ui muted truncated text"><?= $currentUser->getTypeText(); ?></small>
        </span>
        <i class="small grey mobile hidden chevron down icon" aria-hidden="true"></i>
    </div>
    <div class="left menu">
        <div class="ui basic segment">
            <div class="ui top aligned nowrap relaxed flex">
                <?php if ($hasAvatar) : ?>
                <img class="ui tiny circular image" src="<?= $avatar; ?>" alt="">
                <?php else : ?>
                <?= ($avatarLabel)('massive'); ?>
                <?php endif; ?>
                <div class="fluid ui vertical left aligned tight flex">
                    <strong><?= escape_html($currentUser->firstname . ' ' . $currentUser->secondname); ?></strong>
                    <strong><?= escape_html($currentUser->firstLastname . ' ' . $currentUser->secondLastname); ?></strong>
                    <small class="ui muted breaking text"><?= escape_html($currentUser->email); ?></small>
                    <span class="ui small label"><?= $currentUser->getTypeText(); ?></span>
                </div>
            </div>
        </div>
        <div class="divider"></div>
        <a class="item" href="#" edit-account><i class="user icon" aria-hidden="true"></i><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Editar Cuenta'); ?></a>
        <a class="item" href="#" change-password><i class="key icon" aria-hidden="true"></i><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Cambiar contraseña'); ?></a>
        <?php //El tamaño de letra salió del menú a la pestaña «Preferencias» del perfil (H252): el menú se abarrotaba. ?>
        <?php if (UsersController::allowedRoute('form-profile')): ?>
        <a class="item" href="<?= escape_html(UsersController::profileTabURL(UsersController::PROFILE_PREFERENCES_TAB)); ?>"><i class="sliders horizontal icon" aria-hidden="true"></i><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Preferencias'); ?></a>
        <?php endif; ?>
        <?php //El nombre del enlace con el estado AL FINAL: la descripción va antes en el DOM y se leería «Activa Seguridad…». ?>
        <a class="item" href="<?= MySpaceController::routeName('user-security'); ?>" aria-label="<?= escape_html($twoFactorActive ? __(AdminPanelController::ADMIN_LANG_GROUP, 'Seguridad. Verificación en dos pasos: activa') : __(AdminPanelController::ADMIN_LANG_GROUP, 'Seguridad. Verificación en dos pasos: inactiva')); ?>"><span class="description"><?= $twoFactorActive ? StatusLabel::html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Activa'), StatusLabel::POSITIVE) : StatusLabel::neutral(__(AdminPanelController::ADMIN_LANG_GROUP, 'Inactiva')); ?></span><i class="shield alternate icon" aria-hidden="true"></i><span class="ui inline vertical left aligned fitted flex"><span><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Seguridad'); ?></span><small class="ui muted text"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Verificación en dos pasos'); ?></small></span></a>
        <?php if(MyProfileController::allowedRoute('my-profile') && SystemApprovalsRoutes::ENABLE): ?>
        <a class="item" href="<?= MyProfileController::routeName('my-profile', [], true); ?>"><i class="id card outline icon" aria-hidden="true"></i><?= __(ADMIN_MENU_LANG_GROUP, 'Mi perfil'); ?></a>
        <?php endif; ?>
        <?php if(MyOrganizationProfileController::allowedRoute('my-organization-profile') && in_array($currentUserType, OrganizationMapper::PROFILE_EDITOR)): ?>
        <a class="item" href="<?= MyOrganizationProfileController::routeName('my-organization-profile', [], true); ?>"><i class="building outline icon" aria-hidden="true"></i><?= __(ADMIN_MENU_LANG_GROUP, 'Mi organización'); ?></a>
        <?php endif; ?>
        <div class="divider"></div>
        <?php foreach ($userOptions->getItems() as $userOption) : ?>
        <?php if ($userOption->asLink()) : ?>
        <a class="item<?= $userOption->isCurrent() ? " active" : ''; ?>" href="<?= $userOption->getHref(); ?>" <?= $userOption->getAttributes(true); ?>><i class="icon <?= $userOption->getIcon(false); ?>" aria-hidden="true"></i><?= $userOption->getName(); ?></a>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php //El modo de color: tres botones (radiogroup); lo guarda pcsAdminTopbars(). No la pieza segmentada de la guía: dentro
            //del ui dropdown una .menu anidada es un submenú, oculto y fuera de sitio. Ni $mode ni $data ni $output aquí: la
        //vista corre dentro de BaseController::render() y pisaría sus variables. ?>
        <?php //`header` da el relleno de un ítem del desplegable (8 px arriba, 4 abajo y la sangría de sus iconos) sin ser un ítem. ?>
        <div class="header">
            <small class="ui muted text"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Modo de color'); ?></small>
            <div class="ui fluid three mini buttons" role="radiogroup" aria-label="<?= escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Modo de color')); ?>" theme-mode-selector data-url="<?= escape_html(AdminPanelController::routeName('theme-mode')); ?>">
                <?php foreach (['light' => ['Claro', 'sun'], 'dark' => ['Oscuro', 'moon'], 'auto' => ['Sistema', 'adjust']] as $themeOption => [$themeOptionName, $themeOptionIcon]) : ?>
                <button type="button" class="ui button<?= $themeMode === $themeOption ? ' active' : ''; ?>" role="radio" aria-checked="<?= $themeMode === $themeOption ? 'true' : 'false'; ?>" tabindex="<?= $themeMode === $themeOption ? '0' : '-1'; ?>" data-theme-mode="<?= $themeOption; ?>"><i class="<?= $themeOptionIcon; ?> icon" aria-hidden="true"></i><?= __(AdminPanelController::ADMIN_LANG_GROUP, $themeOptionName); ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php foreach ($userOptionsEnd->getItems() as $userOption) : ?>
        <?php if ($userOption->asLink()) : ?>
        <?php //«Acerca de» y «Cerrar sesión», cada uno tras su divisor, como en el marco del tema. ?>
        <div class="divider"></div>
        <a class="item<?= $userOption->isCurrent() ? " active" : ''; ?>" href="<?= $userOption->getHref(); ?>" <?= $userOption->getAttributes(true); ?>><i class="icon <?= $userOption->getIcon(false); ?>" aria-hidden="true"></i><?= $userOption->getName(); ?></a>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
