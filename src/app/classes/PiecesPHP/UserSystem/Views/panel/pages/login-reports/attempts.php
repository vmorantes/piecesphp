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

    <div class="ui stackable relaxed flex">
        <a class="right floated ui basic labeled icon button" href="<?= escape_html($exportUrl); ?>"><i class="download icon" aria-hidden="true"></i><?= __($langGroup, 'Exportar'); ?></a>
    </div>

    <div class="ui hidden divider"></div>

    <section class="ui three stackable cards" aria-label="<?= escape_html(__($langGroup, 'Resumen de intentos')); ?>">
        <?php
            $pieces->_render('panel/built-in/statistics/statistic-card.php', [
                'title' => __($langGroup, 'Intentos totales'),
                'figure' => ['value' => (string) $totalAttempts, 'dataType' => 'totalAttempts', 'description' => sprintf(__($langGroup, '%d exitosos y %d fallidos'), $successAttempts, $errorAttempts)],
            ]);
            $pieces->_render('panel/built-in/statistics/statistic-card.php', [
                'title' => __($langGroup, 'Exitosos'),
                'help' => __($langGroup, 'Intentos que terminaron en ingreso'),
                'figure' => ['value' => (string) $successAttempts, 'dataType' => 'successAttempts', 'description' => sprintf(__($langGroup, '%d%% de %d intentos'), $percent, $totalAttempts)],
            ]);
            $pieces->_render('panel/built-in/statistics/statistic-card.php', [
                'title' => __($langGroup, 'Fallidos'),
                'help' => __($langGroup, 'Contraseña o usuario incorrectos'),
                'figure' => ['value' => (string) $errorAttempts, 'dataType' => 'errorAttempts', 'description' => sprintf(__($langGroup, '%d%% de %d intentos'), $totalAttempts > 0 ? 100 - $percent : 0, $totalAttempts)],
            ]);
        ?>
    </section>

    <div class="ui hidden divider"></div>

    <?php
        //El reparto, en dona, se queda hasta que llegue la gráfica por día del diseño.
        $pieces->_render('panel/built-in/statistics/chart-card.php', [
            'title' => __($langGroup, 'Intentos'),
            'titleModifiers' => ['big'],
            'chartAttributes' => ['data-access-chart' => ''],
            'chartModifiers' => ['tall'],
            'legend' => [
                ['name' => __($langGroup, 'Intentos exitosos'), 'dataType' => 'successAttempts', 'level' => 'primary', 'color' => '#21BA45', 'valueModifiers' => ['neutral', 'right'], 'initial' => (string) $successAttempts],
                ['name' => __($langGroup, 'Intentos fallidos'), 'dataType' => 'errorAttempts', 'level' => 'secondary', 'color' => '#DB2828', 'valueModifiers' => ['neutral', 'right'], 'initial' => (string) $errorAttempts],
            ],
            'footer' => ['caption' => sprintf(__($langGroup, '%d%% de los intentos entraron'), $percent)],
        ]);
    ?>

    <div class="ui fluid card container-standard-table">

        <table data-access-table="<?= escape_html(\PiecesPHP\UserSystem\Controllers\LoginAttemptsController::routeName('reports-ajax', ['type' => 'attempts'])); ?>" class="ui selectable very basic table content-table">
            <thead>
                <tr>
                    <th class-name="collapsing"><?= __($langGroup, 'Indicador'); ?></th>
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
