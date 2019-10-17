<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<div style="max-width:850px;">

    <h3 class="ui dividing header">
        <?=__('usersModule', 'Usuarios');?>
    </h3>

    <div class="ui buttons">
        <?php if(\PiecesPHP\Core\Roles::hasPermissions('users-selection-create', (int)get_config('current_user')->type, true)):?>
        <a href="<?= get_route('users-selection-create'); ?>" class="ui button green"><?= __('usersModule', 'Agregar'); ?></a>
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
            <th><?= __('usersModule', '#'); ?></th>
            <th><?= __('usersModule', 'Nombres'); ?></th>
            <th><?= __('usersModule', 'Apellidos'); ?></th>
            <th><?= __('usersModule', 'Correo electrónico'); ?></th>
            <th><?= __('usersModule', 'Usuario'); ?></th>
            <th><?= __('usersModule', 'Activo/Inactivo'); ?></th>
            <th><?= __('usersModule', 'Tipo'); ?></th>
            <th order='false'><?= __('usersModule', 'Acciones'); ?></th>
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
