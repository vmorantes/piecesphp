<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Model\AppConfigModel;
?>
<?php if(mb_strlen($actionGenericURL) > 0): ?>
<form pcs-generic-handler-js action="<?= $actionGenericURL; ?>" method="POST" class="ui form">

    <div class="field">
        <label><?= __($langGroup, 'Título del sitio'); ?></label>
        <input type="text" name="value" value="<?=htmlentities(AppConfigModel::getConfigValue('title_app'));?>" placeholder="<?= __($langGroup, 'Nombre'); ?>" required>
        <input type="hidden" name="name" value="title_app" required="required">
    </div>

    <div class="field">
        <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
    </div>

</form>

<br><br>

<form pcs-generic-handler-js action="<?= $actionGenericURL; ?>" method="POST" class="ui form">

    <div class="field">
        <label><?= __($langGroup, 'Propietario'); ?></label>
        <input type="text" name="value" value="<?=htmlentities(AppConfigModel::getConfigValue('owner'));?>" placeholder="<?= __($langGroup, 'Propietario'); ?>" required>
        <input type="hidden" name="name" value="owner" required="required">
    </div>

    <div class="field">
        <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
    </div>

</form>

<br><br>

<form pcs-generic-handler-js action="<?= $actionGenericURL; ?>" method="POST" class="ui form">

    <div class="field">
        <label><?= __($langGroup, 'Descripción'); ?></label>
        <textarea name="value" placeholder="<?= __($langGroup, 'Descripción'); ?>" required><?=AppConfigModel::getConfigValue('description');?></textarea>
        <input type="hidden" name="name" value="description" required="required">
    </div>

    <div class="field">
        <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
    </div>

</form>

<br><br>

<form pcs-generic-handler-js action="<?= $actionGenericURL; ?>" method="POST" class="ui form">

    <div class="field">
        <label><?= __($langGroup, 'Palabras clave'); ?></label>
        <?php $keywords = AppConfigModel::getConfigValue('keywords'); ?>
        <select name="value[]" multiple class="ui dropdown multiple search selection additions" required>

            <?php if (is_array($keywords) && count($keywords) > 0): ?>

            <?php foreach ($keywords as $key => $value): ?>

            <option selected value="<?=htmlentities($value);?>"><?=htmlentities($value);?></option>

            <?php endforeach;?>

            <?php else: ?>

            <option value=""><?= __($langGroup, 'Agregue alguna palabra clave'); ?></option>

            <?php endif;?>

        </select>
        <input type="hidden" name="name" value="keywords" required="required">
    </div>

    <div class="field">
        <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
    </div>

</form>

<br><br>

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
        <label><?= __($langGroup, 'Scripts adicionales'); ?></label>
        <textarea name="value" placeholder="<?= __($langGroup, "<script src='ejemplo.js'></script>"); ?>"><?=AppConfigModel::getConfigValue('extra_scripts');?></textarea>
        <input type="hidden" name="name" value="extra_scripts">
    </div>

    <div class="field">
        <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
    </div>

</form>

<br><br>

<?php endif; ?>

<?php if(mb_strlen($actionSitemapURL) > 0): ?>

<form pcs-generic-handler-js action="<?= $actionSitemapURL; ?>" method="POST" class="ui form">

    <div class="field">
        <label><?= __($langGroup, 'Generar sitemap'); ?></label>
    </div>

    <div class="field">
        <button type="submit" class="ui button green"><?= __($langGroup, 'Generar'); ?></button>
    </div>

</form>

<?php endif; ?>
