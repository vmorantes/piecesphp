<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Model\AppConfigModel;
?>
<?php if(mb_strlen($actionGenericURL) > 0): ?>

<form pcs-generic-handler-js action="<?= $actionGenericURL; ?>" method="POST" class="ui form">

    <div class="field">
        <label><?= __($langGroup, 'Color de barra superior en navegadores móviles'); ?></label>
        <input type="text" name="value" value="<?=htmlentities(AppConfigModel::getConfigValue('meta_theme_color'));?>" color-picker-js>
        <input type="hidden" name="name" value="meta_theme_color">
        <input type="hidden" name="parse" value="uppercase">
    </div>

    <div class="field">
        <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
    </div>

</form>

<br><br>

<form pcs-generic-handler-js action="<?= $actionGenericURL; ?>" method="POST" class="ui form">

    <div class="field">
        <label><?= __($langGroup, 'Color del menú'); ?></label>
        <input type="text" name="value" value="<?=htmlentities(AppConfigModel::getConfigValue('admin_menu_bg_color'));?>" color-picker-js>
        <input type="hidden" name="name" value="admin_menu_bg_color">
        <input type="hidden" name="parse" value="uppercase">
    </div>

    <div class="field">
        <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
    </div>

</form>

<br><br>

<div class="ui form">

    <div class="fields">

        <form pcs-generic-handler-js action="<?= $actionGenericURL; ?>" method="POST" class="field">

            <div class="field">
                <label><?= __($langGroup, 'Color de marca #1'); ?></label>
                <input type="text" name="value" value="<?=htmlentities(AppConfigModel::getConfigValue('main_brand_color', true));?>" color-picker-js>
                <input type="hidden" name="name" value="main_brand_color">
                <input type="hidden" name="parse" value="uppercase">
            </div>

            <br>

            <div class="field">
                <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
            </div>

        </form>

        <form pcs-generic-handler-js action="<?= $actionGenericURL; ?>" method="POST" class="field">

            <div class="field">
                <label><?= __($langGroup, 'Color de marca #2'); ?></label>
                <input type="text" name="value" value="<?=htmlentities(AppConfigModel::getConfigValue('second_brand_color', true));?>" color-picker-js>
                <input type="hidden" name="name" value="second_brand_color">
                <input type="hidden" name="parse" value="uppercase">
            </div>

            <br>

            <div class="field">
                <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
            </div>

        </form>

        <form pcs-generic-handler-js action="<?= $actionGenericURL; ?>" method="POST" class="field">

            <div class="field">
                <label><?= __($langGroup, 'Color de texto sobre color #1'); ?></label>
                <input type="text" name="value" value="<?=htmlentities(AppConfigModel::getConfigValue('color_text_over_main_brand_color', true));?>" color-picker-js>
                <input type="hidden" name="name" value="color_text_over_main_brand_color">
                <input type="hidden" name="parse" value="uppercase">
            </div>

            <br>

            <div class="field">
                <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
            </div>

        </form>

        <div class="field">
            <label><?= __($langGroup, 'Muestra'); ?></label>
            <div style="background-color: var(--main-brand-color); padding: 10px;">
                <p style="color: var(--color-text-over-main-brand-color); border-bottom: 1px solid var(--second-brand-color);">Lorem ipsum dolor sit.</p>
            </div>
        </div>

    </div>

</div>

<br><br>

<?php endif; ?>
