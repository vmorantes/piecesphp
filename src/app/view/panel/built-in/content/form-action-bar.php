<?php
/**
 * La barra de acciones de un formulario (diseño de contenidos, §2.3): al final del formulario, sin fijar, con «Guardar»
 * como principal y LA ÚLTIMA, las peligrosas primero y las demás como básicas. Va DENTRO del `form`, como `.actions`
 * (la barra de botones del tema), con la clase del framework `form-action-bar`, sin estilo (ADR 0069). No se apaga.
 * El `form` que la lleva nace con `class="ui form initial" data-content-form`: el rojo de lo obligatorio sale solo al
 * intentar guardar (`admin-area/js/main.js`, contentFormsInitial()).
 *
 * @var string $saveText Por omisión, «Guardar».
 * @var array<string,string> $saveAttributes Atributos extra del botón de guardar (p. ej. `save`, que busca el JS del
 *      módulo).
 * @var array<array{text:string,url?:string,type?:string,icon?:string,attributes?:array<string,string>,danger?:bool}> $secondaryActions
 *      Con `url`, un enlace; si no, un botón del `type` que se diga (`button`, `submit` o `reset`; por omisión, `button`).
 *      `danger`, `ui basic negative` y la primera (eliminar). Los `attributes`, con el filtro de RowActions::attributes().
 *      Cada secundaria lleva su texto también en `title` y `aria-label`.
 */
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\Utilities\Helpers\RowActions;

$saveText = isset($saveText) && is_string($saveText) && $saveText !== '' ? $saveText : __(AdminPanelController::ADMIN_LANG_GROUP, 'Guardar');
$saveAttributes = isset($saveAttributes) && is_array($saveAttributes) ? RowActions::attributes(array_filter($saveAttributes, 'is_string')) : '';
$secondaryActions = isset($secondaryActions) && is_array($secondaryActions) ? $secondaryActions : [];
$attributesOf = fn (array $action): string => isset($action['attributes']) && is_array($action['attributes'])
    ? RowActions::attributes(array_filter($action['attributes'], 'is_string'))
    : '';
$iconOf = fn (array $action): string => isset($action['icon']) && is_string($action['icon']) && $action['icon'] !== ''
    ? '<i class="' . escape_html(trim((string) preg_replace('/[^a-z0-9 -]/', '', mb_strtolower($action['icon'])))) . ' icon" aria-hidden="true"></i>'
    : '';
$labelOf = fn (array $action): string => ' title="' . escape_html($action['text']) . '" aria-label="' . escape_html($action['text']) . '"';
$isDanger = fn (array $action): bool => ($action['danger'] ?? false) === true;
$classOf = fn (array $action): string => $isDanger($action) ? 'ui basic negative button' : 'ui basic button';
//Las peligrosas primero; el orden entre las de cada clase, el de quien llama.
$secondaryActions = array_merge(array_values(array_filter($secondaryActions, $isDanger)), array_values(array_filter($secondaryActions, fn (array $action): bool => !$isDanger($action))));
?>
<div class="actions form-action-bar">
    <?php foreach ($secondaryActions as $action): ?>
    <?php if (isset($action['url']) && is_string($action['url'])): ?>
    <a class="<?= $classOf($action); ?>" href="<?= escape_html($action['url']); ?>"<?= $labelOf($action); ?><?= $attributesOf($action); ?>><?= $iconOf($action); ?><?= escape_html($action['text']); ?></a>
    <?php else: ?>
    <button class="<?= $classOf($action); ?>" type="<?= in_array($action['type'] ?? 'button', ['button', 'submit', 'reset'], true) ? $action['type'] ?? 'button' : 'button'; ?>"<?= $labelOf($action); ?><?= $attributesOf($action); ?>><?= $iconOf($action); ?><?= escape_html($action['text']); ?></button>
    <?php endif; ?>
    <?php endforeach; ?>
    <button class="ui primary button" type="submit"<?= $saveAttributes; ?>><?= escape_html($saveText); ?></button>
</div>
