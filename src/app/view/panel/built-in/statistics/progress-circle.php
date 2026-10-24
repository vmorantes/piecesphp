<?php
/**
 * Un círculo de progreso (`progress-circle`, _graphs-and-statistics.scss), con su porcentaje dentro. El JavaScript de quien
 * llama lo rellena por sus dos `data-type`: el círculo (la variable CSS `--progress`) y el número.
 *
 * @var string $dataType El del círculo.
 * @var string $numberDataType El del número de dentro.
 * @var int $progress De 0 a 100, el de partida.
 * @var string[] $modifiers `no-margin-t-b` (sin margen arriba y abajo).
 */
$dataType = isset($dataType) && is_string($dataType) ? $dataType : '';
$numberDataType = isset($numberDataType) && is_string($numberDataType) ? $numberDataType : '';
$progress = isset($progress) && is_int($progress) ? max(0, min(100, $progress)) : 0;
$modifiers = isset($modifiers) && is_array($modifiers) ? array_values(array_intersect(['no-margin-t-b'], $modifiers)) : [];
?>
<div data-type="<?= escape_html($dataType); ?>" class="progress-circle<?= $modifiers !== [] ? ' ' . implode(' ', $modifiers) : ''; ?>" style="--progress: <?= $progress; ?>;">
    <div class="inner">
        <div data-type="<?= escape_html($numberDataType); ?>" class="number"><?= $progress; ?>%</div>
    </div>
</div>
