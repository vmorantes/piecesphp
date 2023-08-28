<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\UsersController;
$langGroup = UsersController::LANG_GROUP;
$currentUser = getLoggedFrameworkUser();
$title = __($langGroup, 'Usuarios');
?>


<section class="module-view-container">

    <div class="header-options">

        <div class="main-options">

            <a href="<?= get_route('admin'); ?>" class="ui icon button brand-color alt2" title="<?=__($langGroup, 'Regresar');?>">
                <i class="icon left arrow"></i>
            </a>

        </div>

        <div class="columns two">

            <div class="column">

                <div class="section-title">
                    <div class="title"><?= $title;?></div>
                </div>

            </div>

            <div class="column bottom right">

                <?php if(\PiecesPHP\Core\Roles::hasPermissions('users-selection-create', getLoggedFrameworkUser()->type, true)):?>
                <a href="<?= get_route('users-selection-create'); ?>" class="ui button green"><?= __($langGroup, 'Agregar'); ?></a>
                <?php endif;?>

            </div>


        </div>

    </div>

    <div class="mirror-scroll-x" mirror-scroll-target=".container-standard-table.qualified">
        <div class="mirror-scroll-x-content"></div>
    </div>

    <div class="container-standard-table qualified">

        <table process="<?=$process_table;?>" class="ui table striped celled users" style="max-width:100%;width:100%;">
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

    </div>

</section>

<script>
window.onload = function(e) {
    let table = $(`[process]`)
    let processURL = table.attr('process')
    dataTableServerProccesing(table, processURL, 25)
}
</script>
