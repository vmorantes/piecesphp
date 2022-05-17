<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\UsersController;
$langGroup = UsersController::LANG_GROUP;
?>

<div style="max-width:850px;">

    <h3 class="ui dividing header">
        <?=__($langGroup, 'Usuarios');?>
    </h3>

    <div class="ui buttons">
        <?php if(\PiecesPHP\Core\Roles::hasPermissions('users-selection-create', getLoggedFrameworkUser()->type, true)):?>
        <a href="<?= get_route('users-selection-create'); ?>" class="ui button green"><?= __($langGroup, 'Agregar'); ?></a>
        <?php endif;?>
    </div>
    <br>
    <br>
    <br>
</div>


<table process="<?=$process_table;?>" class="ui table striped celled inverted grey users" style="max-width:100%;width:100%;">
    <thead>
        <tr>
            <th><?= __($langGroup, '#'); ?></th>
            <th><?= __($langGroup, 'Nombres'); ?></th>
            <th><?= __($langGroup, 'Apellidos'); ?></th>
            <th><?= __($langGroup, 'Correo electrónico'); ?></th>
            <th><?= __($langGroup, 'Usuario'); ?></th>
            <th><?= __($langGroup, 'Activo/Inactivo'); ?></th>
            <th><?= __($langGroup, 'Tipo'); ?></th>
            <th order='false'><?= __($langGroup, 'Acciones'); ?></th>
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
