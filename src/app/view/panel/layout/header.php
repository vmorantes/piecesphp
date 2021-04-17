<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <base href="<?= baseurl(); ?>">
    <?= \PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsGeneric(); ?>
    <?= \PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsOpenGraph(); ?>
    <link rel="shortcut icon" href="<?= get_config('favicon-back'); ?>" type="image/x-icon">
    <?php load_css(['base_url' => "", 'custom_url' => ""]) ?>
</head>

<body>

    <div class="ui modal" support-js>
        <div class="header"><?= __(SUPPORT_FORM_ADMIN_LANG_GROUP, 'Soporte técnico'); ?></div>
        <div class="content">
            <form action="<?=get_route('tickets-create');?>" class="ui form">
                <input type="hidden" name="name" value="<?=htmlentities(stripslashes(get_config('current_user')->firstname . ' ' . get_config('current_user')->first_lastname));?>">
                <input type="hidden" name="email" value="<?= htmlentities(stripslashes(get_config('current_user')->email)); ?>">
                <div class="field">
                    <label><?= __(SUPPORT_FORM_ADMIN_LANG_GROUP, 'Asunto'); ?></label>
                    <input type="text" name="subject">
                </div>
                <div class="field">
                    <label><?= __(SUPPORT_FORM_ADMIN_LANG_GROUP, 'Mensaje'); ?></label>
                    <textarea name="comments"></textarea>
                </div>
                <div class="field">
                    <button type="submit" class="ui green button"><?= __(SUPPORT_FORM_ADMIN_LANG_GROUP, 'Enviar'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <?php if(ACTIVE_TIMER): ?>
    <div timer-platform-js="<?=base64_encode(json_encode(['user_id' => get_config('current_user')->id, 'url' => get_route('timing-add')]));?>">
    </div>
    <?php endif;?>

    <?php $this->render('panel/layout/topbar'); ?>

    <div  style="--bg-color:<?= get_config('admin_menu_color'); ?>;" class="ui-pcs logo-sidebar-desktop">

        <div class="image">
            <img src="<?= get_config('logo'); ?>">
        </div>

        <div class="text"><?= strReplaceTemplate(__('general', 'Versión {ver}'), ['{ver}' => APP_VERSION,])?></div>

    </div>

    <div class="ui-pcs container-sidebar">

        <?php $this->render('panel/layout/menu'); ?>

        <div class="content">
