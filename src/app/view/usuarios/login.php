<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <base href="<?=baseurl();?>">
    <title><?=get_title();?></title>
    <link rel="shortcut icon" href="<?= get_config('favicon'); ?>" type="image/x-icon">
    <?php load_css(['base_url' => "", 'custom_url' => ""]) ?>
</head>

<body>

    <section class="container" bg-js="<?= base64_encode(json_encode(get_config('backgrounds'))); ?>">

        <article class="form-container">

            <div class="problems-message-container">

                <div class="content">

                    <div class="title">
                        <span class="text"></span> <span class="mark"></span>
                    </div>

                    <p class="message"></p>

                    <span class="ui button green retry">
                       <?= __('userLogin', 'Intentar nuevamente'); ?>
                    </span>

                    <p class="message-bottom"></p>

                    <a href="<?=get_route('user-problems-list')?>" class="ui button red labeled icon problem">
                        <i class="question circle outline icon"></i>
                        <?= __('userLogin', '¿Problemas para ingresar?'); ?>
                    </a>

                </div>

            </div>

            <div class="caption">
                <img src="<?=get_config('logo-login');?>">
            </div>

            <div class="content">

                <form login-form-js last-uri='<?= $requested_uri; ?>' class="ui form">

                    <div class="field">
                        <input type="text" required name='username' placeholder="<?= __('userLogin', 'Digita tu nombre de usuario'); ?>">
                    </div>

                    <div class="field">
                        <input type="password" required name='password' placeholder="<?= __('userLogin', 'Digita tu contraseña'); ?>">
                    </div>

                    <div class="field">
                        <button type="submit" class="ui blue fluid button"><?= __('userLogin', 'Ingresar'); ?></button>
                    </div>

                    <div class="field problems-button">
                        <a href="<?=get_route('user-problems-list')?>" class="ui button red labeled icon">
                            <i class="question circle outline icon"></i>
                            <?= __('userLogin', '¿Problemas para ingresar?'); ?>
                        </a>
                    </div>

                </form>

            </div>

            <div class="footer">
                <div class="developer"><?= __('general','Desarrollado por') . ' ' . get_config('developer');?></div>
            </div>

        </article>

    </section>

    <?php load_js(['base_url' => "", 'custom_url' => ""]) ?>

</body>

</html>
