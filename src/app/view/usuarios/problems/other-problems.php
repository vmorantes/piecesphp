<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>
<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <base href="<?=baseurl();?>">
    <title><?=get_title();?></title>
    <link rel="shortcut icon" href="<?= get_config('favicon'); ?>" type="image/x-icon">
    <?php load_css(['base_url' => "", 'custom_url' => ""])?>
</head>

<body>



    <section class="container">

        <div class="topbar">
            <div class="text">
                <?= __('usersProblems', 'Creación de <br> solicitud de <br> soporte'); ?>
            </div>
            <div class="back">
                <a href="<?=get_route('user-problems-list')?>">
                    <?= __('usersProblems', 'Volver atrás'); ?>
                </a>
            </div>
        </div>

        <div class="header one">
            <div class="content">
                <div class="image">
                    <img src="<?= base_url('statics/login-and-recovery/images/problems/support-1.png'); ?>">
                </div>
                <div class="text"><?= __('usersProblems', 'Solicitud de soporte'); ?></div>
            </div>
        </div>

        <div class="header two">
            <div class="content">
                <div class="image">
                    <img src="<?= base_url('statics/login-and-recovery/images/problems/support-2.png'); ?>">
                </div>
                <div class="text"><?= __('usersProblems', 'Su solicitud de soporte <br> ha sido creada'); ?></div>
            </div>
        </div>

        <div class="form-container" data-system-mail="<?= get_config('mail')['user']; ?>">

            <div message class="message"></div>

            <div claim>

                <form class="ui form" style="max-width: 450px; margin:0 auto;">
                    <div class="field required">
                        <input required type="text" name="name" placeholder="<?= __('usersProblems', 'Nombres'); ?>">
                    </div>
                    <div class="field required">
                        <input required type="text" name="lastname" placeholder="<?= __('usersProblems', 'Apellidos'); ?>">
                    </div>
                    <!-- <div class="field required">
                        <input required type="hidden" name="extra[0][display]" value="Otra cosa">
                        <input required type="text" name="extra[0][text]" placeholder="Otra cosa">
                    </div> -->
                    <div class="field required">
                        <input required type="email" name="email" placeholder="<?= __('usersProblems', 'Correo electrónico'); ?>">
                    </div>
                    <div class="field required">
                        <textarea required name="message" placeholder="<?= __('usersProblems', 'Problema presentado'); ?>"></textarea>
                    </div>
                    <div class="field">
                        <button type="submit" class="ui button green fluid"><?= __('usersProblems', 'Enviar'); ?></button>
                    </div>
                </form>

            </div>

            <div finish>

                <form class="ui form">
                    <div class="field">
                        <a href="<?= get_route('users-form-login'); ?>" class="ui button green fuid"><?= __('usersProblems', 'Ingresar'); ?></a>
                    </div>
                </form>

            </div>

        </div>

    </section>

    <?php load_js(['base_url' => "", 'custom_url' => ""])?>
</body>

</html>
