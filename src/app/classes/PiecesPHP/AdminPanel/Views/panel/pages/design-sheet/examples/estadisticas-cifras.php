<?php
//Dos o tres cifras que se comparan, en la misma tarjeta de cifra del tema, cada una con su concepto.
ob_start();
$this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
    'title' => __($langGroup, 'Movimientos'),
    'figure' => [
        'statistics' => [
            ['value' => '12', 'dataType' => 'exampleUp', 'label' => __($langGroup, 'Altas')],
            ['value' => '3', 'dataType' => 'exampleDown', 'label' => __($langGroup, 'Bajas')],
        ],
        'description' => __($langGroup, 'Este mes'),
    ],
]);
$this->helpController->_render('panel/built-in/statistics/statistics-section.php', [
    'content' => (string) ob_get_clean(),
]);
