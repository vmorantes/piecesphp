<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
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

    <section class="container">

        <div class="overlay-two"></div>

        <article class="form-container">

            <div class="ui centered middle aligned grid">

                <div class="centered row">
                    <div class="computer only four wide column"></div>
                    <div class="eight wide column">


                        <div class="content">

                            <form login-form-js last-uri='<?= $requested_uri; ?>' class="ui form">
                                <span class="title-bold">BIENVENIDOS A</span><br>
                                <div class="title-app"><?= mb_strtoupper(get_config('title_app'), 'UTF-8'); ?></div>
                                <div class="field">
                                    <label class="text-left"><?= __(USER_LOGIN_LANG_GROUP, 'Usuario'); ?></label>
                                    <input type="text" required name='username' placeholder="<?= __(USER_LOGIN_LANG_GROUP, 'name@domain.com'); ?>">
                                </div>

                                <div class="field">
                                    <label class="text-left"><?= __(USER_LOGIN_LANG_GROUP, 'Contraseña'); ?></label>
                                    <input type="password" required name='password' placeholder="**********">
                                </div>

                                <div class="field text-center">
                                    <button type="submit" class="ui blue button"><?= __(USER_LOGIN_LANG_GROUP, 'Ingresar'); ?></button>
                                </div>

                            </form>

                            <div class="problems-button">

                                <p>¿Necesitas
                                    <a href="<?=get_route('user-problems-list')?>">
                                        <?= __(USER_LOGIN_LANG_GROUP, 'Ayuda para ingresar?'); ?>
                                    </a>
                                </p>

                            </div>

                            <div class="logo-footer">
                                <img class="img-logo-footer" src="<?=get_config('logo');?>">
                            </div>

                        </div>

                    </div>
                </div>
            </div>

            <div class="problems-message-container">
                <div class="ui centered middle aligned grid">
                    <div class="centered row">
                        <div class="computer only four wide column"></div>
                        <div class="eight wide column">
                            <div class="content">

                                <div class="title">
                                    <span class="mark"></span>
                                    <span class="text"></span>
                                </div>

                                <div>
                                    <p class="message"></p>

                                    <span class="ui button blue retry">
                                        <?= __(USER_LOGIN_LANG_GROUP, 'Intentar nuevamente'); ?>
                                    </span>
                                </div>

                                <div>
                                    <p class="message-bottom"></p>

                                    <a href="<?=get_route('user-problems-list')?>" class="ui button red">
                                        <?= __(USER_LOGIN_LANG_GROUP, 'Ayuda para ingresar'); ?>
                                    </a>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </article>

        <div class="overlay" bg-js="<?= base64_encode(json_encode(get_config('backgrounds'))); ?>"></div>

    </section>

    <?php load_js(['base_url' => "", 'custom_url' => ""]) ?>

</body>

</html>
