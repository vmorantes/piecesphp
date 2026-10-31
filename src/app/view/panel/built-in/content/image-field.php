<?php
/**
 * La tarjeta de imagen como campo de un formulario: lo que `ContentImageEditor` entrega en `onApply()` lo añade el
 * JavaScript de la pantalla con el nombre de `field`. Con `required`, la `label` oculta le da a genericFormHandler el nombre
 * del campo, y el `.field`, dónde pintar el error. Por omisión, JPEG sobre blanco. Descartable.
 *
 * @var array<string,mixed> $card Los parámetros de `image-card.php`.
 * @var array<string,string> $attributes Atributos extra del `.field`, con el filtro de RowActions::attributes().
 */
use PiecesPHP\Core\Utilities\Helpers\RowActions;

$card = isset($card) && is_array($card) ? $card : [];
$attributes = isset($attributes) && is_array($attributes) ? RowActions::attributes(array_filter($attributes, 'is_string')) : '';
$required = ($card['required'] ?? false) === true;
?>
<div class="<?= $required ? 'required ' : ''; ?>field"<?= $attributes; ?>>
    <label hidden><?= escape_html((string) ($card['title'] ?? '')); ?></label>
    <div class="ui one stackable horizontal cards">
        <?php
            $this->_render('panel/built-in/content/image-card.php', $card + [
                'accept' => 'image/*',
                'format' => 'image/jpeg',
                'quality' => 0.8,
                'fillColor' => 'white',
            ]);
        ?>
    </div>
</div>
