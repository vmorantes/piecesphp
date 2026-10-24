<?php
//El contenedor del gráfico y su leyenda; el gráfico lo dibuja el JavaScript de quien llama (ApexCharts, con
//import_apexcharts() en el controlador), sobre los atributos del contenedor.
ob_start();
$this->helpController->_render('panel/built-in/statistics/chart-card.php', [
    'modifiers' => ['tall'],
    'title' => __($langGroup, 'Usuarios por país'),
    'titleModifiers' => ['big'],
    'chartAttributes' => ['example-chart' => ''],
    'legend' => [
        ['name' => __($langGroup, 'Colombia'), 'dataType' => 'exampleColombia', 'level' => 'primary', 'color' => '#3558A2', 'valueModifiers' => ['neutral', 'right'], 'initial' => '60'],
        ['name' => __($langGroup, 'Francia'), 'dataType' => 'exampleFrance', 'level' => 'secondary', 'color' => '#254079', 'valueModifiers' => ['neutral', 'right'], 'initial' => '25'],
        ['name' => __($langGroup, 'Otros países'), 'dataType' => 'exampleOthers', 'level' => 'secondary', 'color' => '#7A7A7A', 'valueModifiers' => ['neutral', 'right'], 'initial' => '15'],
    ],
]);
$this->helpController->_render('panel/built-in/statistics/statistics-section.php', [
    'content' => (string) ob_get_clean(),
]);
