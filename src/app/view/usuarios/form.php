<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\UsersController;
$langGroup = UsersController::LANG_GROUP;
?>
<div class="user-form-component">

    <div class="ui pointing secondary menu items-pointing">
        <?php if (!$onlyImage): ?>
        <a class="item active" data-tab="form-container"><?= __($langGroup, 'Datos de usuario'); ?></a>
        <?php endif;?>
        <?php if (!$create && !$onlyProfile): ?>
        <a class="item<?= $onlyImage ? ' active' : '';?>" data-tab="avatar-photo-container"><?= __($langGroup, 'Foto de perfil'); ?></a>
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

                <?php $this->_render('panel/built-in/utilities/cropper/workspace.php', [
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

</div>
