<?php
/**
 * Una sección de estadísticas: la rejilla que ordena las tarjetas (sin `one-column`, la del tema, `ui four column adaptive
 * cards`, que crece con la letra; con él, _graphs-and-statistics.scss). Dentro, el HTML de quien
 * llama: tarjetas sueltas o las rejillas `one-column-grid`, `two-columns-grid` y `three-columns-grid` con sus tarjetas.
 *
 * @var string $content HTML de dentro, que pinta quien llama (y escapa lo suyo).
 * @var string[] $modifiers `one-column` (una columna) y `fullsize` (a todo el ancho, con `one-column`).
 * @var array<string,string> $attributes Atributos extra, con el filtro de RowActions::attributes().
 */
use PiecesPHP\Core\Utilities\Helpers\RowActions;

$content = isset($content) && is_string($content) ? $content : '';
$modifiers = isset($modifiers) && is_array($modifiers) ? array_values(array_intersect(['one-column', 'fullsize'], $modifiers)) : [];
$attributes = isset($attributes) && is_array($attributes) ? RowActions::attributes(array_filter($attributes, 'is_string')) : '';
$grid = in_array('one-column', $modifiers, true) ? '' : ' ui four column adaptive cards';
?>
<div class="statistics-section<?= $grid . ($modifiers !== [] ? ' ' . implode(' ', $modifiers) : ''); ?>"<?= $attributes; ?>>
    <?= $content; ?>
</div>
