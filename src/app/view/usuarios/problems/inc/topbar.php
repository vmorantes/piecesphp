<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
<div class="topbar<?= isset($classesCSS) ? ' ' . $classesCSS : ''; ?>">

    <div class="overlay"></div>

    <div class="header">

        <div class="logo">
            <img src="<?=get_config('white-logo');?>">
        </div>

        <div class="back">

            <a href="<?=get_route('users-form-login')?>" class="ui button white inverted icon">
                <i class="icon user"></i>
                <span><?= __(\App\Controller\UserProblemsController::LANG_GROUP, 'Iniciar sesión'); ?></span>
            </a>

        </div>

    </div>

    <div class="content<?= isset($active) && $active ? ' active' : ''; ?>">

        <div class="caption">

            <div class="image">
                <img class="default" src="<?= $defaultImage; ?>">
                <img class="alt" src="<?= $altImage; ?>">
            </div>

            <div class="text">
                <?= $text; ?>
            </div>

        </div>

    </div>

</div>
