<?php 
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
$langGroup = LOGIN_REPORT_LANG_GROUP;
?>

<h3 class="ui dividing header">
    <?= __($langGroup, 'Registro de ingresos'); ?>
</h3>

<div class="ui tabular menu">
    <div class="item active" data-tab="not-logged"><?= __($langGroup, 'Usuarios que no han ingresado'); ?></div>
</div>

<div class="ui tab active" data-tab="not-logged">
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

<script>
window.onload = function(e) {
    $('.tabular.menu .item').tab()
    let tableNotLogged = $('table.ui.table.not-logged')
    dataTableServerProccesing(tableNotLogged, tableNotLogged.attr('process'), 25)
}
</script>
