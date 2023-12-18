<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\UsersController;
$langGroup = UsersController::LANG_GROUP;
$currentUser = getLoggedFrameworkUser();
$title = __($langGroup, 'Usuarios');
$langGroupDatatables = 'datatables';
?>


<section class="module-view-container">

    <div class="header-options">

        <div class="main-options">

            <a href="<?=get_route('admin');?>" class="ui icon button brand-color alt2" title="<?=__($langGroup, 'Regresar');?>">
                <i class="icon left arrow"></i>
            </a>

        </div>

        <div class="columns two">

            <div class="column">

                <div class="section-title">
                    <div class="title"><?=$title;?></div>
                </div>

            </div>


        </div>

    </div>

    <div class="mirror-scroll-x" mirror-scroll-target=".container-standard-table.qualified">
        <div class="mirror-scroll-x-content"></div>
    </div>

    <div class="cards-container-standard">
        <div class="table-to-cards">

            <div class="ui form component-controls">
                <div class="fields">
                    <div class="field">
                        <label><?=__($langGroupDatatables, 'Buscador')?></label>
                        <div class="ui icon input">
                            <input type="search" placeholder="<?=__($langGroupDatatables, 'Buscar')?>">
                            <i class="search icon"></i>
                        </div>
                    </div>
                    <div class="field">
                        <label><?=__($langGroupDatatables, 'Resultados visibles')?></label>
                        <input type="number" length-pagination placeholder="10">
                    </div>
                </div>
            </div>

            <table url="<?=$process_table;?>" style='display:none;'>

                <thead>

                    <tr>
                        <th><?=__($langGroup, '#');?></th>
                        <th><?=__($langGroup, 'Nombres');?></th>
                        <th><?=__($langGroup, 'Apellidos');?></th>
                        <th><?=__($langGroup, 'Correo electrónico');?></th>
                        <th><?=__($langGroup, 'Usuario');?></th>
                        <th><?=__($langGroup, 'Activo/Inactivo');?></th>
                        <th><?=__($langGroup, 'Tipo');?></th>
                        <th order='false'><?=__($langGroup, 'Acciones');?></th>
                    </tr>

                </thead>

            </table>

        </div>
    </div>

</section>

<script>
window.onload = function(e) {
    dataTablesServerProccesingOnCards(".table-to-cards", 20, {});
}
</script>
