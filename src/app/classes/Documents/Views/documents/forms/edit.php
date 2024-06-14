<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Documents\Controllers\DocumentsController;
use Documents\Mappers\DocumentsMapper;

/**
 * @var DocumentsMapper $element
 * @var DocumentsController $this
 */
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
?>
<section class="module-view-container limit-size">

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
                    <div class="subtitle"><?= __($langGroup, 'Editar'); ?></div>
                </div>

            </div>

        </div>

    </div>

    <div class="container-standard-form">
        <?php if($manyLangs): ?>
        <div class="ui form">
            <div class="field required">
                <label><?= __($langGroup, 'Idiomas'); ?></label>
                <select required class="ui dropdown search langs">
                    <?= $allowedLangs; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>
        <form method='POST' action="<?= $action; ?>" class="ui form" document-form>

            <input type="hidden" name="id" value="<?= $element->id; ?>">
            <input type="hidden" name="lang" value="<?= $lang; ?>">

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
                            <input type="text" name="documentName" required value="<?= $element->documentName; ?>">
                        </div>

                    </div>

                    <div class="field required" document>
                        <br>
                        <label><?= __($langGroup, 'Documento'); ?></label>

                        <div preview>
                            <a target="_blank" href="<?= $element->document; ?>" class="ui button icon labeled blue">
                                <i class="ui icon download"></i>
                                <?= __($langGroup, 'Ver documento'); ?>
                            </a>
                        </div>

                        <?php simpleUploadPlaceholderWorkSpace([
                            'onlyButton' => true,
                            'inputNameAttr' => 'document',
                            'buttonText' =>   __($langGroup, 'Cambiar documento'),
                            'required' =>  false,
                            'multiple' =>  false,
                            'icon' => 'file outline',
                        ]); ?>
                        <br>
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Descripción'); ?></label>
                        <textarea name="description"><?= $element->description; ?></textarea>
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
                            'image' => $element->getLangData($lang, 'documentImage'),
                            'imageName' => $element->getLangData($lang, 'documentImage', false, null) === null ? 'image_' . str_replace('.', '', uniqid('', true)) : '',
                        ]); ?>

                    </div>
                </div>

            </div>

            <div class="field">
                <div class="ui buttons">
                    <button type="submit" class="ui button brand-color"><?= __($langGroup, 'Guardar'); ?></button>
                    <?php if($allowDelete): ?>
                    <button type="submit" class="ui button brand-color alt2" delete-document-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
                    <?php endif; ?>
                </div>
            </div>

        </form>
    </div>

</section>
