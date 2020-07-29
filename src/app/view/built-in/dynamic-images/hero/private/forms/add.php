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

        <?php foreach(get_config('allowed_langs') as $lang): ?>

        <div class="field">
            <label><?= __($langGroup, 'Título'); ?> (<?= $lang; ?>)</label>
            <input type="text" name="title[<?= $lang; ?>]" maxlength="100">
        </div>

        <?php endforeach; ?>

        <?php foreach(get_config('allowed_langs') as $lang): ?>

        <div class="field">
            <label><?= __($langGroup, 'Descripción'); ?> (<?= $lang; ?>)</label>
            <textarea name="description[<?= $lang; ?>]" maxlength="230"></textarea>
        </div>

        <?php endforeach; ?>

        <?php foreach(get_config('allowed_langs') as $lang): ?>

        <div class="field">
            <label><?= __($langGroup, 'Enlace'); ?> (<?= $lang; ?>)</label>
            <input type="text" name="link[<?= $lang; ?>]">
        </div>

        <?php endforeach; ?>

        <?php foreach(get_config('allowed_langs') as $lang): ?>

        <br>

        <div class="ui form cropper-adapter" cropper-adapter data-lang="<?= $lang; ?>">

            <div class="field <?= $lang == get_config('default_lang') ? 'required' : ''; ?>">
                <label><?= __($langGroup, 'Imagen'); ?> (<?= $lang; ?>)</label>
                <input <?= $lang == get_config('default_lang') ? 'required' : ''; ?> type="file" accept="image/*">
            </div>

            <?php $this->_render('panel/built-in/utilities/cropper/workspace.php', [
				'referenceW' => '1400',
				'referenceH' => '700',
			]); ?>

        </div>

        <?php endforeach; ?>

        <br>

        <?php if(\PiecesPHP\BuiltIn\DynamicImages\Informative\Mappers\ImageMapper::jsonExtractExistsMySQL()): ?>
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
        <?php else: ?>
        <input type="hidden" name="order" value="0">
        <input type="hidden" name="start_date">
        <input type="hidden" name="end_date">
        <?php endif;?>

        <div class="field">
            <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
        </div>

    </form>
</div>
