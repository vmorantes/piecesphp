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

        <?= $this->render('usuarios/problems/inc/topbar', [
            'defaultImage' => base_url('statics/login-and-recovery/images/problems/problems.svg'),
            'altImage' => base_url('statics/login-and-recovery/images/problems/problems-alt.svg'),
            'text' => __(\App\Controller\UserProblemsController::LANG_GROUP, 'Solicitud de soporte'),
            'classesCSS' => "one",
            'active' => false,
        ]); ?>

        <?= $this->render('usuarios/problems/inc/topbar', [
            'defaultImage' => base_url('statics/login-and-recovery/images/problems/problems.svg'),
            'altImage' => base_url('statics/login-and-recovery/images/problems/problems-alt.svg'),
            'text' => __(\App\Controller\UserProblemsController::LANG_GROUP, 'Su solicitud de soporte <br> ha sido creada'),
            'classesCSS' => "two",
            'active' => true,
        ]); ?>

        <div class="form-container" data-system-mail="<?= \PiecesPHP\Core\ConfigHelpers\MailConfig::getValue('user'); ?>">

            <div message class="message"></div>

            <div claim>

                <form class="ui form" style="max-width: 450px; margin:0 auto;">
                    <div class="field required">
                        <input required type="text" name="name" placeholder="<?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Nombres'); ?>">
                    </div>
                    <div class="field required">
                        <input required type="text" name="lastname" placeholder="<?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Apellidos'); ?>">
                    </div>
                    <!-- <div class="field required">
                        <input required type="hidden" name="extra[0][display]" value="Otra cosa">
                        <input required type="text" name="extra[0][text]" placeholder="Otra cosa">
                    </div> -->
                    <div class="field required">
                        <input required type="email" name="email" placeholder="<?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Correo electrónico'); ?>">
                    </div>
                    <div class="field required">
                        <textarea required name="message" placeholder="<?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Problema presentado'); ?>"></textarea>
                    </div>

                    <div class="field buttons">
                        <a href="<?= get_route('user-problems-list'); ?>" class="ui button">
                            <?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Atrás')?>
                        </a>
                        <button type="submit" class="ui button blue">
                            <?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Enviar')?>
                        </button>
                    </div>

                </form>

            </div>

            <div finish>

                <form class="ui form">
                    <div class="field">
                        <a href="<?= get_route('users-form-login'); ?>" class="ui button blue fuid">
                            <?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Ingresar')?>
                        </a>
                    </div>
                </form>

            </div>

        </div>

    </section>

    <?php load_js(['base_url' => "", 'custom_url' => ""])?>
</body>

</html>
