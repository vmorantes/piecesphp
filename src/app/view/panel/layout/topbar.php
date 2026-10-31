<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use News\NewsLang;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\UserSystem\Controllers\ImpersonationController;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;

//Lo flotante de la barra superior (paneles, ventanas, avisos): fuera de header.bar, cuyo backdrop-filter haría de
//bloque contenedor de sus hijos fixed. La barra visible está en topbar-bar.php.
require __DIR__ . '/topbar-data.php';
//Por qué terminó una suplantación que root no terminó: se enseña una vez.
$impersonationNotice = $isLoggedOtherUser ? null : ImpersonationController::pullNotice();
?>

<?php if ($isLoggedOtherUser) : ?>
<div class="impersonation-band" impersonation-band>
    <span class="text"><?= sprintf(__(UserSystemFeaturesLang::LANG_GROUP, 'Conectado como %s'), escape_html($currentUser->fullName)); ?></span>
    <a href="#" exit-as-logged-options data-url="<?= escape_html($impersonationEndURL); ?>"><?= __(UserSystemFeaturesLang::LANG_GROUP, 'Volver a mi cuenta'); ?></a>
</div>
<?php elseif ($impersonationNotice !== null) : ?>
<div class="impersonation-band notice" impersonation-band>
    <span class="text"><?= escape_html(sprintf(__(UserSystemFeaturesLang::LANG_GROUP, 'La suplantación terminó: %s'), $impersonationNotice)); ?></span>
</div>
<?php endif; ?>

<?php if ($systemAlertNags !== []) : ?>
<div class="system-alert-nags">
<?php foreach ($systemAlertNags as $systemAlertNag) : ?>
<?php $systemAlertNagFixURL = $systemAlertNag->fixRoute() !== null ? \PiecesPHP\SystemStatus\Controllers\SystemStatusController::fixURL($systemAlertNag) : ''; ?>
<div class="ui bottom fixed nag" system-alert-nag data-key="<?= htmlspecialchars($systemAlertNag->key(), ENT_QUOTES, 'UTF-8'); ?>" data-version="<?= htmlspecialchars($systemAlertNag->nagVersion(), ENT_QUOTES, 'UTF-8'); ?>">
    <span class="title"><?= htmlspecialchars($systemAlertNag->message(), ENT_QUOTES, 'UTF-8'); ?></span>
    <?php if ($systemAlertNagFixURL !== ''): ?>
    <a href="<?= htmlspecialchars($systemAlertNagFixURL, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($systemAlertNag->fixLabel() ?? __(\PiecesPHP\SystemStatus\Controllers\SystemStatusController::LANG_GROUP, 'Cómo arreglarlo'), ENT_QUOTES, 'UTF-8'); ?></a>
    <?php endif; ?>
    <i class="close icon"></i>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($withNews) : ?>
<?php //Las noticias: un popup del tema, que abre el botón de la barra (topbar-bar.php). NewsAdapter pinta en el contenedor. ?>
<div class="ui wide fitted popup" news-popup role="dialog" aria-label="<?= escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Noticias')); ?>">
    <div class="ui basic segment"><strong><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Noticias'); ?></strong></div>
    <div class="ui fitted divider"></div>
    <div class="news-popup-list" data-url="<?= \News\Controllers\NewsController::routeName('ajax-all'); ?>">
        <div news-toolbar-container class="news-popup-items"></div>
    </div>
</div>
<?php endif; ?>

