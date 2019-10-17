<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<div style="max-width:850px;">

    <h3><?= __('articles', 'form-Agregar'); ?> <?=$title;?></h3>

    <br><br>

    <form pcsphp-articles method='POST' action="<?=$action;?>" class="ui form" quill="<?=$quill_proccesor_link;?>">

        <div class="ui buttons">
            <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
            <button type="submit" class="ui button green"><?= __('articles', 'form-Guardar'); ?></button>
        </div>

        <div class="ui top attached tabular menu">
            <div class="item active" data-tab="content"><?= __('articles', 'form-Contenido'); ?></div>
            <div class="item" data-tab="images"><?= __('articles', 'form-Imágenes'); ?></div>
            <div class="item" data-tab="details"><?= __('articles', 'form-Detalles'); ?></div>
            <div class="item" data-tab="seo"><?= __('articles', 'form-SEO'); ?></div>
        </div>

        <div class="ui bottom attached tab segment active" data-tab='content'>

            <div class="field required">
                <label><?= __('articles', 'form-Título'); ?></label>
                <input required type="text" name="title" maxlength="255">
            </div>

            <div class="field required">
                <label><?= __('articles', 'form-Contenido'); ?></label>
                <div quill-editor></div>
                <textarea name="content" required></textarea>
            </div>

        </div>

        <div class="ui bottom attached tab segment" data-tab='images'>

            <div class="ui form cropper-adapter" cropper-image-main>

                <div class="field required">
                    <label><?= __('articles', 'form-Imagen principal'); ?></label>
                    <input type="file" accept="image/*" required>
                </div>

                <?php $this->_render('panel/built-in/utilities/cropper/workspace.php', [
					'referenceW'=> '800',
					'referenceH'=> '600',
				]); ?>

            </div>

            <div class="ui form cropper-adapter" cropper-image-thumb>

                <div class="field required">
                    <label><?= __('articles', 'form-Imagen miniatura'); ?></label>
                    <input type="file" accept="image/*" required>
                </div>

                <?php $this->_render('panel/built-in/utilities/cropper/workspace.php', [
					'referenceW'=> '400',
					'referenceH'=> '300',
				]); ?>

            </div>

        </div>

        <div class="ui bottom attached tab segment" data-tab='details'>

            <div class="field required">
                <label><?= __('articles', 'form-Categoría'); ?></label>
                <select required class='ui dropdown' name="category"><?=$options_categories;?></select>
            </div>

            <div class="two fields">

                <div class="field" calendar-group-js='periodo' start>
                    <label><?= __('articles', 'form-Iniciar'); ?></label>
                    <input type="text" name="start_date" autocomplete="off">
                </div>

                <div class="field" calendar-group-js='periodo' end>
                    <label><?= __('articles', 'form-Finalizar'); ?></label>
                    <input type="text" name="end_date" autocomplete="off">
                </div>

            </div>

        </div>

        <div class="ui bottom attached tab segment" data-tab='seo'>

            <div class="ui form cropper-adapter" cropper-image-og>

                <div class="field">
                    <label><?= __('articles', 'form-Imagen'); ?></label>
                    <input type="file" accept="image/*">
                </div>

                <?php $this->_render('panel/built-in/utilities/cropper/workspace.php', [
					'referenceW'=> '1200',
					'referenceH'=> '600',
				]); ?>

            </div>

            <div class="field">
                <label><?= __('articles', 'form-Descripción'); ?></label>
                <textarea name="seo_description"></textarea>
            </div>

        </div>

    </form>

</div>
