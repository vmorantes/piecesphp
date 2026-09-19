<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Model\AppConfigModel;
?>
<?php if (mb_strlen($actionGenericURL) > 0) : ?>

<div class="marca-colors card">

    <div class="body">
        <form brand-colors action="<?= $actionGenericURL; ?>" method="POST" class="field">

            <div class="card-title">
                <span><?= __($langGroup, 'Colores de marca'); ?></span>
            </div>

            <div class="inputs">
                <div form-element class="field">
                    <label><?= __($langGroup, 'Color de marca #1'); ?></label>
                    <input type="text" name="value" value="<?= htmlentities(AppConfigModel::getConfigValue('main_brand_color', true)); ?>" color-picker-js>
                    <input type="hidden" name="name" value="main_brand_color">
                    <input type="hidden" name="parse" value="uppercase">
                </div>

                <div form-element class="field">
                    <label><?= __($langGroup, 'Color de marca #2'); ?></label>
                    <input type="text" name="value" value="<?= htmlentities(AppConfigModel::getConfigValue('second_brand_color', true)); ?>" color-picker-js>
                    <input type="hidden" name="name" value="second_brand_color">
                    <input type="hidden" name="parse" value="uppercase">
                </div>

                <div form-element class="field">
                    <label><?= __($langGroup, 'Color fuente #1'); ?></label>
                    <input type="text" name="value" value="<?= htmlentities(AppConfigModel::getConfigValue('font_color_one', true)); ?>" color-picker-js>
                    <input type="hidden" name="name" value="font_color_one">
                    <input type="hidden" name="parse" value="uppercase">
                </div>

                <div form-element class="field">
                    <label><?= __($langGroup, 'Color fuente #2'); ?></label>
                    <input type="text" name="value" value="<?= htmlentities(AppConfigModel::getConfigValue('font_color_two', true)); ?>" color-picker-js>
                    <input type="hidden" name="name" value="font_color_two">
                    <input type="hidden" name="parse" value="uppercase">
                </div>
            </div>

            <div class="buttons-actions">
                <button style="border-radius: 8px !important;" type="submit" class="ui button primary"><?= __($langGroup, 'Guardar'); ?></button>
                <button style="border-radius: 8px !important;" type="reset" class="ui grey basic button"><?= __($langGroup, 'Cancelar'); ?></button>
            </div>

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
    <form menu-colors action="<?= $actionGenericURL; ?>" method="POST" class="ui form">
        <div class="card-title">
            <span><?= __($langGroup, 'Colores de menú'); ?></span>
        </div>

        <div class="item-color-selector">
            <p>
                <span><?= __($langGroup, 'Color de fondo'); ?>:</span>
                <?= __($langGroup, 'DESCRIPTION_COLOR_MENU_BACKGROUND'); ?>
            </p>
            <div form-element class="picker-color">
                <input type="text" name="value" value="<?= htmlentities(AppConfigModel::getConfigValue('menu_color_background', true)); ?>" color-picker-js data-color-picker-alpha="no" data-color-picker-format="hex">
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
                <input type="text" name="value" value="<?= htmlentities(AppConfigModel::getConfigValue('menu_color_mark', true)); ?>" color-picker-js data-color-picker-alpha="yes" data-color-picker-format="rgb">
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
                <input type="text" name="value" value="<?= htmlentities(AppConfigModel::getConfigValue('menu_color_font', true)); ?>" color-picker-js data-color-picker-alpha="no" data-color-picker-format="hex">
                <input type="hidden" name="name" value="menu_color_font">
                <input type="hidden" name="parse" value="uppercase">
            </div>
        </div>
        <br>
        <div class="buttons-actions">
            <button style="border-radius: 8px !important;" type="submit" class="ui button primary"><?= __($langGroup, 'Guardar'); ?></button>
            <button style="border-radius: 8px !important;" type="reset" class="ui grey basic button"><?= __($langGroup, 'Cancelar'); ?></button>
        </div>

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
    <form pcs-generic-handler-js action="<?= $actionGenericURL; ?>" method="POST" class="ui form">
        <div class="card-title">
            <span><?= __($langGroup, 'Color barra móvil'); ?></span>
        </div>

        <div class="color-selector">
            <label><?= __($langGroup, 'Barra superior en móvil'); ?></label>
            <input type="text" name="value" value="<?= htmlentities(AppConfigModel::getConfigValue('meta_theme_color')); ?>" color-picker-js data-color-picker-alpha="no" data-color-picker-format="hex">
            <input type="hidden" name="name" value="meta_theme_color">
            <input type="hidden" name="parse" value="uppercase">
        </div>

        <div class="buttons-actions">
            <button style="border-radius: 8px !important;" type="submit" class="ui button primary"><?= __($langGroup, 'Guardar'); ?></button>
            <button style="border-radius: 8px !important;" type="reset" class="ui grey basic button"><?= __($langGroup, 'Cancelar'); ?></button>
        </div>

    </form>
    <div class="example">
        <label><?= __($langGroup, 'Muestra'); ?></label>

        <div class="mobil-example">
            <div style="background-color: <?= htmlentities(AppConfigModel::getConfigValue('meta_theme_color')); ?>;" class="head">
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
    <form pcs-generic-handler-js action="<?= $actionGenericURL; ?>" method="POST" class="ui form">
        <div class="card-title">
            <span><?= __($langGroup, 'Color de opciones<br>flotantes arriba-derecha'); ?></span>
        </div>

        <div class="color-selector">
            <label><?= __($langGroup, 'Color de fondo'); ?></label>
            <input type="text" name="value" value="<?= htmlentities(AppConfigModel::getConfigValue('bg_tools_buttons', true)); ?>" color-picker-js data-color-picker-alpha="no" data-color-picker-format="hex">
            <input type="hidden" name="name" value="bg_tools_buttons">
            <input type="hidden" name="parse" value="uppercase">
        </div>

        <div class="buttons-actions">
            <button style="border-radius: 8px !important;" type="submit" class="ui button primary"><?= __($langGroup, 'Guardar'); ?></button>
            <button style="border-radius: 8px !important;" type="reset" class="ui grey basic button"><?= __($langGroup, 'Cancelar'); ?></button>
        </div>

    </form>
    <div class="example" style="visibility: hidden;">
        <label><?= __($langGroup, 'Muestra'); ?></label>

        <div class="mobil-example">
            <div style="background-color: <?= htmlentities(AppConfigModel::getConfigValue('meta_theme_color')); ?>;" class="head">
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

<br><br>

<?php endif; ?>
