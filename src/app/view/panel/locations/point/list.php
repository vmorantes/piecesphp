<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>

<div style="max-width:850px;">

    <h3><?=$title;?></h3>

    <div class="ui buttons">
        <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
        <?php if($has_add_link_permissions):?>
        <a href="<?=$add_link;?>" class="ui button green">Agregar</a>
        <?php endif;?>
    </div>

</div>

<br>
<br>

<div style="max-width:100%;">

    <table process="<?=$process_table;?>" style='width:100%;' class="ui table striped celled grey inverted">
        <thead>
            <tr>
                <th name='id' order='true' search='true'>ID</th>
                <th name='name' order='true' search='true'>Nombre</th>
                <th name='state' order='true' search='true'>Departamento</th>
                <th name='city' order='true' search='true'>Ciudad</th>
                <th name='address' order='true' search='true'>Dirección</th>
                <th name='coords' order='true' search='true'>Coordenadas</th>
                <th name='active' order='true' search='true'>Activo/Inactivo</th>
                <th order='false' search='false'>Acciones</th>
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
