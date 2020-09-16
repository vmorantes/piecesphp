<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\BuiltIn\Article\Controllers\ArticleController;
$langGroup = ArticleController::LANG_GROUP;
?>

<div style="max-width:850px;">

    <div class="ui buttons">
        <a href="<?=$back_link;?>" class="ui button blue"><i class="icon left arrow"></i></a>
        <?php if($has_permissions_add): ?>
        <a href="<?=$add_link;?>" class="ui button green"><?= __($langGroup, 'Agregar')?></a>
        <?php endif; ?>
    </div>

</div>

<br>
<br>

<div style="max-width:100%;">

    <table process="<?=$process_table;?>" style='width:100%;' class="ui table striped celled grey inverted">
        <thead>
            <tr>
                <th><?= __($langGroup, 'ID')?></th>
                <th><?= __($langGroup, 'Nombre')?></th>
                <th><?= __($langGroup, 'Descripción')?></th>
                <th order='false'><?= __($langGroup, 'Acciones')?></th>
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
