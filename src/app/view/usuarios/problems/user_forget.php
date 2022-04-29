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
            'defaultImage' => baseurl('statics/login-and-recovery/images/problems/forms/u-error-user.svg'),
            'altImage' => baseurl('statics/login-and-recovery/images/problems/forms/u-error-user.svg'),
            'headerText' => 'No recuerdo mi usuario',
            'classesCSS' => "one",
            'active' => false,
        ]); ?>

        <?= $this->render('usuarios/problems/inc/topbar', [
            'defaultImage' => baseurl('statics/login-and-recovery/images/problems/forms/u-error-user.svg'),
            'altImage' => baseurl('statics/login-and-recovery/images/problems/forms/u-error-user.svg'),
            'headerText' => 'No recuerdo mi usuario',
            'classesCSS' => "two",
            'active' => false,
        ]); ?>

        <?= $this->render('usuarios/problems/inc/topbar', [
            'defaultImage' => baseurl('statics/login-and-recovery/images/problems/forms/u-error-user.svg'),
            'altImage' => baseurl('statics/login-and-recovery/images/problems/forms/u-error-user.svg'),
            'headerText' => 'No recuerdo mi usuario',
            'text' => __(\App\Controller\UserProblemsController::LANG_GROUP, 'Correo no registrado'),
            'classesCSS' => "three",
            'active' => true,
        ]); ?>

        <?= $this->render('usuarios/problems/inc/topbar', [
            'defaultImage' => baseurl('statics/login-and-recovery/images/problems/forms/u-error-user.svg'),
            'altImage' => baseurl('statics/login-and-recovery/images/problems/forms/u-error-user.svg'),
            'headerText' => 'No recuerdo mi usuario',
            'text' => __(\App\Controller\UserProblemsController::LANG_GROUP, 'Código incorrecto'),
            'classesCSS' => "four",
            'active' => true,
        ]); ?>

        <?= $this->render('usuarios/problems/inc/topbar', [
            'defaultImage' => baseurl('statics/login-and-recovery/images/problems/forms/u-error-user.svg'),
            'altImage' => baseurl('statics/login-and-recovery/images/problems/forms/u-error-user.svg'),
            'headerText' => 'No recuerdo mi usuario',
            'text' => __(\App\Controller\UserProblemsController::LANG_GROUP, 'Usuario recuperado'),
            'classesCSS' => "five",
            'active' => false,
        ]); ?>

        <div class="form-container" data-system-mail="<?= \PiecesPHP\Core\ConfigHelpers\MailConfig::getValue('user'); ?>">

            <div message class="message"></div>

            <div recovery>

                <form class="ui form">

                    <div class="horizontal-section-form">
                        <div class="number-step">PASO 1</div>
                        <div class="field">
                            <label><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Ingrese su correo electrónico')?></label>
                            <input required type="email" name="username" placeholder="<?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'name@domain.com')?>">
                        </div>
                    </div>

                    <div class="horizontal-section-buttons">
                        <div class="field buttons">
                                <a href="#" class="ui button" has-code><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Ya tengo un código')?></a>
                                <a href="<?= get_route('user-problems-list'); ?>" class="ui button back-color">
                                    <?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Atrás')?>
                                </a>
                                <button type="submit" class="ui button custom-color">
                                    <?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Siguiente')?>
                                </button>
                        </div>
                    </div>

                </form>

            </div>

            <div code>

                <form class="ui form">

                    <div class="horizontal-section-form">
                        <div class="number-step">PASO 2</div>
                        <div class="field">
                            <input required type="text" name="code" placeholder="Ingrese el codigó">
                        </div>
                    </div>

                    <div class="horizontal-section-buttons">
                        <div class="field buttons">
                            <div class="field">
                                <a href="#" class="ui button back-color" repeat><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Introducir un email diferente')?></a>
                                <button type="submit" class="ui button custom-color"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Enviar')?></button>
                            </div>
                        </div>
                    </div>

                </form>

            </div>

            <div error>

                <form class="ui form">
                    <div class="two fields">
                        <div class="field">
                            <a href="#" class="ui button back-color" repeat><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Atrás')?></a>
                        </div>
                        <div class="field">
                            <a href="<?= get_route('other-problems-form'); ?>" class="ui button custom-color">
                                <?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Solicitud de soporte')?>
                            </a>
                        </div>
                    </div>
                </form>

            </div>

            <div finish>

                <form class="ui form">
                    <div class="field">
                        <a href="<?= get_route('users-form-login'); ?>" class="ui button custom-color">
                            <?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Ingresar')?>
                        </a>
                    </div>
                </form>

            </div>

        </div>

        <div class="line-footer"></div>
        <div class="img-logo-footer">
            <img src="<?=get_config('logo');?>" alt="">
        </div>

    </section>

    <?php load_js(['base_url' => "", 'custom_url' => ""])?>
</body>

</html>
