<?php
//Dos o tres cifras que se comparan, dentro de una tarjeta centrada.
$numbers = (string) $this->helpController->_render('panel/built-in/statistics/number-cards.php', [
    'items' => [
        ['dataType' => 'exampleUp', 'color' => 'green', 'initial' => '12', 'concept' => __($langGroup, 'Altas'), 'subconcept' => __($langGroup, 'Este mes')],
        ['dataType' => 'exampleDown', 'color' => 'orange', 'initial' => '3', 'concept' => __($langGroup, 'Bajas'), 'subconcept' => __($langGroup, 'Este mes')],
    ],
], false);
ob_start();
$this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
    'modifiers' => ['tall', 'horizontal-center'],
    'title' => __($langGroup, 'Movimientos'),
    'titleModifiers' => ['medium'],
    'content' => $numbers,
]);
$this->helpController->_render('panel/built-in/statistics/statistics-section.php', [
    'content' => (string) ob_get_clean(),
]);
