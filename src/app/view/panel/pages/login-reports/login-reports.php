<?php 
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
$langGroup = LOGIN_REPORT_LANG_GROUP;
?>

<h3 class="ui dividing header">
    <?= __($langGroup, 'Registro de ingresos'); ?>
</h3>

<div class="ui tabular menu">
    <div class="item active" data-tab="logged"><?= __($langGroup, 'Usuarios que han ingresado'); ?></div>
    <div class="item" data-tab="not-logged"><?= __($langGroup, 'Usuarios que no han ingresado'); ?></div>
    <div class="item" data-tab="attempts"><?= __($langGroup, 'Registro de intentos de inicio'); ?></div>
</div>

<div class="ui tab active" data-tab="logged">
    <table process="<?=get_route('informes-acceso-ajax', ['type'=>'logged']);?>" class="ui table striped celled logged"
        style="max-width:100%;width:100%;">
        <thead>
            <tr>
                <th><?= __($langGroup, 'ID'); ?></th>
                <th><?= __($langGroup, 'Nombre'); ?></th>
                <th><?= __($langGroup, 'Último acceso'); ?></th>
                <th><?= __($langGroup, 'Tiempo en plataforma'); ?></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<div class="ui tab" data-tab="not-logged">
    <table process="<?=get_route('informes-acceso-ajax');?>" class="ui table striped celled not-logged"
        style="max-width:100%;width:100%;">
        <thead>
            <tr>
                <th><?= __($langGroup, 'ID'); ?></th>
                <th><?= __($langGroup, 'Nombre'); ?></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<div class="ui tab" data-tab="attempts">
    <table process="<?=get_route('informes-acceso-ajax',['type'=>'attempts']);?>"
        class="ui table striped celled attempts" style="max-width:100%;width:100%;">
        <thead>
            <tr>
                <th><?= __($langGroup, 'Usuario ingresado'); ?></th>
                <th><?= __($langGroup, 'Intento exitoso'); ?></th>
                <th><?= __($langGroup, 'Información'); ?></th>
                <th><?= __($langGroup, 'IP'); ?></th>
                <th><?= __($langGroup, 'Fecha'); ?></th>
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
