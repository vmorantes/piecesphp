<?php
/**
 * La leyenda de un gráfico o de un círculo de progreso (`chart-data`, _graphs-and-statistics.scss): una fila por valor,
 * con su color, su nombre y su número, que rellena el JavaScript de quien llama por su `data-type`.
 *
 * @var array<array{name:string,dataType:string,level?:string,color?:string,valueModifiers?:string[],stack?:bool,initial?:string}> $items
 *      `level`: `primary` (por omisión) o `secondary`. `color`: el de su punto (#hex o var(--…)), si no es el del nivel.
 *      `valueModifiers`: `neutral`, `right` y `text-black`. `stack`: el nombre y su color encima del número.
 */
$items = isset($items) && is_array($items) ? $items : [];
?>
<div class="chart-data">
    <?php foreach ($items as $item): ?>
    <?php
    $level = in_array($item['level'] ?? 'primary', ['primary', 'secondary'], true) ? $item['level'] ?? 'primary' : 'primary';
    $color = isset($item['color']) && is_string($item['color']) && preg_match('/^(#[0-9a-fA-F]{3,8}|var\(--[a-z0-9-]+\))$/', $item['color']) === 1 ? $item['color'] : null;
    $valueModifiers = array_values(array_intersect(['neutral', 'right', 'text-black'], $item['valueModifiers'] ?? []));
    $stack = ($item['stack'] ?? false) === true;
    $initial = isset($item['initial']) && is_string($item['initial']) ? $item['initial'] : '0';
    ?>
    <div class="item<?= $stack ? ' stack' : ''; ?> <?= $level; ?>"<?= $color !== null ? ' style="--custom-color: ' . escape_html($color) . ';"' : ''; ?>>
        <?php if ($stack): ?>
        <div class="legend">
            <div class="color circle"></div>
            <div class="name"><?= escape_html($item['name']); ?></div>
        </div>
        <?php else: ?>
        <div class="color circle"></div>
        <div class="name"><?= escape_html($item['name']); ?></div>
        <?php endif; ?>
        <div data-type="<?= escape_html($item['dataType']); ?>" class="value<?= $valueModifiers !== [] ? ' ' . implode(' ', $valueModifiers) : ''; ?>">&nbsp;<?= escape_html($initial); ?>&nbsp;</div>
    </div>
    <?php endforeach; ?>
</div>
