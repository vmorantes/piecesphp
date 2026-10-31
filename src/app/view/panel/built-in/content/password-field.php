<?php
/**
 * El campo de contraseña con ojo (design.md): `ui action input` con el `input[type=password]` y un `ui icon button` que lo
 * enseña u oculta. Lo mueve contentPasswordFields() (`admin-area/js/main.js`). Pinta el `field` entero. Descartable.
 *
 * @var string $id
 * @var string $inputName El `name` del input. No se llama `name`: esa variable ya la lleva render() con la ruta de esta vista.
 * @var string $value
 * @var string $label
 * @var string $placeholder
 * @var string $autocomplete Por omisión, «current-password».
 * @var bool $required
 * @var array<string,string> $attributes Atributos extra del input, con el filtro de RowActions::attributes().
 * @var string $fieldExtra HTML dentro del `field`, bajo el control (un interruptor que lo modifica), que escapa quien llama.
 */
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\Utilities\Helpers\RowActions;

$id = isset($id) && is_string($id) ? $id : '';
$inputName = isset($inputName) && is_string($inputName) ? $inputName : '';
$value = isset($value) && is_string($value) ? $value : '';
$label = isset($label) && is_string($label) ? $label : '';
$placeholder = isset($placeholder) && is_string($placeholder) ? $placeholder : '';
$autocomplete = isset($autocomplete) && is_string($autocomplete) && $autocomplete !== '' ? $autocomplete : 'current-password';
$required = isset($required) && $required === true;
$attributes = isset($attributes) && is_array($attributes) ? RowActions::attributes(array_filter($attributes, 'is_string')) : '';
$fieldExtra = isset($fieldExtra) && is_string($fieldExtra) ? $fieldExtra : '';
$showText = __(AdminPanelController::ADMIN_LANG_GROUP, 'Mostrar contraseña');
$hideText = __(AdminPanelController::ADMIN_LANG_GROUP, 'Ocultar contraseña');
?>
<div class="<?= $required ? 'required ' : ''; ?>field">
    <?php if ($label !== ''): ?>
    <label<?= $id !== '' ? ' for="' . escape_html($id) . '"' : ''; ?>><?= escape_html($label); ?></label>
    <?php endif; ?>
    <div class="ui action input">
        <input type="password"<?= $id !== '' ? ' id="' . escape_html($id) . '"' : ''; ?><?= $inputName !== '' ? ' name="' . escape_html($inputName) . '"' : ''; ?> value="<?= escape_html($value); ?>" autocomplete="<?= escape_html($autocomplete); ?>"<?= $placeholder !== '' ? ' placeholder="' . escape_html($placeholder) . '"' : ''; ?><?= $required ? ' required' : ''; ?><?= $attributes; ?>>
        <button type="button" class="ui icon button" data-password-toggle data-show-text="<?= escape_html($showText); ?>" data-hide-text="<?= escape_html($hideText); ?>" aria-label="<?= escape_html($showText); ?>" aria-pressed="false"><i class="eye icon" aria-hidden="true"></i></button>
    </div>
    <?= $fieldExtra; ?>
</div>
