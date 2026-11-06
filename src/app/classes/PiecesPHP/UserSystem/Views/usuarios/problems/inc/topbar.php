<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
<?php //La cabecera de un paso: el JS de cada pantalla enseña la del paso en curso por su clase (.topbar.one, .two…). ?>
<div class="topbar<?= isset($classesCSS) ? ' ' . $classesCSS : ''; ?>">

    <div class="ui spaced flex">
        <a href="<?= \PiecesPHP\UserSystem\Controllers\UserProblemsController::routeName('problems-list'); ?>" class="ui basic labeled icon button back-color">
            <i class="arrow left icon"></i>
            <?= __(\PiecesPHP\UserSystem\Controllers\UserProblemsController::LANG_GROUP, 'Atrás'); ?>
        </a>
        <a href="<?=\PiecesPHP\UserSystem\Controllers\UsersController::routeName('form-login')?>" class="ui primary labeled icon button custom-color">
            <i class="icon user"></i>
            <?= __(\PiecesPHP\UserSystem\Controllers\UserProblemsController::LANG_GROUP, 'Iniciar sesión'); ?>
        </a>
    </div>

    <div class="ui center aligned basic segment">
        <img class="ui tiny centered image" src="<?= isset($active) && $active ? $altImage : $defaultImage; ?>" alt="">
        <?php if (isset($headerText) && $headerText !== ''): ?>
        <h1 class="ui header"><?= $headerText; ?></h1>
        <?php endif; ?>
        <?php if (isset($text) && $text !== ''): ?>
        <p class="ui large text"><?= $text; ?></p>
        <?php endif; ?>
    </div>

</div>
