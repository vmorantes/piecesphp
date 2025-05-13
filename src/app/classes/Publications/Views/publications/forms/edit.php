<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use API\APIRoutes;
use PiecesPHP\Core\Config;
use Publications\Mappers\PublicationMapper;
use Publications\Util\AttachmentPackage;
use Publications\Util\FieldTranslationUtility;

/**
 * @var PublicationMapper $element
 */

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 * @var AttachmentPackage[] $attachmentGroup1
 */

$langs = Config::get_allowed_langs();
$baseLang = $element->baseLang;
$currentLang = isset($selectedLang) && is_string($selectedLang) && in_array($selectedLang, $langs) ? $selectedLang : $baseLang;
$langsTabs = [];

//== Ordenar con el idioma base primero ==
//==Se agrega el idioma base
$langsTabs[$baseLang] = __('lang', $baseLang);
//==Se agregan el resto de idiomas ordenados alfabéticamente
$otherLangs = array_filter($langs, function($lang) use ($baseLang) {
    return $lang !== $baseLang;
});
sort($otherLangs);
//==Se mezclan los idiomas
foreach ($otherLangs as $lang) {
    $langsTabs[$lang] = __('lang', $lang);
}

$translatableProperties = $element->getTranslatableProperties();
$fields = array_keys($element->getFields());
$fieldsHandler = [];
foreach ($fields as $fieldName) {
    $fieldsHandler[$fieldName] = new FieldTranslationUtility(
        $element,
        $fieldName,
        $baseLang,
        $currentLang,
        in_array($fieldName, $translatableProperties)
    );
}
$switchFieldsLang = function($lang) use (&$fieldsHandler){
    foreach ($fieldsHandler as $fieldHandler) {
        $fieldsHandler[$fieldHandler->fieldName()]->currentLang($lang);
    }
};
$withAttachments = PublicationMapper::WITH_ATTACHMENTS;
?>

<template variables>
    <var name="baseLang" value="<?= base64_encode($baseLang); ?>"></var>
    <var name="translatableProperties" value="<?= base64_encode(json_encode($translatableProperties)); ?>"></var>
    <var name="translationsLangs" value="<?= base64_encode(json_encode($langsTabs)); ?>"></var>
