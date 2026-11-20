<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * Intentos de ingreso (registros-accesos.html del prototipo): el periodo, las cuatro cifras, las dos tendencias por día
 * (exitosos y fallidos) y la tabla con su filtro por resultado. Gráficas, filtro y tabla, en access-reports.js.
 *
 * @var int $days El periodo, en días que acaban hoy
 * @var array<int,array{days:int,url:string,active:bool}> $periods
 * @var \DateTimeImmutable $from El primer día del periodo
 * @var \DateTimeImmutable $until El último, hoy
 * @var array{total:int,success:int,error:int,uniqueUsers:int,days:array<string,array{success:int,error:int}>} $stats
 * @var int $previousTotal Los intentos del periodo anterior de la misma longitud
 * @var int|null $previousSuccess Los exitosos del periodo anterior; sin él, la variación de «Exitosos por día» no sale
 * @var int|null $previousError Los fallidos del periodo anterior; sin él, la variación de «Fallidos por día» no sale
 * @var \DateTimeImmutable|null $oldest El intento más viejo que se guarda, si es posterior al principio del periodo
 * @var int $maxRows
 * @var string $tableURL
 * @var string $exportUrl Con el periodo; el filtro de resultado lo añade access-reports.js
 * @var \PiecesPHP\UserSystem\Controllers\HelperController $pieces Pinta las piezas del panel
 */
