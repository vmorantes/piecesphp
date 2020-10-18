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
            'text' => __(\App\Controller\UserProblemsController::LANG_GROUP, 'Paso') . ' 1',
        ]); ?>

        <div class="header">
            <?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Ayuda para ingresar'); ?>
        </div>

        <div class="options">

            <a class="option" href="<?= get_route("user-forget-form"); ?>">
                <div class="image">
                    <img src="<?= baseurl("statics/login-and-recovery/images/problems/forget.svg"); ?>">
                </div>
                <div class="content">
                    <div class="title"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'No recuerdo'); ?></div>
                    <div><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Usuario'); ?></div>
                </div>
            </a>

            <a class="option" href="<?= get_route("recovery-form"); ?>">
                <div class="image">
                    <img src="<?= baseurl("statics/login-and-recovery/images/problems/forget.svg"); ?>">
                </div>
                <div class="content">
                    <div class="title"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'No recuerdo'); ?></div>
                    <div><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Contraseña'); ?></div>
                </div>
            </a>

            <a class="option" href="<?= get_route("user-blocked-form"); ?>">
                <div class="image">
                    <img src="<?= baseurl("statics/login-and-recovery/images/problems/locked.svg"); ?>">
                </div>
                <div class="content">
                    <div class="title"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Usuario bloqueado'); ?></div>
                </div>
            </a>

            <a class="option" href="<?= get_route("other-problems-form"); ?>">
                <div class="image">
                    <img src="<?= baseurl("statics/login-and-recovery/images/problems/another.svg"); ?>">
                </div>
                <div class="content">
                    <div class="title"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Otro problema'); ?></div>
                </div>
            </a>

        </div>

    </section>

    <?php load_js(['base_url' => "", 'custom_url' => ""]) ?>

</body>

</html>
