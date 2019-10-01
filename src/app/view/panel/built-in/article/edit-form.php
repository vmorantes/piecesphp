<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var PiecesPHP\BuiltIn\Article\Mappers\ArticleMapper $element
 */
$element;
?>

<div style="max-width:850px;">

    <h3>Editar <?=$title;?></h3>

    <br><br>

    <form pcsphp-articles method='POST' action="<?=$action;?>" class="ui form" quill="<?=$quill_proccesor_link;?>">

        <input type="hidden" name="id" value="<?=$element->id;?>">

        <div class="ui buttons">
            <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
            <button type="submit" disabled="true" class="ui button green">Guardar</button>
        </div>

        <div class="ui top attached tabular menu">
            <div class="item active" data-tab="content">Contenido</div>
            <div class="item" data-tab="images">Imágenes</div>
            <div class="item" data-tab="details">Detalles</div>
            <div class="item" data-tab="seo">SEO</div>
        </div>

        <div class="ui bottom attached tab segment active" data-tab='content'>

            <div class="field required">
                <label>Título</label>
                <input required type="text" name="title" maxlength="255" value="<?=$element->title;?>">
            </div>

            <div class="field required">
                <label>Contenido</label>
                <div quill-editor><?=$element->content;?></div>
                <textarea name="content" required><?=$element->content;?></textarea>
            </div>

        </div>

        <div class="ui bottom attached tab segment active" data-tab='images'>

            <div class="field" cropper-image-main>
                <label>Imagen principal ( mínimo de 800x600[px] )</label>
                <input type="file" name="image-main" accept="image/*">
                <canvas data-image='<?=$element->meta->imageMain;?>'></canvas>
                <br>
                <button class="ui button orange inverted" cut>Vista previa</button>
                <br>
                <div preview></div>
            </div>

            <div class="field" cropper-image-thumb>
                <label>Imagen miniatura ( mínimo de 400x300[px] )</label>
                <input type="file" name="image-thumb" accept="image/*">
                <canvas data-image='<?=$element->meta->imageThumb;?>'></canvas>
                <br>
                <button class="ui button orange inverted" cut>Vista previa</button>
                <br>
                <div preview></div>
            </div>

        </div>

        <div class="ui bottom attached tab segment" data-tab='details'>

            <div class="field required">
                <label>Categoría</label>
                <select required class='ui dropdown' name="category"><?=$options_categories;?></select>
            </div>

            <div class="two fields">
                <div class="field" calendar-group-js='periodo' start>
                    <label>Iniciar</label>
                    <input type="text" name="start_date" autocomplete="off"
                        value="<?=!is_null($element->start_date) ? $element->start_date->format('Y-m-d H:i') : '';?>">
                </div>
                <div class="field" calendar-group-js='periodo' end>
                    <label>Finalizar</label>
                    <input type="text" name="end_date" autocomplete="off"
                        value="<?=!is_null($element->end_date) ? $element->end_date->format('Y-m-d H:i') : '';?>">
                </div>
            </div>

        </div>

        <div class="ui bottom attached tab segment active" data-tab='seo'>

            <div class="field" cropper-image-og>
                <label>Imagen</label>
                <input type="file" name="image-og" accept="image/*">
                <canvas data-image='<?= isset($element->meta->imageOpenGraph) && !is_null($element->meta->imageOpenGraph) ? $element->meta->imageOpenGraph : ''; ?>'></canvas>
                <br>
                <button class="ui button orange inverted" cut>Vista previa</button>
                <br>
                <div preview></div>
            </div>

            <div class="field">
                <label>Descripción</label>
                <textarea name="seo_description"><?= isset($element->meta->seoDescription) ? $element->meta->seoDescription : ''; ?></textarea>
            </div>

        </div>

    </form>

</div>
