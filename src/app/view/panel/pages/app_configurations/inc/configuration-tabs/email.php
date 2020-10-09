<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\AppConfigController;
use App\Model\AppConfigModel;
?>
<?php if(mb_strlen($actionGenericURL) > 0): ?>

<form mail-configuration-form action="<?= $actionGenericURL; ?>" method="POST" class="ui form">

    <input type="hidden" name="name" value="mail" required>
    <input type="hidden" name="parse[auto_tls]" value="<?=AppConfigController::PARSE_TYPE_BOOL;?>" required>
    <input type="hidden" name="parse[auth]" value="<?=AppConfigController::PARSE_TYPE_BOOL;?>" required>
    <input type="hidden" name="parse[port]" value="<?=AppConfigController::PARSE_TYPE_INT;?>" required>

    <div class="fields">

        <div class="field">
            <div class="ui toggle checkbox">
                <input type="checkbox" name="value[auto_tls]" <?=AppConfigModel::getConfigValue('mail')->auto_tls ? 'checked' : '';?>>
                <label><?= __($langGroup, 'Auto TLS'); ?></label>
            </div>
        </div>

        <div class="field">
            <div class="ui toggle checkbox">
                <input type="checkbox" name="value[auth]" <?=AppConfigModel::getConfigValue('mail')->auth == true ? 'checked' : '';?>>
                <label><?= __($langGroup, 'Autenticar'); ?></label>
            </div>
        </div>

    </div>

    <div class="ui divider"></div>

    <div class="fields three">

        <div class="field">
            <label><?= __($langGroup, 'Host'); ?></label>
            <input type="text" name="value[host]" value="<?=AppConfigModel::getConfigValue('mail')->host;?>" required>
        </div>

        <div class="field">
            <label><?= __($langGroup, 'Protocolo'); ?></label>
            <input type="text" name="value[protocol]" value="<?=AppConfigModel::getConfigValue('mail')->protocol;?>" required>
        </div>

        <div class="field">
            <label><?= __($langGroup, 'Puerto'); ?></label>
            <input type="text" name="value[port]" value="<?=AppConfigModel::getConfigValue('mail')->port;?>" required>
        </div>

    </div>

    <div class="ui divider"></div>

    <div class="fields two">

        <div class="field">
            <label><?= __($langGroup, 'Correo electrónico'); ?></label>
            <input type="text" name="value[user]" value="<?=AppConfigModel::getConfigValue('mail')->user;?>" required>
        </div>

        <div class="field">
            <label><?= __($langGroup, 'Contraseña'); ?></label>
            <div class="ui icon input" show-hide-password-event>
                <input type="password" name="value[password]" value="<?=htmlentities(AppConfigModel::getConfigValue('mail')->password);?>" required>
                <i class="inverted circular eye link icon"></i>
            </div>
        </div>

    </div>

    <div class="field">
        <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
    </div>

</form>

<?php endif; ?>
