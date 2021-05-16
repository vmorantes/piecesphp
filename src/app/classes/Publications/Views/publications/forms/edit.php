<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Publications\Mappers\PublicationMapper;
/**
 * @var PublicationMapper $element
 */
$element;

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
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

    <br>
    <br>

    <h3 class="title-form"><?= __($langGroup, 'Editar'); ?>
        <?= $title; ?>
    </h3>

    <div class="ui form">
        <div class="field required">
            <label><?= __($langGroup, 'Idiomas'); ?></label>
            <select required class="ui dropdown search langs">
                <?= $allowedLangs; ?>
            </select>
        </div>
    </div>

    <br><br>

    <div class="ui tabular menu">
        <div class="item active" data-tab="basic"><?= __($langGroup, 'Datos básicos'); ?></div>
        <div class="item" data-tab="images"><?= __($langGroup, 'Imágenes'); ?></div>
        <div class="item" data-tab="details"><?= __($langGroup, 'Detalles'); ?></div>
        <div class="item" data-tab="seo"><?= __($langGroup, 'SEO'); ?></div>
    </div>

    <form method='POST' action="<?= $action; ?>" class="ui form publications standard-form">

        <input type="hidden" name="id" value="<?= $element->id; ?>">
        <input type="hidden" name="lang" value="<?= $lang; ?>">

        <div class="ui tab active" data-tab="basic">

            <div class="field required">
                <label><?= __($langGroup, 'Nombre'); ?></label>
                <input required type="text" name="title" maxlength="300" value="<?= $element->getLangData($lang, 'title', false, ''); ?>">
            </div>

            <div class="field required">
                <label><?= __($langGroup, 'Autor'); ?></label>
                <select class="ui dropdown search" name="author" data-search-url="<?= $searchUsersURL; ?>" required>
                    <option value="<?= $element->author->id; ?>"><?= $element->author->getFullName(); ?></option>
                </select>
            </div>

            <div class="field">
                <div class="ui toggle checkbox">
                    <input type="checkbox" name="featured" value="<?= PublicationMapper::FEATURED; ?>" <?= $element->isFeatured() ? 'checked' : ''; ?>>
                    <label><?= __($langGroup, 'Destacado'); ?></label>
                </div>
            </div>

            <div class="field required">
                <label><?= __($langGroup, 'Contenido'); ?></label>
                <div rich-editor-adapter-component></div>
                <textarea name="content" required><?= $element->getLangData($lang, 'content', false, ''); ?></textarea>
            </div>

        </div>

        <div class="ui tab" data-tab="images">

            <div class="ui form cropper-adapter" cropper-image-main>

                <div class="field required">
                    <label><?= __($langGroup, 'Imagen principal'); ?></label>
                    <input type="file" accept="image/*">
                </div>

                <?php $this->helpController->_render('panel/built-in/utilities/cropper/workspace.php', [
                    'referenceW'=> '800',
                    'referenceH'=> '600',
                    'image' => $element->getLangData($lang, 'mainImage'),
                    'imageName' => $element->getLangData($lang, 'mainImage', false, null) === null ? 'image_' . str_replace('.', '', uniqid('', true)) : '',
                ]); ?>

            </div>

            <div class="ui form cropper-adapter" cropper-image-thumb>

                <div class="field required">
                    <label><?= __($langGroup, 'Imagen miniatura'); ?></label>
                    <input type="file" accept="image/*">
                </div>

                <?php $this->helpController->_render('panel/built-in/utilities/cropper/workspace.php', [
                    'referenceW'=> '400',
                    'referenceH'=> '300',
                    'image' => $element->getLangData($lang, 'thumbImage'),
                    'imageName' => $element->getLangData($lang, 'thumbImage', false, null) === null ? 'image_' . str_replace('.', '', uniqid('', true)) : '',
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
                    <input type="text" name="startDate" autocomplete="off" value="<?= $element->startDate !== null ? $element->startDate->format('Y-m-d h:i:s A') : ''; ?>">
                </div>

                <div class="field" calendar-group-js='periodo' end>
                    <label><?= __($langGroup, 'Finalizar'); ?></label>
                    <input type="text" name="endDate" autocomplete="off" value="<?= $element->endDate !== null ? $element->endDate->format('Y-m-d h:i:s A') : ''; ?>">
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
                    'image' => $element->getLangData($lang, 'ogImage', true, ''),
                    'imageName' => $element->getLangData($lang, 'ogImage', false, null) === null ? 'image_' . str_replace('.', '', uniqid('', true)) : '',
                ]); ?>

            </div>

            <br>

            <div class="field">
                <label><?= __($langGroup, 'Descripción'); ?></label>
                <textarea name="seoDescription"><?= $element->getLangData($lang, 'seoDescription', true, ''); ?></textarea>
            </div>

        </div>

        <br><br>

        <div class="field">
            <div class="ui buttons">
                <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
                <?php if($allowDelete): ?>
                <button type="submit" class="ui button red" delete-publication-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
                <?php endif; ?>
            </div>
        </div>

    </form>

</div>
