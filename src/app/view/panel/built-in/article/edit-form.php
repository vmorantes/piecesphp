<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var PiecesPHP\BuiltIn\Article\Mappers\ArticleMapper $element
 */
$element;
?>

<div style="max-width:850px;">

    <h3>Editar <?=$title;?></h3>

    <div class="ui buttons">
        <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
    </div>

    <br><br>

    <form pcsphp-articles method='POST' action="<?=$action;?>" class="ui form">

        <input type="hidden" name="id" value="<?=$element->id;?>">

        <div class="field required">
            <label>Título</label>
            <input required type="text" name="title" maxlength="255" value="<?=$element->title;?>">
        </div>

        <div class="field required">
            <label>Categoría</label>
            <select required class='ui dropdown' name="category"><?=$options_categories;?></select>
        </div>

        <div class="field required">
            <label>Contenido</label>
            <div quill-editor><?=$element->content;?></div>
            <textarea name="content" required><?=$element->content;?></textarea>
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

        <div class="field">
            <button type="submit" class="ui button green">Guardar</button>
        </div>

    </form>
</div>

<script>
window.onload = () => {

    let imagenMain = new CropperAdapterComponent({
        minWidth: 800,
        containerSelector: '[cropper-image-main]',
    }, {
        aspectRatio: 4 / 3,
        minCropBoxWidth: 800,
    })

    let imageThumb = new CropperAdapterComponent({
        minWidth: 400,
        containerSelector: '[cropper-image-thumb]',
    }, {
        aspectRatio: 4 / 3,
        minCropBoxWidth: 400,
    })

    let quillAdapter = new QuillAdapterComponent({
        containerSelector: '[quill-editor]',
        textareaTargetSelector: "textarea[name='content']",
        urlProcessImage: "<?=$quill_proccesor_link;?>",
        nameOnRequest: 'image',
    })

    genericFormHandler('[pcsphp-articles]', {
        onSetFormData: function(formData) {
            if (imagenMain.wasChanged()) {
                formData.set('image-main', imagenMain.getFile())
            }
            if (imageThumb.wasChanged()) {
                formData.set('image-thumb', imageThumb.getFile())
            }
            return formData
        }
    })

}
</script>
