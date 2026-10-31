<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use API\APIRoutes;
use API\Controllers\APIController;
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
 * @var string $previewLink Vacío si no hay permiso para ver la publicación en el sitio.
 * @var string $deleteRoute
 * @var bool $allowDelete
 */

$langs = Config::get_allowed_langs();
$baseLang = $element->baseLang;
$currentLang = isset($selectedLang) && is_string($selectedLang) && in_array($selectedLang, $langs) ? $selectedLang : $baseLang;
$langsTabs = [];
$allowedManyLangs = count($langs) > 1;
$translationAvailable = API_AI_TRANSLATIONS_ACTIVE && API_TRANSLATION_MODULE && $allowedManyLangs;
//La traducción gasta créditos: el botón y su URL, solo para quien tiene la ruta (P202).
$translationURL = APIController::allowedRoute('translations-actions') && APIController::currentUserCanTranslate() ? (string) APIController::routeName('translations-actions') : '';

//== Ordenar con el idioma base primero ==
//==Se agrega el idioma base
$langsTabs[$baseLang] = __('lang', $baseLang);
//==Se agregan el resto de idiomas ordenados alfabéticamente
$otherLangs = array_filter($langs, function ($lang) use ($baseLang) {
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
$switchFieldsLang = function ($lang) use (&$fieldsHandler) {
    foreach ($fieldsHandler as $fieldHandler) {
        $fieldsHandler[$fieldHandler->fieldName()]->currentLang($lang);
    }
};
$withAttachments = PublicationMapper::WITH_ATTACHMENTS;
?>

<template variables>
    <var name="baseLang" value="<?= base64_encode($baseLang); ?>"></var>
    <var name="translatableProperties" value="<?= base64_encode(json_encode($translatableProperties, \JSON_THROW_ON_ERROR)); ?>"></var>
    <var name="translationsLangs" value="<?= base64_encode(json_encode($langsTabs, \JSON_THROW_ON_ERROR)); ?>"></var>
    <var name="translationURL" value="<?= base64_encode($translationURL); ?>"></var>
</template>

<section class="module-view-container">

    <div class="limiter-content">

        <?php
            //La cabecera del diseño de contenidos (diseno/01-contenidos.md §2.1); sin frase en la edición.
            $this->helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'secondaryActions' => $previewLink !== '' ? [[
                    'text' => __($langGroup, 'Ver en el sitio'),
                    'url' => $previewLink,
                    'icon' => 'external alternate',
                    'attributes' => ['target' => '_blank', 'rel' => 'noopener'],
                ]] : [],
            ]);
        ?>

        <?php if ($allowedManyLangs): ?>
        <div class="ui secondary pointing menu lang-tabs">
            <?php foreach($langsTabs as $langCode => $langName): ?>
            <?php $activeLang = $currentLang == $langCode; ?>
            <a class="item<?= $activeLang ? ' active' : ''; ?>" data-tab="<?= $langCode; ?>"><?= $langName; ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php foreach($langsTabs as $langCode => $langName): ?>
        <?php $activeLang = $currentLang == $langCode; ?>
        <?php $isBaseLang = $baseLang == $langCode; ?>
        <?php $switchFieldsLang($langCode); ?>
        <div class="ui<?= $activeLang ? ' active' : ''; ?> tab" data-tab="<?= $langCode; ?>" lang-container="<?= $langCode; ?>">

            <div class="ui secondary pointing menu tabs-controls content-tabs">
                <div class="active item" data-tab="basic"><?= __($langGroup, 'Datos básicos'); ?></div>
                <div class="item" data-tab="images"><?= __($langGroup, 'Imágenes'); ?></div>
                <?php if ($isBaseLang): ?>
                <div class="item" data-tab="details"><?= __($langGroup, 'Detalles'); ?></div>
                <?php endif; ?>
                <?php if ($withAttachments): ?>
                <div class="item" data-tab="attachments"><?= __($langGroup, 'Anexos'); ?></div>
                <?php endif; ?>
                <div class="item" data-tab="seo"><?= __($langGroup, 'SEO'); ?></div>
                <?php if(!$isBaseLang && APIRoutes::ENABLE_TRANSLATIONS && get_config('translationAIEnable') && $translationAvailable && $translationURL !== ''):?>
                <div class="right menu actions-buttons">
                    <button class="ui right labeled icon primary button" do-translation from-lang="<?= $baseLang; ?>" to-lang="<?= $langCode; ?>">
                        <?= __($langGroup, 'Traducir'); ?><i class="language icon"></i>
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <form method='POST' action="<?= $action; ?>" class="ui form initial publications" data-content-form>

                <div class="container-standard-form">

                    <input type="hidden" name="id" value="<?= $element->id; ?>">
                    <input type="hidden" name="lang" value="<?= $langCode; ?>">

                    <div class="ui tab active" data-tab="basic">

                        <?php $fieldName = 'title'; ?>
                        <?php $fieldHandler = $fieldsHandler[$fieldName]; ?>
                        <div class="field required" translatable="<?= $fieldHandler->isTranslatable(); ?>">
                            <label><?= __($langGroup, 'Nombre'); ?></label>
                            <input required type="text" name="title" maxlength="300" value="<?= escape_html($element->getLangData($langCode, 'title', false, '')); ?>" placeholder=" ">
                        </div>

                        <?php $fieldName = 'author'; ?>
                        <?php $fieldHandler = $fieldsHandler[$fieldName]; ?>
                        <div class="field required" translatable="<?= $fieldHandler->isTranslatable(); ?>">
                            <label><?= __($langGroup, 'Autor'); ?></label>
                            <select class="ui dropdown search" name="<?= $fieldName; ?>" data-search-url="<?= $searchUsersURL; ?>" required>
                                <option value="<?= $element->author->id; ?>"><?= escape_html($element->author->getFullName()); ?></option>
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
                            <?php //Escapado: un </textarea> en el contenido se saldría del campo; el editor lee el .value, que llega decodificado. ?>
                            <textarea name="<?= $fieldName; ?>" required><?= htmlspecialchars((string) $element->getLangData($langCode, 'content', false, ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
                        </div>

                    </div>

                    <div class="ui tab" data-tab="images">

                        <?php if($allowedManyLangs): ?>
                        <p>
                            <strong><?=__($langGroup, 'Si reemplaza las imágenes, estas solo serán visibles en la publicación de este idioma'); ?></strong>
                        </p>
                        <?php endif; ?>

                        <div class="two fields">
                            <?php
                                $this->helpController->_render('panel/built-in/content/image-field.php', [
                                    'attributes' => ['translatable' => (string) $fieldsHandler['mainImage']->isTranslatable()],
                                    'card' => [
                                        'id' => "main-image-{$langCode}",
                                        'title' => __($langGroup, 'Imagen principal'),
                                        'hint' => __($langGroup, 'Imagen preferiblemente en formato .jpg'),
                                        'image' => (string) $element->getLangData($langCode, 'mainImage', false, ''),
                                        'field' => 'mainImage',
                                        'width' => 800,
                                        'height' => 600,
                                    ],
                                ]);
                                $this->helpController->_render('panel/built-in/content/image-field.php', [
                                    'attributes' => ['translatable' => (string) $fieldsHandler['thumbImage']->isTranslatable()],
                                    'card' => [
                                        'id' => "thumb-image-{$langCode}",
                                        'title' => __($langGroup, 'Imagen miniatura'),
                                        'hint' => __($langGroup, 'Imagen preferiblemente en formato .jpg'),
                                        'image' => (string) $element->getLangData($langCode, 'thumbImage', false, ''),
                                        'field' => 'thumbImage',
                                        'width' => 400,
                                        'height' => 300,
                                    ],
                                ]);
                            ?>
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

                        <?php if($allowedManyLangs): ?>
                        <p>
                            <strong><?=__($langGroup, 'Si reemplaza la imagen, esta solo serán visibles en la publicación de este idioma.'); ?></strong>
                        </p>
                        <?php endif; ?>

                        <?php
                            $this->helpController->_render('panel/built-in/content/image-field.php', [
                                'attributes' => ['translatable' => (string) $fieldsHandler['ogImage']->isTranslatable()],
                                'card' => [
                                    'id' => "og-image-{$langCode}",
                                    'title' => __($langGroup, 'Imagen'),
                                    'hint' => __($langGroup, 'Imagen preferiblemente en formato .jpg'),
                                    'image' => (string) $element->getLangData($langCode, 'ogImage', false, ''),
                                    'field' => 'ogImage',
                                    'width' => 1200,
                                    'height' => 600,
                                ],
                            ]);
                        ?>

                        <?php $fieldName = 'seoDescription'; ?>
                        <?php $fieldHandler = $fieldsHandler[$fieldName]; ?>
                        <div class="field" translatable="<?= $fieldHandler->isTranslatable(); ?>">
                            <label><?= __($langGroup, 'Descripción'); ?></label>
                            <textarea name="<?= $fieldName; ?>"><?= htmlspecialchars((string) $element->getLangData($langCode, $fieldName, true, ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
                        </div>

                    </div>

                    <div class="ui tab" data-tab="attachments">

                        <p>
                            <strong><?=__($langGroup, 'Si selecciona los anexos solo serán visibles en la publicación de este idioma.'); ?></strong>
                        </p>

                        <?php $this->helpController->_render('panel/built-in/content/section-header.php', ['title' => __($langGroup, 'Adjuntos y/o anexos')]); ?>

                        <div class="form-attachments-regular">

                            <?php $uniqueIdentifier = "add-trigger"; ?>
                            <div class="attach-placeholder tall" data-dynamic-attachment="<?= $uniqueIdentifier; ?>" data-mappe>
                                <div class="ui top right attached basic green label">
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

                            <?php foreach($element->getAttachmentsByLang($langCode, true) as $attachmentMapper): ?>
                            <?php $attachmentElement = new AttachmentPackage($element->id, $attachmentMapper->id, $attachmentMapper->attachmentName, false, $attachmentMapper->lang); ?>
                            <?php $attachmentMapper = $attachmentElement->getMapper(); ?>
                            <?php $hasAttachment = $attachmentElement->hasAttachment() && $attachmentMapper !== null; ?>
                            <?php $fileLocation = $hasAttachment ? $attachmentMapper->fileLocation : ''; ?>
                            <?php $isImage = $hasAttachment ? $attachmentMapper->fileIsImage() : ''; ?>
                            <?php $existingFileAttr = $isImage ? "data-image" : "data-file"; ?>
                            <?php $existingFileAttr = "{$existingFileAttr}='" . escape_html($fileLocation) . "'"; ?>
                            <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                            <div class="attach-placeholder" data-dynamic-attachment="<?= $uniqueIdentifier; ?>" data-mapper-id="<?= $attachmentMapper !== null ? $attachmentMapper->id : ''; ?>">
                                <div class="ui top right attached basic green label">
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
                                            <div class="title"><?= escape_html($attachmentElement->getDisplayName()); ?></div>
                                        </div>
                                        <div class="name">
                                            <label><?= __($langGroup, 'Título'); ?></label>
                                            <input type="text" attachment-name value="<?= escape_html($attachmentElement->getDisplayName()); ?>" data-file-name="<?= escape_html($attachmentElement->getDisplayName()); ?>">
                                        </div>
                                    </div>
                                </label>
                                <input <?= $attachmentElement->isRequired() ? 'required' : ''; ?> type="file" accept="image/*,.pdf" id="<?= $uniqueIdentifier; ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>

                    </div>

                </div>

                    <?php
                        //La barra fija (§2.3), una por idioma: edit-form.js busca `[lang-container] button[type="submit"][save]`.
                        //Eliminar es `button`: si delete-config.js no cargara, un submit guardaría el formulario.
                        $secondaryActions = [['text' => __($langGroup, 'Cancelar'), 'url' => $backLink, 'icon' => 'times']];
                        if ($allowDelete) {
                            $secondaryActions[] = ['text' => __($langGroup, 'Eliminar'), 'type' => 'button', 'icon' => 'trash alternate', 'danger' => true, 'attributes' => ['delete-publication-button' => '', 'data-route' => $deleteRoute]];
                        }
                        $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
                            'saveText' => __($langGroup, 'Guardar'),
                            'saveAttributes' => ['save' => ''],
                            'secondaryActions' => $secondaryActions,
                        ]);
                    ?>

            </form>

        </div>

        <?php endforeach; ?>

    </div>

</section>

<?php $this->helpController->_render('panel/built-in/content/image-editor.php'); ?>
<template attach>
    <div class="attach-placeholder" data-dynamic-attachment="{ID}">
        <div class="ui top right attached basic green label">
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
