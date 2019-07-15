<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Model\AppConfigModel;
?>


<div class="container-medium">

    <div class="ui top attached tabular menu">
        <a class="item active" data-tab="images">Imágenes</a>
        <a class="item" data-tab="bg">Fondos del login</a>
    </div>

    <div class="ui bottom attached tab segment active" data-tab="images">

        <br><br><br><br>

        <form pcs-generic-handler-js action="<?=get_route('configurations-customization-images');?>" method="POST"
            class="ui form">

            <div class="ui header small">Ícono de favoritos (favicon)</div>

            <div class="image-preview favicon">
                <img src="<?=AppConfigModel::getConfigValue('favicon');?>">
            </div>

            <div class="field">
                <label>Cambiar</label>
                <input type="file" name="favicon" accept="image/png" required>
            </div>

            <div class="field">
                <button type="submit" class="ui button green">Guardar</button>
            </div>

        </form>

        <br><br><br><br><br>

        <form pcs-generic-handler-js action="<?=get_route('configurations-customization-images');?>" method="POST"
            class="ui form">

            <div class="ui header small">Logo</div>

            <div class="image-preview logo">
                <img src="<?=AppConfigModel::getConfigValue('logo');?>">
            </div>

            <div class="field">
                <label>Cambiar</label>
                <input type="file" name="logo" accept="image/png" required>
            </div>

            <div class="field">
                <button type="submit" class="ui button green">Guardar</button>
            </div>

        </form>

        <br><br><br><br><br>

        <form pcs-generic-handler-js action="<?=get_route('configurations-customization-images');?>" method="POST"
            class="ui form">

            <div class="ui header small">Logo del login</div>

            <div class="image-preview logo">
                <img src="<?=AppConfigModel::getConfigValue('logo-login');?>">
            </div>

            <div class="field">
                <label>Cambiar</label>
                <input type="file" name="logo-login" accept="image/png" required>
            </div>

            <div class="field">
                <button type="submit" class="ui button green">Guardar</button>
            </div>

        </form>

        <br><br><br><br><br>

        <form pcs-generic-handler-js action="<?=get_route('configurations-customization-images');?>" method="POST"
            class="ui form">

            <div class="ui header small">Logo de la parte superior de la barra lateral</div>

            <div class="image-preview logo">
                <img src="<?=AppConfigModel::getConfigValue('logo-sidebar-top');?>">
            </div>

            <div class="field">
                <label>Cambiar</label>
                <input type="file" name="logo-sidebar-top" accept="image/png" required>
            </div>

            <div class="field">
                <button type="submit" class="ui button green">Guardar</button>
            </div>

        </form>

        <br><br><br><br><br>

        <form pcs-generic-handler-js action="<?=get_route('configurations-customization-images');?>" method="POST"
            class="ui form">

            <div class="ui header small">Logo de la parte inferior de la barra lateral</div>

            <div class="image-preview logo">
                <img src="<?=AppConfigModel::getConfigValue('logo-sidebar-bottom');?>">
            </div>

            <div class="field">
                <label>Cambiar</label>
                <input type="file" name="logo-sidebar-bottom" accept="image/png" required>
            </div>

            <div class="field">
                <button type="submit" class="ui button green">Guardar</button>
            </div>

        </form>

        <br><br><br><br><br>

        <form pcs-generic-handler-js action="<?=get_route('configurations-customization-images');?>" method="POST"
            class="ui form">

            <div class="ui header small">Logo en plantillas de correo electrónico</div>

            <div class="image-preview logo">
                <img src="<?=AppConfigModel::getConfigValue('logo-mailing');?>">
            </div>

            <div class="field">
                <label>Cambiar</label>
                <input type="file" name="logo-mailing" accept="image/png" required>
            </div>

            <div class="field">
                <button type="submit" class="ui button green">Guardar</button>
            </div>

        </form>

        <br><br><br><br>

    </div>

    <div class="ui bottom attached tab segment" data-tab="bg">

        <br><br><br><br>

        <?php foreach (get_config('backgrounds') as $index => $background): ?>

        <form pcs-generic-handler-js action="<?=get_route('configurations-customization-images');?>" method="POST"
            class="ui form">

            <div class="image-preview background">
                <img src="<?=$background;?>">
            </div>

            <div class="field">
                <label>Cambiar</label>
                <input type="file" name="<?= "background-" . ($index + 1);?>" accept="image/jpeg" required>
            </div>

            <div class="field">
                <button type="submit" class="ui button green">Guardar</button>
            </div>

        </form>

        <br><br><br><br><br>

        <?php endforeach;?>

    </div>

</div>

<script>
window.addEventListener('load', function(e) {
    $('.ui.top.menu .item').tab()
})
</script>

<style>
.ui.form {
    max-width: 800px;
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