<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\Utilities\Helpers\StatusLabel;

/**
 * Usuarios sin ingreso (registros-accesos.html del prototipo): el periodo, dos cifras y la tabla de quienes no
 * ingresaron en el periodo; sin ninguno, el estado vacío. La tabla, en access-reports.js.
 *
 * @var int $days
 * @var array<int,array{days:int,url:string,active:bool}> $periods
 * @var \DateTimeImmutable $from
 * @var \DateTimeImmutable $until
 * @var int $totalUsers
 * @var int $loggedUsers
 * @var int $notLoggedUsers Sin ningún ingreso correcto en el periodo
 * @var string $exportUrl
 * @var string $tableURL
 * @var \PiecesPHP\UserSystem\Controllers\HelperController $pieces Pinta las piezas del panel
 */
$langGroup = LOGIN_REPORT_LANG_GROUP;
$percentLogged = $totalUsers > 0 ? intval(($loggedUsers * 100) / $totalUsers) : 0;
$percentNotLogged = $totalUsers > 0 ? 100 - $percentLogged : 0;
?>
<div class="login-reports-view" data-access-not-logged>

    <?php require __DIR__ . '/period-toolbar.php'; ?>

    <div class="ui hidden divider"></div>

    <section class="ui two stackable cards" aria-label="<?= escape_html(__($langGroup, 'Resumen de ingresos')); ?>">
        <?php
            //El porcentaje de los que no ingresaron, en el color de advertencia del tema (StatusLabel).
            $pieces->_render('panel/built-in/statistics/statistic-card.php', [
                'title' => __($langGroup, 'Sin ingreso'),
                'help' => __($langGroup, 'Usuarios sin ningún ingreso en el periodo'),
                'figure' => ['value' => (string) $notLoggedUsers, 'dataType' => 'notLoggedUsers', 'content' => '<div class="description">' . StatusLabel::html(sprintf(__($langGroup, '%d %% del total de usuarios'), $percentNotLogged), StatusLabel::WARNING) . '</div>'],
            ]);
            $pieces->_render('panel/built-in/statistics/statistic-card.php', [
                'title' => __($langGroup, 'Con ingreso'),
                'help' => __($langGroup, 'Usuarios con al menos un ingreso en el periodo'),
                'figure' => ['value' => (string) $loggedUsers, 'dataType' => 'loggedUsers', 'description' => sprintf(__($langGroup, '%d %% del total de usuarios'), $percentLogged)],
            ]);
        ?>
    </section>

    <div class="ui hidden divider"></div>

    <?php if ($notLoggedUsers === 0): ?>
    <?php $pieces->_render('panel/built-in/content/empty-state.php', ['text' => __($langGroup, 'Todos los usuarios han ingresado en este periodo.'), 'icon' => 'check circle']); ?>
    <?php else: ?>
    <div class="ui fluid card container-standard-table">

        <table data-access-table="<?= escape_html($tableURL); ?>" class="ui selectable very basic table content-table">
            <thead>
                <tr>
                    <th class="collapsing"><?= __($langGroup, 'ID'); ?></th>
                    <th><?= __($langGroup, 'Usuario'); ?></th>
                    <th><?= __($langGroup, 'Nombre'); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

    </div>
    <?php endif; ?>

</div>
