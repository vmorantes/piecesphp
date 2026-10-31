<?php
/**
 * Cifras en fila (`numbers-cards`, _graphs-and-statistics.scss): cada una con su icono, su número, su concepto y un
 * subconcepto. El JavaScript de quien llama rellena el número por su `data-type`.
 *
 * @var array<array{dataType:string,concept:string,subconcept?:string,color?:string,icon?:string,initial?:string}> $items
 *      `color`: `green` u `orange` (sin color, el primario del panel). `icon`: el de Fomantic (por omisión, `chart bar outline`).
 */
$items = isset($items) && is_array($items) ? $items : [];
?>
<div class="numbers-cards">
    <?php foreach ($items as $item): ?>
    <?php
        $color = in_array($item['color'] ?? '', ['green', 'orange'], true) ? ' ' . $item['color'] : '';
        $icon = trim((string) preg_replace('/[^a-z0-9 -]/', '', mb_strtolower((string) ($item['icon'] ?? 'chart bar outline'))));
        $initial = isset($item['initial']) && is_string($item['initial']) ? $item['initial'] : '0';
    ?>
    <div class="number-card<?= $color; ?>">
        <div class="icon">
            <i class="<?= escape_html($icon); ?> icon"></i>
        </div>
        <div class="number" data-type="<?= escape_html($item['dataType']); ?>"><?= escape_html($initial); ?>&nbsp;</div>
        <div class="concept"><?= escape_html($item['concept']); ?></div>
        <?php if (isset($item['subconcept']) && is_string($item['subconcept'])): ?>
        <div class="subconcept"><?= escape_html($item['subconcept']); ?></div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
