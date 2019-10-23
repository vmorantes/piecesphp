<?php
 defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
 $allowed_langs = get_config('allowed_langs');
 $isFirst = true;
 ?>

<div style="max-width:850px;">

    <h3><?= __('articlesBackend', 'Agregar'); ?> <?= $title; ?></h3>

    <div class="ui buttons">
        <a href="<?= $back_link; ?>" class="ui button blue"><i class="icon left arrow"></i></a>
    </div>

    <br><br>

    <form method='POST' action="<?= $action; ?>" class="ui form category">

        <div class="ui top attached tabular menu">
            <?php foreach($allowed_langs as $lang): ?>

            <?php if($isFirst): ?>

            <div class="item active" data-tab="<?= $lang; ?>"><?= __('lang', $lang); ?></div>

            <?php else: ?>

            <div class="item" data-tab="<?= $lang; ?>"><?= __('lang', $lang); ?></div>

            <?php endif; ?>

            <?php $isFirst = false; ?>

            <?php endforeach; ?>

        </div>

        <?php $isFirst = true; ?>

        <?php foreach($allowed_langs as $lang): ?>

        <div class="ui bottom attached tab segment<?= $isFirst ? ' active' : ''; ?>" data-tab='<?= $lang; ?>'>

            <div class="field">
                <label><?= __('articlesBackend', 'Nombre'); ?></label>
                <input type="text" name="properties[<?= $lang; ?>][name]" maxlength="255">
            </div>

            <div class="field">
                <label><?= __('articlesBackend', 'Descripción'); ?></label>
                <input type="text" name="properties[<?= $lang; ?>][description]">
            </div>

        </div>

        <?php $isFirst = false; ?>

        <?php endforeach; ?>

        <div class="field">
            <button type="submit" class="ui button green"><?= __('articlesBackend', 'Guardar'); ?></button>
        </div>

    </form>

</div>
