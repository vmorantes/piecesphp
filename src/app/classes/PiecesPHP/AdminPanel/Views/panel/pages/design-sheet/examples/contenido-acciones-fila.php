<?php
use PiecesPHP\Core\Utilities\Helpers\RowActions;

//Hasta tres acciones (RowActions::MAX_VISIBLE), todas a la vista; con más, la directa y un menú «⋯».
$edit = RowActions::action(__($langGroup, 'Editar'), '#', 'edit');
$preview = RowActions::action(__($langGroup, 'Ver en el sitio'), '#', 'external alternate', ['target' => '_blank', 'rel' => 'noopener']);
$translate = RowActions::action(__($langGroup, 'Traducir'), '#', 'language');
$delete = RowActions::delete(__($langGroup, 'Eliminar'), '#', __($langGroup, '¿Seguro de eliminar el elemento?'));
//La principal de la fila, si la tiene; la que no aplica a esta fila, y un botón de texto.
$approve = RowActions::primary(__($langGroup, 'Aprobar'), '#', 'check');
$inactive = RowActions::inactive(__($langGroup, 'Ver en el sitio'), 'external alternate');
$body = RowActions::text(__($langGroup, 'Ver cuerpo'), '#');
?>
<p><?= __($langGroup, 'Con tres acciones:'); ?></p>
<?= RowActions::cell($edit, [$preview, $delete]); ?>
<p><?= __($langGroup, 'Con cuatro:'); ?></p>
<?= RowActions::cell($edit, [$preview, $translate, $delete]); ?>
<p><?= __($langGroup, 'Principal, inactiva y de texto:'); ?></p>
<?= RowActions::cell($approve, [$inactive, $body]); ?>
