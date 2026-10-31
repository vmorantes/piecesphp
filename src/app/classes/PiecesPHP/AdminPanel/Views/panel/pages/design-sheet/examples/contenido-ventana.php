<?php
use PiecesPHP\Core\Utilities\Helpers\RowActions;

//Lo que la abre: vacía (alta) o con valores por `name` (edición), con su acción y su título.
$values = json_encode(['id' => '1', 'name' => __($langGroup, 'Informes')], \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
?>
<button class="ui primary button" type="button" data-content-modal="design-sheet-category-modal"><i class="plus icon"></i><?= __($langGroup, 'Nueva categoría'); ?></button>
<?= RowActions::cell(RowActions::action(__($langGroup, 'Editar'), '#', 'edit', [
    'data-content-modal' => 'design-sheet-category-modal',
    'data-content-modal-values' => $values,
    'data-content-modal-title' => sprintf(__($langGroup, 'Editar: %s'), __($langGroup, 'Informes')),
])); ?>
<?php ob_start(); ?>
<input type="hidden" name="id" value="" data-content-modal-when-value>
<div class="field required">
    <label><?= __($langGroup, 'Nombre'); ?></label>
    <input type="text" name="name" maxlength="300" required>
</div>
<?php
//Al guardar con éxito, la pieza cierra la ventana y recarga las tablas de la página, sin recargarla.
$this->helpController->_render('panel/built-in/content/form-modal.php', [
    'modalId' => 'design-sheet-category-modal',
    'title' => __($langGroup, 'Nueva categoría'),
    'action' => '#',
    'fields' => (string) ob_get_clean(),
]);
