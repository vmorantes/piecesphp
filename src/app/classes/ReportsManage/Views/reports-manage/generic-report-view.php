<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 */
//Los dos primeros países y «otros»: generic-report.js pone el nombre que trae el JSON, la cifra y el porcentaje por el
//data-type de cada fila y oculta la de un país que falte; la dona toma de cada fila su color (data-chart-color).
$countriesProportion = fn (string $dataTypePrefix, string $unit, ?string $unitShort = null): array => [
    'totalDataType' => "{$dataTypePrefix}Total",
    'unit' => $unit,
    'unitShort' => $unitShort ?? $unit,
    'items' => [
        ['name' => '', 'dataType' => "{$dataTypePrefix}Country1", 'series' => 'primary'],
        ['name' => '', 'dataType' => "{$dataTypePrefix}Country2", 'series' => '2'],
        ['name' => __($langGroup, 'Otros países'), 'dataType' => "{$dataTypePrefix}Others", 'series' => 'other'],
    ],
];
?>
<section class="module-view-container" data-generic-data-url="<?= escape_html(\API\Controllers\APIController::routeName('reports-actions', ['actionType' => 'get-generic-data'], true)); ?>">

    <div class="limiter-content">

        <?php
            $this->helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description,
            ]);
        ?>

        <div class="ui four doubling stackable cards">
            <?php
                $this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
                    'title' => __($langGroup, 'Tokens de IA disponibles'),
                    'figure' => ['value' => '0', 'dataType' => 'totalRemainingTokens', 'variant' => 'compact'],
                ]);
                $approvalsURL = \SystemApprovals\SystemApprovalsRoutes::enabled() ? \SystemApprovals\Controllers\SystemApprovalsController::routeName('list') : null;
                $this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
                    'title' => __($langGroup, 'Publicaciones por validar'),
                    'figure' => [
                        'value' => '0',
                        'dataType' => 'totalPendingPublicationsQty',
                        'variant' => 'compact',
                        'content' => $approvalsURL !== null ? '<div class="description"><a href="' . escape_html($approvalsURL) . '">' . escape_html(__($langGroup, 'Ver las aprobaciones')) . '</a></div>' : '',
                    ],
                ]);
            ?>
        </div>

        <div class="ui two stackable cards">
            <?php
                $this->helpController->_render('panel/built-in/statistics/chart-card.php', [
                    'attributes' => ['data-type' => 'peopleData'],
                    'title' => __($langGroup, 'Total de usuarios generales'),
                    'proportion' => $countriesProportion('peopleData', __($langGroup, 'usuarios generales'), __($langGroup, 'usuarios')),
                ]);
                $this->helpController->_render('panel/built-in/statistics/chart-card.php', [
                    'attributes' => ['data-type' => 'organizationsData'],
                    'title' => __($langGroup, 'Total Organizaciones'),
                    'proportion' => $countriesProportion('organizationsData', __($langGroup, 'organización(es)')),
                ]);
            ?>
        </div>

    </div>

</section>
