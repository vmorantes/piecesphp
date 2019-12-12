<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\AppConfigController;
use App\Model\AppConfigModel;

?>


<div class="container-medium">

    <div class="ui top attached tabular menu">
        <a class="item active" data-tab="general"><?= __('configurationsAdminZone', 'Generales'); ?></a>
        <a class="item" data-tab="email"><?= __('configurationsAdminZone', 'Email'); ?></a>
        <a class="item" data-tab="os-ticket"><?= __('configurationsAdminZone', 'OsTicket'); ?></a>
    </div>

    <div class="ui bottom attached tab segment active" data-tab="general">

        <form pcs-generic-handler-js action="<?=get_route('configurations-generals-generic-action');?>" method="POST"
            class="ui form">

            <div class="field">
                <label><?= __('configurationsAdminZone', 'Título del sitio'); ?></label>
                <input type="text" name="value" value="<?=htmlentities(AppConfigModel::getConfigValue('title_app'));?>"
                    placeholder="<?= __('configurationsAdminZone', 'Nombre'); ?>" required>
                <input type="hidden" name="name" value="title_app" required="required">
            </div>

            <div class="field">
                <button type="submit" class="ui button green"><?= __('configurationsAdminZone', 'Guardar'); ?></button>
            </div>

        </form>

        <br><br>

        <form pcs-generic-handler-js action="<?=get_route('configurations-generals-generic-action');?>" method="POST"
            class="ui form">

            <div class="field">
                <label><?= __('configurationsAdminZone', 'Propietario'); ?></label>
                <input type="text" name="value" value="<?=htmlentities(AppConfigModel::getConfigValue('owner'));?>"
                    placeholder="<?= __('configurationsAdminZone', 'Propietario'); ?>" required>
                <input type="hidden" name="name" value="owner" required="required">
            </div>

            <div class="field">
                <button type="submit" class="ui button green"><?= __('configurationsAdminZone', 'Guardar'); ?></button>
            </div>

        </form>

        <br><br>

        <form pcs-generic-handler-js action="<?=get_route('configurations-generals-generic-action');?>" method="POST"
            class="ui form">

            <div class="field">
                <label><?= __('configurationsAdminZone', 'Descripción'); ?></label>
                <textarea name="value" placeholder="<?= __('configurationsAdminZone', 'Descripción'); ?>"
                    required><?=AppConfigModel::getConfigValue('description');?></textarea>
                <input type="hidden" name="name" value="description" required="required">
            </div>

            <div class="field">
                <button type="submit" class="ui button green"><?= __('configurationsAdminZone', 'Guardar'); ?></button>
            </div>

        </form>

        <br><br>

        <form pcs-generic-handler-js action="<?=get_route('configurations-generals-generic-action');?>" method="POST"
            class="ui form">

            <div class="field">
                <label><?= __('configurationsAdminZone', 'Palabras clave'); ?></label>
                <?php $keywords = AppConfigModel::getConfigValue('keywords'); ?>
                <select name="value[]" multiple class="ui dropdown multiple search selection additions" required>

                    <?php if (is_array($keywords) && count($keywords) > 0): ?>

                    <?php foreach ($keywords as $key => $value): ?>

                    <option selected value="<?=htmlentities($value);?>"><?=htmlentities($value);?></option>

                    <?php endforeach;?>

                    <?php else: ?>

                    <option value=""><?= __('configurationsAdminZone', 'Agregue alguna palabra clave'); ?></option>

                    <?php endif;?>

                </select>
                <input type="hidden" name="name" value="keywords" required="required">
            </div>

            <div class="field">
                <button type="submit" class="ui button green"><?= __('configurationsAdminZone', 'Guardar'); ?></button>
            </div>

        </form>

        <br><br>

        <form pcs-generic-handler-js action="<?=get_route('configurations-generals-generic-action');?>" method="POST"
            class="ui form">

            <div class="field">
				<label><?= __('configurationsAdminZone', 'Color de barra superior en navegadores móviles'); ?></label>
				<input type="text" name="value" value="<?=htmlentities(AppConfigModel::getConfigValue('meta_theme_color'));?>" color-picker-js>
                <input type="hidden" name="name" value="meta_theme_color">
                <input type="hidden" name="parse" value="uppercase">
            </div>

            <div class="field">
                <button type="submit" class="ui button green"><?= __('configurationsAdminZone', 'Guardar'); ?></button>
            </div>

        </form>

        <br><br>

        <form pcs-generic-handler-js action="<?=get_route('configurations-generals-generic-action');?>" method="POST"
            class="ui form">

            <div class="field">
                <label><?= __('configurationsAdminZone', 'Scripts adicionales'); ?></label>
                <textarea name="value"
                    placeholder="<?= __('configurationsAdminZone', "<script src='ejemplo.js'></script>"); ?>"><?=AppConfigModel::getConfigValue('extra_scripts');?></textarea>
                <input type="hidden" name="name" value="extra_scripts">
            </div>

            <div class="field">
                <button type="submit" class="ui button green"><?= __('configurationsAdminZone', 'Guardar'); ?></button>
            </div>

        </form>

        <br><br>

        <form pcs-generic-handler-js action="<?=get_route('configurations-generals-sitemap-create');?>" method="POST"
            class="ui form">

            <div class="field">
                <label><?= __('configurationsAdminZone', 'Generar sitemap'); ?></label>
            </div>

            <div class="field">
                <button type="submit" class="ui button green"><?= __('configurationsAdminZone', 'Generar'); ?></button>
            </div>

        </form>

    </div>

    <div class="ui bottom attached tab segment" data-tab="email">

        <form mail-configuration-form action="<?=get_route('configurations-generals-generic-action');?>" method="POST"
            class="ui form">

            <input type="hidden" name="name" value="mail" required>
            <input type="hidden" name="parse[auto_tls]" value="<?=AppConfigController::PARSE_TYPE_BOOL;?>" required>
            <input type="hidden" name="parse[auth]" value="<?=AppConfigController::PARSE_TYPE_BOOL;?>" required>
            <input type="hidden" name="parse[port]" value="<?=AppConfigController::PARSE_TYPE_INT;?>" required>

            <div class="fields">

                <div class="field">
                    <div class="ui toggle checkbox">
                        <input type="checkbox" name="value[auto_tls]"
                            <?=AppConfigModel::getConfigValue('mail')->auto_tls ? 'checked' : '';?>>
                        <label><?= __('configurationsAdminZone', 'Auto TLS'); ?></label>
                    </div>
                </div>

                <div class="field">
                    <div class="ui toggle checkbox">
                        <input type="checkbox" name="value[auth]"
                            <?=AppConfigModel::getConfigValue('mail')->auth == true ? 'checked' : '';?>>
                        <label><?= __('configurationsAdminZone', 'Autenticar'); ?></label>
                    </div>
                </div>

            </div>

            <div class="ui divider"></div>

            <div class="fields three">

                <div class="field">
                    <label><?= __('configurationsAdminZone', 'Host'); ?></label>
                    <input type="text" name="value[host]" value="<?=AppConfigModel::getConfigValue('mail')->host;?>"
                        required>
                </div>

                <div class="field">
                    <label><?= __('configurationsAdminZone', 'Protocolo'); ?></label>
                    <input type="text" name="value[protocol]"
                        value="<?=AppConfigModel::getConfigValue('mail')->protocol;?>" required>
                </div>

                <div class="field">
                    <label><?= __('configurationsAdminZone', 'Puerto'); ?></label>
                    <input type="text" name="value[port]" value="<?=AppConfigModel::getConfigValue('mail')->port;?>"
                        required>
                </div>

            </div>

            <div class="ui divider"></div>

            <div class="fields two">

                <div class="field">
                    <label><?= __('configurationsAdminZone', 'Correo electrónico'); ?></label>
                    <input type="text" name="value[user]" value="<?=AppConfigModel::getConfigValue('mail')->user;?>"
                        required>
                </div>

                <div class="field">
                    <label><?= __('configurationsAdminZone', 'Contraseña'); ?></label>
                    <div class="ui icon input" show-hide-password-event>
                        <input type="password" name="value[password]"
                            value="<?=htmlentities(AppConfigModel::getConfigValue('mail')->password);?>" required>
                        <i class="inverted circular eye link icon"></i>
                    </div>
                </div>

            </div>

            <div class="field">
                <button type="submit" class="ui button green"><?= __('configurationsAdminZone', 'Guardar'); ?></button>
            </div>

        </form>

    </div>

    <div class="ui bottom attached tab segment" data-tab="os-ticket">

        <form pcs-generic-handler-js action="<?=get_route('configurations-generals-osticket-action');?>" method="POST"
            class="ui form">

            <div class="field">
                <label><?= __('configurationsAdminZone', 'URL'); ?></label>
                <input type="text" name="url" value="<?=AppConfigModel::getConfigValue('osTicketAPI');?>"
                    placeholder="<?= __('configurationsAdminZone', 'https://api.dominio.com/'); ?>" required>
            </div>

            <div class="field">
                <label><?= __('configurationsAdminZone', 'Key'); ?></label>
                <input autocomplete="off" type="text" name="key"
                    value="<?=AppConfigModel::getConfigValue('osTicketAPIKey');?>" placeholder="ABCD123456EFGH"
                    required>
            </div>

            <div class="field">
                <button type="submit" class="ui button green"><?= __('configurationsAdminZone', 'Guardar'); ?></button>
            </div>

        </form>

    </div>

