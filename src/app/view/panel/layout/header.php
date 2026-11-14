<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
<?php
//El modo de color y el tamaño de letra del usuario, en <html> antes de pintar nada: sin parpadeo. Sin elegir, o sin
//sesión, los del sitio (Apariencia › Accesibilidad). El tema traduce data-text-size a partir de --font-size-site.
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

    <?php
        //El marcado de form-modal.php, sin su data-content-modal-form: esa pieza recarga las tablas al guardar, y esto está en
        //todas las pantallas. Solo sale con destinatarios y permiso, como el botón (topbar-data.php).
        $supportFormVisible = \PiecesPHP\Core\Roles::hasPermissions('admin-tickets-create', (int) $currentUserLogged->type) && \PiecesPHP\AdminPanel\Controllers\AdminPanelController::supportFormRecipients() !== [];
    ?>
    <?php if ($supportFormVisible): ?>
    <div class="ui small modal" support-js aria-labelledby="support-modal-title">
        <i class="close icon" aria-label="<?= escape_html(__(\PiecesPHP\AdminPanel\Controllers\AdminPanelController::ADMIN_LANG_GROUP, 'Cerrar')); ?>"></i>
        <div class="header" id="support-modal-title"><?= __(SUPPORT_FORM_ADMIN_LANG_GROUP, 'Soporte técnico'); ?></div>
        <div class="scrolling content">
            <form method="POST" action="<?= escape_html(\PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName('tickets-create')); ?>" class="ui form initial" id="support-modal-form" data-content-form>
                <div class="field required">
                    <label for="support-modal-subject"><?= __(SUPPORT_FORM_ADMIN_LANG_GROUP, 'Asunto'); ?></label>
                    <input type="text" id="support-modal-subject" name="subject" maxlength="300" required>
                </div>
                <div class="field required">
                    <label for="support-modal-comments"><?= __(SUPPORT_FORM_ADMIN_LANG_GROUP, 'Mensaje'); ?></label>
                    <textarea id="support-modal-comments" name="comments" rows="6" required></textarea>
                </div>
            </form>
        </div>
        <div class="actions">
            <button class="ui basic button" type="button" data-content-modal-cancel><?= __(\PiecesPHP\AdminPanel\Controllers\AdminPanelController::ADMIN_LANG_GROUP, 'Cancelar'); ?></button>
            <button class="ui primary button" type="submit" form="support-modal-form"><?= __(SUPPORT_FORM_ADMIN_LANG_GROUP, 'Enviar'); ?></button>
        </div>
    </div>
    <?php endif; ?>

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
                <?php //En móvil la marca no se ve (el menú es un cajón): el logo va en la barra, como el del menú. ?>
                <span class="mobile only logo" aria-hidden="true"><img class="<?= \PiecesPHP\Settings\Controllers\SettingsController::brandImageInvertsOnDark('logo') ? 'invert-on-dark' : ''; ?>" src="<?= escape_html((string) get_config('logo')); ?>" alt=""></span>
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
