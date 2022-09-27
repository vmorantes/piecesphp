<?php

use PiecesPHP\Core\Config;

defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

$alternativesURL = Config::get_config('alternatives_url');

?>
<!DOCTYPE HTML>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="cache-stamp" value="<?= get_config('cacheStamp'); ?>">
    <base href="<?=baseurl();?>">
    <?= \PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsGeneric(); ?>
    <?= \PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsOpenGraph(); ?>
    <link rel="shortcut icon" href="<?= get_config('favicon'); ?>" type="image/x-icon">
    <?php load_font() ?>
    <?php load_css([
        'base_url' => "", 
        'custom_url' => "",
    ]) ?>
    <?= \PiecesPHP\Core\Utilities\Helpers\ExtraScripts::getScripts(); ?>
</head>

<body>
