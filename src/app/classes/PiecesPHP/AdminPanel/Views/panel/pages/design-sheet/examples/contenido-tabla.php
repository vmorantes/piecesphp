<?php
use PiecesPHP\Core\Utilities\Helpers\RowActions;

//Datos de muestra. En un módulo, las filas las da su `dataTables()` y la tabla lleva `url` (dataTableServerProccesing).
$rows = [
    ['title' => __($langGroup, 'Resultados del trimestre'), 'category' => __($langGroup, 'Informes'), 'status' => __($langGroup, 'Activo'), 'color' => 'green', 'date' => '09/10/2026'],
    ['title' => __($langGroup, 'Guía de bienvenida'), 'category' => __($langGroup, 'Guías'), 'status' => __($langGroup, 'Borrador'), 'color' => 'orange', 'date' => '08/10/2026'],
];
?>
<div class="container-standard-table">
    <table class="ui basic table content-table" example-table>
        <thead>
            <tr>
                <th><?= __($langGroup, 'Título'); ?></th>
                <th class="collapsing"><?= __($langGroup, 'Estado'); ?></th>
                <th class="collapsing"><?= __($langGroup, 'Fecha'); ?></th>
                <th class="collapsing"><?= __($langGroup, 'Acciones'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td>
                    <a href="#"><?= escape_html($row['title']); ?></a>
                    <br><span class="ui small grey text"><?= escape_html($row['category']); ?></span>
                </td>
                <td><span class="ui <?= escape_html($row['color']); ?> label"><?= escape_html($row['status']); ?></span></td>
                <td><?= escape_html($row['date']); ?></td>
                <td><?= RowActions::cell(RowActions::action(__($langGroup, 'Editar'), '#', 'edit')); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
