<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\BuiltIn\Article\Controllers\ArticleController;

$langGroup = ArticleController::LANG_GROUP;
$lanGroupDataTables = 'datatables';
?>

<div style="max-width:850px;">

    <div class="ui buttons">
        <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
        <?php if ($has_permissions_add): ?>
        <a href="<?=$add_link;?>" class="ui button green"><?= __($langGroup, 'Agregar'); ?></a>
        <?php endif;?>
    </div>

</div>

<br>
<br>

<div style="max-width:100%;">

    <div class="table-to-cards">

        <div class="ui form component-controls">

            <div class="fields">

                <div class="field">
                    <label><?= __($lanGroupDataTables, 'Buscador')?></label>
                    <div class="ui transparent icon input">
                        <input type="search" placeholder="<?= __($lanGroupDataTables, 'Buscar')?>">
                        <i class="search icon"></i>
                    </div>
                </div>

                <div class="field">
                    <label><?= __($lanGroupDataTables, 'Resultados visibles')?></label>
                    <input type="number" length-pagination placeholder="10">
                </div>

                <div class="field">
                    <label><?= __($lanGroupDataTables, 'Ordenar por')?>:</label>
                    <select class="ui dropdown" options-order></select>
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <select class="ui dropdown" options-order-type>
                        <option selected value="ASC"><?= __($lanGroupDataTables, 'ASC')?></option>
                        <option value="DESC"><?= __($lanGroupDataTables, 'DESC')?></option>
                    </select>
                </div>

            </div>

        </div>

        <table url="<?=$process_table;?>" style='display:none;'>
            <thead>
                <tr>
                    <th><?=__($langGroup, 'ID');?></th>
                    <th><?=__($langGroup, 'Título');?></th>
                    <th><?=__($langGroup, 'Autor');?></th>
                    <th><?=__($langGroup, 'Categoría');?></th>
                    <th><?=__($langGroup, 'Inicio');?></th>
                    <th><?=__($langGroup, 'Fin');?></th>
                    <th><?=__($langGroup, 'Creado');?></th>
                    <th><?=__($langGroup, 'Editado');?></th>
                    <th><?=__($langGroup, 'Visitas');?></th>
                    <th order='false' search='false'><?=__($langGroup, 'Acciones');?></th>
                </tr>
            </thead>
        </table>

    </div>

</div>
