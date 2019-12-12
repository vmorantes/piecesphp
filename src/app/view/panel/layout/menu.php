<?php
use App\Model\UsersModel;
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<div class="ui-pcs sidebar-toggle ">
    <i class="icon bars"></i>
</div>
<aside class="ui-pcs sidebar">

    <div class="user-info">
        <div class="avatar">
            <?php if($user->hasAvatar): ?>
            <img src="<?= $user->avatar; ?>">
            <?php endif; ?>
        </div>
        <div class="text">
            <div class="main">
                <?= htmlentities(stripslashes($user->firstname)) . '<br>' . htmlentities(stripslashes($user->first_lastname)); ?>
            </div>
            <div class="second">
                <?= isset(UsersModel::getTypesUser()[$user->type]) ? UsersModel::getTypesUser()[$user->type] : '';?>
            </div>
        </div>
    </div>

    <article class="links">
        <?= menu_sidebar_items($user); ?>
    </article>

    <div class="logo-developed">
        <small><?= __('general', 'Desarrollado por'); ?> <?= get_config('developer'); ?> <?= __('general', 'para'); ?>:</small>
        <img src="<?=get_config('logo-sidebar-bottom');?>">
    </div>
</aside> <!-- .ui-pcs.sidebar -->
