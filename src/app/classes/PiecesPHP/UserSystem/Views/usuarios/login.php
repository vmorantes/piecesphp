<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\UserSystem\Controllers\UserSystemFeaturesController;
?>
<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="front-configurations" value="<?= base64EncodeOrDefault(jsonEncodeFallbackNull(get_front_configurations()), '[]'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <base href="<?= baseurl(); ?>">
    <?php \PiecesPHP\Core\Utilities\Helpers\MetaTags::setRobots('noindex, nofollow'); ?>
    <?= \PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsGeneric(); ?>
    <link rel="shortcut icon" href="<?= add_cache_stamp_to_url(get_config('favicon-back')); ?>" type="image/x-icon">
    <?php load_css(['base_url' => "", 'custom_url' => ""]) ?>
    <?php //Sin ExtraScripts a propósito: un script de terceros aquí leería la contraseña mientras se teclea. ?>
</head>

<body>

    <?php
        $accessBackgrounds = get_config('backgrounds');
        $accessBackgrounds = is_array($accessBackgrounds) ? $accessBackgrounds : [];
        ob_start();
    ?>

    <div class="login-form">

        <h1 class="ui large header" defauld-show>
            <?= __(USER_LOGIN_LANG_GROUP, 'Iniciar sesión'); ?>
            <div class="sub header"><?= __(USER_LOGIN_LANG_GROUP, 'Ingresa con tu cuenta para continuar.'); ?></div>
        </h1>

        <form defauld-show login-form-js last-uri='<?= htmlspecialchars((string) $requested_uri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>' class="ui form initial">

            <div class="field">
                <label for="login-username"><?= __(USER_LOGIN_LANG_GROUP, 'Usuario'); ?></label>
                <div class="ui left icon input">
                    <input type="text" id="login-username" required name='username' autocomplete="username" placeholder="<?= __(USER_LOGIN_LANG_GROUP, 'correo@dominio.com'); ?>">
                    <i class="user icon" aria-hidden="true"></i>
                </div>
            </div>

            <div class="field">
                <label for="login-password"><?= __(USER_LOGIN_LANG_GROUP, 'Contraseña'); ?></label>
                <div class="ui left icon input">
                    <input type="password" id="login-password" required name='password' autocomplete="current-password" placeholder="••••••••">
                    <i class="lock icon" aria-hidden="true"></i>
                    <i class="link eye icon" role="button" tabindex="0" aria-pressed="false" aria-controls="login-password" aria-label="<?= escape_html(__(USER_LOGIN_LANG_GROUP, 'Mostrar contraseña')); ?>" data-show-text="<?= escape_html(__(USER_LOGIN_LANG_GROUP, 'Mostrar contraseña')); ?>" data-hide-text="<?= escape_html(__(USER_LOGIN_LANG_GROUP, 'Ocultar contraseña')); ?>" data-login-eye></i>
                </div>
            </div>

            <div class="field" twofa-field>
                <label for="login-two-factor"><?= __(USER_LOGIN_LANG_GROUP, 'Código temporal de aplicación'); ?></label>
                <input type="text" id="login-two-factor" name='twoFactor' autocomplete="one-time-code" placeholder="000000">
            </div>

            <div class="field">
                <div class="ui checkbox remember-me">
                    <input type="checkbox" id="login-remember">
                    <label for="login-remember"><?= __(USER_LOGIN_LANG_GROUP, 'Recordarme'); ?></label>
                </div>
            </div>

            <div class="field">
                <button type="submit" class="ui fluid primary button"><?= __(USER_LOGIN_LANG_GROUP, 'Ingresar'); ?></button>
            </div>

            <div class="field">
                <button otp-trigger data-url="<?= UserSystemFeaturesController::routeName('generate-otp'); ?>" class="ui fluid basic button"><?= __(USER_LOGIN_LANG_GROUP, 'Contraseña de un uso'); ?></button>
            </div>
        </form>

        <div show-error class="ui negative message" role="alert">
            <div class="header"><?= __(USER_LOGIN_LANG_GROUP, 'Error al ingresar'); ?></div>
            <p><strong title></strong></p>
            <p message></p>
            <p bottom-message></p>
            <button try-again class="ui basic button" type="button"><?= __(USER_LOGIN_LANG_GROUP, 'Intentar nuevamente'); ?></button>
        </div>

        <div class="ui hidden divider"></div>

        <div class="ui center aligned container problems-button">
            <small><?= strReplaceTemplate(__(USER_LOGIN_LANG_GROUP, 'NEED_HELP_TO_LOGIN'), [
    '${ot}' => "<a href='" . \PiecesPHP\UserSystem\Controllers\UserProblemsController::routeName('problems-list') . "'>",
    '${ct}' => "</a>",
]); ?></small>
        </div>

    </div>

    <?php $accessContent = (string) ob_get_clean(); require __DIR__ . '/inc/access-frame.php'; ?>

    <?php load_js(['base_url' => "", 'custom_url' => ""]) ?>

</body>

</html>
