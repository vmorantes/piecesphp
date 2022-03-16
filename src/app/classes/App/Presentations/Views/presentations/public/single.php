<?php

use App\Presentations\Mappers\PresentationMapper;

defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var PresentationMapper $element
 */
?>

<div style="max-width:850px;">

    <div>

        <a href="<?= $backLink; ?>" class="ui labeled icon button red">
            <i class="icon left arrow"></i>
            <?= __($langGroup, 'Regresar'); ?>
        </a>

    </div>

    <br><br>

    <h3>
        <strong><?= $title; ?></strong>
    </h3>

    <h4>
        <strong><?= $titleCategory; ?></strong>
    </h4>

</div>

<div>

    <div class="single-presentation">

        <div class="main">
            <a data-fancybox="images" href="<?= $element->currentLangData('images')[0]; ?>">
                <img src="<?= $element->currentLangData('images')[0]; ?>" />
            </a>
        </div>

        <div class="list">
            <?php for($i = 1; $i < count($element->currentLangData('images')); $i++): ?>
            <a data-fancybox="images" href="<?= $element->currentLangData('images')[$i]; ?>">
                <img src="<?= $element->currentLangData('images')[$i]; ?>" />
            </a>
            <?php endfor;?>
        </div>

    </div>

    <br><br>

    <div data-presentation-url="<?= $ajaxURL; ?>">

        <h4><?= __($langGroup, 'Presentaciones relacionadas'); ?></h4>

        <div class="presentations-list mini">

            <div class="content" app-presentations-js></div>

            <br>
            <span class="ui button red fluid load-more" app-presentations-load-more-js><?= __($langGroup, 'Cargar más'); ?></span>
            <br>

        </div>

    </div>

</div>
