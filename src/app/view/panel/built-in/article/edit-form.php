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
            <button type="submit" class="ui button green">Guardar</button>
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

        <div class="ui bottom attached tab segment" data-tab='images'>

            <div class="ui form cropper-adapter" cropper-image-main>

                <div class="field">
                    <label>Imagen principal</label>
                    <input type="file" accept="image/*">
                </div>

                <div class="preview" w="800">
                    <img src="img-gen/800/600">
                    <button class="ui button blue" type="button" start></button>
                </div>

                <div class="workspace">

                    <div class="steps">

                        <div class="step add">

                            <div class="ui header medium centered">Agregar imagen</div>

                            <div class="placeholder">

                                <div class="content">
                                    <div>
                                        <i class="upload icon"></i>
                                        <button class="ui button blue" type="button" load-image>Seleccionar
                                            imagen</button>
                                    </div>
                                </div>

                            </div>

                        </div>

                        <div class="step edit">

                            <div class="field required">
                                <label>Título de la imagen</label>
                                <input type="text" cropper-title-export>
                            </div>

                            <div class="field">
                                <canvas data-image='<?=$element->meta->imageMain;?>'></canvas>
                            </div>

                        </div>

                    </div>

                    <?php $this->_render('panel/built-in/utilities/cropper/controls.php'); ?>
                    <?php $this->_render('panel/built-in/utilities/cropper/main-buttons.html'); ?>

                </div>

            </div>

            <div class="ui form cropper-adapter" cropper-image-thumb>

                <div class="field">
                    <label>Imagen miniatura</label>
                    <input type="file" accept="image/*">
                </div>

                <div class="preview" w="400">
                    <img src="img-gen/400/300">
                    <button class="ui button blue" type="button" start></button>
                </div>

                <div class="workspace">

                    <div class="steps">

                        <div class="step add">

                            <div class="ui header medium centered">Agregar imagen</div>

                            <div class="placeholder">

                                <div class="content">
                                    <div>
                                        <i class="upload icon"></i>
                                        <button class="ui button blue" type="button" load-image>Seleccionar
                                            imagen</button>
                                    </div>
                                </div>

                            </div>

                        </div>

                        <div class="step edit">

                            <div class="field required">
                                <label>Título de la imagen</label>
                                <input type="text" cropper-title-export>
                            </div>

                            <div class="field">
                                <canvas data-image='<?=$element->meta->imageThumb;?>'></canvas>
                            </div>

                        </div>

                    </div>

                    <?php $this->_render('panel/built-in/utilities/cropper/controls.php'); ?>
                    <?php $this->_render('panel/built-in/utilities/cropper/main-buttons.html'); ?>

                </div>

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

        <div class="ui bottom attached tab segment" data-tab='seo'>

            <div class="ui form cropper-adapter" cropper-image-og>

                <div class="field">
                    <label>Imagen</label>
                    <input type="file" accept="image/*">
                </div>

                <div class="preview" w="1200">
                    <img src="img-gen/1200/600">
                    <button class="ui button blue" type="button" start></button>
                </div>

                <div class="workspace">

                    <div class="steps">

                        <div class="step add">

                            <div class="ui header medium centered">Agregar imagen</div>

                            <div class="placeholder">

                                <div class="content">
                                    <div>
                                        <i class="upload icon"></i>
                                        <button class="ui button blue" type="button" load-image>Seleccionar
                                            imagen</button>
                                    </div>
                                </div>

                            </div>

                        </div>

                        <div class="step edit">

                            <div class="field required">
                                <label>Título de la imagen</label>
                                <input type="text" cropper-title-export>
                            </div>

                            <div class="field">
                                <canvas
                                    data-image='<?= isset($element->meta->imageOpenGraph) && !is_null($element->meta->imageOpenGraph) ? $element->meta->imageOpenGraph : ''; ?>'></canvas>
                            </div>

                        </div>

                    </div>

                    <?php $this->_render('panel/built-in/utilities/cropper/controls.php'); ?>
                    <?php $this->_render('panel/built-in/utilities/cropper/main-buttons.html'); ?>

                </div>

            </div>

            <div class="field">
                <label>Descripción</label>
                <textarea
                    name="seo_description"><?= isset($element->meta->seoDescription) ? $element->meta->seoDescription : ''; ?></textarea>
            </div>

        </div>

    </form>

</div>
