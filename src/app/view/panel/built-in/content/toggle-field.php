<?php
/**
 * El interruptor con su ayuda (design.md §3.8.1 y `.pp-hint-toggle`): `ui toggle checkbox` y, debajo, `.description`. Lo
 * inicia el JavaScript de la pantalla, con `.checkbox()`; el input es hijo directo del `ui toggle checkbox`. Descartable.
 *
 * @var string $inputName El `name` del input; sin él, no se envía. No se llama `name`: esa variable ya la lleva render().
 * @var string $label
 * @var bool $checked
 * @var string $description Opcional.
 * @var string $id Opcional: enlaza la etiqueta y la ayuda con el input.
 * @var array<string,string> $attributes Atributos extra del input, con el filtro de RowActions::attributes().
 */
use PiecesPHP\Core\Utilities\Helpers\RowActions;

$inputName = isset($inputName) && is_string($inputName) ? $inputName : '';
$label = isset($label) && is_string($label) ? $label : '';
$checked = isset($checked) && $checked === true;
$description = isset($description) && is_string($description) ? $description : '';
$id = isset($id) && is_string($id) ? $id : '';
$attributes = isset($attributes) && is_array($attributes) ? RowActions::attributes(array_filter($attributes, 'is_string')) : '';
$descriptionId = $id !== '' && $description !== '' ? $id . '-description' : '';
?>
<div class="field">
    <div class="ui toggle checkbox">
        <input type="checkbox"<?= $id !== '' ? ' id="' . escape_html($id) . '"' : ''; ?><?= $inputName !== '' ? ' name="' . escape_html($inputName) . '"' : ''; ?><?= $descriptionId !== '' ? ' aria-describedby="' . escape_html($descriptionId) . '"' : ''; ?><?= $checked ? ' checked' : ''; ?><?= $attributes; ?>>
        <label<?= $id !== '' ? ' for="' . escape_html($id) . '"' : ''; ?>><?= escape_html($label); ?></label>
    </div>
    <?php if ($description !== ''): ?>
    <div class="description"<?= $descriptionId !== '' ? ' id="' . escape_html($descriptionId) . '"' : ''; ?>><?= escape_html($description); ?></div>
    <?php endif; ?>
</div>
