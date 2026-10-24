<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * Intentos de ingreso: cifras con las piezas de estadísticas y la tabla. Gráfica y tabla, en access-reports.js.
 *
 * @var int $totalAttempts
 * @var int $successAttempts
 * @var int $errorAttempts
 * @var string $exportUrl
 * @var \PiecesPHP\UserSystem\Controllers\HelperController $pieces Pinta las piezas del panel
 */
$langGroup = LOGIN_REPORT_LANG_GROUP;
$percent = $totalAttempts > 0 ? intval(($successAttempts * 100) / $totalAttempts) : 0;
?>
<div class="login-reports-view">

    <?php ob_start(); ?>
    <div class="three-columns-grid">
        <?php
        $pieces->_render('panel/built-in/statistics/chart-card.php', [
            'title' => __($langGroup, 'Intentos'),
            'titleModifiers' => ['big'],
            'chartAttributes' => ['data-access-chart' => ''],
            'legend' => [
                ['name' => __($langGroup, 'Intentos exitosos'), 'dataType' => 'successAttempts', 'level' => 'primary', 'color' => '#21BA45', 'valueModifiers' => ['neutral', 'right'], 'initial' => (string) $successAttempts],
                ['name' => __($langGroup, 'Intentos fallidos'), 'dataType' => 'errorAttempts', 'level' => 'secondary', 'color' => '#DB2828', 'valueModifiers' => ['neutral', 'right'], 'initial' => (string) $errorAttempts],
            ],
            'footer' => ['caption' => sprintf(__($langGroup, '%d%% de los intentos entraron'), $percent)],
        ]);
        $pieces->_render('panel/built-in/statistics/statistic-card.php', [
            'number' => ['dataType' => 'totalAttempts', 'initial' => (string) $totalAttempts],
            'footer' => ['caption' => __($langGroup, 'Intentos Totales')],
        ]);
        $pieces->_render('panel/built-in/statistics/action-card.php', [
            'modifiers' => ['horizontal-center'],
            'url' => $exportUrl,
            'icon' => 'file export',
            'title' => __($langGroup, 'Exportar datos'),
            'description' => __($langGroup, 'Los intentos de ingreso, en una hoja de cálculo'),
        ]);
        ?>
    </div>
    <?php
    $pieces->_render('panel/built-in/statistics/statistics-section.php', [
        'modifiers' => ['one-column'],
        'content' => (string) ob_get_clean(),
    ]);
    ?>

    <div class="container-standard-table">

        <table data-access-table="<?= escape_html(\PiecesPHP\UserSystem\Controllers\LoginAttemptsController::routeName('reports-ajax', ['type' => 'attempts'])); ?>" class="ui basic table content-table">
            <thead>
                <tr>
                    <th class="collapsing"><?= __($langGroup, 'Indicador'); ?></th>
                    <th><?= __($langGroup, 'Usuario ingresado'); ?></th>
                    <th><?= __($langGroup, 'Información'); ?></th>
                    <th><?= __($langGroup, 'Vía'); ?></th>
                    <th><?= __($langGroup, 'IP'); ?></th>
                    <th><?= __($langGroup, 'Fecha'); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

    </div>

</div>
