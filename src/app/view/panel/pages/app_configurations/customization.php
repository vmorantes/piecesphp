<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Model\AppConfigModel;
?>


<div class="container-medium">

    <div class="ui top attached tabular menu main">
        <a class="item active" data-tab="images"><?= __('customizationAdminZone', 'Imágenes'); ?></a>
        <a class="item" data-tab="bg"><?= __('customizationAdminZone', 'Fondos del login'); ?></a>
    </div>

    <div class="ui bottom attached tab segment active" data-tab="images">

        <div class="ui top attached tabular menu second">
            <a class="item active" data-tab="favicon"><?= __('customizationAdminZone', 'Íconos de favoritos (favicon)'); ?></a>
            <a class="item" data-tab="logos"><?= __('customizationAdminZone', 'Logos'); ?></a>
            <a class="item" data-tab="og"><?= __('customizationAdminZone', 'Open Graph'); ?></a>
        </div>

        <div class="ui bottom attached tab segment active" data-tab="favicon">

            <br><br>

            <form action="<?=get_route('configurations-customization-images-action');?>" pcs-generic-handler-js
                method="POST" class="ui form">

                <div class="ui header small"><?= __('customizationAdminZone', 'Zona pública'); ?></div>

                <div class="image-preview favicon">
                    <img src="<?=AppConfigModel::getConfigValue('favicon');?>">
                </div>

                <div class="field">
                    <label><?= __('customizationAdminZone', 'Cambiar'); ?></label>
                    <input type="file" name="favicon" accept="image/png" required>
                </div>

                <div class="field">
                    <button type="submit" class="ui button green"><?= __('customizationAdminZone', 'Guardar'); ?></button>
                </div>

            </form>

            <br><br><br><br>

            <form action="<?=get_route('configurations-customization-images-action');?>" pcs-generic-handler-js
                method="POST" class="ui form">

                <div class="ui header small"><?= __('customizationAdminZone', 'Zona administrativa'); ?></div>

                <div class="image-preview favicon">
                    <img src="<?=AppConfigModel::getConfigValue('favicon-back');?>">
                </div>

                <div class="field">
                    <label><?= __('customizationAdminZone', 'Cambiar'); ?></label>
                    <input type="file" name="favicon-back" accept="image/png" required>
                </div>

                <div class="field">
                    <button type="submit" class="ui button green"><?= __('customizationAdminZone', 'Guardar'); ?></button>
                </div>

            </form>

            <br><br>

        </div>

        <div class="ui bottom attached tab segment" data-tab="logos">

            <br><br>

            <form action="<?=get_route('configurations-customization-images-action');?>" pcs-generic-handler-js
                method="POST" class="ui form">

                <div class="ui header small"><?= __('customizationAdminZone', 'General'); ?></div>

                <div class="image-preview logo">
                    <img src="<?=AppConfigModel::getConfigValue('logo');?>">
                </div>

                <div class="field">
                    <label><?= __('customizationAdminZone', 'Cambiar'); ?></label>
                    <input type="file" name="logo" accept="image/png" required>
                </div>

                <div class="field">
                    <button type="submit" class="ui button green"><?= __('customizationAdminZone', 'Guardar'); ?></button>
                </div>

            </form>

            <br><br><br><br>

            <form action="<?=get_route('configurations-customization-images-action');?>" pcs-generic-handler-js
                method="POST" class="ui form">

                <div class="ui header small"><?= __('customizationAdminZone', 'Login'); ?></div>

                <div class="image-preview logo">
                    <img src="<?=AppConfigModel::getConfigValue('logo-login');?>">
                </div>

                <div class="field">
                    <label><?= __('customizationAdminZone', 'Cambiar'); ?></label>
                    <input type="file" name="logo-login" accept="image/png" required>
                </div>

                <div class="field">
                    <button type="submit" class="ui button green"><?= __('customizationAdminZone', 'Guardar'); ?></button>
                </div>

            </form>

            <br><br><br><br>

            <form action="<?=get_route('configurations-customization-images-action');?>" pcs-generic-handler-js
                method="POST" class="ui form">

                <div class="ui header small"><?= __('customizationAdminZone', 'Superior de la barra lateral'); ?></div>

                <div class="image-preview logo">
                    <img src="<?=AppConfigModel::getConfigValue('logo-sidebar-top');?>">
                </div>

                <div class="field">
                    <label><?= __('customizationAdminZone', 'Cambiar'); ?></label>
                    <input type="file" name="logo-sidebar-top" accept="image/png" required>
                </div>

                <div class="field">
                    <button type="submit" class="ui button green"><?= __('customizationAdminZone', 'Guardar'); ?></button>
                </div>

            </form>

            <br><br><br><br>

            <form action="<?=get_route('configurations-customization-images-action');?>" pcs-generic-handler-js
                method="POST" class="ui form">

                <div class="ui header small"><?= __('customizationAdminZone', 'Inferior de la barra lateral'); ?></div>

                <div class="image-preview logo">
                    <img src="<?=AppConfigModel::getConfigValue('logo-sidebar-bottom');?>">
                </div>

                <div class="field">
                    <label><?= __('customizationAdminZone', 'Cambiar'); ?></label>
                    <input type="file" name="logo-sidebar-bottom" accept="image/png" required>
                </div>

                <div class="field">
                    <button type="submit" class="ui button green"><?= __('customizationAdminZone', 'Guardar'); ?></button>
                </div>

            </form>

            <br><br><br><br>

            <form action="<?=get_route('configurations-customization-images-action');?>" pcs-generic-handler-js
                method="POST" class="ui form">

                <div class="ui header small"><?= __('customizationAdminZone', 'Plantillas de correo electrónico'); ?></div>

                <div class="image-preview logo">
                    <img src="<?=AppConfigModel::getConfigValue('logo-mailing');?>">
                </div>

                <div class="field">
                    <label><?= __('customizationAdminZone', 'Cambiar'); ?></label>
                    <input type="file" name="logo-mailing" accept="image/png" required>
                </div>

                <div class="field">
                    <button type="submit" class="ui button green"><?= __('customizationAdminZone', 'Guardar'); ?></button>
                </div>

            </form>

            <br><br>

        </div>

        <div class="ui bottom attached tab segment" data-tab="og">

            <br><br>

            <form action="<?=get_route('configurations-customization-images-action');?>" pcs-generic-handler-js
                method="POST" class="ui form">

                <div class="ui header small"><?= __('customizationAdminZone', 'Imagen general'); ?></div>

                <div class="image-preview logo">
                    <img src="<?=AppConfigModel::getConfigValue('open_graph_image');?>">
                </div>

                <div class="field">
                    <label><?= __('customizationAdminZone', 'Cambiar'); ?></label>
                    <input type="file" name="open_graph_image" accept="image/jpeg" required>
                </div>

                <div class="field">
                    <button type="submit" class="ui button green"><?= __('customizationAdminZone', 'Guardar'); ?></button>
                </div>

            </form>

            <br><br>

		</div>
		
    </div>

    <div class="ui bottom attached tab segment" data-tab="bg">

        <br><br><br><br>

        <?php foreach (get_config('backgrounds') as $index => $background): ?>

        <form pcs-generic-handler-js action="<?=get_route('configurations-customization-images-action');?>"
            method="POST" class="ui form">

            <div class="image-preview background">
                <img src="<?=$background;?>">
            </div>

            <div class="field">
                <label><?= __('customizationAdminZone', 'Cambiar'); ?></label>
                <input type="file" name="<?= "background-" . ($index + 1);?>" accept="image/jpeg" required>
            </div>

            <div class="field">
                <button type="submit" class="ui button green"><?= __('customizationAdminZone', 'Guardar'); ?></button>
            </div>

        </form>

        <br><br><br><br><br>

        <?php endforeach;?>

    </div>

</div>

<script>
window.addEventListener('load', function(e) {
    $('.ui.top.menu.main .item').tab({
        context: 'parent',
    })
    $('.ui.top.menu.second .item').tab({
        context: 'parent',
    })
})
</script>

<style>
.ui.form {
    max-width: 450px;
}

.image-preview.favicon {
    max-width: 90px;
}

.image-preview.logo {
    max-width: 200px;
}

.image-preview.background {
    width: 320px;
    max-width: 100%;
}
</style>
