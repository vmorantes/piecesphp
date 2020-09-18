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

        <div class="topbar">
            <div class="text">
                <?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Recuperación de <br> contraseña'); ?>
            </div>
            <div class="back">
                <a href="<?=get_route('user-problems-list')?>">
                    <?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Volver atrás'); ?>
                </a>
            </div>
        </div>

        <div class="header one">
            <div class="content">
                <div class="image">
                    <img src="<?= base_url('statics/login-and-recovery/images/problems/password-block.png'); ?>">
                </div>
                <div class="text"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Paso'); ?> 2</div>
            </div>
        </div>

        <div class="header two">
            <div class="content">
                <div class="image">
                    <img src="<?= base_url('statics/login-and-recovery/images/problems/code-mail.png'); ?>">
                </div>
                <div class="text"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Paso'); ?> 3</div>
            </div>
        </div>

        <div class="header two-two">
            <div class="content">
                <div class="image">
                    <img src="<?= base_url('statics/login-and-recovery/images/problems/password-block.png'); ?>">
                </div>
                <div class="text"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Paso'); ?> 4</div>
            </div>
        </div>

        <div class="header three">
            <div class="content">
                <div class="image">
                    <img src="<?= base_url('statics/login-and-recovery/images/problems/wrong-mail.png'); ?>">
                </div>
                <div class="text"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Correo no registrado'); ?></div>
            </div>
        </div>

        <div class="header four">
            <div class="content">
                <div class="image">
                    <img src="<?= base_url('statics/login-and-recovery/images/problems/wrong-code.png'); ?>">
                </div>
                <div class="text"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Código incorrecto'); ?></div>
            </div>
        </div>

        <div class="header five">
            <div class="content">
                <div class="image">
                    <img src="<?= base_url('statics/login-and-recovery/images/problems/password-unblock.png'); ?>">
                </div>
                <div class="text"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Su contraseña ha sido cambiada'); ?></div>
            </div>
        </div>

        <div class="form-container" data-system-mail="<?= get_config('mail')['user']; ?>">

            <div message class="message"></div>

            <div recovery>

                <form class="ui form">
                    <div class="field required">
                        <input type="email" name="username" placeholder="<?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Digite su correo electrónico'); ?>">
                    </div>
                    <div class="field">
                        <button type="submit" class="ui button green fluid"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Siguiente'); ?></button>
                    </div>
                    <p>
                        <strong>
                            <a href="#" class="ui mini button blue" has-code><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Ya tengo un código'); ?></a>
                        </strong>
                    </p>
                </form>

            </div>

            <div code>

                <form class="ui form">
                    <div class="field required">
                        <input required type="text" name="code" placeholder="######">
                    </div>
                    <div class="field">
                        <button type="submit" class="ui button green fluid"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Siguiente'); ?></button>
                    </div>
                    <p>
                        <strong>
                            <a href="#" class="ui mini button blue" repeat><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Introducir un email diferente'); ?></a>
                        </strong>
                    </p>
                </form>

            </div>

            <div change-password>

                <form class="ui form">
                    <input required type="hidden" name="code">
                    <div class="field required">
                        <input required type="password" name="password" placeholder="<?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Ingrese su nueva contraseña'); ?>">
                    </div>
                    <div class="field required">
                        <input required type="password" name="repassword" placeholder="<?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Confirme su nueva contraseña'); ?>">
                    </div>
                    <div class="field">
                        <button type="submit" class="ui button green fluid"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Restablecer contraseña'); ?></button>
                    </div>
                </form>

            </div>

            <div error>

                <form class="ui form">
                    <div class="two fields">
                        <div class="field">
                            <a href="<?= get_route('other-problems-form'); ?>" class="ui button green"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Solicitud de soporte'); ?></a>
                        </div>
                        <div class="field">
                            <a href="#" class="ui button green" repeat><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Nuevo correo'); ?></a>
                        </div>
                    </div>
                </form>

            </div>

            <div finish>

                <form class="ui form">
                    <div class="field">
                        <a href="<?= get_route('users-form-login'); ?>" class="ui button green fuid"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Ingresar'); ?></a>
                    </div>
                </form>

            </div>

        </div>

    </section>

    <?php load_js(['base_url' => "", 'custom_url' => ""])?>
</body>

</html>
