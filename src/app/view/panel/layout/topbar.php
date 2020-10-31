<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Model\UsersModel;
?>

<div style="--bg-color:<?= get_config('admin_menu_color'); ?>;" class="ui-pcs topbar">

    <div class="blank">
    </div>

    <div class="user-info">

        <div class="avatar">
            <?php if($user->hasAvatar): ?>
            <img src="<?= $user->avatar; ?>">
            <?php endif; ?>
        </div>

        <div class="text">
            <div class="main">
                <?= htmlentities(stripslashes($user->fullName)); ?>
            </div>
            <div class="second">
                <?= isset(UsersModel::getTypesUser()[$user->type]) ? UsersModel::getTypesUser()[$user->type] : '';?>
            </div>
        </div>

        <div class="info-menu">
            <div>
                <!-- MENÚ -->
            </div>
        </div>

    </div>
</div>
