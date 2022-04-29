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
            'defaultImage' => baseurl('statics/login-and-recovery/images/problems/problem-image-header.svg'),
            'altImage' => baseurl('statics/login-and-recovery/images/problems/problem-image-header.svg'),
            'headerText' => 'Ayuda para ingresar',
        ]); ?>


        <div class="options">

            <a class="option-alt" href="<?= get_route("user-forget-form"); ?>">
                <div class="image">
                    <img src="<?= baseurl("statics/login-and-recovery/images/problems/u-error-user.svg"); ?>">
                </div>
                <div class="content">
                    <div class="title"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Recuperar usuario'); ?></div>
                    <div class="text"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'No recuerdo mi usuario'); ?></div>
                </div>
            </a>

            <a class="option-alt" href="<?= get_route("recovery-form"); ?>">
                <div class="image">
                    <img src="<?= baseurl("statics/login-and-recovery/images/problems/u-error-user.svg"); ?>">
                </div>
                <div class="content">
                    <div class="title"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Restablecer contraseña'); ?></div>
                    <div class="text"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'No recuerdo mi contraseña'); ?></div>
                </div>
            </a>

            <a class="option-alt" href="<?= get_route("user-blocked-form"); ?>">
                <div class="image">
                    <img src="<?= baseurl("statics/login-and-recovery/images/problems/u-error-block.svg"); ?>">
                </div>
                <div class="content">
                    <div class="title"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Usuario bloqueado'); ?></div>
                    <div class="text"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Mi usuario se encuentra bloqueado'); ?></div>
                </div>
            </a>

            <a class="option-alt" href="#" id="modalNoUserButton">
                <div class="image">
                    <img sytle="background-color: #707070" src="<?= baseurl("statics/login-and-recovery/images/problems/u-error-no-user.svg"); ?>">
                </div>
                <div class="content">
                    <div class="title"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'No tengo un usuario'); ?></div>
                    <div class="text"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'No tengo asignado un usuario'); ?></div>
                </div>
            </a>

            <a class="option-alt" href="<?= get_route("other-problems-form"); ?>">
                <div class="image">
                    <img src="<?= baseurl("statics/login-and-recovery/images/problems/u-error-other.svg"); ?>">
                </div>
                <div class="content">
                    <div class="title"><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Otro problema'); ?></div>
                    <br><br>
                </div>
            </a>

        </div>

        <div class="footer-div">
            <div>
                <img src="<?=get_config('logo');?>" alt="">
            </div>
            <div>
                <span class="text-footer"><?= mb_strtoupper(get_config('title_app'), 'UTF-8'); ?></span>
            </div>
        </div>

    </section>

    <?php load_js(['base_url' => "", 'custom_url' => ""]) ?>

    <div class="modal-no-user" id="modalNoUser">
        <div class="container-modals" id="backgrodund-modal">
            <div class="card-ui-center" id="cardModal">
                <div class="title">No tengo un usuario</div>
                <div class="description">
                    <p>La asignación de usuarios es realizada únicamente por <span class="name"><?= get_config('owner'); ?></span>, por favor comuníquese directamente con el área encargada y exponga su caso. Si no recuerda ninguno de sus datos de acceso, pero está seguro de contar con un usuario,
                        por favor envíe su nombre, correo y cargo a la Mesa de Ayuda.
                    </p>
                </div>
                <div class="actions">
                    <div class="btn-help-desk">
                        <a href="<?= get_route("other-problems-form"); ?>">Solicitar soporte</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
