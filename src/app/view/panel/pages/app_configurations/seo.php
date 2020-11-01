<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>

<div class="ui header"><?=  __($langGroup, 'Ajustes SEO'); ?></div>

<div class="container-seo">

    <form action="<?= $actionURL; ?>" method="POST" class="ui form seo">

        <div class="field">

            <div class="two fields">

                <div class="field required">
                    <label><?= __($langGroup, 'Título del sitio'); ?></label>
                    <input type="text" name="titleApp" value="<?= $titleApp; ?>" placeholder="<?= __($langGroup, 'Nombre'); ?>" required>
                </div>

                <div class="field required">
                    <label><?= __($langGroup, 'Propietario'); ?></label>
                    <input type="text" name="owner" value="<?= $owner; ?>" placeholder="<?= __($langGroup, 'Propietario'); ?>" required>
                </div>

            </div>

        </div>

        <div class="field required">
            <label><?= __($langGroup, 'Descripción'); ?></label>
            <textarea required name="description" placeholder="<?= __($langGroup, 'Descripción de la página.'); ?>" required><?= $description; ?></textarea>
        </div>

        <div class="field">
            <label><?= __($langGroup, 'Palabras clave'); ?></label>
            <select name="keywords[]" multiple class="ui dropdown multiple search selection keywords">
                <?= $keywords; ?>
            </select>
        </div>

        <div class="field">
            <label><?= __($langGroup, 'Scripts adicionales'); ?></label>
            <textarea name="extraScripts" placeholder="<?= __($langGroup, "<script src='ejemplo.js'></script>"); ?>"><?= $extraScripts; ?></textarea>
        </div>

        <div class="field">

            <div class="ui card">

                <div class="content">

                    <div class="ui form cropper-adapter">

                        <input type="file" accept="image/*">
                        <?php 
                            cropperAdapterWorkSpace([
                                'withTitle'=> false,
                                'image'=> $openGraph,
                                'referenceW'=> '1200',
                                'referenceH'=> '630',
                                'cancelButtonText' => null,
                                'saveButtonText' => __($langGroup, 'Seleccionar imagen'),                            
                                'controls' =>[
                                    'rotate' => false, 
                                    'flip' => false, 
                                    'adjust' => false, 
                                ],
                            ]); 
                        ?>
                    </div>

                </div>

                <div class="content">
                    <label class="header"><?= __($langGroup, 'Imagen Open graph'); ?></label>

                    <div class="meta">
                        <span><?= strReplaceTemplate(__($langGroup, 'Tamaño de la imagen {dimensions}'), ['{dimensions}' => "1200x630px",])?></span>
                    </div>

                </div>

                <button class="ui bottom attached button green" type="submit">
                    <?= __($langGroup, 'Guardar imagen'); ?>
                </button>

            </div>

        </div>

    </form>

</div>
