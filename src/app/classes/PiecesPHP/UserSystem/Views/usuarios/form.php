<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\Controllers\UserSessionsController;
$langGroup = UsersController::LANG_GROUP;
$onlyProfile = isset($onlyProfile) ? $onlyProfile : false;
$onlyImage = isset($onlyImage) ? $onlyImage : false;
//Misma regla que la ruta: solo root o administrador general, y a un root solo lo cierra otro root.
$canRevokeSessions = !$create && !$onlyProfile && isset($edit_user) && UsersController::canRevokeSessionsOf((int) $edit_user->type);
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

    <?php //«Cuenta» y su icono son los mismos en usuarios y en organizaciones; dos pestañas nunca con el mismo icono. ?>
    <div class="ui pointing secondary menu">
        <?php if (!$onlyImage): ?>
        <a class="item active" data-tab="form-container"><i class="user circle icon"></i><?= __($langGroup, 'Cuenta'); ?></a>
        <?php endif;?>
        <?php if (!$create && !$onlyProfile): ?>
        <a class="item<?= $onlyImage ? ' active' : '';?>" data-tab="avatar-photo-container"><i class="image outline icon"></i><?= __($langGroup, 'Foto de perfil'); ?></a>
        <?php endif;?>
        <?php if ($canRevokeSessions): ?>
        <a class="item" data-tab="sessions-container"><i class="clock outline icon"></i><?= __($langGroup, 'Sesiones'); ?></a>
        <?php endif;?>
    </div>

    <?php if (!$onlyImage): ?>
    <div class="ui tab active" data-tab="form-container">
        <?= $form; ?>
    </div>
    <?php endif;?>

    <?php if (!$create && !$onlyProfile): ?>

    <div class="ui tab<?= $onlyImage ? ' active' : '';?>" data-tab="avatar-photo-container">

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
                    ]);
                ?>
            </div>
        </section>

        <?php $this->helpController->_render('panel/built-in/content/image-editor.php'); ?>

    </div>

    <?php endif;?>

    <?php if ($canRevokeSessions): ?>

    <div class="ui tab" data-tab="sessions-container">

        <?= UserSessionsController::userSessionsHTML((int) $edit_user->id, false); ?>

        <div class="ui divider"></div>

        <p><?= __($langGroup, 'Cierra la sesión del usuario en todos sus dispositivos. Tendrá que volver a ingresar con su contraseña.'); ?></p>

        <form action="<?= UsersController::routeName('revoke-sessions-request'); ?>" method="POST" class="ui form" revoke-user-sessions data-confirmation-title="<?= __($langGroup, 'Cerrar sesiones'); ?>" data-confirmation-message="<?= htmlspecialchars(strReplaceTemplate(__($langGroup, 'Se cerrarán todas las sesiones de %s.'), ['%s' => $edit_user->username])); ?>">
            <input type="hidden" name="id" value="<?= $edit_user->id; ?>">
            <button type="submit" class="ui negative button"><?= __($langGroup, 'Cerrar todas sus sesiones'); ?></button>
        </form>

    </div>

    <?php endif;?>

</div>

</div>

</section>