$langGroup = LOGIN_REPORT_LANG_GROUP;
$total = $stats['total'];
$percent = $total > 0 ? intval(($stats['success'] * 100) / $total) : 0;
if ($previousTotal > 0) {
    $change = (($total - $previousTotal) * 100) / $previousTotal;
    $changeText = sprintf(__($langGroup, '%s frente al periodo anterior'), ($change > 0 ? '+' : ($change < 0 ? '−' : '')) . number_format(abs($change), 1, ',', '.') . ' %');
} else {
    $changeText = __($langGroup, 'Sin datos del periodo anterior');
}
$points = ['success' => [], 'error' => []];
foreach ($stats['days'] as $day => $counts) {
    $label = date('d-m', (int) strtotime($day));
    $points['success'][] = ['label' => $label, 'value' => $counts['success']];
    $points['error'][] = ['label' => $label, 'value' => $counts['error']];
}
$previousByResult = [
    'success' => isset($previousSuccess) && is_int($previousSuccess) ? $previousSuccess : null,
    'error' => isset($previousError) && is_int($previousError) ? $previousError : null,
];
$trends = [
    ['result' => 'success', 'title' => __($langGroup, 'Exitosos por día'), 'chartLabel' => sprintf(__($langGroup, 'Intentos exitosos por día en los últimos %d días'), $days), 'better' => 'up', 'color' => 'var(--data-1)'],
    ['result' => 'error', 'title' => __($langGroup, 'Fallidos por día'), 'chartLabel' => sprintf(__($langGroup, 'Intentos fallidos por día en los últimos %d días'), $days), 'better' => 'down', 'color' => 'var(--data-2)'],
];
$chips = [
    ['result' => '', 'text' => __($langGroup, 'Todos'), 'count' => $total],
    ['result' => 'success', 'text' => __($langGroup, 'Exitosos'), 'count' => $stats['success']],
    ['result' => 'error', 'text' => __($langGroup, 'Fallidos'), 'count' => $stats['error']],
];
?>
<div class="login-reports-view" data-access-attempts>

    <?php require __DIR__ . '/period-toolbar.php'; ?>
    <?php if ($oldest !== null): ?>
    <p class="ui muted text" data-access-trimmed><?= escape_html(sprintf(__($langGroup, 'El registro guarda los últimos %d intentos: los datos empiezan el %s.'), $maxRows, $oldest->format('d-m-Y'))); ?></p>
    <?php endif; ?>

    <div class="ui hidden divider"></div>

    <section class="ui four stackable cards" aria-label="<?= escape_html(__($langGroup, 'Resumen de intentos')); ?>">
        <?php
            $pieces->_render('panel/built-in/statistics/statistic-card.php', [
                'title' => __($langGroup, 'Intentos totales'),
                'help' => __($langGroup, 'Exitosos y fallidos del periodo'),
                'figure' => ['value' => (string) $total, 'dataType' => 'totalAttempts', 'description' => $changeText],
            ]);
            $pieces->_render('panel/built-in/statistics/statistic-card.php', [
                'title' => __($langGroup, 'Exitosos'),
                'help' => __($langGroup, 'Intentos que terminaron en ingreso'),
                'figure' => ['value' => (string) $stats['success'], 'dataType' => 'successAttempts', 'description' => sprintf(__($langGroup, '%d%% de %d intentos'), $percent, $total)],
            ]);
            $pieces->_render('panel/built-in/statistics/statistic-card.php', [
                'title' => __($langGroup, 'Fallidos'),
                'help' => __($langGroup, 'Contraseña o usuario incorrectos'),
                'figure' => ['value' => (string) $stats['error'], 'dataType' => 'errorAttempts', 'description' => sprintf(__($langGroup, '%d%% de %d intentos'), $total > 0 ? 100 - $percent : 0, $total)],
            ]);
            $pieces->_render('panel/built-in/statistics/statistic-card.php', [
                'title' => __($langGroup, 'Usuarios únicos'),
                'help' => __($langGroup, 'Usuarios distintos que intentaron ingresar'),
                'figure' => ['value' => (string) $stats['uniqueUsers'], 'dataType' => 'uniqueUsers', 'description' => sprintf(__($langGroup, 'entre %d intentos'), $total)],
            ]);
        ?>
    </section>

    <div class="ui hidden divider"></div>

    <?php //Una tendencia por resultado (tarjeta-mediana): la variación, contra el mismo resultado del periodo anterior. ?>
    <section class="ui two stackable cards" aria-label="<?= escape_html(__($langGroup, 'Intentos por día')); ?>">
        <?php foreach ($trends as $trend): ?>
        <?php
            $pieces->_render('panel/built-in/statistics/chart-card.php', [
                'title' => $trend['title'],
                'chartAttributes' => [
                    'data-access-trend' => (string) json_encode([
                        'points' => $points[$trend['result']],
                        'previous' => $previousByResult[$trend['result']],
                        'better' => $trend['better'],
                        'color' => $trend['color'],
                    ], \JSON_THROW_ON_ERROR),
                ],
                'trend' => [
                    'totalDataType' => $trend['result'] . 'AttemptsTrend',
                    'meta' => sprintf(__($langGroup, 'Últimos %d días'), $days),
                    'chartLabel' => $trend['chartLabel'],
                ],
            ]);
        ?>
        <?php endforeach; ?>
    </section>

    <div class="ui hidden divider"></div>

    <div class="ui fluid card container-standard-table">

        <div class="content">
            <?php //Filtran la tabla en el servidor (`result`) sin recargar: la URL de la página no cambia. ?>
            <div class="ui labels" role="radiogroup" aria-label="<?= escape_html(__($langGroup, 'Filtrar por resultado')); ?>" data-access-results>
                <?php foreach ($chips as $index => $chip): ?>
                <a class="ui<?= $index === 0 ? ' active' : ''; ?> label" role="radio" aria-checked="<?= $index === 0 ? 'true' : 'false'; ?>" tabindex="<?= $index === 0 ? '0' : '-1'; ?>" href="#" data-access-result="<?= escape_html($chip['result']); ?>"><?= escape_html($chip['text']); ?><span class="detail"><?= (int) $chip['count']; ?></span></a>
                <?php endforeach; ?>
            </div>
        </div>

        <table data-access-table="<?= escape_html($tableURL); ?>" class="ui selectable very basic table content-table">
            <thead>
                <tr>
                    <th class-name="collapsing"><?= __($langGroup, 'Indicador'); ?></th>
                    <th><?= __($langGroup, 'Usuario ingresado'); ?></th>
                    <th><?= __($langGroup, 'Información'); ?></th>
                    <th><?= __($langGroup, 'Vía'); ?></th>
                    <th class-name="collapsing"><?= __($langGroup, 'IP'); ?></th>
                    <th><?= __($langGroup, 'Fecha'); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

    </div>

</div>