</template>

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

        <div class="ui pointing secondary menu lang-tabs">
            <?php foreach($langsTabs as $langCode => $langName): ?>
            <?php $activeLang = $currentLang == $langCode; ?>
            <a class="item<?= $activeLang ? ' active' : ''; ?>" data-tab="<?= $langCode; ?>"><?= $langName; ?></a>
            <?php endforeach; ?>
        </div>

        <br>

        <?php foreach($langsTabs as $langCode => $langName): ?>
        <?php $activeLang = $currentLang == $langCode; ?>
        <?php $isBaseLang = $baseLang == $langCode; ?>
        <?php $switchFieldsLang($langCode); ?>
        <div class="ui<?= $activeLang ? ' active' : ''; ?> tab" data-tab="<?= $langCode; ?>" lang-container="<?= $langCode; ?>">

            <div class="tabs-controls">
                <div class="active" data-tab="basic"><?= __($langGroup, 'Datos básicos'); ?></div>
                <div data-tab="images"><?= __($langGroup, 'Imágenes'); ?></div>
                <div data-tab="details" <?= !$isBaseLang ? 'style="display:none;"' : '' ?>><?= __($langGroup, 'Detalles'); ?></div>
                <div data-tab="attachments" style="<?= $withAttachments ? '' : 'display:none;' ?>"><?= __($langGroup, 'Anexos'); ?></div>
                <div data-tab="seo"><?= __($langGroup, 'SEO'); ?></div>
                <?php if(!$isBaseLang && APIRoutes::ENABLE_TRANSLATIONS && get_config('translationAIEnable')):?>
                <button class="ui right labeled icon button brand-color" do-translation from-lang="<?= $baseLang; ?>" to-lang="<?= $langCode; ?>">
                    <?= __($langGroup, 'Traducir'); ?><i class="language icon"></i>
                </button>
                <br><br>
                <?php endif; ?>
            </div>

            <div class="container-standard-form">

                <form method='POST' action="<?= $action; ?>" class="ui form publications">

                    <input type="hidden" name="id" value="<?= $element->id; ?>">
                    <input type="hidden" name="lang" value="<?= $langCode; ?>">

                    <div class="ui tab active" data-tab="basic">

                        <?php $fieldName = 'title'; ?>
                        <?php $fieldHandler = $fieldsHandler[$fieldName]; ?>
                        <div class="field required" translatable="<?= $fieldHandler->isTranslatable(); ?>">
                            <label><?= __($langGroup, 'Nombre'); ?></label>
                            <input required type="text" name="title" maxlength="300" value="<?= $element->getLangData($langCode, 'title', false, ''); ?>" placeholder=" ">
                        </div>

                        <?php $fieldName = 'author'; ?>
                        <?php $fieldHandler = $fieldsHandler[$fieldName]; ?>
                        <div class="field required" translatable="<?= $fieldHandler->isTranslatable(); ?>">
                            <label><?= __($langGroup, 'Autor'); ?></label>
                            <select class="ui dropdown search" name="<?= $fieldName; ?>" data-search-url="<?= $searchUsersURL; ?>" required>
                                <option value="<?= $element->author->id; ?>"><?= $element->author->getFullName(); ?></option>
                            </select>
                        </div>

                        <?php $fieldName = 'publicDate'; ?>
                        <?php $fieldHandler = $fieldsHandler[$fieldName]; ?>
                        <div class="field" calendar-js calendar-type="date" translatable="<?= $fieldHandler->isTranslatable(); ?>">
                            <label><?= __($langGroup, 'Fecha'); ?></label>
                            <input type="text" name="<?= $fieldName; ?>" required autocomplete="off" value="<?= $element->publicDate->format('Y-m-d h:i:s A'); ?>">
                        </div>

                        <div class="fields">
                            <?php $fieldName = 'featured'; ?>
                            <?php $fieldHandler = $fieldsHandler[$fieldName]; ?>
                            <div class="field" translatable="<?= $fieldHandler->isTranslatable(); ?>">
                                <div class="ui toggle checkbox">
                                    <input type="checkbox" name="<?= $fieldName; ?>" value="<?= PublicationMapper::FEATURED; ?>" <?= $element->isFeatured() ? 'checked' : ''; ?>>
                                    <label><?= __($langGroup, 'Destacado'); ?></label>
                                </div>
                            </div>

                            <?php $fieldName = 'status'; ?>
                            <?php $fieldHandler = $fieldsHandler[$fieldName]; ?>
                            <div class="field" translatable="<?= $fieldHandler->isTranslatable(); ?>">
                                <div class="ui toggle checkbox">
                                    <input type="checkbox" name="<?= $fieldName; ?>" value="yes" <?= $element->isDraft() ? 'checked' : ''; ?>>
                                    <label><?= __($langGroup, 'Borrador'); ?></label>
                                </div>
                            </div>
                        </div>

                        <?php $fieldName = 'content'; ?>
                        <?php $fieldHandler = $fieldsHandler[$fieldName]; ?>
                        <div class="field required" translatable="<?= $fieldHandler->isTranslatable(); ?>">
                            <label><?= __($langGroup, 'Contenido'); ?></label>
                            <div rich-editor-adapter-component></div>
                            <textarea name="<?= $fieldName; ?>" required><?= $element->getLangData($langCode, 'content', false, ''); ?></textarea>
                        </div>

                    </div>

                    <div class="ui tab" data-tab="images">

                        <p>
                            <strong><?=__($langGroup, 'Si reemplaza las imágenes, estas solo serán visibles en la publicación de este idioma'); ?></strong>
                        </p>
                        <br>

                        <div class="form-attachments-regular">
                            <?php $fieldName = 'mainImage'; ?>
                            <?php $fieldHandler = $fieldsHandler[$fieldName]; ?>
                            <div class="attach-placeholder main-image-<?= $langCode; ?> required" translatable="<?= $fieldHandler->isTranslatable(); ?>">
                                <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                                <div class="ui top right attached label green">
                                    <i class="paperclip icon"></i>
                                </div>
                                <label for="<?= $uniqueIdentifier; ?>">
                                    <div data-image="<?= $element->getLangData($langCode, $fieldName, false, ''); ?>" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
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
                                <input type="file" accept="image/*" id="<?= $uniqueIdentifier; ?>">
                            </div>

                            <?php $fieldName = 'thumbImage'; ?>
                            <?php $fieldHandler = $fieldsHandler[$fieldName]; ?>
                            <div class="attach-placeholder thumb-image-<?= $langCode; ?> required" translatable="<?= $fieldHandler->isTranslatable(); ?>">
                                <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                                <div class="ui top right attached label green">
                                    <i class="paperclip icon"></i>
                                </div>
                                <label for="<?= $uniqueIdentifier; ?>">
                                    <div data-image="<?= $element->getLangData($langCode, $fieldName, false, ''); ?>" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
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
                                <input type="file" accept="image/*" id="<?= $uniqueIdentifier; ?>">
                            </div>
                        </div>

                    </div>

                    <div class="ui tab" data-tab="details">

                        <?php $fieldName = 'category'; ?>
                        <?php $fieldHandler = $fieldsHandler[$fieldName]; ?>
                        <div class="field required" translatable="<?= $fieldHandler->isTranslatable(); ?>">
                            <label><?= __($langGroup, 'Categorías'); ?></label>
                            <select class="ui dropdown" name="<?= $fieldName; ?>" required>
                                <?= $allCategories; ?>
                            </select>
                        </div>

                        <div class="two fields">

                            <?php $fieldName = 'startDate'; ?>
                            <?php $fieldHandler = $fieldsHandler[$fieldName]; ?>
                            <?php $fieldValue = $element->getLangData($langCode, $fieldName, false, ''); ?>
                            <?php $fieldValue = $fieldValue instanceof \stdClass ? new \DateTime($fieldValue->date) : $fieldValue; ?>
                            <?php $fieldValue = $fieldValue instanceof \DateTime ? $fieldValue->format('Y-m-d h:i:s A') : ''; ?>
                            <div class="field" calendar-group-js-lang-<?= $langCode; ?>='periodo' start translatable="<?= $fieldHandler->isTranslatable(); ?>">
                                <label><?= __($langGroup, 'Iniciar'); ?></label>
                                <input type="text" name="<?= $fieldName; ?>" autocomplete="off" value="<?= $fieldValue; ?>">
                            </div>

                            <?php $fieldName = 'endDate'; ?>
                            <?php $fieldHandler = $fieldsHandler[$fieldName]; ?>
                            <?php $fieldValue = $element->getLangData($langCode, $fieldName, false, ''); ?>
                            <?php $fieldValue = $fieldValue instanceof \stdClass ? new \DateTime($fieldValue->date) : $fieldValue; ?>
                            <?php $fieldValue = $fieldValue instanceof \DateTime ? $fieldValue->format('Y-m-d h:i:s A') : ''; ?>
                            <div class="field" calendar-group-js-lang-<?= $langCode; ?>='periodo' end translatable="<?= $fieldHandler->isTranslatable(); ?>">
                                <label><?= __($langGroup, 'Finalizar'); ?></label>
                                <input type="text" name="<?= $fieldName; ?>" autocomplete="off" value="<?= $fieldValue; ?>">
                            </div>

                        </div>

                    </div>

                    <div class="ui tab" data-tab="seo">

                        <p>
                            <strong><?=__($langGroup, 'Si reemplaza la imagen, esta solo serán visibles en la publicación de este idioma.'); ?></strong>
                        </p>
                        <br>

                        <?php $fieldName = 'ogImage'; ?>
                        <?php $fieldHandler = $fieldsHandler[$fieldName]; ?>
                        <div class="attach-placeholder og-image-<?= $langCode; ?>" translatable="<?= $fieldHandler->isTranslatable(); ?>">
                            <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                            <div class="ui top right attached label green">
                                <i class="paperclip icon"></i>
                            </div>
                            <label for="<?= $uniqueIdentifier; ?>">
                                <div data-image="<?= $element->getLangData($langCode, $fieldName, false, ''); ?>" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
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

                        <?php $fieldName = 'seoDescription'; ?>
                        <?php $fieldHandler = $fieldsHandler[$fieldName]; ?>
                        <div class="field" translatable="<?= $fieldHandler->isTranslatable(); ?>">
                            <label><?= __($langGroup, 'Descripción'); ?></label>
                            <textarea name="<?= $fieldName; ?>"><?= $element->getLangData($langCode, $fieldName, true, ''); ?></textarea>
                        </div>

                    </div>

                    <div class="ui tab" data-tab="attachments" style="<?= $withAttachments ? '' : 'display:none;' ?>">

                        <p>
                            <strong><?=__($langGroup, 'Si reemplaza los anexos, estos solo serán visibles en la publicación de este idioma.'); ?></strong>
                        </p>
                        <br>

                        <div class="two fields">

                            <?php foreach($attachmentGroup1 as $attachmentElement): ?>
                            <?php $attachmentElement->setLang($langCode); ?>
                            <?php $attachmentElement->forceEvaluationMapper(); ?>
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
                                    'required' =>  $attachmentElement->isRequired() && !$attachmentElement->hasAttachment(),
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
                        <div class="ui buttons">
                            <button type="submit" class="ui button brand-color" save><?= __($langGroup, 'Guardar'); ?></button>
                            <?php if($allowDelete): ?>
                            <button type="submit" class="ui button brand-color alt2" delete-publication-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
                            <?php endif; ?>
                        </div>
                    </div>

                </form>

            </div>

        </div>

        <?php endforeach; ?>

    </div>