<?php if ($withAdminOptions) : ?>
<?php //Configuración: el patrón del tema (panel-configuracion): buscador y grupos plegables, todos cerrados; no marca la vista actual. ?>
<div class="ui right flyout" id="admin-options-flyout" admin-options-flyout role="dialog" aria-modal="true" aria-label="<?= escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Configuración del sistema')); ?>">
    <i class="close icon" aria-hidden="true"></i>
    <div class="ui header"><i class="cogs bordered icon" aria-hidden="true"></i><div class="content"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Configuración del sistema'); ?></div></div>
    <div class="content">
        <div class="ui fluid left icon input">
            <input type="search" admin-options-search placeholder="<?= escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Buscar ajuste…')); ?>" autocomplete="off" aria-label="<?= escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Buscar ajuste')); ?>" aria-controls="admin-options-groups">
            <i class="search icon" aria-hidden="true"></i>
        </div>
    </div>
    <div class="scrolling content">
        <div class="ui fluid accordion" id="admin-options-groups" admin-options-groups>
            <?php $adminOptionsGroupIndex = 0; ?>
            <?php foreach ($adminOptionsGroups as $title => $config) : ?>
            <?php if ($config['visible']) : ?>
            <?php /** @var MenuGroup[] */ $adminOptionsLinks = array_filter($config['collection']->getItems(), fn(MenuGroup $adminOption): bool => $adminOption->asLink() && $adminOption->isVisible()); ?>
            <?php $adminOptionsGroupIndex++; ?>
            <div class="title" role="button" tabindex="0" aria-expanded="false" aria-controls="admin-options-group-<?= $adminOptionsGroupIndex; ?>"><i class="<?= escape_html($config['icon']); ?> bordered icon" aria-hidden="true"></i><span class="texto"><?= $title; ?></span> <span class="ui circular label"><?= count($adminOptionsLinks); ?></span><i class="dropdown icon" aria-hidden="true"></i></div>
            <div class="content" id="admin-options-group-<?= $adminOptionsGroupIndex; ?>">
                <div class="ui link list">
                    <?php foreach ($adminOptionsLinks as $adminOption) : ?>
                    <a class="item" href="<?= $adminOption->getHref() === '#' ? 'javascript:void(0);' : $adminOption->getHref(); ?>" <?= $adminOption->getAttributes(true); ?>><?= $adminOption->getName(); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <p class="ui muted text" admin-options-empty role="status" hidden></p>
    </div>
</div>
<?php endif; ?>

