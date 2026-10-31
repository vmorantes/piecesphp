<?php
/**
 * El campo de color (diseño de contenidos): `ui colorpicker input` del tema, con su muestra y su valor hexadecimal. Lo
 * inicia contentColorFields() (`admin-area/js/main.js`). Pinta solo el campo: el `field` que lo lleva y lo que vaya al
 * lado son de quien llama. No se apaga.
 *
 * @var string $id
 * @var string $value Un #RRGGBB
 * @var string $inputName El `name` del input; sin él, el campo no se envía. No se llama `name`: esa variable ya la lleva
 *      render() con la ruta de esta vista, y una llamada sin ella la pintaría como nombre.
 * @var string $label Etiqueta visible; sin ella, `ariaLabel`.
 * @var string $ariaLabel
 * @var bool $disabled
 * @var array<string,string> $attributes Atributos extra del input, con el filtro de RowActions::attributes().
 */
use PiecesPHP\Core\Utilities\Helpers\RowActions;

$label = isset($label) && is_string($label) ? $label : '';
$ariaLabel = isset($ariaLabel) && is_string($ariaLabel) ? $ariaLabel : '';
$inputName = isset($inputName) && is_string($inputName) ? $inputName : '';
$disabled = isset($disabled) && $disabled === true;
$attributes = isset($attributes) && is_array($attributes) ? RowActions::attributes(array_filter($attributes, 'is_string')) : '';
?>
<?php if ($label !== ''): ?>
<label for="<?= escape_html($id); ?>"><span class="ui regular text"><?= escape_html($label); ?></span></label>
<?php endif; ?>
<div class="ui colorpicker input">
    <input type="text" id="<?= escape_html($id); ?>" value="<?= escape_html(mb_strtolower($value)); ?>"<?= $inputName !== '' ? ' name="' . escape_html($inputName) . '"' : ''; ?><?= $ariaLabel !== '' ? ' aria-label="' . escape_html($ariaLabel) . '"' : ''; ?><?= $disabled ? ' disabled' : ''; ?><?= $attributes; ?>>
</div>
