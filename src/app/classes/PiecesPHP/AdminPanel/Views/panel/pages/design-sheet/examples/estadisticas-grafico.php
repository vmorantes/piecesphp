<?php
//La tarjeta de proporción del tema, dentro de `ui cards`: la rellena y la dibuja el JavaScript de quien llama (Apache
//ECharts, con import_echarts() en el controlador) por el `data-type` de cada fila.
?>
<div class="ui two stackable cards">
    <?php
        $this->helpController->_render('panel/built-in/statistics/chart-card.php', [
            'attributes' => ['example-chart' => ''],
            'title' => __($langGroup, 'Usuarios por país'),
            'proportion' => [
                'totalDataType' => 'exampleTotal',
                'unit' => __($langGroup, 'usuarios'),
                'items' => [
                    ['name' => __($langGroup, 'Colombia'), 'dataType' => 'exampleColombia', 'series' => 'primary'],
                    ['name' => __($langGroup, 'Francia'), 'dataType' => 'exampleFrance', 'series' => '2'],
                    ['name' => __($langGroup, 'Otros países'), 'dataType' => 'exampleOthers', 'series' => 'other'],
                ],
            ],
        ]);
    ?>
</div>
