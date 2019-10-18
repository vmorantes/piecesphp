<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var PiecesPHP\BuiltIn\Article\Mappers\ArticleMapper $element
 */
$element;
?>

<div style="max-width:850px;">

    <h3><?= __('articlesBackend', 'Editar'); ?> <?=$title;?></h3>

    <br><br>

    <form pcsphp-articles method='POST' action="<?=$action;?>" class="ui form" quill="<?=$quill_proccesor_link;?>">

        <input type="hidden" name="id" value="<?=$element->id;?>">

        <div class="ui buttons">
            <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
            <button type="submit" class="ui button green"><?= __('articlesBackend', 'Guardar'); ?></button>
        </div>

        <div class="ui top attached tabular menu">
            <div class="item active" data-tab="content"><?= __('articlesBackend', 'Contenido'); ?></div>
            <div class="item" data-tab="images"><?= __('articlesBackend', 'Imágenes'); ?></div>
            <div class="item" data-tab="details"><?= __('articlesBackend', 'Detalles'); ?></div>
            <div class="item" data-tab="seo"><?= __('articlesBackend', 'SEO'); ?></div>
        </div>

        <div class="ui bottom attached tab segment active" data-tab='content'>

            <div class="field required">
                <label><?= __('articlesBackend', 'Título'); ?></label>
                <input required type="text" name="title" maxlength="255" value="<?=$element->title;?>">
            </div>

            <div class="field required">
                <label><?= __('articlesBackend', 'Contenido'); ?></label>
                <div quill-editor><?=$element->content;?></div>
                <textarea name="content" required><?=$element->content;?></textarea>
            </div>

        </div>

        <div class="ui bottom attached tab segment" data-tab='images'>

            <div class="ui form cropper-adapter" cropper-image-main>

                <div class="field">
                    <label><?= __('articlesBackend', 'Imagen principal'); ?></label>
                    <input type="file" accept="image/*">
                </div>

                <?php $this->_render('panel/built-in/utilities/cropper/workspace.php', [
					'referenceW'=> '800',
					'referenceH'=> '600',
					'image' => $element->meta->imageMain,
				]); ?>

            </div>

            <div class="ui form cropper-adapter" cropper-image-thumb>

                <div class="field">
                    <label><?= __('articlesBackend', 'Imagen miniatura'); ?></label>
                    <input type="file" accept="image/*">
                </div>

                <?php $this->_render('panel/built-in/utilities/cropper/workspace.php', [
					'referenceW'=> '400',
					'referenceH'=> '300',
					'image' => $element->meta->imageThumb,
				]); ?>

            </div>

        </div>

        <div class="ui bottom attached tab segment" data-tab='details'>

            <div class="field required">
                <label><?= __('articlesBackend', 'Categoría'); ?></label>
                <select required class='ui dropdown' name="category"><?=$options_categories;?></select>
            </div>

            <div class="two fields">
                <div class="field" calendar-group-js='periodo' start>
                    <label><?= __('articlesBackend', 'Iniciar'); ?></label>
                    <input type="text" name="start_date" autocomplete="off" value="<?=!is_null($element->start_date) ? $element->start_date->format('Y-m-d H:i') : '';?>">
                </div>
                <div class="field" calendar-group-js='periodo' end>
                    <label><?= __('articlesBackend', 'Finalizar'); ?></label>
                    <input type="text" name="end_date" autocomplete="off" value="<?=!is_null($element->end_date) ? $element->end_date->format('Y-m-d H:i') : '';?>">
                </div>
            </div>

        </div>

        <div class="ui bottom attached tab segment" data-tab='seo'>

            <div class="ui form cropper-adapter" cropper-image-og>

                <div class="field">
                    <label><?= __('articlesBackend', 'Imagen'); ?></label>
                    <input type="file" accept="image/*">
                </div>

                <?php $this->_render('panel/built-in/utilities/cropper/workspace.php', [
					'referenceW'=> '1200',
					'referenceH'=> '600',
					'image' =>
					 isset($element->meta->imageOpenGraph) && !is_null($element->meta->imageOpenGraph) ? 
					 $element->meta->imageOpenGraph : 
					 '',
				]); ?>

            </div>

            <div class="field">
                <label><?= __('articlesBackend', 'Descripción'); ?></label>
                <textarea name="seo_description"><?= isset($element->meta->seoDescription) ? $element->meta->seoDescription : ''; ?></textarea>
            </div>

        </div>

    </form>

</div>
