<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
?>
<section class="module-view-container limit-size">

    <?php
        //La cabecera del diseño de contenidos (diseno/01-contenidos.md §2.1); sin frase en el alta.
        $this->helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
        ]);
    ?>

    <form method='POST' action="<?= $action; ?>" class="ui form initial" document-form data-content-form>
        <div class="container-standard-form">

            <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_lang(); ?>">

            <div class="fields">

                <div class="ten wide field">

                    <div class="field">
                        <h4 class="ui dividing header"><?= __($langGroup, 'Carga de documentos'); ?></h4>
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
                        <label><?= __($langGroup, 'Documento'); ?></label>
                        <?php simpleUploadPlaceholderWorkSpace([
                                'onlyButton' => false,
                                'inputNameAttr' => 'document',
                                'buttonText' => __($langGroup, 'Agregar documento'),
                                'required' => true,
                                'multiple' => false,
                                'icon' => 'file outline',
                        ]); ?>
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Descripción'); ?></label>
                        <textarea name="description"></textarea>
                    </div>

                </div>

                <div class="six wide field">

                    <h4 class="ui dividing header"><?= __($langGroup, 'Imagen'); ?></h4>

                    <?php
                        $this->helpController->_render('panel/built-in/content/image-field.php', [
                            'card' => [
                                'id' => 'document-image',
                                'title' => __($langGroup, 'Imagen'),
                                'field' => 'documentImage',
                                'image' => '',
                                'width' => 300,
                                'height' => 300,
                            ],
                        ]);
                    ?>
                </div>

            </div>

        </div>

            <?php
                //La barra al final del formulario (§2.3). utils.js guarda con el submit del form.
                $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Guardar'),
                    'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $backLink, 'icon' => 'times']],
                ]);
            ?>

    </form>

    <?php $this->helpController->_render('panel/built-in/content/image-editor.php'); ?>

</section>
