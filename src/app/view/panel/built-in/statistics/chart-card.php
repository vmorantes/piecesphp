<?php
/**
 * Una tarjeta de estadística con un gráfico (statistic-card.php con el contenedor del gráfico y, si la hay, su leyenda,
 * chart-legend.php). El gráfico lo dibuja el JavaScript de quien llama sobre el contenedor (ApexCharts, con
 * import_apexcharts()); por eso el contenedor lleva los atributos con que se le encuentra.
 *
 * @var array<string,string> $chartAttributes Los del contenedor del gráfico (p. ej. `class` o `id`), con el filtro de
 *      RowActions::attributes().
 * @var array<array<string,mixed>> $legend Las filas de la leyenda (chart-legend.php, `items`); vacía, sin leyenda.
 * @var string[] $modifiers Los de statistic-card.php.
 * @var array<string,string> $attributes Los de statistic-card.php.
 * @var string|null $title Los de statistic-card.php.
 * @var string[] $titleModifiers Los de statistic-card.php.
 * @var string $help Los de statistic-card.php.
 * @var array{caption?:string|null,url?:string|null,two?:bool}|null $footer Los de statistic-card.php.
 */
use PiecesPHP\Core\Utilities\Helpers\RowActions;

$chartAttributes = isset($chartAttributes) && is_array($chartAttributes) ? RowActions::attributes(array_filter($chartAttributes, 'is_string')) : '';
$legend = isset($legend) && is_array($legend) ? $legend : [];
ob_start();
?>
<div class="data">
    <div <?= trim($chartAttributes); ?>></div>
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
