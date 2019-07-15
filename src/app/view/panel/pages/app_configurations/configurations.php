<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Model\AppConfigModel;
?>


<div class="container-medium">

    <div class="ui top attached tabular menu">
        <a class="item active" data-tab="general">Generales</a>
        <a class="item" data-tab="os-ticket">OsTicket</a>
    </div>

    <div class="ui bottom attached tab segment active" data-tab="general">

        <form pcs-generic-handler-js action="<?=get_route('configurations-generals-generic-action');?>" method="POST"
            class="ui form">

            <div class="field">
                <label>Nombre del sitio</label>
                <input type="text" name="value" value="<?=AppConfigModel::getConfigValue('title_app');?>"
                    placeholder="Nombre" required>
                    <input type="hidden" name="name" value="title_app" required="required">
            </div>

            <div class="field">
                <button type="submit" class="ui button green">Guardar</button>
            </div>

        </form>

    </div>

    <div class="ui bottom attached tab segment" data-tab="os-ticket">

        <form pcs-generic-handler-js action="<?=get_route('configurations-generals-osticket-action');?>" method="POST"
            class="ui form">

            <div class="field">
                <label>URL</label>
                <input type="text" name="url" value="<?=AppConfigModel::getConfigValue('osTicketAPI');?>"
                    placeholder="https://api.dominio.com/" required>
            </div>

            <div class="field">
                <label>Key</label>
                <input autocomplete="off" type="text" name="key" value="<?=AppConfigModel::getConfigValue('osTicketAPIKey');?>"
                    placeholder="ABCD123456EFGH" required>
            </div>

            <div class="field">
                <button type="submit" class="ui button green">Guardar</button>
            </div>

        </form>

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
</style>