<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); 
use PiecesPHP\UserSystem\Controllers\UserSystemFeaturesController;
?>
<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <base href="<?= baseurl(); ?>">
    <?= \PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsGeneric(); ?>
    <link rel="shortcut icon" href="<?= get_config('favicon-back'); ?>" type="image/x-icon">
    <?php load_css(['base_url' => "", 'custom_url' => ""]) ?>
</head>

<body>

    <section class="login-container">

        <article class="form-container">

            <div class="overlay" bg-js="<?= base64_encode(json_encode(get_config('backgrounds'))); ?>">
                <div class="welcome-msg">
                    <?= __(USER_LOGIN_LANG_GROUP, 'WELCOME_MSG'); ?>
                </div>
            </div>

            <div class="login-form">

                <div defauld-show class="logo-header">
                    <img src="<?= get_config('logo'); ?>">
                </div>

                <form defauld-show login-form-js last-uri='<?= $requested_uri; ?>' class="ui form">

                    <div class="field">
                        <label class="text-left"><?= __(USER_LOGIN_LANG_GROUP, 'Usuario'); ?></label>
                        <input type="text" required name='username' placeholder="<?= __(USER_LOGIN_LANG_GROUP, 'correo@dominio.com'); ?>">
                    </div>
                    <br>
                    <div class="field">
                        <label class="text-left"><?= __(USER_LOGIN_LANG_GROUP, 'Contraseña'); ?></label>
                        <input type="password" required name='password' placeholder="**********">
                    </div>

                    <div class="remember-me">
                        <input type="checkbox">
                        <span><?= __(USER_LOGIN_LANG_GROUP, 'Recordarme'); ?></span>
                    </div>

                    <div class="field text-center">
                        <button type="submit" class="ui button"><?= __(USER_LOGIN_LANG_GROUP, 'Ingresar'); ?></button>
                    </div>

                    <div class="field text-center">
                        <button otp-trigger data-url="<?= UserSystemFeaturesController::routeName('generate-otp'); ?>" class="ui button blue"><?= __(USER_LOGIN_LANG_GROUP, 'Contraseña de un uso'); ?></button>
                    </div>
                </form>

                <div show-error class="error-container">
                    <h2><?= __(USER_LOGIN_LANG_GROUP, 'Error al ingresar'); ?></h2>
                    <span title></span>
                    <p message></p>
                    <p bottom-message></p>
                    <button try-again><?= __(USER_LOGIN_LANG_GROUP, 'Intentar nuevamente'); ?></button>
                </div>

                <div class="problems-button">

                    <p>
                        <?= strReplaceTemplate(__(USER_LOGIN_LANG_GROUP, 'NEED_HELP_TO_LOGIN'), [
                            '${ot}' => "<a href='" . get_route('user-problems-list') . "'>",
                            '${ct}' => "</a>",
                        ]); ?>
                    </p>

                    <div show-error class="logo">
                        <img src="<?= get_config('logo'); ?>">
                    </div>

                </div>

                <div class="partners">
                    <img src="<?= get_config('partners'); ?>">
                </div>

            </div>

        </article>

        <div class="platform-name">
            <span><?= mb_strtoupper(get_config('title_app'), 'UTF-8'); ?></span>
            <p><?= __(USER_LOGIN_LANG_GROUP, 'Todos los derechos reservados'); ?> <?= date('Y'); ?></p>
        </div>


    </section>

    <?php load_js(['base_url' => "", 'custom_url' => ""]) ?>

</body>

</html>
