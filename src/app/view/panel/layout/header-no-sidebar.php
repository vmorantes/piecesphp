<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
<?php
//El modo de color y el tamaño de letra del usuario, como en header.php: sin ellos, el gestor que abre el editor sale
//siempre en claro y a tamaño normal.
$themeUser = getLoggedFrameworkUser();
$themeMode = $themeUser !== null ? \PiecesPHP\AdminPanel\Controllers\AdminPanelController::themeModeOf((int) $themeUser->id) : \PiecesPHP\Settings\Controllers\SettingsController::accessibilityThemeMode();
$textSize = $themeUser !== null ? \PiecesPHP\AdminPanel\Controllers\AdminPanelController::textSizeOf((int) $themeUser->id) : \PiecesPHP\Settings\Controllers\SettingsController::accessibilityTextSize();
?>
<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>"<?= ' data-theme="' . escape_html($themeMode) . '" data-text-size="' . escape_html($textSize) . '"'; ?>>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="cache-stamp" value="<?= get_config('cacheStamp'); ?>">
    <meta name="lang-messages-from-server-url" value="<?= base64_encode(\PiecesPHP\LocalizationSystem\Controllers\LocalizationSystemController::routeName('get-lang-messages-by-group')); ?>">
    <meta name="mapbox-key-url" value="<?= base64_encode(\PiecesPHP\Settings\Controllers\SettingsController::routeName('integrations-mapbox-key')); ?>">
    <meta name="map-default-center" value="<?= base64EncodeOrDefault(jsonEncodeFallbackNull(\PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper::mapDefaultCenter()), '{}'); ?>">
    <meta name="logout-url" value="<?= base64_encode(\PiecesPHP\UserSystem\Controllers\UserSessionsController::routeName('logout')); ?>">
    <meta name="config-admin-url" value="<?= base64EncodeOrDefault(jsonEncodeFallbackNull(get_config('admin_url')), '{}'); ?>">
    <meta name="front-configurations" value="<?= base64EncodeOrDefault(jsonEncodeFallbackNull(get_front_configurations()), '[]'); ?>">
    <base href="<?= baseurl(); ?>">
    <?php \PiecesPHP\Core\Utilities\Helpers\MetaTags::setRobots('noindex, nofollow'); ?>
    <?= \PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsGeneric(); ?>
    <?= \PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsOpenGraph(); ?>
    <link rel="shortcut icon" href="<?= add_cache_stamp_to_url(get_config('favicon-back')); ?>" type="image/x-icon">
    <?php load_font() ?>
    <?php load_css([
            'base_url' => "",
            'custom_url' => "",
    ]) ?>
    <?= \PiecesPHP\Core\Utilities\Helpers\ExtraScripts::getScriptsFor('panel', 'head'); ?>
</head>

<body>
    <?= \PiecesPHP\Core\Utilities\Helpers\ExtraScripts::getScriptsFor('panel', 'body_start'); ?>

    <?php //Sin shell no hay header.bar para topbar-bar.php: aquí solo cabe lo flotante. Su único uso (el editor enriquecido, FileManagerController) pasa noTopBar. ?>
    <?php if(!isset($noTopBar) || $noTopBar === false): ?>
    <?php $this->render('panel/layout/topbar'); ?>
    <?php endif; ?>
