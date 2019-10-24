<?php

use PiecesPHP\Core\Config;

defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

$alternativesURL = Config::get_config('alternatives_url');

?>

<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <base href="<?=baseurl();?>">
    <?= \PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsGeneric(); ?>
    <?= \PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsOpenGraph(); ?>
    <link rel="shortcut icon" href="<?= get_config('favicon'); ?>" type="image/x-icon">
    <?php load_css(['base_url' => "", 'custom_url' => ""]) ?>
    <?= \PiecesPHP\Core\Utilities\Helpers\ExtraScripts::getScripts(); ?>
</head>

<body>
    <div class="global-layout">

        <?php if(is_array($alternativesURL)):?>

        <?php foreach($alternativesURL as $lang => $url): ?>

        <a href="<?= $url; ?>"><?= __('lang',$lang); ?></a>

        <?php endforeach;?>

        <?php endif;?>
