<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use PiecesPHP\BuiltIn\Shop\Product\Mappers\ProductMapper;

/**
 * @var ProductMapper $element
 */
$element;

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 * @var string $optionsBrands
 * @var string $optionsCategories
 * @var string $subcategoryURL
 */;
 $langGroup;
 $backLink;
 $action;
 $optionsBrands;
 $optionsCategories;
 $subcategoryURL;

?>

<div style="max-width:850px;">

    <h3><?= __($langGroup, 'Editar'); ?>
        <?= $title; ?>
    </h3>

    <div class="ui buttons">

        <a href="<?= $backLink; ?>" class="ui labeled icon button">
            <i class="icon left arrow"></i>
            <?= __($langGroup, 'Regresar'); ?>
        </a>

    </div>

    <br><br>

    <div class="ui tabular menu">
        <div class="item active" data-tab="basic"><?= __($langGroup, 'Datos básicos'); ?></div>
        <div class="item" data-tab="images"><?= __($langGroup, 'Imágenes'); ?></div>
    </div>

    <form method='POST' action="<?= $action; ?>" class="ui form shop-products">

        <input type="hidden" name="id" value="<?= $element->id; ?>">

        <div class="ui tab active" data-tab="basic">

            <div class="field required">
                <label><?= __($langGroup, 'Nombre'); ?></label>
                <input required type="text" name="name" maxlength="300" value="<?= $element->name; ?>">
            </div>

            <div class="field required">
                <label><?= __($langGroup, 'Referencia'); ?></label>
                <input required type="text" name="reference_code" maxlength="300" value="<?= $element->reference_code; ?>">
            </div>

            <div class="field required">
                <label><?= __($langGroup, 'Marca'); ?></label>
                <select name="brand" class="ui dropdown search autodropdown" required>
                    <?= $optionsBrands; ?>
                </select>
            </div>

            <div class="field required">
                <label><?= __($langGroup, 'Categoría'); ?></label>
                <select name="category" class="ui dropdown search" required>
                    <?= $optionsCategories; ?>
                </select>
            </div>

            <div class="field">
                <label><?= __($langGroup, 'Subcategoría'); ?></label>
                <select name="subcategory" class="ui dropdown search" url="<?= $subcategoryURL; ?>" current="<?= $element->subcategory !== null ? $element->subcategory->id : -1; ?>">
                    <option value=""><?= __($langGroup, 'Subcategorías'); ?></option>
                </select>
            </div>

            <div class="field">
                <label><?= __($langGroup, 'Descripción'); ?></label>
                <input type="text" name="description" maxlength="300" value="<?= $element->description; ?>">
            </div>

            <div class="field">
                <label><?= __($langGroup, 'Precio'); ?></label>
                <input type="number" step="any" min="0" name="price" value="<?= $element->price; ?>">
            </div>

            <div class="field">

                <label><?= __($langGroup, 'Tiene garantía'); ?></label>

                <div class="ui toggle checkbox">
                    <?php if($element->warranty_measure !== ProductMapper::WARRANTY_NO): ?>
                    <input type="checkbox" name="has_warranty" checked>
                    <?php else:?>
                    <input type="checkbox" name="has_warranty">
                    <?php endif; ?>
                    <label></label>
                </div>

            </div>

            <div class="fields">

                <div class="field five wide required">
                    <label><?= __($langGroup, 'Tiempo de garantía'); ?></label>
                    <input type="number" step="any" min="0" name="warranty_duration" required value="<?= $element->warranty_duration; ?>">
                </div>

                <div class="field eleven wide required">
                    <label><?= __($langGroup, 'Garantía expresada en'); ?></label>
                    <select name="warranty_measure" class="ui dropdown search" required>
                        <?= $optionsWarrantyMeasures; ?>
                    </select>
                </div>

            </div>

            <div class="ui form cropper-adapter" cropper-main-image>

                <div class="field required">
                    <label><?= __($langGroup, 'Imagen principal'); ?></label>
                    <input type="file" accept="image/*">
                </div>

                <?php $this->_render('panel/built-in/utilities/cropper/workspace.php', [
					'referenceW' => '400',
					'referenceH' => '300',
					'image' => $element->main_image,
				]); ?>

            </div>

        </div>

        <div class="ui tab" data-tab="images">

            <button images-multiple-trigger-add class="ui labeled icon button blue">
                <?= __($langGroup, 'Agregar imagen'); ?>
                <i class="icon image"></i>
            </button>

            <br>
            <br>

            <div images-multiple-editor>

                <div class="ui form cropper-adapter">

                    <div class="field required">
                        <label><?= __($langGroup, 'Imagen'); ?></label>
                        <input type="file" accept="image/*">
                    </div>

                    <?php $this->_render('panel/built-in/utilities/cropper/workspace.php', [
						'referenceW' => '400',
						'referenceH' => '300',
					]); ?>

                </div>

            </div>

            <div class="ui header big"><?= __($langGroup, 'Imágenes.'); ?></div>

            <div class="ui divider"></div>
            <br>

            <div class="ui cards center" images-multiple-container>

                <?php foreach($element->images as $image): ?>
                <div class="ui card" data-id="<?= uniqid(); ?>" item>

                    <div class="content">

                        <div class="image">
                            <img src="<?= $image; ?>">
                        </div>

                        <br>

                        <div class="description">

                            <div>

                                <button delete class="ui labeled icon button red">
                                    <?= __($langGroup, 'Borrar'); ?>
                                    <i class="icon trash"></i>
                                </button>

                            </div>

                        </div>

                    </div>

                </div>
                <?php endforeach;?>

            </div>

        </div>

        <br><br>

        <div class="field">
            <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
        </div>

    </form>

</div>

<script type="text/html" template-item-images-multiple>
<div class="ui card" data-id item>

    <div class="content">

        <div class="image">
            <img src="">
        </div>

        <br>

        <div class="description">

            <div>

                <button delete class="ui labeled icon button red">
                    <?= __($langGroup, 'Borrar'); ?>
                    <i class="icon trash"></i>
                </button>

            </div>

        </div>

    </div>

</div>
</script>
