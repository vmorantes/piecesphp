<?php
use PiecesPHP\Core\Utilities\Helpers\RowActions;

//Hasta tres acciones (RowActions::MAX_VISIBLE), todas a la vista; con más, la directa y un menú «⋯».
$edit = RowActions::action(__($langGroup, 'Editar'), '#', 'edit');
$preview = RowActions::action(__($langGroup, 'Ver en el sitio'), '#', 'external alternate', ['target' => '_blank', 'rel' => 'noopener']);
$translate = RowActions::action(__($langGroup, 'Traducir'), '#', 'language');
$delete = RowActions::delete(__($langGroup, 'Eliminar'), '#', __($langGroup, '¿Seguro de eliminar el elemento?'));
?>
<p><?= __($langGroup, 'Con tres acciones:'); ?></p>
<?= RowActions::cell($edit, [$preview, $delete]); ?>
<p><?= __($langGroup, 'Con cuatro:'); ?></p>
<?= RowActions::cell($edit, [$preview, $translate, $delete]); ?>
