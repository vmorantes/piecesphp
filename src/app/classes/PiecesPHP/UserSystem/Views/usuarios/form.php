<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\Controllers\UserSessionsController;
$langGroup = UsersController::LANG_GROUP;
$onlyProfile = isset($onlyProfile) ? $onlyProfile : false;
$onlyImage = isset($onlyImage) ? $onlyImage : false;
//Misma regla que la ruta: solo root o administrador general, y a un root solo lo cierra otro root.
$canRevokeSessions = !$create && !$onlyProfile && isset($edit_user) && UsersController::canRevokeSessionsOf((int) $edit_user->type);
//«Preferencias» solo la pide el perfil propio (formProfileByType); crear y editar a otro no la pasan.
$showPreferences = isset($showPreferences) && $showPreferences === true;
$visibleTabs = array_keys(array_filter([
    'form-container' => !$onlyImage,
    'avatar-photo-container' => !$create && !$onlyProfile,
    'sessions-container' => $canRevokeSessions,
    UsersController::PROFILE_PREFERENCES_TAB => $showPreferences,
]));
//La activa la decide el servidor (?tab=): una que no está visible cae en la de siempre.
$requestedTab = isset($requestedTab) && is_string($requestedTab) ? $requestedTab : '';
$activeTab = in_array($requestedTab, $visibleTabs, true) ? $requestedTab : ($onlyImage ? 'avatar-photo-container' : 'form-container');
$activeClass = fn(string $tab): string => $activeTab === $tab ? ' active' : '';
?>
<section class="module-view-container">

<div class="limiter-content">

<?php
//La cabecera del panel (diseno/01-contenidos.md §2.1): las migas, el título y de quién es la cuenta.
$this->helpController->_render('panel/built-in/content/page-header.php', [
    'breadcrumbs' => $breadcrumbs ?? [],
    'title' => $pageTitle ?? '',
    'description' => $pageDescription ?? '',
    'secondaryActions' => $pageSecondaryActions ?? [],
]);
?>

