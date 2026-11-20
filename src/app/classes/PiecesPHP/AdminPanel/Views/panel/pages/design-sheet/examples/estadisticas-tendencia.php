<?php
//La tarjeta de tendencia del tema, dentro de `ui cards`: el total lo rellena el JavaScript de quien llama (Apache ECharts,
//con import_echarts() en el controlador) y drawStatisticsTrend() dibuja el área y pone la variación.
?>
<div class="ui two stackable cards">
    <?php
        $this->helpController->_render('panel/built-in/statistics/chart-card.php', [
            'attributes' => ['example-trend' => ''],
            'title' => __($langGroup, 'Nuevos registros'),
            'trend' => [
                'totalDataType' => 'exampleTrendTotal',
                'meta' => __($langGroup, 'Por día · últimos 14 días'),
                'chartLabel' => __($langGroup, 'Nuevos registros por día en los últimos 14 días'),
            ],
        ]);
    ?>
</div>
