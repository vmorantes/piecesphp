<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * Registro de ingreso (registros-accesos.html del prototipo): el periodo, tres cifras y la tabla de quienes ingresaron
 * en el periodo. La tabla, en access-reports.js.
 *
 * @var int $days
 * @var array<int,array{days:int,url:string,active:bool}> $periods
 * @var \DateTimeImmutable $from
 * @var \DateTimeImmutable $until
 * @var int $totalUsers
 * @var int $loggedUsers Con al menos un ingreso correcto en el periodo
 * @var int $notLoggedUsers
 * @var int $deletedLoggedUsers Eliminados que entraron en el periodo: en la tabla, no en las cifras
 * @var float|int|string $allTimeOnPlatform Desde que hay registro: no admite periodo
 * @var string $exportUrl
 * @var string $tableURL
 * @var \PiecesPHP\UserSystem\Controllers\HelperController $pieces Pinta las piezas del panel
 */
$langGroup = LOGIN_REPORT_LANG_GROUP;
$percent = $totalUsers > 0 ? intval(($loggedUsers * 100) / $totalUsers) : 0;
?>
<div class="login-reports-view" data-access-logged>

    <?php require __DIR__ . '/period-toolbar.php'; ?>

    <div class="ui hidden divider"></div>

    <section class="ui three stackable cards" aria-label="<?= escape_html(__($langGroup, 'Resumen de ingresos')); ?>">
        <?php
            $pieces->_render('panel/built-in/statistics/statistic-card.php', [
                'title' => __($langGroup, 'Con ingreso'),
                'help' => __($langGroup, 'Usuarios con al menos un ingreso en el periodo'),
                'figure' => ['value' => (string) $loggedUsers, 'dataType' => 'loggedUsers', 'description' => sprintf(__($langGroup, '%d %% del total de usuarios'), $percent)],
            ]);
            $pieces->_render('panel/built-in/statistics/statistic-card.php', [
                'title' => __($langGroup, 'Sin ingreso'),
                'help' => __($langGroup, 'Usuarios sin ningún ingreso en el periodo'),
                'figure' => ['value' => (string) $notLoggedUsers, 'dataType' => 'notLoggedUsers', 'description' => sprintf(__($langGroup, '%d %% del total de usuarios'), $totalUsers > 0 ? 100 - $percent : 0)],
            ]);
            $pieces->_render('panel/built-in/statistics/statistic-card.php', [
                'title' => __($langGroup, 'Horas totales'),
                'help' => __($langGroup, 'Desde que hay registro: el tiempo en la plataforma no se guarda por día'),
                'figure' => ['value' => (string) $allTimeOnPlatform, 'dataType' => 'allTimeOnPlatform', 'description' => __($langGroup, 'Horas totales de uso de la plataforma')],
            ]);
        ?>
    </section>
    <?php if ($deletedLoggedUsers > 0): ?>
    <p class="ui muted text" data-access-deleted><?= escape_html(sprintf(__($langGroup, 'La tabla incluye %d cuentas eliminadas que entraron en este periodo; no cuentan en las cifras.'), $deletedLoggedUsers)); ?></p>
    <?php endif; ?>

    <div class="ui hidden divider"></div>

    <div class="ui fluid card container-standard-table">

        <table data-access-table="<?= escape_html($tableURL); ?>" class="ui selectable very basic table content-table">
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
