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

<div style="max-width:850px;">

    <h3><?= __($langGroup, 'Agregar'); ?>
        <?= $title; ?>
    </h3>

    <div class="ui buttons">

        <a href="<?= $backLink; ?>" class="ui labeled icon button">
            <i class="icon left arrow"></i>
            <?= __($langGroup, 'Regresar'); ?>
        </a>

    </div>

    <br><br>

    <form method='POST' action="<?= $action; ?>" class="ui form dynamic-images-hero">

        <div class="field required">
            <label><?= __($langGroup, 'Título'); ?></label>
            <input required type="text" name="title" maxlength="300">
        </div>

        <div class="field">
            <label><?= __($langGroup, 'Descripción'); ?></label>
            <textarea name="description"></textarea>
        </div>

        <div class="field">
            <label><?= __($langGroup, 'Enlace'); ?></label>
            <input type="text" name="link">
        </div>

        <div class="ui form cropper-adapter" cropper-main-image>

            <div class="field required">
                <label><?= __($langGroup, 'Imagen'); ?></label>
                <input required type="file" accept="image/*">
            </div>

            <?php $this->_render('panel/built-in/utilities/cropper/workspace.php', [
				'referenceW' => '400',
				'referenceH' => '300',
			]); ?>

        </div>

        <br>

        <div class="field">
            <label><?= __($langGroup, 'Orden'); ?></label>
            <input type="number" name="order" value="0" min="0">
        </div>

        <div class="two fields">
            <div class="field">
                <label><?= __($langGroup, 'Fecha inicial'); ?></label>
                <div calendar-group-js="dates" start>
                    <input type="text" name="start_date">
                </div>
            </div>
            <div class="field">
                <label><?= __($langGroup, 'Fecha final'); ?></label>
                <div calendar-group-js="dates" end>
                    <input type="text" name="end_date">
                </div>
            </div>
        </div>

        <div class="field">
            <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
        </div>

    </form>
</div>
