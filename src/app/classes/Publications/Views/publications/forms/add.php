<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Publications\Mappers\PublicationMapper;
use Publications\Util\AttachmentPackage;

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 * @var AttachmentPackage[] $dynamicAttachments
 */
$withAttachments = PublicationMapper::WITH_ATTACHMENTS;
$langs = \PiecesPHP\Core\Config::get_allowed_langs();
$allowedManyLangs = count($langs) > 1;
?>
<section class="module-view-container">

    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>

    <div class="limiter-content">

        <div class="section-title">
            <div class="title"><?= $title ?></div>
            <?php if(isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
            <div class="description"><?= $description; ?></div>
            <?php endif; ?>
        </div>

        <br>

        <div class="tabs-controls">
            <div class="active" data-tab="basic"><?= __($langGroup, 'Datos básicos'); ?></div>
            <div data-tab="images"><?= __($langGroup, 'Imágenes'); ?></div>
            <div data-tab="details"><?= __($langGroup, 'Detalles'); ?></div>
            <div data-tab="attachments" style="<?= $withAttachments ? '' : 'display:none;' ?>"><?= __($langGroup, 'Anexos'); ?></div>
            <div data-tab="seo"><?= __($langGroup, 'SEO'); ?></div>
        </div>

        <form method='POST' action="<?= $action; ?>" class="ui form publications">

            <div class="container-standard-form">

                <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_lang(); ?>">

                <div class="ui tab active" data-tab="basic">

                    <div class="field required" style="<?= $allowedManyLangs ? '' : 'display:none;'; ?>">
                        <label><?= __('lang', 'Idioma principal'); ?></label>
                        <select class="ui dropdown search" name="baseLang" required>
                            <?= $langsOptions; ?>
                        </select>
                    </div>

                    <div class="field required">
                        <label><?= __($langGroup, 'Nombre'); ?></label>
                        <input required type="text" name="title" maxlength="300" placeholder=" ">
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

                    <div class="fields">
                        <div class="field">
                            <div class="ui toggle checkbox">
                                <input type="checkbox" name="featured" value="<?= PublicationMapper::FEATURED; ?>">
                                <label><?= __($langGroup, 'Destacado'); ?></label>
                            </div>
                        </div>

                        <div class="field">
                            <div class="ui toggle checkbox">
                                <input type="checkbox" name="draft" value="yes">
                                <label><?= __($langGroup, 'Borrador'); ?></label>
                            </div>
                        </div>
                    </div>

                    <div class="field required">
                        <label><?= __($langGroup, 'Contenido'); ?></label>
                        <div rich-editor-adapter-component></div>
                        <textarea name="content" required></textarea>
                    </div>

                </div>

                <div class="ui tab" data-tab="images">

                    <div class="form-attachments-regular">
                        <div class="attach-placeholder main-image required">
                            <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                            <div class="ui top right attached label green">
                                <i class="paperclip icon"></i>
                            </div>
                            <label for="<?= $uniqueIdentifier; ?>">
                                <div data-image="" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
                                    <i class="icon upload"></i>
                                    <div class="caption"><?= __($langGroup, 'Anexar'); ?></div>
                                </div>
                                <div class="text">
                                    <div class="filename"></div>
                                    <div class="header">
                                        <div class="title"><?= __($langGroup, 'Imagen principal'); ?></div>
                                        <div class="meta"><?= __($langGroup, 'Tamaño 800x600'); ?></div>
                                    </div>
                                    <div class="description"><?= __($langGroup, 'Imagen preferiblemente en formato .jpg'); ?></div>
                                </div>
                            </label>
                            <input required type="file" accept="image/*" id="<?= $uniqueIdentifier; ?>">
                        </div>

                        <div class="attach-placeholder thumb-image required">
                            <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                            <div class="ui top right attached label green">
                                <i class="paperclip icon"></i>
                            </div>
                            <label for="<?= $uniqueIdentifier; ?>">
                                <div data-image="" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
                                    <i class="icon upload"></i>
                                    <div class="caption"><?= __($langGroup, 'Anexar'); ?></div>
                                </div>
                                <div class="text">
                                    <div class="filename"></div>
                                    <div class="header">
                                        <div class="title"><?= __($langGroup, 'Imagen miniatura'); ?></div>
                                        <div class="meta"><?= __($langGroup, 'Tamaño 400x300'); ?></div>
                                    </div>
                                    <div class="description"><?= __($langGroup, 'Imagen preferiblemente en formato .jpg'); ?></div>
                                </div>
                            </label>
                            <input required type="file" accept="image/*" id="<?= $uniqueIdentifier; ?>">
                        </div>
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

                    <div class="attach-placeholder og-image">
                        <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                        <div class="ui top right attached label green">
                            <i class="paperclip icon"></i>
                        </div>
                        <label for="<?= $uniqueIdentifier; ?>">
                            <div data-image="" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
                                <i class="icon upload"></i>
                                <div class="caption"><?= __($langGroup, 'Anexar'); ?></div>
                            </div>
                            <div class="text">
                                <div class="filename"></div>
                                <div class="header">
                                    <div class="title"><?= __($langGroup, 'Imagen'); ?></div>
                                    <div class="meta"><?= __($langGroup, 'Tamaño 1200x600'); ?></div>
                                </div>
                                <div class="description"><?= __($langGroup, 'Imagen preferiblemente en formato .jpg'); ?></div>
                            </div>
                        </label>
                        <input type="file" accept="image/*" id="<?= $uniqueIdentifier; ?>">
                    </div>

                    <br>

                    <div class="field">
                        <label><?= __($langGroup, 'Descripción'); ?></label>
                        <textarea name="seoDescription"></textarea>
                    </div>

                </div>

                <div class="ui tab" data-tab="attachments" style="<?= $withAttachments ? '' : 'display:none;' ?>">

                    <div class="section-fields-divider">
                        <div class="title s20">
                            <?= __($langGroup, 'Adjuntos y/o anexos'); ?>
                        </div>
                    </div>

                    <div class="form-attachments-regular">

                        <?php $uniqueIdentifier = "add-trigger"; ?>
                        <div class="attach-placeholder tall" data-dynamic-attachment="<?= $uniqueIdentifier; ?>">
                            <div class="ui top right attached label green">
                                <i class="paperclip icon"></i>
                            </div>
                            <label for="<?= $uniqueIdentifier; ?>">
                                <div data-image="" class="image mark" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
                                    <i class="icon upload"></i>
                                    <div class="caption"><?= __($langGroup, 'Anexar'); ?></div>
                                </div>
                                <div class="text">
                                    <div class="filename"></div>
                                    <div class="header">
                                        <div class="title"><?= __($langGroup, 'Agregar un nuevo anexo'); ?></div>
                                    </div>
                                    <div class="description"><?= __($langGroup, 'Tamaño máximo del archivo 2MB'); ?></div>
                                </div>
                            </label>
                            <input type="file">
                        </div>

                        <?php foreach($dynamicAttachments as $attachmentElement): ?>
                        <?php $hasAttachment = $attachmentElement->hasAttachment(); ?>
                        <?php $attachmentMapper = $attachmentElement->getMapper(); ?>
                        <?php $fileLocation = $hasAttachment ? $attachmentMapper->fileLocation : ''; ?>
                        <?php $isImage = $hasAttachment ? $attachmentMapper->fileIsImage() : ''; ?>
                        <?php $existingFileAttr = $isImage ? "data-image" : "data-file"; ?>
                        <?php $existingFileAttr = "{$existingFileAttr}='{$fileLocation}'"; ?>
                        <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                        <div class="attach-placeholder" data-dynamic-attachment="<?= $uniqueIdentifier; ?>">
                            <div class="ui top right attached label green">
                                <i class="paperclip icon"></i>
                            </div>
                            <label <?= $existingFileAttr; ?> for="<?= $uniqueIdentifier; ?>">
                                <div data-image="" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
                                    <i class="icon upload"></i>
                                    <div class="caption"><?= __($langGroup, 'Anexar'); ?></div>
                                </div>
                                <div class="text">
                                    <div class="filename"></div>
                                    <div class="header">
                                        <div class="title"><?= $attachmentElement->getDisplayName(); ?></div>
                                    </div>
                                    <div class="name">
                                        <label><?= __($langGroup, 'Título'); ?></label>
                                        <input type="text" attachment-name value="<?= $attachmentElement->getDisplayName(); ?>">
                                    </div>
                                </div>
                            </label>
                            <input <?= $attachmentElement->isRequired() ? 'required' : ''; ?> type="file" accept="image/*,.pdf" id="<?= $uniqueIdentifier; ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>

                </div>

            </div>

            <br>

            <div class="field">
                <button type="submit" class="ui button brand-color" save><?= __($langGroup, 'Guardar'); ?></button>
                <button type="submit" class="ui button blue" add-translation <?= $allowedManyLangs ? '' : 'style="display:none;"'; ?>><?= __($langGroup, 'Agregar traducción'); ?></button>
            </div>

        </form>

    </div>

</section>

<?php

    $croppers = [
        [
            'id' => 'main-image',
            'cropperOptions' => [
                'type' => 'image/*',
                'required' => true,
                'referenceW' => '800',
                'referenceH' => '600',
                'image' => '',
            ],
        ],
        [
            'id' => 'thumb-image',
            'cropperOptions' => [
                'type' => 'image/*',
                'required' => true,
                'referenceW' => '400',
                'referenceH' => '300',
                'image' => '',
            ],
        ],
        [
            'id' => 'og-image',
            'cropperOptions' => [
                'type' => 'image/*',
                'required' => false,
                'referenceW' => '1200',
                'referenceH' => '600',
                'image' => '',
            ],
        ],
    ];

    foreach ($croppers as $cropperData) {
        $id = $cropperData['id'];
        $cropperOptions = $cropperData['cropperOptions'];
        $cropperOptions['selectorAttr'] = $id;
        modalImageUploaderForCropperAdminViews([
            //El contenido (si se usa simpleCropperAdapterWorkSpace o similar debe ser con el parámetro $echo en false)
            'content' => simpleCropperAdapterWorkSpace($cropperOptions, false),
            //Atributos que se asignarán al modal (el contenedor principal), string
            'modalContainerAttrs' => "modal='{$id}'",
            //Clases que se asignarán al modal (el contenedor principal), string
            'modalContainerClasses' => "ui tiny modal",
            //Atributos que se asignarán al elemento de contenido del modal (modal > .content), string
            'modalContentElementAttrs' => null,
            //Clase por defecto del elemento informativo del modal (donde están el título y la descripcion, por omisión cropper-info-content), string
            'informationContentMainClass' => null,
            //Clases que se asignarán al elemento informativo del modal (donde están el título y la descripcion), string
            'informationContentClasses' => null,
            //Título del modal, string
            'titleModal' => null,
            //Descripción del modal, string
            'descriptionModal' => null,
        ]);
    }

?>
<template attach>
    <div class="attach-placeholder" data-dynamic-attachment="{ID}">
        <div class="ui top right attached label green">
            <i class="paperclip icon"></i>
        </div>
        <label for="{ID}">
            <div data-image="" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
                <i class="icon upload"></i>
                <div class="caption"><?= __($langGroup, 'Anexar'); ?></div>
            </div>
            <div class="text">
                <div class="filename"></div>
                <div class="header">
                    <div class="title"><?= __($langGroup, 'Anexo'); ?> #{NUMBER}</div>
                </div>
                <div class="name">
                    <label><?= __($langGroup, 'Título'); ?></label>
                    <input type="text" attachment-name value="">
                </div>
            </div>
        </label>
        <input type="file" accept="image/*,.pdf" id="{ID}">
    </div>
</template>
