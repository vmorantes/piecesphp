<?php 
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
$langGroup = LOGIN_REPORT_LANG_GROUP;
?>

<h3 class="ui dividing header">
    <?= __($langGroup, 'Registro de ingresos'); ?>
</h3>

<div class="ui tabular menu">
    <div class="item active" data-tab="logged"><?= __($langGroup, 'Usuarios que han ingresado'); ?></div>
</div>

<div class="ui tab active" data-tab="logged">
    <table process="<?=get_route('informes-acceso-ajax', ['type'=>'logged']);?>" class="ui table stripped celled logged"
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

<script>
window.onload = function(e) {
    $('.tabular.menu .item').tab()
    let tableLogged = $('table.ui.table.logged')
    dataTableServerProccesing(tableLogged, tableLogged.attr('process'), 25)
}
</script>
