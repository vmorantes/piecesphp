<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$standalone = isset($standalone) && is_bool($standalone) ? $standalone : true;
$submitButtonText = isset($submitButtonText) ? $submitButtonText : __($langGroup, 'Guardar');
?>
<?php if($standalone): ?>
<section class="module-view-container limit-size">
    <?php endif; ?>

    <?php if($standalone): ?>
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
                    <div class="subtitle"><?= __($langGroup, 'Agregar'); ?></div>
                </div>

            </div>

        </div>

    </div>
    <?php endif; ?>

    <div class="container-standard-form <?= !$standalone ? 'max-w-1200 block-centered' : 'max-w-1200'; ?>">
        <form method='POST' action="<?= $action; ?>" class="ui form news-categories">

            <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_lang(); ?>">

            <div class="fields">

                <div class="ten wide field">

                    <h4 class="ui dividing header"><?= __($langGroup, 'Datos básicos'); ?></h4>

                    <div class="field required">
                        <label><?= __($langGroup, 'Nombre'); ?></label>
                        <input required type="text" name="name" maxlength="300">
                    </div>

                    <br>

                    <div class="field required">
                        <label><?= __($langGroup, 'Color'); ?></label>
                        <input type="text" name="color" value="#000000" color-picker-js data-color-picker-alpha="yes" data-color-picker-format="rgb">
                    </div>

                </div>

                <div class="six wide field">

                    <h4 class="ui dividing header"><?= __($langGroup, 'Ícono de categoría'); ?></h4>

                    <div class="field" placeholder="<?= __($langGroup, 'Ícono de categoría'); ?>" image-element>
                        <label><?= __($langGroup, 'Tamaño del ícono'); ?> 300x300px</label>
                        <?php simpleUploadPlaceholderWorkSpace([
                            'inputNameAttr' => 'iconImage',
                            'buttonText' => __($langGroup, 'Agregar'),
                            'classesButton' => 'fomantic green',
                            'required' =>  true,
                            'multiple' => false,
                            'icon' => 'upload',
                            'accept' => 'image/*',
                        ]); ?>
                        <br><br>
                    </div>

                </div>

            </div>

            <div class="field">
                <button class="ui button brand-color" type="submit"><?= $submitButtonText; ?></button>
            </div>

        </form>
    </div>
    <?php if($standalone): ?>
</section>
<?php endif; ?>
