<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use News\Mappers\NewsCategoryMapper;
use PiecesPHP\Core\Config;

/**
 * @var NewsCategoryMapper $element
 */

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
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

        <?php if($manyLangs): ?>
        <div class="ui form">
            <div class="field required">
                <label><?= __($langGroup, 'Idiomas'); ?></label>
                <select required class="ui dropdown search langs">
                    <?= $allowedLangsOptions; ?>
                </select>
            </div>
        </div>
        <br>
        <?php endif; ?>

        <form method='POST' action="<?= $action; ?>" class="ui form news-categories">

            <div class="container-standard-form">

                <input type="hidden" name="id" value="<?= $element->id; ?>">
                <input type="hidden" name="lang" value="<?= $selectedLang; ?>">

                <div class="fields">

                    <div class="ten wide field">

                        <h4 class="ui dividing header"><?= __($langGroup, 'Datos básicos'); ?></h4>

                        <div class="field required">
                            <label><?= __($langGroup, 'Nombre'); ?></label>
                            <input required type="text" name="name" maxlength="300" value="<?= $element->getLangData($selectedLang, 'name', false, ''); ?>">
                        </div>

                        <br>

                        <div class="field required">
                            <label><?= __($langGroup, 'Color'); ?></label>
                            <input type="text" name="color" color-picker-js data-color-picker-alpha="yes" data-color-picker-format="rgb" value="<?= $element->color; ?>">
                        </div>

                    </div>

                    <div class="six wide field">

                        <h4 class="ui dividing header"><?= __($langGroup, 'Ícono de categoría'); ?></h4>

                        <div class="field" placeholder="<?= __($langGroup, 'Ícono de categoría'); ?>" image-element>
                            <label><?= __($langGroup, 'Tamaño del ícono'); ?> 300x300px</label>
                            <?php simpleUploadPlaceholderWorkSpace([
                            'imagePreview' => $element->getLangData($selectedLang, 'iconImage'),
                            'inputNameAttr' => 'iconImage',
                            'buttonText' => __($langGroup, 'Agregar'),
                            'classesButton' => 'fomantic green',
                            'required' => false,
                            'multiple' => false,
                            'icon' => 'upload',
                            'accept' => 'image/*',
                        ]); ?>
                            <br><br>
                        </div>

                    </div>

                </div>

            </div>

            <br>

            <div class="field">
                <div class="ui buttons">
                    <button type="submit" class="ui button brand-color" save><?= __($langGroup, 'Guardar'); ?></button>
                    <?php if($allowDelete): ?>
                    <button type="submit" class="ui button brand-color alt2" delete-news-category-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
                    <?php endif; ?>
                </div>
            </div>

        </form>

    </div>

</section>