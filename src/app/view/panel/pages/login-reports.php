<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<h3 class="ui dividing header">
    Registro de ingresos
</h3>

<div class="ui tabular menu">
    <div class="item active" data-tab="logged">Usuarios que han ingresado</div>
    <div class="item" data-tab="not-logged">Usuarios que no han ingresado</div>
    <div class="item" data-tab="attempts">Registro de intentos de inicio</div>
</div>

<div class="ui tab active" data-tab="logged">
    <table process="<?=get_route('informes-acceso-ajax', ['type'=>'logged']);?>" class="ui table stripped celled logged"
        style="max-width:100%;width:100%;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Último acceso</th>
                <th>Tiempo en plataforma</th>
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
                <th>ID</th>
                <th>Nombre</th>
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
                <th>Usuario ingresado</th>
                <th>Intento exitoso</th>
                <th>Información</th>
                <th>IP</th>
                <th>Fecha</th>
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
