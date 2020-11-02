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
        <input type="text" name="value" value="<?=htmlentities(AppConfigModel::getConfigValue('admin_menu_color'));?>" color-picker-js>
        <input type="hidden" name="name" value="admin_menu_color">
        <input type="hidden" name="parse" value="uppercase">
    </div>

    <div class="field">
        <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
    </div>

</form>

<br><br>

<?php endif; ?>
