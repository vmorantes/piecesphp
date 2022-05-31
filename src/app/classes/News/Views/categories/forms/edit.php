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

        <form method='POST' action="<?= $action; ?>" class="ui form news-categories">

            <input type="hidden" name="id" value="<?= $element->id; ?>">
            <input type="hidden" name="lang" value="<?= $lang; ?>">

            <div class="fields">

                <div class="ten wide field">

                    <h4 class="ui dividing header"><?= __($langGroup, 'Datos básicos'); ?></h4>

                    <div class="field required">
                        <label><?= __($langGroup, 'Nombre'); ?></label>
                        <input required type="text" name="name" maxlength="300" value="<?= $element->getLangData($lang, 'name', false, ''); ?>">
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
                            'imagePreview' => $element->getLangData($lang, 'iconImage'),
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


            <div class="field">
                <div class="ui buttons">
                    <button type="submit" class="ui button brand-color"><?= __($langGroup, 'Guardar'); ?></button>
                    <?php if($allowDelete): ?>
                    <button type="submit" class="ui button brand-color alt2" delete-news-category-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
                    <?php endif; ?>
                </div>
            </div>

        </form>

    </div>

</section>
