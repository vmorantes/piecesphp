<?php 
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
$langGroup = LOGIN_REPORT_LANG_GROUP;
?>

<h3 class="ui dividing header">
    <?= __($langGroup, 'Registro de ingresos'); ?>
</h3>

<div class="ui tabular menu">
    <div class="item active" data-tab="attempts"><?= __($langGroup, 'Registro de intentos de inicio'); ?></div>
</div>

<div class="ui tab active" data-tab="attempts">
    <table process="<?=get_route('informes-acceso-ajax',['type'=>'attempts']);?>"
        class="ui table stripped celled attempts" style="max-width:100%;width:100%;">
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
    let tableAttempts = $('table.ui.table.attempts')
    dataTableServerProccesing(tableAttempts, tableAttempts.attr('process'), 25)
}
</script>
