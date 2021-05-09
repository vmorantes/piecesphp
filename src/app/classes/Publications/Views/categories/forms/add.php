<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */;
$langGroup;
$backLink;
$action;

?>

<div>

    <div class="ui buttons">

        <a href="<?= $backLink; ?>" class="ui labeled icon button">
            <i class="icon left arrow"></i>
            <?= __($langGroup, 'Regresar'); ?>
        </a>

    </div>

    <br><br>

    <h3 class="title-form"><?= __($langGroup, 'Agregar'); ?>
        <?= $title; ?>
    </h3>

    <form method='POST' action="<?= $action; ?>" class="ui form publications-categories standard-form">

        <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_lang(); ?>">

        <div class="field required">
            <label><?= __($langGroup, 'Nombre'); ?></label>
            <input required type="text" name="name" maxlength="300">
        </div>

        <div class="field">
            <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
        </div>

    </form>
</div>
