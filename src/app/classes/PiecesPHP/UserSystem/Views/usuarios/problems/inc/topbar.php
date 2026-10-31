<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
<div class="topbar<?= isset($classesCSS) ? ' ' . $classesCSS : ''; ?>">

    <div class="header">

        <a href="<?= \PiecesPHP\UserSystem\Controllers\UserProblemsController::routeName('problems-list'); ?>" class="ui basic labeled icon button back-color">
            <i class="arrow left icon"></i>
            Atrás
        </a>

        <div class="back">

            <a href="<?=\PiecesPHP\UserSystem\Controllers\UsersController::routeName('form-login')?>" class="ui primary labeled icon button custom-color">
                <i class="icon user"></i>
                <?= __(\PiecesPHP\UserSystem\Controllers\UserProblemsController::LANG_GROUP, 'Iniciar sesión'); ?>
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
