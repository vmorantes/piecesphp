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
 * @deprecated 11.0.0 La forma de antes, sin `proportion` ni `trend` (`chartModifiers`, `legend`, `modifiers`, `titleModifiers`,
 *      `help`, `footer` y el contenedor sin `ui chart`): sin uso en el núcleo desde que Accesos pasó a `trend`; sale en la
 *      11.0.0. Usa `proportion` o `trend`.
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
 * @var array{totalDataType:string,unit?:string,unitShort?:string,meta?:string,items:array<array{name:string,dataType:string,series?:string}>}|null $proportion
 *      La tarjeta de proporción del tema (tarjeta-mediana): va dentro de `ui cards` y usa `title`, `attributes` y
 *      `chartAttributes`; lo demás no. Una dona `ui square chart` (drawStatisticsDonut()) con el total en el centro
 *      (`totalDataType`, y `unit` debajo; `unitShort`, la que se ve en el móvil, por debajo de 768 px, donde la larga
 *      rozaría el aro; sin ella, la larga) y una fila por porción con su nombre y, debajo, «cifra · porcentaje», que
 *      rellena el JavaScript de quien llama por el `data-type` de la fila (`[data-name]`, `[data-value]`, `[data-percent]`).
 *      `series`: `primary` (la principal, `--data-1`), de `2` a `5` (`--data-N`) u `other` (`--theme-text-muted`: el
 *      resto, que no es serie; el único token neutro con 3:1 sobre la superficie en los dos modos); otra cosa o nada, `other`. La muestra toma el color por `data-series` (del tema; `primary` es el 1) y la
 *      fila lo da en `data-chart-color` para la dona. El gráfico no se lee: la leyenda y el total ya lo dicen en texto.
 * @var array{totalDataType:string,meta?:string,chartLabel?:string}|null $trend
 *      La tarjeta de tendencia del tema (tarjeta-mediana): va dentro de `ui cards` y usa `title`, `attributes` y
 *      `chartAttributes`; lo demás no. Arriba, el título y el `.meta` (el periodo) y, a la derecha, el total en una
 *      `ui small statistic` (`totalDataType`; lo rellena el JavaScript de quien llama) y la variación, un
 *      `ui basic circular label` oculto (`[data-trend-change]`) que rellena drawStatisticsTrend(). Debajo, el `ui chart`
 *      con `role="img"` y `chartLabel` (sin él, el título) de `aria-label`, y su `.canvas` con `chartAttributes`.
 *      Con `proportion`, gana `proportion`.
 */
use PiecesPHP\Core\Utilities\Helpers\RowActions;

$chartAttributes = isset($chartAttributes) && is_array($chartAttributes) ? array_filter($chartAttributes, 'is_string') : [];
$chartModifiers = isset($chartModifiers) && is_array($chartModifiers) ? array_values(array_filter($chartModifiers, static fn ($modifier): bool => is_string($modifier) && preg_match('/^[a-z-]+$/', $modifier) === 1)) : [];
if ($chartModifiers !== []) {
    $chartAttributes['class'] = trim('canvas ' . ($chartAttributes['class'] ?? ''));
}
$legend = isset($legend) && is_array($legend) ? $legend : [];
$proportion = isset($proportion) && is_array($proportion) && isset($proportion['totalDataType']) && is_string($proportion['totalDataType']) && isset($proportion['items']) && is_array($proportion['items']) ? $proportion : null;
$trend = $proportion === null && isset($trend) && is_array($trend) && isset($trend['totalDataType']) && is_string($trend['totalDataType']) ? $trend : null;
?>
<?php if ($trend !== null): ?>
<?php
$canvasAttributes = $chartAttributes;
$canvasAttributes['class'] = trim('canvas ' . (string) preg_replace('/(^|\s)canvas(\s|$)/', ' ', $chartAttributes['class'] ?? ''));
$trendTitle = isset($title) && is_string($title) ? $title : '';
$chartLabel = isset($trend['chartLabel']) && is_string($trend['chartLabel']) && $trend['chartLabel'] !== '' ? $trend['chartLabel'] : $trendTitle;
?>
<div class="statistic card"<?= RowActions::attributes(isset($attributes) && is_array($attributes) ? array_filter($attributes, 'is_string') : []); ?>>
    <div class="content">
        <div class="ui spaced top aligned nowrap relaxed flex">
            <div>
                <?php if ($trendTitle !== ''): ?>
                <div class="header"><?= escape_html($trendTitle); ?></div>
                <?php endif; ?>
                <?php if (isset($trend['meta']) && is_string($trend['meta']) && $trend['meta'] !== ''): ?>
                <div class="meta"><?= escape_html($trend['meta']); ?></div>
                <?php endif; ?>
            </div>
            <div class="ui vertical right aligned tight flex">
                <div class="ui small statistic"><div class="value" data-type="<?= escape_html($trend['totalDataType']); ?>">0</div></div>
                <span class="ui basic circular label" data-trend-change hidden><i class="arrow up icon" aria-hidden="true"></i><span data-trend-percent></span></span>
            </div>
        </div>
        <div class="ui chart" role="img" aria-label="<?= escape_html($chartLabel); ?>">
            <div <?= trim(RowActions::attributes($canvasAttributes)); ?>></div>
        </div>
    </div>
