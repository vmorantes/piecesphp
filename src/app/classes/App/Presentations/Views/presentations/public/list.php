<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 */
$langGroup;
?>

<div style="max-width:850px;">

    <h3>
        <strong><?= $title; ?></strong>
    </h3>

    <div>

        <a href="<?= $backLink; ?>" class="ui labeled icon button red">
            <i class="icon left arrow"></i>
            <?= __($langGroup, 'Regresar'); ?>
        </a>

    </div>

</div>

<br>

<div style="max-width:500px;">

    <form action="<?= $currentURL; ?>" method="GET" class="ui form presentations-filter">

        <h3><small><?= __($langGroup, 'Filtrar por categoría'); ?></small></h3>

        <div class="field">
            <label><?= __($langGroup, 'Categoría'); ?></label>
            <select name="category" class="ui dropdown search">
                <?= $categoriesOptions; ?>
            </select>
        </div>

        <div class="field">
            <button class="ui button red icon" type="submit">
                <?= __($langGroup, 'Filtrar'); ?>
                <i class="ui icon search"></i>
            </button>
            <a href="<?= $currentURL; ?>" class="ui button red inverted icon" type="reset">
                <?= __($langGroup, 'Limpiar'); ?>
                <i class="ui icon trash"></i>
            </a>
        </div>

    </form>

</div>

<br>

<div data-presentation-url="<?= $ajaxURL; ?>">

    <div class="presentations-list">

        <div class="content" app-presentations-js></div>

        <br>
        <span class="ui button red fluid load-more" app-presentations-load-more-js><?= __($langGroup, 'Cargar más'); ?></span>
        <br>

    </div>

</div>
