<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<div style="max-width:850px;">

    <div class="ui buttons">
        <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
        <?php if ($has_permissions_add): ?>
        <a href="<?=$add_link;?>" class="ui button green"><?= __('articlesBackend', 'Agregar'); ?></a>
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
                    <label><?= __('datatables', 'Buscador')?></label>
                    <div class="ui transparent icon input">
                        <input type="search" placeholder="<?= __('datatables', 'Buscar')?>">
                        <i class="search icon"></i>
                    </div>
                </div>

                <div class="field">
                    <label><?= __('datatables', 'Resultados visibles')?></label>
                    <input type="number" length-pagination placeholder="10">
                </div>

                <div class="field">
                    <label><?= __('datatables', 'Ordenar por')?>:</label>
                    <select class="ui dropdown" options-order></select>
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <select class="ui dropdown" options-order-type>
                        <option selected value="ASC"><?= __('datatables', 'ASC')?></option>
                        <option value="DESC"><?= __('datatables', 'DESC')?></option>
                    </select>
                </div>

            </div>

        </div>

        <table url="<?=$process_table;?>" style='display:none;'>
            <thead>
                <tr>
                    <th><?=__('articlesBackend', 'ID');?></th>
                    <th><?=__('articlesBackend', 'Título');?></th>
                    <th><?=__('articlesBackend', 'Autor');?></th>
                    <th><?=__('articlesBackend', 'Categoría');?></th>
                    <th><?=__('articlesBackend', 'Inicio');?></th>
                    <th><?=__('articlesBackend', 'Fin');?></th>
                    <th><?=__('articlesBackend', 'Creado');?></th>
                    <th><?=__('articlesBackend', 'Editado');?></th>
                    <th><?=__('articlesBackend', 'Visitas');?></th>
                    <th order='false' search='false'><?=__('articlesBackend', 'Acciones');?></th>
                </tr>
            </thead>
        </table>

    </div>

</div>
