<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\Config;
$alternativesURL = Config::get_config('alternatives_url');
?>
<!DOCTYPE HTML>
<html lang="<?=get_config('app_lang');?>" dlang="<?=get_config('default_lang');?>">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="cache-stamp" value="<?=get_config('cacheStamp');?>">
    <meta name="lang-messages-from-server-url" value="<?=base64_encode(\PiecesPHP\LocalizationSystem\Controllers\LocalizationSystemController::routeName('get-lang-messages-by-group'));?>">
    <meta name="config-admin-url" value="<?=base64_encode(@json_encode(get_config('admin_url')));?>">
    <meta name="front-configurations" value="<?=base64_encode(@json_encode(get_front_configurations()));?>">
    <base href="<?=baseurl();?>">
    <link href="<?=baseurl('statics/wf/css/normalize.css');?>" rel="stylesheet" type="text/css">
    <link href="<?=baseurl('statics/wf/css/webflow.css');?>" rel="stylesheet" type="text/css">
    <link href="<?=baseurl('statics/wf/css/PROJECT.webflow.css');?>" rel="stylesheet" type="text/css">
    <?=\PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsGeneric();?>
    <?=\PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsOpenGraph();?>
    <script type="text/javascript">
    ! function(o, c) {
        var n = c.documentElement,
            t = " w-mod-";
        n.className += t + "js", ("ontouchstart" in o || o.DocumentTouch && c instanceof DocumentTouch) && (n.className += t + "touch")
    }(window, document);
    </script>
    <link href="<?=add_cache_stamp_to_url(get_config('favicon'));?>" rel="shortcut icon" type="image/x-icon">
    <link href="<?=add_cache_stamp_to_url(get_config('favicon'));?>" rel="apple-touch-icon">
    <?php load_font()?>
    <?php load_css([
            'base_url' => "",
            'custom_url' => "",
    ])?>
    <?=\PiecesPHP\Core\Utilities\Helpers\ExtraScripts::getScripts();?>
</head>

<body class="<?=isset($bodyClasses) && is_string($bodyClasses) ? $bodyClasses : '';?>">