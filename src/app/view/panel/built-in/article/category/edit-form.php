<?php
 defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
 $allowed_langs = get_config('allowed_langs');
 $isFirst = true;
 /**
  * @var PiecesPHP\BuiltIn\Article\Category\Mappers\CategoryMapper $element
  */
  $element;
 ?>

<div style="max-width:850px;">

    <h3><?= __('articlesBackend', 'Editar'); ?> <?= $title; ?></h3>

    <div class="ui buttons">
        <a href="<?= $back_link; ?>" class="ui button blue"><i class="icon left arrow"></i></a>
    </div>

    <br><br>

    <form method='POST' action="<?= $action; ?>" class="ui form category">

        <input type="hidden" name="id" value="<?= $element->id; ?>">

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

			<?php $subElement = $element->getContentByLang($lang); ?>
			<?php $subElementName = !is_null($subElement) ? $subElement->name : ''; ?>
			<?php $subElementDescription = !is_null($subElement) ? $subElement->description : ''; ?>
			<?php $subElementRequired = strlen(trim($subElementName)) > 0;?>
			
			<input type="hidden" name="properties[<?= $lang; ?>][id]" value="<?= !is_null($subElement) ? $subElement->id : ''; ?>">

            <div class="field<?= $subElementRequired ? ' required' : ''; ?>">
                <label><?= __('articlesBackend', 'Nombre'); ?></label>
                <input <?= $subElementRequired ? 'required' : ''; ?> type="text" name="properties[<?= $lang; ?>][name]" maxlength="255" value="<?= htmlentities($subElementName) ?>">
            </div>

            <div class="field">
                <label><?= __('articlesBackend', 'Descripción'); ?></label>
                <input type="text" name="properties[<?= $lang; ?>][description]" value="<?= htmlentities($subElementDescription); ?>">
            </div>

        </div>

        <?php $isFirst = false; ?>

        <?php endforeach; ?>

        <div class="field">
            <button type="submit" class="ui button green"><?= __('articlesBackend', 'Guardar'); ?></button>
        </div>

    </form>

</div>
