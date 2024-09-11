<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\UsersController;
use App\Model\UsersModel;
$langGroup = UsersController::LANG_GROUP;
$currentUser = getLoggedFrameworkUser();
$title = __($langGroup, 'Usuarios');
$langGroupDatatables = 'datatables';

$processTableAll = $process_table;
$processTableActives = $process_table . '?with-status=' . UsersModel::STATUS_USER_ACTIVE;
$processTableInactives = $process_table . '?with-status=' . UsersModel::STATUS_USER_INACTIVE;
$processTableBlocked = $process_table . '?with-status=' . UsersModel::STATUS_USER_ATTEMPTS_BLOCK;
?>


<section class="module-view-container limit-size">

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

    <div class="cards-container-standard">

        <div class="tabs-controls">
            <div class="active" data-tab="all"><?= __($langGroup, 'Todos'); ?></div>
            <div data-tab="actives-elements"><?= __($langGroup, 'Activos'); ?></div>
            <div data-tab="inactives"><?= __($langGroup, 'Inactivos'); ?></div>
            <div data-tab="blocked"><?= __($langGroup, 'Bloqueados por intentos fallidos'); ?></div>
        </div>

        <div class="ui tab tab-element active" data-tab="all">

            <div class="table-to-cards all padding-40">

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

                <table url="<?= $processTableAll; ?>" style='display:none;'>

                    <thead>

                        <tr>
                            <th><?=__($langGroup, '#');?></th>
                            <th><?=__($langGroup, 'Nombres');?></th>
                            <th><?=__($langGroup, 'Apellidos');?></th>
                            <th><?=__($langGroup, 'Correo electrónico');?></th>
                            <th><?=__($langGroup, 'Usuario');?></th>
                            <th><?=__($langGroup, 'Activo/Inactivo');?></th>
                            <th><?=__($langGroup, 'Tipo');?></th>
                            <th order="false" class-name="buttons" with-container="true"><?=__($langGroup, 'Acciones');?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

        <div class="ui tab tab-element" data-tab="actives-elements">

            <div class="table-to-cards actives-elements padding-40">

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

                <table url="<?= $processTableActives; ?>" style='display:none;'>

                    <thead>

                        <tr>
                            <th><?=__($langGroup, '#');?></th>
                            <th><?=__($langGroup, 'Nombres');?></th>
                            <th><?=__($langGroup, 'Apellidos');?></th>
                            <th><?=__($langGroup, 'Correo electrónico');?></th>
                            <th><?=__($langGroup, 'Usuario');?></th>
                            <th><?=__($langGroup, 'Activo/Inactivo');?></th>
                            <th><?=__($langGroup, 'Tipo');?></th>
                            <th order="false" class-name="buttons" with-container="true"><?=__($langGroup, 'Acciones');?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

        <div class="ui tab tab-element" data-tab="inactives">

            <div class="table-to-cards inactives padding-40">

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

                <table url="<?= $processTableInactives; ?>" style='display:none;'>

                    <thead>

                        <tr>
                            <th><?=__($langGroup, '#');?></th>
                            <th><?=__($langGroup, 'Nombres');?></th>
                            <th><?=__($langGroup, 'Apellidos');?></th>
                            <th><?=__($langGroup, 'Correo electrónico');?></th>
                            <th><?=__($langGroup, 'Usuario');?></th>
                            <th><?=__($langGroup, 'Activo/Inactivo');?></th>
                            <th><?=__($langGroup, 'Tipo');?></th>
                            <th order="false" class-name="buttons" with-container="true"><?=__($langGroup, 'Acciones');?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

        <div class="ui tab tab-element" data-tab="blocked">

            <div class="table-to-cards blocked padding-40">

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

                <table url="<?= $processTableBlocked; ?>" style='display:none;'>

                    <thead>

                        <tr>
                            <th><?=__($langGroup, '#');?></th>
                            <th><?=__($langGroup, 'Nombres');?></th>
                            <th><?=__($langGroup, 'Apellidos');?></th>
                            <th><?=__($langGroup, 'Correo electrónico');?></th>
                            <th><?=__($langGroup, 'Usuario');?></th>
                            <th><?=__($langGroup, 'Activo/Inactivo');?></th>
                            <th><?=__($langGroup, 'Tipo');?></th>
                            <th order="false" class-name="buttons" with-container="true"><?=__($langGroup, 'Acciones');?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

    </div>

</section>

<script>
window.onload = function(e) {
    //Cards
    dataTablesServerProccesingOnCards(".table-to-cards.all", 20, {});
    dataTablesServerProccesingOnCards(".table-to-cards.actives-elements", 20, {});
    dataTablesServerProccesingOnCards(".table-to-cards.inactives", 20, {});
    dataTablesServerProccesingOnCards(".table-to-cards.blocked", 20, {});
    //Tabs
    const tabs = $('.tabs-controls [data-tab]').tab({
        onVisible: function(tabName) {}
    })
}
</script>
