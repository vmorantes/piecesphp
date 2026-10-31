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

    <div class="limiter-content">

        <?php
            //La cabecera del diseño de contenidos (diseno/01-contenidos.md §2.1); sin frase en el alta.
            $this->helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
            ]);
        ?>

        <div class="ui secondary pointing menu tabs-controls content-tabs">
            <div class="active item" data-tab="basic"><?= __($langGroup, 'Datos básicos'); ?></div>
            <div class="item" data-tab="images"><?= __($langGroup, 'Imágenes'); ?></div>
            <div class="item" data-tab="details"><?= __($langGroup, 'Detalles'); ?></div>
            <?php if ($withAttachments): ?>
            <div class="item" data-tab="attachments"><?= __($langGroup, 'Anexos'); ?></div>
            <?php endif; ?>
            <div class="item" data-tab="seo"><?= __($langGroup, 'SEO'); ?></div>
        </div>

        <form method='POST' action="<?= $action; ?>" class="ui form initial publications" data-content-form>

            <div class="container-standard-form">

                <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_lang(); ?>">

                <div class="ui tab active" data-tab="basic">

                    <div class="field required"<?= $allowedManyLangs ? '' : ' hidden'; ?>>
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

                    <div class="two fields">
                        <?php
                            $this->helpController->_render('panel/built-in/content/image-field.php', [
                                'card' => [
                                    'id' => 'main-image',
                                    'title' => __($langGroup, 'Imagen principal'),
                                    'hint' => __($langGroup, 'Imagen preferiblemente en formato .jpg'),
                                    'field' => 'mainImage',
                                    'width' => 800,
                                    'height' => 600,
                                    'required' => true,
                                ],
                            ]);
                            $this->helpController->_render('panel/built-in/content/image-field.php', [
                                'card' => [
                                    'id' => 'thumb-image',
                                    'title' => __($langGroup, 'Imagen miniatura'),
                                    'hint' => __($langGroup, 'Imagen preferiblemente en formato .jpg'),
                                    'field' => 'thumbImage',
                                    'width' => 400,
                                    'height' => 300,
                                    'required' => true,
                                ],
                            ]);
                        ?>
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

                    <?php
                        $this->helpController->_render('panel/built-in/content/image-field.php', [
                            'card' => [
                                'id' => 'og-image',
                                'title' => __($langGroup, 'Imagen'),
                                'hint' => __($langGroup, 'Imagen preferiblemente en formato .jpg'),
                                'field' => 'ogImage',
                                'width' => 1200,
                                'height' => 600,
                            ],
                        ]);
                    ?>

                    <div class="field">
                        <label><?= __($langGroup, 'Descripción'); ?></label>
                        <textarea name="seoDescription"></textarea>
                    </div>

                </div>

                <div class="ui tab" data-tab="attachments">

                    <?php $this->helpController->_render('panel/built-in/content/section-header.php', ['title' => __($langGroup, 'Adjuntos y/o anexos')]); ?>

                    <div class="form-attachments-regular">

                        <?php $uniqueIdentifier = "add-trigger"; ?>
                        <div class="attach-placeholder tall" data-dynamic-attachment="<?= $uniqueIdentifier; ?>">
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

                        <?php foreach($dynamicAttachments as $attachmentElement): ?>
                        <?php $hasAttachment = $attachmentElement->hasAttachment(); ?>
                        <?php $attachmentMapper = $attachmentElement->getMapper(); ?>
                        <?php $fileLocation = $hasAttachment ? $attachmentMapper->fileLocation : ''; ?>
                        <?php $isImage = $hasAttachment ? $attachmentMapper->fileIsImage() : ''; ?>
                        <?php $existingFileAttr = $isImage ? "data-image" : "data-file"; ?>
                        <?php $existingFileAttr = "{$existingFileAttr}='" . escape_html($fileLocation) . "'"; ?>
                        <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                        <div class="attach-placeholder" data-dynamic-attachment="<?= $uniqueIdentifier; ?>">
                            <div class="ui top right attached basic green label">
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

            <?php
                //La barra fija (§2.3). Los atributos `save` y `add-translation` son los que busca add-form.js.
                $secondaryActions = [];
                if ($allowedManyLangs) {
                    $secondaryActions[] = ['text' => __($langGroup, 'Guardar y traducir'), 'type' => 'submit', 'icon' => 'language', 'attributes' => ['add-translation' => '']];
                }
                $secondaryActions[] = ['text' => __($langGroup, 'Cancelar'), 'url' => $backLink, 'icon' => 'times'];
                $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Guardar'),
                    'saveAttributes' => ['save' => ''],
                    'secondaryActions' => $secondaryActions,
                ]);
            ?>

        </form>

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
