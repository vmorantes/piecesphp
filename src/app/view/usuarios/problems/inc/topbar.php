<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
<div class="topbar<?= isset($classesCSS) ? ' ' . $classesCSS : ''; ?>">

    <div class="header">

        <a href="<?= get_route('user-problems-list'); ?>" class="ui button back-color icon">
            <i class="arrow left icon"></i>
            <span class="ml-8">Atrás</span>
        </a>

        <div class="back">

            <a href="<?=get_route('users-form-login')?>" class="ui button custom-color icon">
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

            <div class="title-step">
                <?= isset($headerText) ? $headerText : ''; ?>
            </div>

            <div class="text">
                <?= isset($text) ? $text : ''; ?>
            </div>

        </div>

    </div>

</div>
