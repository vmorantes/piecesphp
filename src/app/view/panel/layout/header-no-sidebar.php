<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="cache-stamp" value="<?= get_config('cacheStamp'); ?>">
    <base href="<?= baseurl(); ?>">
    <?= \PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsGeneric(); ?>
    <?= \PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsOpenGraph(); ?>
    <link rel="shortcut icon" href="<?= add_cache_stamp_to_url(get_config('favicon-back')); ?>" type="image/x-icon">
    <?php load_font() ?>
    <?php load_css([
        'base_url' => "", 
        'custom_url' => "",
    ]) ?>
</head>
<?php $currentUserLogged = getLoggedFrameworkUser(); ?>

<body>

    <?php if(!isset($noTopBar) || $noTopBar === false): ?>
    <?php $this->render('panel/layout/topbar'); ?>
    <?php endif; ?>
