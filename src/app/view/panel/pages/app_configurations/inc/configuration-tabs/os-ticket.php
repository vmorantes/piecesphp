<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Model\AppConfigModel;
?>
<?php if(mb_strlen($actionOsTicketURL) > 0): ?>

<form pcs-generic-handler-js action="<?= $actionOsTicketURL; ?>" method="POST" class="ui form">

    <div class="field">
        <label><?= __($langGroup, 'URL'); ?></label>
        <input type="text" name="url" value="<?=AppConfigModel::getConfigValue('osTicketAPI');?>" placeholder="<?= __($langGroup, 'https://api.dominio.com/'); ?>" required>
    </div>

    <div class="field">
        <label><?= __($langGroup, 'Key'); ?></label>
        <input autocomplete="off" type="text" name="key" value="<?=AppConfigModel::getConfigValue('osTicketAPIKey');?>" placeholder="ABCD123456EFGH" required>
    </div>

    <div class="field">
        <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
    </div>

</form>

<?php endif; ?>