<?php //El perfil en línea: un flyout del tema; lo abren «Editar Cuenta» y «Cambiar contraseña» del menú de usuario. ?>
<div class="ui wide right flyout" profile-flyout role="dialog" aria-label="<?= escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Perfil')); ?>">
    <i class="close icon" aria-hidden="true"></i>
    <div class="ui header"><i class="user outline bordered icon" aria-hidden="true"></i><div class="content"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Perfil'); ?></div></div>
    <div class="scrolling content">
        <div class="profile-content-system" data-url="<?= \PiecesPHP\UserSystem\Controllers\UsersController::routeName('edit-request'); ?>">

            <div class="user-data">

                <div class="image">
                    <?php if ($hasAvatar) : ?>
                    <img class="ui tiny circular image" src="<?= $avatar; ?>" alt="Avatar">
                    <?php else : ?>
                    <i class="user icon"></i>
                    <?php endif; ?>
                </div>

                <div class="info">
                    <strong class="ui large text"><?= escape_html($currentUser->firstname . ' ' . $currentUser->secondname . ' ' . $currentUser->firstLastname . ' ' . $currentUser->secondLastname); ?></strong>
                    <small class="ui muted text"><?= $currentUser->getTypeText(); ?></small>
                    <a action-image-profile href="javascript:void(0);"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Editar foto'); ?></a>
                    <a href="<?= escape_html(\PiecesPHP\UserSystem\Controllers\UsersController::routeName('form-profile')); ?>"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Ver mi cuenta completa'); ?></a>
                </div>
            </div>

            <div class="ui secondary pointing menu tab-options">

                <div class="item" data-tab="account">
                    <i class="user outline icon"></i>
                    <span><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Cuenta'); ?></span>
                </div>
                <div class="item" data-tab="password">
                    <i class="key icon"></i>
                    <span><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Contraseña'); ?></span>
                </div>
                <?php if(in_array($currentUserType, $usersCanDeleteAccount)): ?>
                <div class="item" delete-account-trigger>
                    <i class="user times icon"></i>
                    <span><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Eliminar cuenta'); ?></span>
                </div>
                <?php endif;?>
            </div>

            <div class="body-content">

                <div class="content-view account" data-view="account">
                    <form profile-information-form class="ui form" action="">
                        <input type="hidden" name="id" value="<?= $currentUser->id ?>">
                        <h3 class="ui header"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Información de perfil'); ?></h3>
                        <div class="field">
                            <label><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Nombres'); ?></label>
                            <div class="two fields">
                                <div class="field">
                                    <input type="text" name="firstname" placeholder="<?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Primer nombre'); ?>" required value="<?= escape_html($currentUser->firstname); ?>">
                                </div>
                                <div class="field">
                                    <input type="text" name="secondname" placeholder="<?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Segundo nombre'); ?>" value="<?= escape_html($currentUser->secondname); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="field">
                            <label><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Apellidos'); ?></label>
                            <div class="two fields">
                                <div class="field">
                                    <input required type="text" name="first_lastname" placeholder="<?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Primer apellido'); ?>" value="<?= escape_html($currentUser->firstLastname); ?>">
                                </div>
                                <div class="field">
                                    <input type="text" name="second_lastname" placeholder="<?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Segundo apellido'); ?>" value="<?= escape_html($currentUser->secondLastname); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="field">
                            <label><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Usuario'); ?></label>
                            <input required type="text" name="username" placeholder="" value="<?= escape_html($currentUser->username); ?>">
                        </div>
                        <div class="field">
                            <label><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Correo'); ?></label>
                            <input required type="email" name="email" placeholder="" value="<?= escape_html($currentUser->email); ?>">
                        </div>
                        <div class="actions">
                            <button class="ui primary button" type="submit"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Guardar'); ?></button>
                        </div>
                    </form>
                </div>
                <div class="content-view password" data-view="password">
                    <form user-password-form class="ui form" action="">
                        <input type="hidden" name="id" value="<?= $currentUser->id ?>">

                        <h3 class="ui header"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Cambiar de contraseña'); ?><div class="sub header"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Una contraseña segura debe tener 8 caracteres como mínimo, distingue mayúsculas de minúsculas'); ?></div></h3>
                        <div class="field">
                            <label><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Contraseña actual'); ?></label>
                            <input type="password" name="current-password" placeholder="">
                        </div>
                        <div class="field">
                            <label><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Nueva contraseña'); ?></label>
                            <input type="password" name="password" placeholder="">
                        </div>
                        <div class="field">
                            <label><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Confirmar contraseña'); ?></label>
                            <input type="password" name="password2" placeholder="">
                        </div>
                        <div class="actions">
                            <button close-profile class="ui basic button" type="reset"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Cancelar'); ?></button>
                            <button class="ui primary button" type="submit"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Guardar'); ?></button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php if($withNews): ?>
<?php //Modal módulo noticias internas ?>
<div news-modal class="ui tiny modal">
    <div class="header"></div>
    <div class="content"></div>
    <div class="actions">
        <div class="ui cancel button primary"><?= __(NewsLang::LANG_GROUP, 'Cerrar'); ?></div>
    </div>
</div>
<?php endif; ?>

<?php if ($withHelpCenter) : ?>
<div class="ui popup" help-center-menu>
    <div class="header"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Centro de ayuda'); ?></div>
    <div class="ui selection list" role="menu" aria-label="<?= escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Ayuda')); ?>">
        <?php if ($helpFaqURL !== '') : ?>
        <a class="item" href="<?= escape_html($helpFaqURL); ?>" target="_blank" rel="noopener" role="menuitem"><div class="ui top aligned nowrap relaxed flex"><i class="primary book bordered icon" aria-hidden="true"></i><div class="content"><div class="header"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Preguntas frecuentes'); ?></div><div class="description"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Respuestas rápidas a las dudas más comunes.'); ?></div></div></div></a>
        <?php endif; ?>
        <?php if ($canContactSupport) : ?>
        <a class="item" href="#" role="menuitem" support-button-js><div class="ui top aligned nowrap relaxed flex"><i class="primary headset bordered icon" aria-hidden="true"></i><div class="content"><div class="header"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Contactar a soporte'); ?></div><div class="description"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Escríbenos y te ayudamos con tu caso.'); ?></div></div></div></a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if($manyLangs): ?>
