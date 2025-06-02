<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use ApplicationCalls\Mappers\ApplicationCallsMapper;
use ApplicationCalls\Util\AttachmentPackage;
use PiecesPHP\Core\Config;
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$langs = Config::get_allowed_langs(false, Config::get_default_lang());
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
            <div data-tab="attachments"><?= __($langGroup, 'Imágenes y anexos'); ?></div>
        </div>

        <form method='POST' action="<?= $action; ?>" class="ui form application-calls">

            <div class="container-standard-form">

                <input type="hidden" name="id" value="<?= $element->id; ?>">
                <input type="hidden" name="lang" value="<?= $selectedLang; ?>">

                <div class="ui tab active" data-tab="basic">

                    <div class="field">
                        <div class="ui stackable grid">
                            <div class="six wide column">
                                <div class="field required">
                                    <label><?= __($langGroup, 'Tipo de convocatoria'); ?></label>
                                    <select class="ui dropdown search" name="contentType" required>
                                        <?= $contentTypesOptions; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="six wide column">
                                <div class="field required">
                                    <label><?= __($langGroup, 'Tipo de contratación'); ?></label>
                                    <select class="ui dropdown search" name="financingType" required>
                                        <?= $financingTypesOptions; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="four wide column">
                                <button type="submit" class="ui right floated button brand-color alt" translate>
                                    <i class="icon world"></i>
                                    <?= __($langGroup, 'Traducir'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <div class="ui stackable grid">
                            <?php foreach($langs as $lang): ?>
                            <div class="eight wide column">
                                <div class="field required">
                                    <label><?= __($langGroup, 'Nombre'); ?> (<?= __('lang', $lang); ?>)</label>
                                    <input required type="text" name="title[<?= $lang; ?>]" maxlength="300" value="<?= $element->getLangData($lang, 'title', false, ''); ?>">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="three fields">
                        <div class="field required">
                            <label><?= __($langGroup, 'Dirigido a'); ?></label>
                            <select class="ui dropdown search multiple" multiple name="targetCountries[]" required>
                                <?= $targetCountriesOptions; ?>
                            </select>
                        </div>
                        <div class="field required">
                            <label><?= __($langGroup, 'Moneda'); ?></label>
                            <select class="ui dropdown search" name="currency" required>
                                <?= $currenciesOptions; ?>
                            </select>
                        </div>
                        <div class="field required">
                            <label><?= __($langGroup, 'Monto'); ?></label>
                            <div class="ui input left icon">
                                <input type="number" step="any" name="amount" required value="<?= $element->getLangData($selectedLang, 'amount', false, ''); ?>">
                                <i class="dollar sign icon"></i>
                            </div>
                        </div>
                    </div>

                    <div class="field required">
                        <label><?= __($langGroup, 'Áreas de investigación'); ?></label>
                        <select class="ui dropdown search multiple" multiple name="interestResearhAreas[]" required>
                            <?= $interestResearchAreasOptions; ?>
                        </select>
                    </div>

                    <div class="field required">
                        <label><?= __($langGroup, 'Instituciones que participan'); ?></label>
                        <select class="ui dropdown search multiple additions" multiple name="participatingInstitutions[]" required>
                            <option value=""><?= __($langGroup, 'Agregar'); ?></option>
                            <?= $participatingInstitutionsOptions; ?>
                        </select>
                    </div>

                    <div class="two fields">
                        <div class="field required">
                            <label><?= __($langGroup, 'Enlace del sitio de postulación'); ?></label>
                            <input required type="url" name="applicationLink" value="<?= $element->getLangData($selectedLang, 'applicationLink', false, ''); ?>">
                        </div>
                        <div class="field">
                            <div class="two fields">

                                <div class="field required" calendar-group-js='periodo' start>
                                    <label><?= __($langGroup, 'Fecha de inicio'); ?></label>
                                    <input required type="text" name="startDate" autocomplete="off" value="<?= $element->startDate->format('Y-m-d h:i:s A'); ?>">
                                </div>

                                <div class="field required" calendar-group-js='periodo' end>
                                    <label><?= __($langGroup, 'Fecha de cierre'); ?></label>
                                    <input required type="text" name="endDate" autocomplete="off" value="<?= $element->endDate->format('Y-m-d h:i:s A'); ?>">
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <div class="ui stackable grid">
                            <?php foreach($langs as $lang): ?>
                            <div class="eight wide column">
                                <div class="field required">
                                    <label><?= __($langGroup, 'Descripción'); ?> (<?= __('lang', $lang); ?>)</label>
                                    <div rich-editor-adapter-component="<?= $lang; ?>"><?= $element->getLangData($lang, 'content', false, ''); ?></div>
                                    <textarea name="content[<?= $lang; ?>]" required><?= $element->getLangData($lang, 'content', false, ''); ?></textarea>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>

                <div class="ui tab" data-tab="attachments">

                    <div class="section-fields-divider">
                        <div class="title s20">
                            <?= __($langGroup, 'Imágenes'); ?>
                        </div>
                    </div>

                    <div class="form-attachments-regular">
                        <div class="attach-placeholder main-image required">
                            <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                            <div class="ui top right attached label green">
                                <i class="paperclip icon"></i>
                            </div>
                            <label for="<?= $uniqueIdentifier; ?>">
                                <div data-image="<?= $element->getLangData($selectedLang, 'mainImage', false, ''); ?>" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
                                    <i class="icon upload"></i>
                                    <div class="caption"><?= __($langGroup, 'Anexar'); ?></div>
                                </div>
                                <div class="text">
                                    <div class="filename"></div>
                                    <div class="header">
                                        <div class="title"><?= __($langGroup, 'Imagen principal'); ?></div>
                                        <div class="meta"><?= __($langGroup, 'Tamaño 1200x675'); ?></div>
                                    </div>
                                    <div class="description"><?= __($langGroup, 'Imagen preferiblemente en formato .jpg'); ?></div>
                                </div>
                            </label>
                            <input type="file" accept="image/*" id="<?= $uniqueIdentifier; ?>">
                        </div>

                        <div class="attach-placeholder thumb-image required">
                            <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                            <div class="ui top right attached label green">
                                <i class="paperclip icon"></i>
                            </div>
                            <label for="<?= $uniqueIdentifier; ?>">
                                <div data-image="<?= $element->getLangData($selectedLang, 'thumbImage', false, ''); ?>" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
                                    <i class="icon upload"></i>
                                    <div class="caption"><?= __($langGroup, 'Anexar'); ?></div>
                                </div>
                                <div class="text">
                                    <div class="filename"></div>
                                    <div class="header">
                                        <div class="title"><?= __($langGroup, 'Imagen miniatura'); ?></div>
                                        <div class="meta"><?= __($langGroup, 'Tamaño 640x640'); ?></div>
                                    </div>
                                    <div class="description"><?= __($langGroup, 'Imagen preferiblemente en formato .jpg'); ?></div>
                                </div>
                            </label>
                            <input type="file" accept="image/*" id="<?= $uniqueIdentifier; ?>">
                        </div>
                    </div>

                    <div class="horizontal-space"></div>

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

                        <?php foreach($element->getAttachmentsByLang($selectedLang, true) as $attachmentMapper): ?>
                        <?php $attachmentElement = new AttachmentPackage($element->id, $attachmentMapper->id, $attachmentMapper->attachmentName, false, $attachmentMapper->lang); ?>
                        <?php $hasAttachment = $attachmentElement->hasAttachment(); ?>
                        <?php $attachmentMapper = $attachmentElement->getMapper(); ?>
                        <?php $fileLocation = $hasAttachment ? $attachmentMapper->fileLocation : ''; ?>
                        <?php $isImage = $hasAttachment ? $attachmentMapper->fileIsImage() : ''; ?>
                        <?php $existingFileAttr = $isImage ? "data-image" : "data-file"; ?>
                        <?php $existingFileAttr = "{$existingFileAttr}='{$fileLocation}'"; ?>
                        <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                        <div class="attach-placeholder" data-dynamic-attachment="<?= $uniqueIdentifier; ?>" data-mapper-id="<?= $attachmentMapper->id; ?>">
                            <div class="ui top right attached label green">
                                <i class="paperclip icon"></i>
                            </div>
                            <label for="<?= $uniqueIdentifier; ?>">
                                <div <?= $existingFileAttr; ?> class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
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
                                        <input type="text" attachment-name value="<?= $attachmentElement->getDisplayName(); ?>" data-file-name="<?= $attachmentElement->getDisplayName(); ?>">
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
                <div class="ui right floated buttons">
                    <button data-tab-related="basic" class="ui button blue" go-to-tab="attachments"><?= __($langGroup, 'Siguiente'); ?></button>
                    <button data-tab-related="attachments" class="ui button blue" go-to-tab="basic"><?= __($langGroup, 'Atrás'); ?></button>
                    <button data-tab-related="attachments" type="submit" class="ui button brand-color" save><?= __($langGroup, 'Guardar'); ?></button>
                    <?php if($allowDelete): ?>
                    <button data-tab-related="attachments" type="submit" class="ui button brand-color alt2" delete-application-call-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
                    <?php endif; ?>
                </div>
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
                'required' => false,
                'referenceW' => '1200',
                'referenceH' => '675',
                'image' => $element->getLangData($selectedLang, 'mainImage'),
                'imageName' => $element->getLangData($selectedLang, 'mainImage', false, null) === null ? 'image_' . str_replace('.', '', uniqid('', true)) : '',
            ],
        ],
        [
            'id' => 'thumb-image',
            'cropperOptions' => [
                'type' => 'image/*',
                'required' => false,
                'referenceW' => '640',
                'referenceH' => '640',
                'image' => $element->getLangData($selectedLang, 'thumbImage'),
                'imageName' => $element->getLangData($selectedLang, 'thumbImage', false, null) === null ? 'image_' . str_replace('.', '', uniqid('', true)) : '',
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