<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<div style="max-width:850px;">

    <h3 class="ui dividing header">
        <?=__('general', 'users');?>
    </h3>

    <div class="ui buttons">
        <?php if(\PiecesPHP\Core\Roles::hasPermissions('users-selection-create', (int)get_config('current_user')->type, true)):?>
        <a href="<?= get_route('users-selection-create'); ?>" class="ui button green">Agregar</a>
        <?php endif;?>
    </div>
	<br>
	<br>
	<br>
</div>


<table process="<?=$process_table;?>" class="ui table stripped celled inverted grey users"
    style="max-width:100%;width:100%;">
    <thead>
        <tr>
            <th>#</th>
            <th>Nombres</th>
            <th>Apellidos</th>
            <th>Correo electrónico</th>
            <th>Usuario</th>
            <th>Activo/Inactivo</th>
            <th>Tipo</th>
            <th order='false'>Acciones</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>


<script>
window.onload = function(e) {
    let table = $(`[process]`)
    let processURL = table.attr('process')
    dataTableServerProccesing(table, processURL, 25)
}
</script>
