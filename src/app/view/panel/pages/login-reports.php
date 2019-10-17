<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<h3 class="ui dividing header">
    <?= __('loginReport', 'Registro de ingresos'); ?>
</h3>

<div class="ui tabular menu">
    <div class="item active" data-tab="logged"><?= __('loginReport', 'Usuarios que han ingresado'); ?></div>
    <div class="item" data-tab="not-logged"><?= __('loginReport', 'Usuarios que no han ingresado'); ?></div>
    <div class="item" data-tab="attempts"><?= __('loginReport', 'Registro de intentos de inicio'); ?></div>
</div>

<div class="ui tab active" data-tab="logged">
    <table process="<?=get_route('informes-acceso-ajax', ['type'=>'logged']);?>" class="ui table stripped celled logged"
        style="max-width:100%;width:100%;">
        <thead>
            <tr>
                <th><?= __('loginReport', 'ID'); ?></th>
                <th><?= __('loginReport', 'Nombre'); ?></th>
                <th><?= __('loginReport', 'Último acceso'); ?></th>
                <th><?= __('loginReport', 'Tiempo en plataforma'); ?></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<div class="ui tab" data-tab="not-logged">
    <table process="<?=get_route('informes-acceso-ajax');?>" class="ui table stripped celled not-logged"
        style="max-width:100%;width:100%;">
        <thead>
            <tr>
                <th><?= __('loginReport', 'ID'); ?></th>
                <th><?= __('loginReport', 'Nombre'); ?></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<div class="ui tab" data-tab="attempts">
    <table process="<?=get_route('informes-acceso-ajax',['type'=>'attempts']);?>"
        class="ui table stripped celled attempts" style="max-width:100%;width:100%;">
        <thead>
            <tr>
                <th><?= __('loginReport', 'Usuario ingresado'); ?></th>
                <th><?= __('loginReport', 'Intento exitoso'); ?></th>
                <th><?= __('loginReport', 'Información'); ?></th>
                <th><?= __('loginReport', 'IP'); ?></th>
                <th><?= __('loginReport', 'Fecha'); ?></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<script>
window.onload = function(e) {
    $('.tabular.menu .item').tab()
    let tableLogged = $('table.ui.table.logged')
    let tableNotLogged = $('table.ui.table.not-logged')
    let tableAttempts = $('table.ui.table.attempts')
    dataTableServerProccesing(tableLogged, tableLogged.attr('process'), 25)
    dataTableServerProccesing(tableNotLogged, tableNotLogged.attr('process'), 25)
    dataTableServerProccesing(tableAttempts, tableAttempts.attr('process'), 25)

}
</script>
