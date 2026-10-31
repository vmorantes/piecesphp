<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * Registro de ingresos: cifras con las piezas de estadísticas y la tabla. Gráfica y tabla, en access-reports.js.
 *
 * @var int $totalUsers
 * @var int $loggedUsers
 * @var int $notLoggedUsers
 * @var float|int|string $allTimeOnPlatform
 * @var string $exportUrl
 * @var \PiecesPHP\UserSystem\Controllers\HelperController $pieces Pinta las piezas del panel
 */
$langGroup = LOGIN_REPORT_LANG_GROUP;
$percent = $totalUsers > 0 ? intval(($loggedUsers * 100) / $totalUsers) : 0;
?>
<div class="login-reports-view">

    <?php ob_start(); ?>
    <div class="three-columns-grid">
        <?php
            $pieces->_render('panel/built-in/statistics/chart-card.php', [
                'title' => __($langGroup, 'Usuarios totales'),
                'titleModifiers' => ['big'],
                'chartAttributes' => ['data-access-chart' => ''],
                'legend' => [
                    ['name' => __($langGroup, 'Con ingreso'), 'dataType' => 'loggedUsers', 'level' => 'primary', 'color' => '#21BA45', 'valueModifiers' => ['neutral', 'right'], 'initial' => (string) $loggedUsers],
                    ['name' => __($langGroup, 'Sin ingreso'), 'dataType' => 'notLoggedUsers', 'level' => 'secondary', 'color' => '#7A7A7A', 'valueModifiers' => ['neutral', 'right'], 'initial' => (string) $notLoggedUsers],
                ],
                'footer' => ['caption' => sprintf(__($langGroup, '%d%% de %d usuarios ha entrado'), $percent, $totalUsers)],
            ]);
            $pieces->_render('panel/built-in/statistics/statistic-card.php', [
                'number' => ['dataType' => 'allTimeOnPlatform', 'initial' => (string) $allTimeOnPlatform],
                'footer' => ['caption' => __($langGroup, 'Horas totales de uso de la plataforma')],
            ]);
            $pieces->_render('panel/built-in/statistics/action-card.php', [
                'modifiers' => ['horizontal-center'],
                'url' => $exportUrl,
                'icon' => 'file export',
                'title' => __($langGroup, 'Exportar datos'),
            ]);
        ?>
    </div>
    <?php
        $pieces->_render('panel/built-in/statistics/statistics-section.php', [
            'modifiers' => ['one-column'],
            'content' => (string) ob_get_clean(),
        ]);
    ?>

    <div class="ui fluid card container-standard-table">

        <table data-access-table="<?= escape_html(\PiecesPHP\UserSystem\Controllers\LoginAttemptsController::routeName('reports-ajax', ['type' => 'logged'])); ?>" class="ui selectable very basic table content-table">
            <thead>
                <tr>
                    <th class="collapsing"><?= __($langGroup, 'ID'); ?></th>
                    <th><?= __($langGroup, 'Usuario'); ?></th>
                    <th><?= __($langGroup, 'Nombre'); ?></th>
                    <th><?= __($langGroup, 'Último acceso'); ?></th>
                    <th><?= __($langGroup, 'Tiempo en plataforma'); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

    </div>

</div>
