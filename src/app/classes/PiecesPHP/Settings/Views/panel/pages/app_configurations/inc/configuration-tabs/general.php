<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Settings\ORM\SettingsModel;

/**
 * @var string $langGroup
 * @var string $actionGenericURL
 * @var string $indexLink
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
//La barra al final de cada formulario (diseno/02-configuracion.md §2.3): cada uno guarda lo suyo.
$actionBar = function () use ($helpController, $langGroup, $indexLink): void {
    $helpController->_render('panel/built-in/content/form-action-bar.php', [
        'saveText' => __($langGroup, 'Guardar'),
        'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
    ]);
};
?>
<?php if (mb_strlen($actionGenericURL) > 0) : ?>

<div class="marca-colors card">

    <div class="body">
        <form brand-colors action="<?= $actionGenericURL; ?>" method="POST" class="ui form initial" data-content-form>

            <div class="card-title">
                <span><?= __($langGroup, 'Colores de marca'); ?></span>
            </div>

            <div class="inputs">
                <div form-element class="field">
                    <label><?= __($langGroup, 'Color de marca #1'); ?></label>
                    <input type="text" name="value" value="<?= htmlentities(SettingsModel::getConfigValue('main_brand_color', true)); ?>" color-picker-js>
                    <input type="hidden" name="name" value="main_brand_color">
                    <input type="hidden" name="parse" value="uppercase">
                </div>

                <div form-element class="field">
                    <label><?= __($langGroup, 'Color de marca #2'); ?></label>
                    <input type="text" name="value" value="<?= htmlentities(SettingsModel::getConfigValue('second_brand_color', true)); ?>" color-picker-js>
                    <input type="hidden" name="name" value="second_brand_color">
                    <input type="hidden" name="parse" value="uppercase">
                </div>

                <div form-element class="field">
                    <label><?= __($langGroup, 'Color fuente #1'); ?></label>
                    <input type="text" name="value" value="<?= htmlentities(SettingsModel::getConfigValue('font_color_one', true)); ?>" color-picker-js>
                    <input type="hidden" name="name" value="font_color_one">
                    <input type="hidden" name="parse" value="uppercase">
                </div>

                <div form-element class="field">
                    <label><?= __($langGroup, 'Color fuente #2'); ?></label>
                    <input type="text" name="value" value="<?= htmlentities(SettingsModel::getConfigValue('font_color_two', true)); ?>" color-picker-js>
                    <input type="hidden" name="name" value="font_color_two">
                    <input type="hidden" name="parse" value="uppercase">
                </div>
            </div>

            <?php ($actionBar)(); ?>

        </form>

        <div class="colors-examples">
            <div class="field">
                <label><?= __($langGroup, 'Muestra'); ?></label>
                <div class="first-example" style="background-color: var(--main-brand-color);">
                    <p style="color: var(--font-color-two); border-bottom: 2px solid var(--font-color-two);">Lorem ipsum</p>
                </div>
            </div>
            <div class="field">
                <p style="color: var(--main-brand-color);">Lorem, ipsum</p>
                <p style="color: var(--second-brand-color);">Lorem, ipsum</p>
                <p style="color: var(--font-color-one);">Lorem, ipsum</p>
            </div>
        </div>
    </div>

</div>

<div class="menu-colors card">
    <form menu-colors action="<?= $actionGenericURL; ?>" method="POST" class="ui form initial" data-content-form>
        <div class="card-title">
            <span><?= __($langGroup, 'Colores de menú'); ?></span>
        </div>

        <div class="item-color-selector">
            <p>
                <span><?= __($langGroup, 'Color de fondo'); ?>:</span>
                <?= __($langGroup, 'DESCRIPTION_COLOR_MENU_BACKGROUND'); ?>
            </p>
            <div form-element class="picker-color">
                <input type="text" name="value" value="<?= htmlentities(SettingsModel::getConfigValue('menu_color_background', true)); ?>" color-picker-js data-color-picker-alpha="no" data-color-picker-format="hex">
                <input type="hidden" name="name" value="menu_color_background">
                <input type="hidden" name="parse" value="uppercase">
            </div>
        </div>
        <div class="item-color-selector">
            <p>
                <span><?= __($langGroup, 'Color resaltado'); ?>:</span>
                <?= __($langGroup, 'DESCRIPTION_COLOR_MENU_MARK'); ?>
            </p>
            <div form-element class="picker-color">
                <input type="text" name="value" value="<?= htmlentities(SettingsModel::getConfigValue('menu_color_mark', true)); ?>" color-picker-js data-color-picker-alpha="yes" data-color-picker-format="rgb">
                <input type="hidden" name="name" value="menu_color_mark">
                <input type="hidden" name="parse" value="uppercase">
            </div>
        </div>
        <div class="item-color-selector">
            <p>
                <span><?= __($langGroup, 'Color fuente'); ?>:</span>
                <?= __($langGroup, 'DESCRIPTION_COLOR_MENU_FONT'); ?>
            </p>
            <div form-element class="picker-color">
                <input type="text" name="value" value="<?= htmlentities(SettingsModel::getConfigValue('menu_color_font', true)); ?>" color-picker-js data-color-picker-alpha="no" data-color-picker-format="hex">
                <input type="hidden" name="name" value="menu_color_font">
                <input type="hidden" name="parse" value="uppercase">
            </div>
        </div>
        <?php ($actionBar)(); ?>

    </form>

    <div class="example">
        <label><?= __($langGroup, 'Muestra'); ?></label>

        <div class="menu-example">
            <div class="head-circle"></div>
            <div class="item">
                <div class="element"></div>
            </div>
            <div class="item mark">
                <div class="element"></div>
            </div>
        </div>
    </div>
</div>

<div class="menu-mobile-colors card">
    <form pcs-generic-handler-js action="<?= $actionGenericURL; ?>" method="POST" class="ui form initial" data-content-form>
        <div class="card-title">
            <span><?= __($langGroup, 'Color barra móvil'); ?></span>
        </div>

        <div class="color-selector">
            <label><?= __($langGroup, 'Barra superior en móvil'); ?></label>
            <input type="text" name="value" value="<?= htmlentities(SettingsModel::getConfigValue('meta_theme_color')); ?>" color-picker-js data-color-picker-alpha="no" data-color-picker-format="hex">
            <input type="hidden" name="name" value="meta_theme_color">
            <input type="hidden" name="parse" value="uppercase">
        </div>

        <?php ($actionBar)(); ?>

    </form>
    <div class="example">
        <label><?= __($langGroup, 'Muestra'); ?></label>

        <div class="mobil-example">
            <div style="background-color: <?= htmlentities(SettingsModel::getConfigValue('meta_theme_color')); ?>;" class="head">
                <i class="arrow left icon"></i>
                <i class="bars icon"></i>
            </div>
            <div class="footter">
                <img src="<?= base_url('statics/images/framework/triangle.png') ?>" alt="">
                <i class="circle icon"></i>
                <i class="square full icon"></i>
            </div>
        </div>
    </div>
</div>

<div class="menu-tools-bg-colors card">
    <form pcs-generic-handler-js action="<?= $actionGenericURL; ?>" method="POST" class="ui form initial" data-content-form>
        <div class="card-title">
            <span><?= __($langGroup, 'Color de opciones<br>flotantes arriba-derecha'); ?></span>
        </div>

        <div class="color-selector">
            <label><?= __($langGroup, 'Color de fondo'); ?></label>
            <input type="text" name="value" value="<?= htmlentities(SettingsModel::getConfigValue('bg_tools_buttons', true)); ?>" color-picker-js data-color-picker-alpha="no" data-color-picker-format="hex">
            <input type="hidden" name="name" value="bg_tools_buttons">
            <input type="hidden" name="parse" value="uppercase">
        </div>

        <?php ($actionBar)(); ?>

    </form>
    <div class="example" style="visibility: hidden;">
        <label><?= __($langGroup, 'Muestra'); ?></label>

        <div class="mobil-example">
            <div style="background-color: <?= htmlentities(SettingsModel::getConfigValue('meta_theme_color')); ?>;" class="head">
                <i class="arrow left icon"></i>
                <i class="bars icon"></i>
            </div>
            <div class="footter">
                <img src="<?= base_url('statics/images/framework/triangle.png') ?>" alt="">
                <i class="circle icon"></i>
                <i class="square full icon"></i>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>
