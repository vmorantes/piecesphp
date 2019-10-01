<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<div style="max-width:850px;">

    <h3>Agregar <?=$title;?></h3>

    <br><br>

    <form pcsphp-articles method='POST' action="<?=$action;?>" class="ui form" quill="<?=$quill_proccesor_link;?>">

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
                <input required type="text" name="title" maxlength="255">
            </div>

            <div class="field required">
                <label>Contenido</label>
                <div quill-editor></div>
                <textarea name="content" required></textarea>
            </div>

        </div>

        <div class="ui bottom attached tab segment active" data-tab='images'>

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

        </div>

        <div class="ui bottom attached tab segment" data-tab='details'>

            <div class="field required">
                <label>Categoría</label>
                <select required class='ui dropdown' name="category"><?=$options_categories;?></select>
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

        </div>

        <div class="ui bottom attached tab segment active" data-tab='seo'>

            <div class="field" cropper-image-og>
                <label>Imagen</label>
                <input type="file" name="image-og" accept="image/*">
                <canvas data-image=''></canvas>
                <br>
                <button class="ui button orange inverted" cut>Vista previa</button>
                <br>
                <div preview></div>
            </div>

            <div class="field">
                <label>Descripción</label>
                <textarea name="seo_description"></textarea>
            </div>

        </div>

    </form>

</div>
