<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use App\Presentations\Mappers\PresentationMapper;
use PiecesPHP\Core\Config;

/**
 * @var PresentationMapper $element
 */
$element;

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$langGroup;
$backLink;
$action;

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

    <br>
    <br>

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
    </div>

    <form method='POST' action="<?= $action; ?>" class="ui form app-presentations">

        <input type="hidden" name="id" value="<?= $element->id; ?>">
        <input type="hidden" name="lang" value="<?= $lang; ?>">

        <div class="ui tab active" data-tab="basic">

            <div class="field required">
                <label><?= __($langGroup, 'Nombre'); ?></label>
                <input required type="text" name="name" maxlength="300" value="<?= $element->getLangData($lang, 'name', false, ''); ?>">
            </div>

            <?php if($lang === Config::get_default_lang()): ?>

            <div class="field">
                <label><?= __($langGroup, 'Orden'); ?></label>
                <input required type="number" name="order" value="<?= $element->getLangData($lang, 'order', false, 1); ?>">
            </div>

            <div class="field required">
                <label><?= __($langGroup, 'Categorías'); ?></label>
                <select required name="category" class="ui dropdown search">
                    <?= $allCategories; ?>
                </select>
            </div>

            <?php else: ?>

            <input type="hidden" name="category" value="<?= $element->getLangData($lang, 'category')->id; ?>">
            <input type="hidden" name="order" value="<?= $element->getLangData($lang, 'order'); ?>">

            <div class="field">
                <label><?= __($langGroup, 'Orden'); ?></label>
                <input disabled type="text" value="<?= $element->getLangData($lang, 'order'); ?>">
            </div>

            <div class="field">
                <label><?= __($langGroup, 'Categorías'); ?></label>
                <input disabled type="text" readonly value="<?= $element->getLangData($lang, 'category')->getLangData($lang, 'name'); ?>">
            </div>

            <?php endif;?>

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

                    <?php $this->helpController->_render('panel/built-in/utilities/cropper/workspace.php', [
						'referenceW' => '400',
						'referenceH' => '300',
					]); ?>

                    <small>(<?= __($langGroup, 'Tamaño de referencia, es de recorte libre.'); ?>)</small>

                </div>

            </div>

            <div class="ui header big"><?= __($langGroup, 'Imágenes.'); ?></div>

            <div class="ui divider"></div>
            <br>

            <div class="ui cards center" images-multiple-container>

                <?php foreach($element->getLangData($lang, 'images', false, []) as $image): ?>
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

        <br>

        <div class="field">
            <div class="ui buttons">
                <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
                <?php if($allowDelete): ?>
                <button type="submit" class="ui button red" delete-presentation-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
                <?php endif; ?>
            </div>
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
