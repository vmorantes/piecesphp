<?php
//La sección ordena las tarjetas (la rejilla del tema); cada una, la tarjeta de cifra con su pie. El JavaScript del módulo
//rellena la cifra por su `data-type` (aquí, el valor inicial).
ob_start();
$this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
    'title' => __($langGroup, 'Usuarios'),
    'help' => __($langGroup, 'Los usuarios activos del panel.'),
    'figure' => ['value' => '128', 'dataType' => 'exampleUsers'],
    'footer' => ['caption' => __($langGroup, 'Usuarios activos'), 'url' => '#'],
]);
$this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
    'title' => __($langGroup, 'Publicaciones'),
    'figure' => ['value' => '42', 'dataType' => 'examplePublications', 'variant' => 'tall'],
    'footer' => ['caption' => __($langGroup, 'Por validar'), 'url' => '#'],
]);
$this->helpController->_render('panel/built-in/statistics/statistics-section.php', [
    'content' => (string) ob_get_clean(),
]);