</div>
<?php return; ?>
<?php endif; ?>
<?php if ($proportion !== null): ?>
<?php
$canvasAttributes = $chartAttributes;
$canvasAttributes['class'] = trim('canvas ' . (string) preg_replace('/(^|\s)canvas(\s|$)/', ' ', $chartAttributes['class'] ?? ''));
$canvasAttributes['aria-hidden'] = 'true';
?>
<div class="tall statistic card"<?= RowActions::attributes(isset($attributes) && is_array($attributes) ? array_filter($attributes, 'is_string') : []); ?>>
    <div class="ui vertical relaxed flex content">
        <div>
            <?php if (isset($title) && is_string($title)): ?>
            <div class="header"><?= escape_html($title); ?></div>
            <?php endif; ?>
            <?php if (isset($proportion['meta']) && is_string($proportion['meta']) && $proportion['meta'] !== ''): ?>
            <div class="meta"><?= escape_html($proportion['meta']); ?></div>
            <?php endif; ?>
        </div>
        <div class="fluid ui nowrap very relaxed flex">
            <div class="ui square chart">
                <div <?= trim(RowActions::attributes($canvasAttributes)); ?>></div>
                <div class="ui small statistic">
                    <div class="value" data-type="<?= escape_html($proportion['totalDataType']); ?>">0</div>
                    <?php if (isset($proportion['unit']) && is_string($proportion['unit']) && $proportion['unit'] !== ''): ?>
                    <?php if (isset($proportion['unitShort']) && is_string($proportion['unitShort']) && $proportion['unitShort'] !== '' && $proportion['unitShort'] !== $proportion['unit']): ?>
                    <div class="label"><span class="unit-long"><?= escape_html($proportion['unit']); ?></span><span class="unit-short"><?= escape_html($proportion['unitShort']); ?></span></div>
                    <?php else: ?>
                    <div class="label"><?= escape_html($proportion['unit']); ?></div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="ui vertical relaxed flex" role="list">
                <?php foreach ($proportion['items'] as $item): ?>
                <?php
                    if (!is_array($item) || !isset($item['dataType']) || !is_string($item['dataType'])) {
                        continue;
                    }
                    $series = in_array($item['series'] ?? null, ['primary', '2', '3', '4', '5'], true) ? $item['series'] : 'other';
                    $dataSeries = $series === 'primary' ? '1' : $series;
                    $chartColor = $dataSeries === 'other' ? 'var(--theme-text-muted)' : "var(--data-{$dataSeries})";
                ?>
                <div class="ui top aligned nowrap flex" role="listitem" data-type="<?= escape_html($item['dataType']); ?>" data-chart-color="<?= escape_html($chartColor); ?>">
                    <span class="ui mini swatch" data-series="<?= $dataSeries; ?>" aria-hidden="true"></span>
                    <div><span data-name><?= escape_html(isset($item['name']) && is_string($item['name']) ? $item['name'] : ''); ?></span><div><strong data-value>0</strong><span class="ui muted text"><span aria-hidden="true"> · </span><span data-percent></span></span></div></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php return; ?>
<?php endif; ?>
<?php
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
