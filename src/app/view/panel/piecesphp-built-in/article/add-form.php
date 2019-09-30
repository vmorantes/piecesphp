<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<div style="max-width:850px;">

    <h3>Agregar <?=$title;?></h3>

    <div class="ui buttons">
        <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
    </div>

    <br><br>

    <form pcsphp-articles method='POST' action="<?=$action;?>" class="ui form">

        <div class="field required">
            <label>Título</label>
            <input required type="text" name="title" maxlength="255">
        </div>

        <div class="field required">
            <label>Categoría</label>
            <select required class='ui dropdown' name="category"><?=$options_categories;?></select>
        </div>

        <div class="field required">
            <label>Contenido</label>
            <div quill-editor></div>
            <textarea name="content" required></textarea>
        </div>

        <div class="two fields">
            <div class="field" calendar-group-js='periodo' start>
                <label>Iniciar</label>
                <input type="text" name="start_date" autocomplete="off">
            </div>
            <div class="field" calendar-group-js='periodo' end>
                <label>Finalizar</label>
                <input type="text" name="end_date" autocomplete="off">
            </div>
        </div>

        <div class="field required" cropper-image-main>
            <label>Imagen principal ( mínimo de 800x600[px] )</label>
            <input type="file" name="image-main" accept="image/*" required>
            <canvas data-image=''></canvas>
            <br>
            <button class="ui button orange inverted" cut>Vista previa</button>
            <br>
            <div preview></div>
        </div>

        <div class="field required" cropper-image-thumb>
            <label>Imagen miniatura ( mínimo de 400x300[px] )</label>
            <input type="file" name="image-thumb" accept="image/*" required>
            <canvas data-image=''></canvas>
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
            formData.set('image-main', imagenMain.getFile())
            formData.set('image-thumb', imageThumb.getFile())
            return formData
        }
    })

}
</script>
