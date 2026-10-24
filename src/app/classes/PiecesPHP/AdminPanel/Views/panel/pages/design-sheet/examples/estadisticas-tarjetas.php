<?php
//La sección ordena las tarjetas; dentro, el HTML de quien llama. Cada tarjeta, un número con su pie: el JavaScript del
//módulo lo rellena por su `data-type` (aquí, el valor inicial).
ob_start();
$this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
    'title' => __($langGroup, 'Usuarios'),
    'number' => ['dataType' => 'exampleUsers', 'initial' => '128'],
    'help' => __($langGroup, 'Los usuarios activos del panel.'),
    'footer' => ['caption' => __($langGroup, 'Usuarios activos'), 'url' => '#'],
]);
$this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
    'modifiers' => ['tall'],
    'title' => __($langGroup, 'Publicaciones'),
    'titleModifiers' => ['big'],
    'number' => ['dataType' => 'examplePublications', 'initial' => '42'],
    'footer' => ['caption' => __($langGroup, 'Por validar'), 'url' => '#', 'two' => true],
]);
$this->helpController->_render('panel/built-in/statistics/statistics-section.php', [
    'content' => (string) ob_get_clean(),
]);
