<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$standalone = isset($standalone) && is_bool($standalone) ? $standalone : true;
$submitButtonText = isset($submitButtonText) ? $submitButtonText : __($langGroup, 'Guardar');
?>
<?php if($standalone): ?>
<section class="module-view-container limit-size">
    <?php endif; ?>

    <?php if($standalone): ?>
    <div class="header-options">

        <div class="main-options">

            <a href="<?= $backLink; ?>" class="ui icon button brand-color alt2" title="<?= __($langGroup, 'Regresar'); ?>">
                <i class="icon left arrow"></i>
            </a>

        </div>

        <div class="columns">

            <div class="column">

                <div class="section-title">
                    <div class="title"><?= $title; ?></div>
                    <div class="subtitle"><?= __($langGroup, 'Agregar'); ?></div>
                </div>

            </div>

        </div>

    </div>
    <?php endif; ?>

    <div class="container-standard-form <?= !$standalone ? 'max-w-1200' : ''; ?>">
        <form method='POST' action="<?= $action; ?>" class="ui form" document-form>

            <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_lang(); ?>">

            <div class="fields">

                <div class="ten wide field">

                    <div class="field">
                        <h4 class="ui dividing header alt"><?= __($langGroup, 'Carga de documentos'); ?></h4>
                        <br>
                    </div>

                    <div class="two fields">

                        <div class="field required">
                            <label><?= __($langGroup, 'Tipo de documento'); ?></label>
                            <select required name="documentType" class="ui dropdown search"><?= $documentTypes; ?></select>
                        </div>

                        <div class="field required">
                            <label><?= __($langGroup, 'Nombre del documento'); ?></label>
                            <input type="text" name="documentName" required>
                        </div>

                    </div>

                    <div class="field required" document>
                        <br>
                        <label><?= __($langGroup, 'Documento'); ?></label>
                        <?php simpleUploadPlaceholderWorkSpace([
                            'onlyButton' => false,
                            'inputNameAttr' => 'document',
                            'buttonText' =>   __($langGroup, 'Agregar documento'),
                            'required' =>  true,
                            'multiple' =>  false,
                            'icon' => 'file outline',
                        ]); ?>
                        <br>
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Descripción'); ?></label>
                        <textarea name="description"></textarea>
                    </div>

                </div>

                <div class="six wide field">

                    <h4 class="ui dividing header"><?= __($langGroup, 'Imagen'); ?></h4>

                    <div class="ui form cropper-adapter" document-image-main>

                        <div class="field">
                            <label style="display: none;"><?= __($langGroup, 'Imagen'); ?></label>
                            <input type="file" accept="image/*">
                            <em><?= __($langGroup, 'Tamaño de la imagen 300x300 (px)'); ?></em>
                        </div>

                        <?php $this->helpController->_render('panel/built-in/utilities/cropper/workspace.php', [
                            'referenceW'=> '300',
                            'referenceH'=> '300',
                        ]); ?>

                    </div>
                </div>

            </div>

            <div class="field">
                <button class="ui button brand-color" type="submit"><?= $submitButtonText; ?></button>
            </div>

        </form>
    </div>
    <?php if($standalone): ?>
</section>
<?php endif; ?>
