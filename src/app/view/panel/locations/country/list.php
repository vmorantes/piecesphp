<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<div style="max-width:850px;">

    <h3><?= $title; ?></h3>

    <div class="ui buttons">
        <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
        <?php if($has_add_link_permissions):?>
        <a href="<?=$add_link;?>" class="ui button green"><?= __(LOCATIONS_LANG_GROUP, 'Agregar'); ?></a>
        <?php endif;?>
    </div>

</div>

<br>
<br>

<div style="max-width:100%;">

    <table process="<?=$process_table;?>" style='width:100%;' class="ui table striped celled grey inverted">
        <thead>
            <tr>
                <th><?= __(LOCATIONS_LANG_GROUP, 'ID'); ?></th>
                <th><?= __(LOCATIONS_LANG_GROUP, 'Código'); ?></th>
                <th><?= __(LOCATIONS_LANG_GROUP, 'Nombre'); ?></th>
                <th><?= __(LOCATIONS_LANG_GROUP, 'Activo/Inactivo'); ?></th>
                <th order='false'><?= __(LOCATIONS_LANG_GROUP, 'Acciones'); ?></th>
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