<div class="ui tiny modal" change-system-lang-modal data-lang-cookie="<?= get_config('cookie_lang_definer'); ?>">
    <i class="close icon" aria-label="<?= escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Cerrar')); ?>"></i>
    <div class="header"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Cambiar idioma'); ?></div>
    <div class="content">
        <form class="ui form" id="change-system-lang-form">
            <div class="field">
                <select name="lang" class="ui dropdown search auto" aria-label="<?= escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Cambiar idioma')); ?>">
                    <?php foreach($alternativesURL as $lang => $url): ?>
                    <option <?= $lang == $currentLang ? 'selected' : ''; ?> value="<?= $lang; ?>"><?= __('lang', $lang); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    <div class="actions">
        <button type="button" class="ui basic cancel button"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Cancelar'); ?></button>
        <button type="submit" form="change-system-lang-form" class="ui primary button"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Cambiar'); ?></button>
    </div>
</div>
<?php endif; ?>

<?php if($isRoot): ?>
<div class="ui tiny modal" connect-as-another-user-modal data-url="<?= escape_html($impersonationStartURL); ?>">
    <i class="close icon" aria-label="<?= escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Cerrar')); ?>"></i>
    <div class="header"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Cambio de usuario'); ?></div>
    <div class="content">
        <form class="ui form" id="connect-as-another-user-form">
            <div class="field">
                <select class="ui dropdown search" name="userID" data-search-url="<?= $searchUsersURL; ?>" required aria-label="<?= escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Cambio de usuario')); ?>">
                    <option value=""><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Seleccionar usuario'); ?></option>
                </select>
            </div>
            <div class="ui negative message transition hidden" connect-as-another-user-refusal></div>
        </form>
    </div>
    <div class="actions">
        <button type="button" class="ui basic cancel button"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Cancelar'); ?></button>
        <button type="submit" form="connect-as-another-user-form" class="ui primary button"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Cambiar'); ?></button>
    </div>
</div>
<?php endif; ?>

<?php //La foto de perfil: una tarjeta oculta y su editor, con su propio alcance porque la página puede traer los suyos. ?>
<div hidden data-profile-photo data-url="<?= escape_html(get_route('push-avatars')); ?>" data-user-id="<?= (int) $currentUserID; ?>">
    <?php
        $this->_render('panel/built-in/content/image-card.php', [
            'id' => 'profile-image-card',
            'scope' => 'profile',
            'title' => __(AdminPanelController::ADMIN_LANG_GROUP, 'Imagen de perfil'),
            'accept' => 'image/jpeg,image/png,image/webp',
            'image' => $hasAvatar ? (string) $avatar : '',
            'field' => 'image',
            'width' => 400,
            'height' => 400,
        ]);
    ?>
</div>
<?php $this->_render('panel/built-in/content/image-editor.php', ['scope' => 'profile']); ?>

<div class="ui toast delete-account-handler">
    <div class="content">
        <div class="ui header"><?= __(GLOBAL_LANG_GROUP, 'Confirmación'); ?></div>
        <p>
            <?= __(GLOBAL_LANG_GROUP, 'Para confirmar la acción escriba en el siguiente campo: Eliminar');?>
        </p>
        <div class="ui form">
            <input type="text" name="confirm-delete" placeholder="<?= __(GLOBAL_LANG_GROUP, "Escriba 'Eliminar'"); ?>" required>
        </div>
        <br>
    </div>
    <div class="left basic actions">
        <button class="ui button red" data-url="<?= \PiecesPHP\UserSystem\Controllers\UsersController::routeName('delete-account-request'); ?>" delete data-expected-word="<?= __(GLOBAL_LANG_GROUP, 'Eliminar'); ?>"><?= __(GLOBAL_LANG_GROUP, 'Eliminar'); ?></button>
        <button class="ui button grey" cancel><?= __(GLOBAL_LANG_GROUP, 'Cancelar'); ?></button>
    </div>
</div>
