<?php
/**
 * Una tarjeta de estadística con un gráfico (statistic-card.php con el contenedor del gráfico y, si la hay, su leyenda,
 * chart-legend.php). El gráfico lo dibuja el JavaScript de quien llama sobre el contenedor; por eso lleva los atributos
 * con que se le encuentra.
 *
 * CON `chartModifiers`, el contenedor es el `ui chart` del tema y se dibuja en su `.canvas` (Apache ECharts, con
 * import_echarts() y echarts.init()). SIN ellos, el contenedor de antes, sin `ui chart`: es el que necesita ApexCharts y
 * está en desuso hasta la 11.0.0 (H155).
 *
 * @var array<string,string> $chartAttributes Los del lienzo del gráfico (p. ej. `class` o `id`), con el filtro de
 *      RowActions::attributes(); con `chartModifiers`, una `class` se suma a `canvas`.
 * @var string[] $chartModifiers Las clases de `ui chart` del tema, una por elemento (`tall`; `very` y `tall`; `square`…);
 *      sin ninguna, el contenedor en desuso.
 * @var array<array<string,mixed>> $legend Las filas de la leyenda (chart-legend.php, `items`); vacía, sin leyenda.
 * @var string[] $modifiers Los de statistic-card.php.
 * @var array<string,string> $attributes Los de statistic-card.php.
 * @var string|null $title Los de statistic-card.php.
 * @var string[] $titleModifiers Los de statistic-card.php.
 * @var string $help Los de statistic-card.php.
 * @var array{caption?:string|null,url?:string|null,two?:bool}|null $footer Los de statistic-card.php.
 */
use PiecesPHP\Core\Utilities\Helpers\RowActions;

$chartAttributes = isset($chartAttributes) && is_array($chartAttributes) ? array_filter($chartAttributes, 'is_string') : [];
$chartModifiers = isset($chartModifiers) && is_array($chartModifiers) ? array_values(array_filter($chartModifiers, static fn ($modifier): bool => is_string($modifier) && preg_match('/^[a-z-]+$/', $modifier) === 1)) : [];
if ($chartModifiers !== []) {
    $chartAttributes['class'] = trim('canvas ' . ($chartAttributes['class'] ?? ''));
}
$legend = isset($legend) && is_array($legend) ? $legend : [];
ob_start();
?>
<div class="data">
    <?php if ($chartModifiers !== []): ?>
    <div class="ui <?= implode(' ', $chartModifiers); ?> chart">
        <div <?= trim(RowActions::attributes($chartAttributes)); ?>></div>
    </div>
    <?php else: ?>
    <div <?= trim(RowActions::attributes($chartAttributes)); ?>></div>
    <?php endif; ?>
</div>
<?php
$content = (string) ob_get_clean();
if ($legend !== []) {
    $content .= (string) $this->_render('panel/built-in/statistics/chart-legend.php', ['items' => $legend], false);
}
$this->_render('panel/built-in/statistics/statistic-card.php', [
    'modifiers' => $modifiers ?? [],
    'attributes' => $attributes ?? [],
    'title' => $title ?? null,
    'titleModifiers' => $titleModifiers ?? [],
    'help' => $help ?? '',
    'content' => $content,
    'footer' => $footer ?? null,
]);