</div>

<script>
window.addEventListener('load', function(e) {

    //Inicializaciones generales
    $('.ui.top.menu .item').tab()
    $('.ui.checkbox').checkbox()
    $('.ui.dropdown.additions')
        .dropdown({
            allowAdditions: true
        })

    //Eventos

    //Mostrar/ocultar contraseña
    $('[show-hide-password-event] .icon').click(function(e) {
        let that = $(e.target)
        let parent = that.parent()
        let input = parent.find('input')

        if (input.attr('type') == 'text') {
            that.removeClass('eye slash')
            that.addClass('eye')
            input.attr('type', 'password')
        } else {
            that.removeClass('eye')
            that.addClass('eye slash')
            input.attr('type', 'text')
        }

    })

    //Formulario mail
    genericFormHandler(
        'form[mail-configuration-form]', {
            onSetFormData: (formData, form) => {

                formData.set(
                    'value[auto_tls]',
                    form.find(`[name="value[auto_tls]"]`).parent().checkbox('is checked') ? true :
                    false
                )
                formData.set(
                    'value[auth]',
                    form.find(`[name="value[auth]"]`).parent().checkbox('is checked') ? true : false
                )

                return formData
            },
        }
    )

})
</script>
<style>
.ui.form {
    max-width: 800px;
}
</style>
