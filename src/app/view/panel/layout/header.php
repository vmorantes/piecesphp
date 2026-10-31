<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
<?php
//El modo de color del usuario, en <html> antes de pintar nada: sin parpadeo. Sin elegir, el del sistema (auto).
$themeUser = getLoggedFrameworkUser();
$themeMode = $themeUser !== null ? \PiecesPHP\AdminPanel\Controllers\AdminPanelController::themeModeOf((int) $themeUser->id) : \PiecesPHP\AdminPanel\Controllers\AdminPanelController::THEME_MODE_AUTO;
?>
<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>"<?= ' data-theme="' . escape_html($themeMode) . '"'; ?>>

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
<?php $currentUserLogged = getLoggedFrameworkUserOrFail(); ?>
<?php
if (isset($bodyClasses) && is_array($bodyClasses)) {
    foreach ($bodyClasses as $k => $class) {
        if (!is_string($class)) {
            unset($bodyClasses[$k]);
        }
    }
    $bodyClasses = trim(implode(' ', $bodyClasses));
} else {
    $bodyClasses = '';
}
?>
<body class="<?= $bodyClasses; ?>">
    <?= \PiecesPHP\Core\Utilities\Helpers\ExtraScripts::getScriptsFor('panel', 'body_start'); ?>

    <div class="ui tiny modal" support-js>
        <i class="close icon" aria-label="<?= escape_html(__(\PiecesPHP\AdminPanel\Controllers\AdminPanelController::ADMIN_LANG_GROUP, 'Cerrar')); ?>"></i>
        <div class="header"><?= __(SUPPORT_FORM_ADMIN_LANG_GROUP, 'Soporte técnico'); ?></div>
        <div class="content">
            <form action="<?=\PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName('tickets-create');?>" class="ui form">
                <input type="hidden" name="name" value="<?=htmlentities($currentUserLogged->firstname . ' ' . $currentUserLogged->firstLastname);?>">
                <input type="hidden" name="email" value="<?= htmlentities($currentUserLogged->email); ?>">
                <div class="field">
                    <label><?= __(SUPPORT_FORM_ADMIN_LANG_GROUP, 'Asunto'); ?></label>
                    <input type="text" name="subject">
                </div>
                <div class="field">
                    <label><?= __(SUPPORT_FORM_ADMIN_LANG_GROUP, 'Mensaje'); ?></label>
                    <textarea name="comments"></textarea>
                </div>
                <div class="field">
                    <button type="submit" class="ui primary button"><?= __(SUPPORT_FORM_ADMIN_LANG_GROUP, 'Enviar'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <?php if(ACTIVE_TIMER): ?>
    <div timer-platform-js="<?=base64_encode(json_encode(['user_id' => $currentUserLogged->id, 'url' => \PiecesPHP\UserSystem\Controllers\TimerController::routeName('add')], \JSON_THROW_ON_ERROR));?>">
    </div>
    <?php endif;?>

    <?php if(!isset($noTopBar) || $noTopBar === false): ?>
    <?php $this->render('panel/layout/topbar'); ?>
    <?php endif; ?>

    <?php
        //admin-shell: ámbito de las reglas funcionales del contenido; sin ui-pcs para que el tema pinte (ADR 0069).
        $shellText = [
            'collapse' => __(\PiecesPHP\AdminPanel\Controllers\AdminPanelController::ADMIN_LANG_GROUP, 'Contraer menú'),
            'expand' => __(\PiecesPHP\AdminPanel\Controllers\AdminPanelController::ADMIN_LANG_GROUP, 'Expandir menú'),
            'openMenu' => __(\PiecesPHP\AdminPanel\Controllers\AdminPanelController::ADMIN_LANG_GROUP, 'Abrir menú'),
            'closeMenu' => __(\PiecesPHP\AdminPanel\Controllers\AdminPanelController::ADMIN_LANG_GROUP, 'Cerrar menú'),
        ];
    ?>
    <?php //El .pusher es de los flyouts del tema (topbar.php): sin él, Fomantic lo crea al iniciar, mueve los flyouts y lo avisa en la consola. ?>
    <div class="pusher">
    <div class="ui shell admin-shell" data-shell-text="<?= escape_html(json_encode($shellText, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE)); ?>">

        <?php $this->render('panel/layout/menu'); ?>

        <div class="main">

            <header class="bar">
                <button class="ui tertiary icon button drawer toggle" type="button"><i class="bars icon" aria-hidden="true"></i></button>
                <?php if(!isset($noTopBar) || $noTopBar === false): ?>
                <?php $this->render('panel/layout/topbar-search'); ?>
                <div class="right">
                    <?php $this->render('panel/layout/topbar-bar'); ?>
                </div>
                <?php endif; ?>
            </header>

        <?php
            if (isset($containerClasses) && is_array($containerClasses)) {

                foreach ($containerClasses as $k => $class) {
                    if (!is_string($class)) {
                        unset($containerClasses[$k]);
                    }
                }

                $containerClasses = trim(implode(' ', $containerClasses));

            } else {
                $containerClasses = '';
            }
        ?>
            <main class="content <?= $containerClasses; ?>">
