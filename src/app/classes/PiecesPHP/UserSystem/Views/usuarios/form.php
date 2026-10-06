<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\UserSystem\Controllers\UsersController;
$langGroup = UsersController::LANG_GROUP;
$onlyProfile = isset($onlyProfile) ? $onlyProfile : false;
$onlyImage = isset($onlyImage) ? $onlyImage : false;
//Misma regla que la ruta: solo root o administrador general, y a un root solo lo cierra otro root.
$canRevokeSessions = !$create && !$onlyProfile && isset($edit_user) && UsersController::canRevokeSessionsOf((int) $edit_user->type);
?>
<div class="user-form-component">

    <div class="ui pointing secondary menu items-pointing">
        <?php if (!$onlyImage): ?>
        <a class="item active" data-tab="form-container"><?= __($langGroup, 'Datos de usuario'); ?><?= isset($typeName) ? " ({$typeName})" : '';?></a>
        <?php endif;?>
        <?php if (!$create && !$onlyProfile): ?>
        <a class="item<?= $onlyImage ? ' active' : '';?>" data-tab="avatar-photo-container"><?= __($langGroup, 'Foto de perfil'); ?></a>
        <?php endif;?>
        <?php if ($canRevokeSessions): ?>
        <a class="item" data-tab="sessions-container"><?= __($langGroup, 'Sesiones'); ?></a>
        <?php endif;?>
    </div>

    <?php if (!$onlyImage): ?>
    <div class="ui bottom attached tab active" data-tab="form-container">
        <?= $form; ?>
    </div>
    <?php endif;?>

    <?php if (!$create && !$onlyProfile): ?>

    <div class="ui bottom attached tab<?= $onlyImage ? ' active' : '';?>" data-tab="avatar-photo-container">

        <form action="<?=get_route('push-avatars');?>" class="ui form profile-photo-form">

            <input type="hidden" name="user" value="<?=$edit_user->id;?>">
            <input type="hidden" name="edit" value="<?= $hasAvatar ? '1' : '0';?>">

            <div class="ui form cropper-adapter">

                <div class="field required">
                    <label><?= __($langGroup, 'Foto de perfil'); ?></label>
                    <input type="file" accept="image/*" required>
                </div>

                <?php $this->helpController->_render('panel/built-in/utilities/cropper/workspace.php', [
                    'referenceW'=> '400',
                    'referenceH'=> '400',
                    'withTitle' => false,
                    'image' => $hasAvatar ? $avatar : '',
                ]); ?>

                <br>

                <div style="text-align:center;">
                    <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar foto de perfil'); ?></button>
                </div>

            </div>

        </form>

    </div>

    <?php endif;?>

    <?php if ($canRevokeSessions): ?>

    <div class="ui bottom attached tab" data-tab="sessions-container">

        <p><?= __($langGroup, 'Cierra la sesión del usuario en todos sus dispositivos. Tendrá que volver a ingresar con su contraseña.'); ?></p>

        <form action="<?= UsersController::routeName('revoke-sessions-request'); ?>" method="POST" class="ui form" revoke-user-sessions data-confirmation-title="<?= __($langGroup, 'Cerrar sesiones'); ?>" data-confirmation-message="<?= htmlspecialchars(strReplaceTemplate(__($langGroup, 'Se cerrarán todas las sesiones de %s.'), ['%s' => $edit_user->username])); ?>">
            <input type="hidden" name="id" value="<?= $edit_user->id; ?>">
            <button type="submit" class="ui button red"><?= __($langGroup, 'Cerrar todas sus sesiones'); ?></button>
        </form>

    </div>

    <?php endif;?>

</div>
