<?php
use App\Model\UsersModel;
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<div class="ui-pcs sidebar-toggle">
    <i class="icon bars"></i>
</div>
<aside class="ui-pcs sidebar">

    <div class="logo">

        <div class="image">
            <img src="<?= get_config('logo'); ?>">
        </div>

        <div class="text"><?= strReplaceTemplate(__('general', 'Versión {ver}'), ['{ver}' => APP_VERSION,])?></div>

    </div>

    <article class="links">
        <?= menu_sidebar_items($user); ?>
    </article>

    <div class="logo-developed">
        <small><?= __('general', 'Desarrollado por'); ?> <?= get_config('developer'); ?> <?= __('general', 'para'); ?>:</small>
        <img src="<?=get_config('white-logo');?>">
    </div>
</aside> <!-- .ui-pcs.sidebar -->