</section>

<?php

    $croppers = [];

    foreach($langsTabs as $langCode => $langName){

        $croppers  = array_merge($croppers, [
            [
                'id' => 'main-image-' . $langCode,
                'cropperOptions' => [
                    'type' => 'image/*',
                    'required' => false,
                    'referenceW' => '800',
                    'referenceH' => '600',
                    'image' => $element->getLangData($langCode, 'mainImage'),
                    'imageName' => $element->getLangData($langCode, 'mainImage', false, null) === null ? 'image_' . str_replace('.', '', uniqid('', true)) : '',
                ],
            ],
            [
                'id' => 'thumb-image-' . $langCode,
                'cropperOptions' => [
                    'type' => 'image/*',
                    'required' => false,
                    'referenceW' => '400',
                    'referenceH' => '300',
                    'image' => $element->getLangData($langCode, 'thumbImage'),
                    'imageName' => $element->getLangData($langCode, 'thumbImage', false, null) === null ? 'image_' . str_replace('.', '', uniqid('', true)) : '',
                ],
            ],
            [
                'id' => 'og-image-' . $langCode,
                'cropperOptions' => [
                    'type' => 'image/*',
                    'required' => false,
                    'referenceW' => '1200',
                    'referenceH' => '600',
                    'image' => $element->getLangData($langCode, 'ogImage'),
                    'imageName' => $element->getLangData($langCode, 'ogImage', false, null) === null ? 'image_' . str_replace('.', '', uniqid('', true)) : '',
                ],
            ],
        ]);
        
    }

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