<div class="user-form-component">

    <?php
        //Con una sola pestaña (crear) no hay barra (vistas-referencia.md §3.44). Los iconos, los del prototipo (§3.46).
        $tabsCount = count($visibleTabs);
    ?>
    <?php if ($tabsCount > 1): ?>
    <div class="ui pointing secondary menu">
        <?php if (!$onlyImage): ?>
        <a class="item<?= $activeClass('form-container'); ?>" data-tab="form-container"><i class="user icon" aria-hidden="true"></i><?= __($langGroup, 'Cuenta'); ?></a>
        <?php endif;?>
        <?php if (!$create && !$onlyProfile): ?>
        <a class="item<?= $activeClass('avatar-photo-container'); ?>" data-tab="avatar-photo-container"><i class="image icon" aria-hidden="true"></i><?= __($langGroup, 'Foto de perfil'); ?></a>
        <?php endif;?>
        <?php if ($canRevokeSessions): ?>
        <a class="item<?= $activeClass('sessions-container'); ?>" data-tab="sessions-container"><i class="desktop icon" aria-hidden="true"></i><?= __($langGroup, 'Sesiones'); ?></a>
        <?php endif;?>
        <?php if ($showPreferences): ?>
        <a class="item<?= $activeClass(UsersController::PROFILE_PREFERENCES_TAB); ?>" data-tab="<?= UsersController::PROFILE_PREFERENCES_TAB; ?>"><i class="sliders horizontal icon" aria-hidden="true"></i><?= __($langGroup, 'Preferencias'); ?></a>
        <?php endif;?>
    </div>
    <?php endif; ?>

    <?php if (!$onlyImage): ?>
    <div class="ui tab<?= $activeClass('form-container'); ?>" data-tab="form-container">
        <?= $form; ?>
    </div>
    <?php endif;?>

    <?php if (!$create && !$onlyProfile): ?>

    <div class="ui tab<?= $activeClass('avatar-photo-container'); ?>" data-tab="avatar-photo-container">

        <section class="ui segment" aria-labelledby="user-photo-title" data-user-avatar="<?= (int) $edit_user->id; ?>" data-user-avatar-url="<?= escape_html(get_route('push-avatars')); ?>">
            <?php
                $this->helpController->_render('panel/built-in/content/section-header.php', [
                    'id' => 'user-photo-title',
                    'title' => __($langGroup, 'Foto de perfil'),
                    'description' => __($langGroup, 'Se muestra en el menú del usuario, en su tarjeta de la lista de usuarios y junto a lo que publica.'),
                ]);
            ?>
            <div class="ui one stackable horizontal cards">
                <?php
                    $this->helpController->_render('panel/built-in/content/image-card.php', [
                        'id' => 'user-avatar-card',
                        'title' => __($langGroup, 'Foto de perfil'),
                        'description' => __($langGroup, 'Una foto del rostro, centrada y con buena luz. Se recorta en círculo donde se muestra.'),
                        'hint' => __($langGroup, 'Formatos: JPG, PNG o WEBP'),
                        'accept' => 'image/jpeg,image/png,image/webp',
                        'image' => $hasAvatar ? (string) $avatar : '',
                        'field' => 'image',
                        'width' => 400,
                        'height' => 400,
                        'format' => 'image/png',
                        'emptyText' => __($langGroup, 'Sin foto'),
                    ]);
                ?>
            </div>
        </section>

        <?php $this->helpController->_render('panel/built-in/content/image-editor.php'); ?>

    </div>

    <?php endif;?>

    <?php if ($canRevokeSessions): ?>

    <div class="ui tab<?= $activeClass('sessions-container'); ?>" data-tab="sessions-container">

        <?php //Primero cerrar todas, luego la tabla (vistas-referencia.md §3.46). ?>
        <section class="ui segment" aria-labelledby="user-sessions-title">
            <div class="ui stackable middle aligned wide flex" data-user-sessions-close-all>
                <div class="fluid ui vertical tight flex">
                    <h2 class="ui small header" id="user-sessions-title"><?= __($langGroup, 'Cerrar todas sus sesiones'); ?></h2>
                    <p class="ui muted text"><?= __($langGroup, 'Expulsa a este usuario de todos los dispositivos de una sola vez, en lugar de cerrarlas una a una. Tendrá que volver a iniciar sesión en cada uno.'); ?></p>
                </div>
                <form action="<?= UsersController::routeName('revoke-sessions-request'); ?>" method="POST" class="ui form" revoke-user-sessions data-confirmation-title="<?= __($langGroup, 'Cerrar sesiones'); ?>" data-confirmation-message="<?= htmlspecialchars(strReplaceTemplate(__($langGroup, 'Se cerrarán todas las sesiones de %s.'), ['%s' => $edit_user->username])); ?>">
                    <input type="hidden" name="id" value="<?= $edit_user->id; ?>">
                    <button type="submit" class="mobile stretched ui negative basic labeled icon button"><i class="sign out alternate icon" aria-hidden="true"></i><?= __($langGroup, 'Cerrar todas sus sesiones'); ?></button>
                </form>
            </div>

            <div class="ui section divider"></div>

            <?= UserSessionsController::userSessionsTableHTML((int) $edit_user->id); ?>
        </section>

    </div>

    <?php endif;?>

    <?php if ($showPreferences): ?>

    <div class="ui tab<?= $activeClass(UsersController::PROFILE_PREFERENCES_TAB); ?>" data-tab="<?= UsersController::PROFILE_PREFERENCES_TAB; ?>">

        <section class="ui segment" aria-labelledby="user-preferences-title">
            <?php
                $this->helpController->_render('panel/built-in/content/section-header.php', [
                    'id' => 'user-preferences-title',
                    'title' => __($langGroup, 'Preferencias'),
                ]);
            ?>
            <?php //El mismo grupo que tenía el menú de usuario: lo guarda y lo aplica el manejador de configurations.js. ?>
            <div class="ui vertical flex">
                <small class="ui muted text"><?= __(AdminPanelController::ADMIN_LANG_GROUP, 'Tamaño de letra'); ?></small>
                <div class="ui three mini buttons" role="radiogroup" aria-label="<?= escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Tamaño de letra')); ?>" aria-describedby="user-preferences-text-size-help" text-size-selector data-url="<?= escape_html(AdminPanelController::routeName('text-size')); ?>">
                    <?php foreach (['normal' => 'Normal', 'large' => 'Grande', 'larger' => 'Más grande'] as $textSizeOption => $textSizeOptionName) : ?>
                    <button type="button" class="ui button<?= ($textSize ?? '') === $textSizeOption ? ' active' : ''; ?>" role="radio" aria-checked="<?= ($textSize ?? '') === $textSizeOption ? 'true' : 'false'; ?>" tabindex="<?= ($textSize ?? '') === $textSizeOption ? '0' : '-1'; ?>" data-text-size="<?= $textSizeOption; ?>"><?= __(AdminPanelController::ADMIN_LANG_GROUP, $textSizeOptionName); ?></button>
                    <?php endforeach; ?>
                </div>
                <p class="ui muted text" id="user-preferences-text-size-help"><?= __($langGroup, 'Se aplica al momento y se guarda en tu cuenta.'); ?></p>
            </div>
        </section>

    </div>

    <?php endif;?>

</div>

</div>

</section>
