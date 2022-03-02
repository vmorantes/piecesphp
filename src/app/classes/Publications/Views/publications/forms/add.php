<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Publications\Mappers\PublicationMapper;
use Publications\Util\AttachmentPackage;

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 * @var AttachmentPackage[] $attachmentGroup1
 */;
$langGroup;
$backLink;
$action;
?>

<div>

    <div class="ui buttons">

        <a href="<?= $backLink; ?>" class="ui labeled icon button">
            <i class="icon left arrow"></i>
            <?= __($langGroup, 'Regresar'); ?>
        </a>

    </div>

    <br><br>

    <h3 class="title-form"><?= __($langGroup, 'Agregar'); ?>
        <?= $title; ?>
    </h3>

    <div class="ui tabular menu">
        <div class="item active" data-tab="basic"><?= __($langGroup, 'Datos básicos'); ?></div>
        <div class="item" data-tab="images"><?= __($langGroup, 'Imágenes'); ?></div>
        <div class="item" data-tab="details"><?= __($langGroup, 'Detalles'); ?></div>
        <div class="item" data-tab="attachments"><?= __($langGroup, 'Anexos'); ?></div>
        <div class="item" data-tab="seo"><?= __($langGroup, 'SEO'); ?></div>
    </div>

    <form method='POST' action="<?= $action; ?>" class="ui form publications standard-form">

        <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_lang(); ?>">

        <div class="ui tab active" data-tab="basic">

            <div class="field required">
                <label><?= __($langGroup, 'Nombre'); ?></label>
                <input required type="text" name="title" maxlength="300">
            </div>

            <div class="field required">
                <label><?= __($langGroup, 'Autor'); ?></label>
                <select class="ui dropdown search" name="author" data-search-url="<?= $searchUsersURL; ?>" required>
                    <option value=""><?= __($langGroup, 'Seleccionar usuario'); ?></option>
                </select>
            </div>

            <div class="field required" calendar-js calendar-type="date">
                <label><?= __($langGroup, 'Fecha'); ?></label>
                <input type="text" name="publicDate" required autocomplete="off" value="<?= date('Y-m-d h:i:s A'); ?>">
            </div>

            <div class="field">
                <div class="ui toggle checkbox">
                    <input type="checkbox" name="featured" value="<?= PublicationMapper::FEATURED; ?>">
                    <label><?= __($langGroup, 'Destacado'); ?></label>
                </div>
            </div>

            <div class="field required">
                <label><?= __($langGroup, 'Contenido'); ?></label>
                <div rich-editor-adapter-component></div>
                <textarea name="content" required></textarea>
            </div>

        </div>

        <div class="ui tab" data-tab="images">

            <div class="ui form cropper-adapter" cropper-image-main>

                <div class="field required">
                    <label><?= __($langGroup, 'Imagen principal'); ?></label>
                    <input type="file" accept="image/*" required>
                </div>

                <?php $this->helpController->_render('panel/built-in/utilities/cropper/workspace.php', [
                    'referenceW'=> '800',
                    'referenceH'=> '600',
                ]); ?>

            </div>

            <div class="ui form cropper-adapter" cropper-image-thumb>

                <div class="field required">
                    <label><?= __($langGroup, 'Imagen miniatura'); ?></label>
                    <input type="file" accept="image/*" required>
                </div>

                <?php $this->helpController->_render('panel/built-in/utilities/cropper/workspace.php', [
                    'referenceW'=> '400',
                    'referenceH'=> '300',
                ]); ?>

            </div>

        </div>

        <div class="ui tab" data-tab="details">

            <div class="field required">
                <label><?= __($langGroup, 'Categorías'); ?></label>
                <select class="ui dropdown" name="category" required>
                    <?= $allCategories; ?>
                </select>
            </div>

            <div class="two fields">

                <div class="field" calendar-group-js='periodo' start>
                    <label><?= __($langGroup, 'Iniciar'); ?></label>
                    <input type="text" name="startDate" autocomplete="off">
                </div>

                <div class="field" calendar-group-js='periodo' end>
                    <label><?= __($langGroup, 'Finalizar'); ?></label>
                    <input type="text" name="endDate" autocomplete="off">
                </div>

            </div>

        </div>

        <div class="ui tab" data-tab="seo">

            <div class="ui form cropper-adapter" cropper-image-og>

                <div class="field">
                    <label><?= __($langGroup, 'Imagen'); ?></label>
                    <input type="file" accept="image/*">
                </div>

                <?php $this->helpController->_render('panel/built-in/utilities/cropper/workspace.php', [
                    'referenceW'=> '1200',
                    'referenceH'=> '600',
                ]); ?>

            </div>

            <br>

            <div class="field">
                <label><?= __($langGroup, 'Descripción'); ?></label>
                <textarea name="seoDescription"></textarea>
            </div>

        </div>

        <div class="ui tab" data-tab="attachments">

            <h4 class="ui dividing header"><?= __($langGroup, 'Anexos'); ?></h4>

            <div class="two fields">

                <?php foreach($attachmentGroup1 as $attachmentElement): ?>

                <div class="field" attachment-element>
                    <label><?= $attachmentElement->getTypeText(); ?></label>
                    <input type="hidden" name="<?= $attachmentElement->baseNameAppend('Type'); ?>" value="<?= $attachmentElement->getType(); ?>">
                    <?php if($attachmentElement->hasAttachment()): ?>
                    <div preview>
                        <?php if(!$attachmentElement->getMapper()->fileIsImage()):?>
                        <a target="_blank" href="<?= $attachmentElement->getMapper()->fileLocation; ?>" class="ui button icon labeled blue">
                            <i class="ui icon download"></i>
                            <?= __($langGroup, 'Ver documento'); ?>
                        </a>
                        <?php else: ?>
                        <img src="<?= $attachmentElement->getMapper()->fileLocation; ?>">
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php simpleUploadPlaceholderWorkSpace([
                        'onlyButton' => $attachmentElement->hasAttachment(),
                        'inputNameAttr' => $attachmentElement->baseNameAppend('File'),
                        'buttonText' => $attachmentElement->hasAttachment() ? __($langGroup, 'Cambiar anexo') :  __($langGroup, 'Agregar anexo'),
                        'required' =>  $attachmentElement->isRequired(),
                        'multiple' =>  $attachmentElement->isMultiple(),
                        'icon' => 'image outline',
                        'accept' => implode(',', $attachmentElement->getExtensions()),
                    ]); ?>
                    <br><br>
                </div>

                <?php endforeach; ?>

            </div>


        </div>

        <br><br>

        <div class="field">
            <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
        </div>

    </form>
</div>
