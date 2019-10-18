<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
 /**
  * @var PiecesPHP\BuiltIn\Article\Category\Mappers\CategoryMapper $element
  */
  $element;
 ?>

<div style="max-width:850px;">

    <h3><?= __('articlesBackend', 'Editar'); ?> <?= $title; ?></h3>

    <div class="ui buttons">
        <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
    </div>

    <br><br>

	<form pcs-generic-handler-js method='POST' action="<?= $action; ?>" class="ui form category">
	
		<input type="hidden" name="id" value="<?= $element->id; ?>">

        <div class="field required">
            <label><?= __('articlesBackend', 'Nombre'); ?></label>
            <input required type="text" name="name" required maxlength="255" value="<?= $element->name; ?>">
		</div>
		
        <div class="field">
            <label><?= __('articlesBackend', 'Descripción'); ?></label>
            <input type="text" name="description" value="<?= $element->description; ?>">
        </div>

        <div class="field">
            <button type="submit" class="ui button green"><?= __('articlesBackend', 'Guardar'); ?></button>
        </div>

    </form>
</div>
