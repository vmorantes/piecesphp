<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
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
</head>

<body>

    <?php ob_start(); ?>

    <section class="container">

        <?= $this->render('usuarios/problems/inc/topbar', [
    'defaultImage' => \PiecesPHP\UserSystem\UserSystemFeaturesRoutes::staticRoute('images/problems/forms/u-error-user.svg'),
    'altImage' => \PiecesPHP\UserSystem\UserSystemFeaturesRoutes::staticRoute('images/problems/forms/u-error-user.svg'),
    'headerText' => 'No recuerdo mi usuario',
    'classesCSS' => "one",
    'active' => false,
]); ?>

        <?= $this->render('usuarios/problems/inc/topbar', [
    'defaultImage' => \PiecesPHP\UserSystem\UserSystemFeaturesRoutes::staticRoute('images/problems/forms/u-error-user.svg'),
    'altImage' => \PiecesPHP\UserSystem\UserSystemFeaturesRoutes::staticRoute('images/problems/forms/u-error-user.svg'),
    'headerText' => 'No recuerdo mi usuario',
    'classesCSS' => "two",
    'active' => false,
]); ?>

        <?= $this->render('usuarios/problems/inc/topbar', [
    'defaultImage' => \PiecesPHP\UserSystem\UserSystemFeaturesRoutes::staticRoute('images/problems/forms/u-error-user.svg'),
    'altImage' => \PiecesPHP\UserSystem\UserSystemFeaturesRoutes::staticRoute('images/problems/forms/u-error-user.svg'),
    'headerText' => 'No recuerdo mi usuario',
    'text' => __(\PiecesPHP\UserSystem\Controllers\UserProblemsController::LANG_GROUP, 'Código incorrecto'),
    'classesCSS' => "four",
    'active' => true,
]); ?>

        <?= $this->render('usuarios/problems/inc/topbar', [
    'defaultImage' => \PiecesPHP\UserSystem\UserSystemFeaturesRoutes::staticRoute('images/problems/forms/u-error-user.svg'),
    'altImage' => \PiecesPHP\UserSystem\UserSystemFeaturesRoutes::staticRoute('images/problems/forms/u-error-user.svg'),
    'headerText' => 'No recuerdo mi usuario',
    'text' => __(\PiecesPHP\UserSystem\Controllers\UserProblemsController::LANG_GROUP, 'Usuario recuperado'),
    'classesCSS' => "five",
    'active' => false,
]); ?>

        <div class="form-container" data-system-mail="<?= \PiecesPHP\Core\ConfigHelpers\MailConfig::getValue('user'); ?>">

            <div message class="ui large text"></div>

            <div recovery>

                <form class="ui form initial">

                    <div class="horizontal-section-form">
                        <div class="ui small primary header number-step">PASO 1</div>
                        <div class="field">
                            <label><?= __(\PiecesPHP\UserSystem\Controllers\UserProblemsController::LANG_GROUP, 'Ingrese su correo electrónico')?></label>
                            <input required type="email" name="username" placeholder="<?= __(\PiecesPHP\UserSystem\Controllers\UserProblemsController::LANG_GROUP, 'name@domain.com')?>">
                        </div>
                    </div>

                    <div class="horizontal-section-buttons">
                        <div class="field buttons ui right aligned flex">
                                <a href="#" class="ui basic button" has-code><?= __(\PiecesPHP\UserSystem\Controllers\UserProblemsController::LANG_GROUP, 'Ya tengo un código')?></a>
                                <a href="<?= \PiecesPHP\UserSystem\Controllers\UserProblemsController::routeName('problems-list'); ?>" class="ui basic button back-color">
                                    <?= __(\PiecesPHP\UserSystem\Controllers\UserProblemsController::LANG_GROUP, 'Atrás')?>
                                </a>
                                <button type="submit" class="ui primary button custom-color">
                                    <?= __(\PiecesPHP\UserSystem\Controllers\UserProblemsController::LANG_GROUP, 'Siguiente')?>
                                </button>
                        </div>
                    </div>

                </form>

            </div>

            <div code>

                <form class="ui form initial">

                    <div class="horizontal-section-form">
                        <div class="ui small primary header number-step">PASO 2</div>
                        <div class="field">
                            <input required type="text" name="code" placeholder="Ingrese el codigó">
                        </div>
                    </div>

                    <div class="horizontal-section-buttons">
                        <div class="field buttons ui right aligned flex">
                            <div class="field">
                                <a href="#" class="ui basic button back-color" repeat><?= __(\PiecesPHP\UserSystem\Controllers\UserProblemsController::LANG_GROUP, 'Introducir un email diferente')?></a>
                                <button type="submit" class="ui primary button custom-color"><?= __(\PiecesPHP\UserSystem\Controllers\UserProblemsController::LANG_GROUP, 'Enviar')?></button>
                            </div>
                        </div>
                    </div>

                </form>

            </div>

            <div error>

                <form class="ui form initial">
                    <div class="two fields">
                        <div class="field">
                            <a href="#" class="ui basic button back-color" repeat><?= __(\PiecesPHP\UserSystem\Controllers\UserProblemsController::LANG_GROUP, 'Atrás')?></a>
                        </div>
                        <div class="field">
                            <a href="<?= \PiecesPHP\UserSystem\Controllers\UserProblemsController::routeName('other-problems-form'); ?>" class="ui primary button custom-color">
                                <?= __(\PiecesPHP\UserSystem\Controllers\UserProblemsController::LANG_GROUP, 'Solicitud de soporte')?>
                            </a>
                        </div>
                    </div>
                </form>

            </div>

            <div finish>

                <form class="ui form initial">
                    <div class="field">
                        <a href="<?= \PiecesPHP\UserSystem\Controllers\UsersController::routeName('form-login'); ?>" class="ui primary button custom-color">
                            <?= __(\PiecesPHP\UserSystem\Controllers\UserProblemsController::LANG_GROUP, 'Ingresar')?>
                        </a>
                    </div>
                </form>

            </div>

        </div>

    </section>

    <?php $accessContent = (string) ob_get_clean(); require dirname(__DIR__) . '/inc/access-frame.php'; ?>

    <?php load_js(['base_url' => "", 'custom_url' => ""])?>
</body>

</html>
