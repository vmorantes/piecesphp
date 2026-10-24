<?php
/**
 * La ventana de formulario de una pantalla de contenido (diseño de contenidos, §2.4 y §2.6): el alta y la edición de lo
 * que tiene uno o dos campos (las categorías), en un `ui modal` de Fomantic sobre el listado. Apagable: un módulo con
 * categorías complejas usa su página.
 *
 * La abre un disparador `[data-content-modal="<modalId>"]` (admin-area/js/main.js, contentModals()); con
 * `data-content-modal-values` (JSON por `name`) rellena el formulario (edición) y sin él lo abre vacío (alta). Con
 * `data-content-modal-action` y `data-content-modal-title`, cambia la acción y el título. Un campo con
 * `data-content-modal-when-value` solo se envía si los valores lo traen (el `id` de la edición). Al guardar con éxito,
 * cierra la ventana y recarga las tablas de la página, sin recargarla.
 *
 * @var string $modalId Letras, números y guiones.
 * @var string $title
 * @var string $action La acción del alta, por omisión.
 * @var string $fields HTML de los campos, que pinta quien llama (y escapa lo suyo).
 * @var string $saveText Por omisión, «Guardar».
 * @var string $cancelText Por omisión, «Cancelar».
 * @var array<string,string> $formAttributes Atributos extra del `form`, con el filtro de RowActions::attributes().
 */
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\Utilities\Helpers\RowActions;

$modalId = isset($modalId) && is_string($modalId) ? (string) preg_replace('/[^a-z0-9-]/', '', mb_strtolower($modalId)) : '';
$title = isset($title) && is_string($title) ? $title : '';
$action = isset($action) && is_string($action) ? $action : '';
$fields = isset($fields) && is_string($fields) ? $fields : '';
$saveText = isset($saveText) && is_string($saveText) && $saveText !== '' ? $saveText : __(AdminPanelController::ADMIN_LANG_GROUP, 'Guardar');
$cancelText = isset($cancelText) && is_string($cancelText) && $cancelText !== '' ? $cancelText : __(AdminPanelController::ADMIN_LANG_GROUP, 'Cancelar');
$formAttributes = isset($formAttributes) && is_array($formAttributes) ? RowActions::attributes(array_filter($formAttributes, 'is_string')) : '';
$formId = $modalId . '-form';
?>
<?php if ($modalId !== ''): ?>
<div class="ui small modal content-form-modal" id="<?= $modalId; ?>">
    <div class="header" data-content-modal-header><?= escape_html($title); ?></div>
    <div class="content">
        <form method="POST" action="<?= escape_html($action); ?>" class="ui form initial" id="<?= $formId; ?>" data-content-form data-content-modal-form<?= $formAttributes; ?>>
            <?= $fields; ?>
        </form>
    </div>
    <div class="actions">
        <button class="ui basic button" type="button" data-content-modal-cancel><?= escape_html($cancelText); ?></button>
        <button class="ui button brand-color" type="submit" form="<?= $formId; ?>"><?= escape_html($saveText); ?></button>
    </div>
</div>
<?php endif; ?>
