<?php
//Un porcentaje con el círculo del tema (`ui circular progress`: su JavaScript pone el porcentaje de `data-percent`) y
//las cifras de lo que suma.
$circle = '<div class="ui circular progress" data-percent="70" data-type="exampleProgress" example-progress><div class="bar"><div class="progress"></div></div></div>';
ob_start();
$this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
    'title' => __($langGroup, 'Solicitudes'),
    'figure' => [
        'statistics' => [
            ['value' => '70', 'dataType' => 'exampleApproved', 'label' => __($langGroup, 'Aprobadas')],
            ['value' => '30', 'dataType' => 'examplePending', 'label' => __($langGroup, 'Pendientes')],
        ],
        'content' => $circle,
    ],
]);
$this->helpController->_render('panel/built-in/statistics/statistics-section.php', [
    'content' => (string) ob_get_clean(),
]);
