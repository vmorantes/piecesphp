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
                <?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Solución a <br> problemas de <br> ingreso'); ?>
            </div>
            <div class="back">
                <a href="<?=get_route('users-form-login')?>">
                    <?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Volver al login'); ?>
                </a>
            </div>
        </div>

        <div class="header">
            <div class="content">
                <div class="image">
                    <img src="<?= base_url('statics/login-and-recovery/images/problems/notebook.png'); ?>">
                </div>
                <div class="text"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Paso'); ?> 1</div>
            </div>
        </div>

        <div class="options">

            <div class="option">
                <div class="content">
                    <a href="<?= get_route("user-forget-form"); ?>" class="link"></a>
                    <div class="title"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'No recuerdo mi usuario'); ?></div>
                    <div class="subtitle"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'No recuerda cuál fue el usuario asignado'); ?></div>
                </div>
            </div>

            <div class="option">
                <div class="content">
                    <a href="<?= get_route("recovery-form"); ?>" class="link"></a>
                    <div class="title"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'No recuerdo mi contraseña'); ?></div>
                    <div class="subtitle"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'No recuerda la contraseña asignada'); ?></div>
                </div>
            </div>

            <div class="option">
                <div class="content">
                    <a href="<?= get_route("user-blocked-form"); ?>" class="link"></a>
                    <div class="title"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Mi usuario está bloqueado'); ?></div>
                    <div class="subtitle"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Intenta ingresar y aparece el mensaje informando el bloqueo'); ?></div>
                </div>
            </div>

            <div class="option">
                <div class="content">
                    <a href="<?= get_route("other-problems-form"); ?>" class="link"></a>
                    <div class="title"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'No recuerdo mi usuario ni mi contraseña'); ?></div>
                    <div class="subtitle"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'No recuerda cuáles fueron sus usuario y contraseña asignados'); ?></div>
                </div>
            </div>

            <div class="option">
                <div class="content">
                    <a href="<?= get_route("other-problems-form"); ?>" class="link"></a>
                    <div class="title"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'No tengo una cuenta'); ?></div>
                    <div class="subtitle"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'No se encuentra inscrito o tiene problemas al crear el usuario'); ?></div>
                </div>
            </div>

            <div class="option">
                <div class="content">
                    <a href="<?= get_route("other-problems-form"); ?>" class="link"></a>
                    <div class="title"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'No funciona el login'); ?></div>
                    <div class="subtitle"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Intenta ingresar, pero luego de digitar la información no pasa nada'); ?></div>
                </div>
            </div>

        </div>

    </section>

    <?php load_js(['base_url' => "", 'custom_url' => ""]) ?>

</body>

</html>
