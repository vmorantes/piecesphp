<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 */
//Los dos primeros países y «otros»: generic-report.js pone el nombre que trae el JSON, el valor por su data-type y oculta
//la fila de un país que falte; las tortas usan estos mismos colores.
$countriesLegend = fn (string $dataTypePrefix): array => [
    ['name' => '', 'dataType' => "{$dataTypePrefix}Country1", 'level' => 'primary', 'color' => 'var(--main-brand-color)', 'valueModifiers' => ['neutral', 'right']],
    ['name' => '', 'dataType' => "{$dataTypePrefix}Country2", 'level' => 'secondary', 'color' => 'var(--second-brand-color)', 'valueModifiers' => ['neutral', 'right']],
    ['name' => __($langGroup, 'Otros países'), 'dataType' => "{$dataTypePrefix}Others", 'level' => 'secondary', 'color' => 'var(--statistics-disabled-color)', 'valueModifiers' => ['neutral', 'right']],
];
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
        $this->helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'description' => $description,
        ]);
        ?>

        <?php ob_start(); ?>
        <div class="two-columns-grid">
            <?php
            //Como las cifras del inicio (home.php): el número y su concepto juntos, dentro de la tarjeta.
            $this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
                'content' => (string) $this->helpController->_render('panel/built-in/statistics/number-cards.php', [
                    'items' => [['dataType' => 'totalRemainingTokens', 'concept' => __($langGroup, 'Tokens de IA disponibles')]],
                ], false, false),
            ]);
            $approvalsURL = \SystemApprovals\SystemApprovalsRoutes::ENABLE ? \SystemApprovals\Controllers\SystemApprovalsController::routeName('list') : null;
            $this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
                'content' => (string) $this->helpController->_render('panel/built-in/statistics/number-cards.php', [
                    'items' => [['dataType' => 'totalPendingPublicationsQty', 'concept' => __($langGroup, 'Publicaciones por validar')]],
                ], false, false),
                'footer' => $approvalsURL !== null ? ['caption' => __($langGroup, 'Ver las aprobaciones'), 'url' => $approvalsURL] : null,
            ]);
            ?>
        </div>
        <div class="two-columns-grid">
            <?php
            $this->helpController->_render('panel/built-in/statistics/chart-card.php', [
                'attributes' => ['data-type' => 'peopleData'],
                'title' => __($langGroup, 'Total de usuarios generales'),
                'titleModifiers' => ['big'],
                'chartAttributes' => ['class' => 'apex-chart'],
                'legend' => $countriesLegend('peopleData'),
            ]);
            $this->helpController->_render('panel/built-in/statistics/chart-card.php', [
                'attributes' => ['data-type' => 'organizationsData'],
                'title' => __($langGroup, 'Total Organizaciones'),
                'titleModifiers' => ['big'],
                'chartAttributes' => ['class' => 'apex-chart'],
                'legend' => $countriesLegend('organizationsData'),
            ]);
            ?>
        </div>
        <?php
        $this->helpController->_render('panel/built-in/statistics/statistics-section.php', [
            'modifiers' => ['one-column'],
            'content' => (string) ob_get_clean(),
        ]);
        ?>

    </div>

</section>
