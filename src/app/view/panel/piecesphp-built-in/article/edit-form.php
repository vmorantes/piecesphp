<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
 /**
  * @var PiecesPHP\BuiltIn\Article\Mappers\ArticleMapper $element
  */
  $element;
 ?>

<div style="max-width:850px;">

    <h3>Editar <?= $title; ?></h3>

    <div class="ui buttons">
        <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
    </div>

    <br><br>

    <form pcs-generic-handler-js method='POST' action="<?=$action;?>" class="ui form">

        <input type="hidden" name="id" value="<?= $element->id; ?>">

        <div class="field required">
            <label>Título</label>
            <input type="text" name="title" maxlength="255" value="<?= $element->title; ?>">
        </div>

        <div class="field required">
            <label>Descripción</label>
            <div image-process="<?= get_route('piecesphp-built-in-articles-image-handler')?>" image-name="image"
                rich-editor-js editor-target="[name='content']"><?=$element->content;?></div>
            <textarea name="content" required><?=$element->content;?></textarea>
        </div>

        <div class="two fields">
            <div class="field" calendar-group-js='periodo' start>
                <label>Iniciar</label>
                <input type="text" name="start_date" autocomplete="off"
                    value="<?= !is_null($element->start_date) ? $element->start_date->format('d-m-Y h:i:s') : ''; ?>">
            </div>
            <div class="field" calendar-group-js='periodo' end>
                <label>Finalizar</label>
                <input type="text" name="end_date" autocomplete="off"
                    value="<?= !is_null($element->end_date) ? $element->end_date->format('d-m-Y h:i:s') : ''; ?>">
            </div>
        </div>

        <div class="field">
            <button type="submit" class="ui button green">Guardar</button>
        </div>

    </form>
</div>

<script>
window.onload = () => {

    let sublinesDropdown = $(`.ui.dropdown.multiple`).dropdown()

}
</script>
