<?php
/**
 * La zona de una acción de peso al pie de una pantalla (Estado y cachés: «Borrar todo lo temporal»; la de cerrar todas las
 * sesiones de un usuario): un recuadro con el título y lo que hace a la izquierda, y su botón a la derecha. Con
 * `ui secondary segment`, `ui stackable flex` del fork (a ≤767 se apila y el botón va a todo el ancho) y el título como
 * `ui small header` con su `.sub.header`. `content-main-action` (en la raíz) es la clase del framework, sin estilo
 * (ADR 0069).
 *
 * @var string $title
 * @var string $description Lo que hace la acción, en una frase.
 * @var string $buttonText
 * @var array<string,string> $attributes Atributos del botón (p. ej. `data-confirm` y el gancho del JavaScript del
 *      módulo), con el filtro de RowActions::attributes(). El botón no hace nada por sí solo: lo engancha el módulo.
 * @var string $variant 'primary' (por omisión) o 'negative': la de una acción destructiva, en rojo y básico.
 * @var string $icon Icono de Fomantic del botón, opcional.
 */
use PiecesPHP\Core\Utilities\Helpers\RowActions;

$title = isset($title) && is_string($title) ? $title : '';
$description = isset($description) && is_string($description) ? $description : '';
$buttonText = isset($buttonText) && is_string($buttonText) ? $buttonText : '';
$attributes = isset($attributes) && is_array($attributes) ? RowActions::attributes(array_filter($attributes, 'is_string')) : '';
$variant = isset($variant) && $variant === 'negative' ? 'negative basic' : 'primary';
$icon = isset($icon) && is_string($icon) ? trim((string) preg_replace('/[^a-z0-9 -]/', '', mb_strtolower($icon))) : '';
?>
<div class="ui secondary segment content-main-action">
    <div class="ui stackable flex">
        <div class="fluid">
            <h2 class="ui small header"><?= escape_html($title); ?><?php if ($description !== ''): ?><div class="sub header"><?= escape_html($description); ?></div><?php endif; ?></h2>
        </div>
        <button type="button" class="ui <?= $variant; ?><?= $icon !== '' ? ' labeled icon' : ''; ?> button"<?= $attributes; ?>><?php if ($icon !== ''): ?><i class="<?= escape_html($icon); ?> icon" aria-hidden="true"></i><?php endif; ?><?= escape_html($buttonText); ?></button>
    </div>
</div>
