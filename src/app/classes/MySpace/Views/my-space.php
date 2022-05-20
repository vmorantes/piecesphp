<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 */
?>

<div class="custom-ribbon wide no-negative">
    <div class="header-area center fluid">
        <div class="title big"><?= __($langGroup, '¡HOLA!'); ?></div>
        <div class="subtitle black big"><?= $subtitle; ?></div>
    </div>
</div>

<div class="my-space-content">
    <div class="column-medium-6"></div>
    <div class="column-medium-6 news-content" data-url="<?= $newsAjaxURL; ?>">

        <div class="title">
            <?= __($langGroup, 'Noticias'); ?>
        </div>

        <div class="non-results-content">
            <div class="title"><?= __($langGroup, 'Ups!'); ?></div>
            <div class="text"><?= __($langGroup, 'En este momento no tenemos noticias'); ?></div>
            <div class="image">
                <img src="statics/images/news/non-results.png" alt="<?= __($langGroup, 'En este momento no tenemos noticias'); ?>">
            </div>
        </div>

        <div class="content"></div>

        <div class="footer">
            <div class="ui button brand-color alt" news-load-more-js>
                <?= __($langGroup, 'Cargar más'); ?>...
            </div>
        </div>

    </div>
</div>
