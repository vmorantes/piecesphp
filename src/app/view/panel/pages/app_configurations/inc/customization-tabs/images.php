<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Model\AppConfigModel;
?>

<?php if(mb_strlen($actionCustomImagesURL) > 0): ?>

<div class="ui top attached tabular menu second">
    <a class="item active" data-tab="favicon"><?= __($langGroup, 'Íconos de favoritos (favicon)'); ?></a>
    <a class="item" data-tab="logos"><?= __($langGroup, 'Logos'); ?></a>
    <a class="item" data-tab="og"><?= __($langGroup, 'Open Graph'); ?></a>
</div>

<div class="ui bottom attached tab segment active" data-tab="favicon">

    <br><br>

    <form action="<?= $actionCustomImagesURL; ?>" pcs-generic-handler-js method="POST" class="ui form">

        <div class="ui header small"><?= __($langGroup, 'Zona pública'); ?></div>

        <div class="image-preview favicon">
            <img src="<?=AppConfigModel::getConfigValue('favicon');?>">
        </div>

        <div class="field">
            <label><?= __($langGroup, 'Cambiar'); ?></label>
            <input type="file" name="favicon" accept="image/png" required>
        </div>

        <div class="field">
            <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
        </div>

    </form>

    <br><br><br><br>

    <form action="<?= $actionCustomImagesURL; ?>" pcs-generic-handler-js method="POST" class="ui form">

        <div class="ui header small"><?= __($langGroup, 'Zona administrativa'); ?></div>

        <div class="image-preview favicon">
            <img src="<?=AppConfigModel::getConfigValue('favicon-back');?>">
        </div>

        <div class="field">
            <label><?= __($langGroup, 'Cambiar'); ?></label>
            <input type="file" name="favicon-back" accept="image/png" required>
        </div>

        <div class="field">
            <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
        </div>

    </form>

    <br><br>

</div>

<div class="ui bottom attached tab segment" data-tab="logos">

    <br><br>

    <form action="<?= $actionCustomImagesURL; ?>" pcs-generic-handler-js method="POST" class="ui form">

        <div class="ui header small"><?= __($langGroup, 'General'); ?></div>

        <div class="image-preview logo">
            <img src="<?=AppConfigModel::getConfigValue('logo');?>">
        </div>

        <div class="field">
            <label><?= __($langGroup, 'Cambiar'); ?></label>
            <input type="file" name="logo" accept="image/png" required>
        </div>

        <div class="field">
            <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
        </div>

    </form>

    <br><br><br><br>

    <form action="<?= $actionCustomImagesURL; ?>" pcs-generic-handler-js method="POST" class="ui form">

        <div class="ui header small"><?= __($langGroup, 'Inferior de la barra lateral'); ?></div>

        <div class="image-preview logo">
            <img src="<?=AppConfigModel::getConfigValue('white-logo');?>">
        </div>

        <div class="field">
            <label><?= __($langGroup, 'Cambiar'); ?></label>
            <input type="file" name="white-logo" accept="image/png" required>
        </div>

        <div class="field">
            <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
        </div>

    </form>

    <br><br>

</div>

<div class="ui bottom attached tab segment" data-tab="og">

    <br><br>

    <form action="<?= $actionCustomImagesURL; ?>" pcs-generic-handler-js method="POST" class="ui form">

        <div class="ui header small"><?= __($langGroup, 'Imagen general'); ?></div>

        <div class="image-preview logo">
            <img src="<?=AppConfigModel::getConfigValue('open_graph_image');?>">
        </div>

        <div class="field">
            <label><?= __($langGroup, 'Cambiar'); ?></label>
            <input type="file" name="open_graph_image" accept="image/jpeg" required>
        </div>

        <div class="field">
            <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
        </div>

    </form>

    <br><br>

</div>
<?php endif; ?>
