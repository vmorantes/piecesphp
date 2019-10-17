<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<div style="max-width:850px;">

    <div class="ui buttons">
        <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
        <?php if ($has_permissions_add): ?>
        <a href="<?=$add_link;?>" class="ui button green"><?= __('articles', 'Agregar'); ?></a>
        <?php endif;?>
    </div>

</div>

<br>
<br>

<div style="max-width:100%;">

    <table process="<?=$process_table;?>" style='width:100%;' class="ui table striped celled grey inverted">
        <thead>
            <tr>
                <th><?=__('articles', 'list-ID');?></th>
                <th><?=__('articles', 'list-Título');?></th>
                <th><?=__('articles', 'list-Autor');?></th>
                <th><?=__('articles', 'list-Categoría');?></th>
                <th><?=__('articles', 'list-Inicio');?></th>
                <th><?=__('articles', 'list-Fin');?></th>
                <th><?=__('articles', 'list-Creado');?></th>
                <th><?=__('articles', 'list-Editado');?></th>
                <th><?=__('articles', 'list-Visitas');?></th>
                <th order='false'><?=__('articles', 'list-Acciones');?></th>
            </tr>
        </thead>
    </table>

</div>

<script>
window.onload = () => {

    let table = $(`[process]`)
    let processURL = table.attr('process')
    dataTableServerProccesing(table, processURL, 10)

}
</script>
