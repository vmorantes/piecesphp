<?php
//Lo que se hace desde un tablero: el acceso directo del tema (la tarjeta entera es el enlace). Sin permiso, `disabled`:
//la tarjeta apagada del tema, sin enlace.
ob_start();
$this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
    'title' => __($langGroup, 'Nueva publicación'),
    'figure' => ['url' => '#', 'description' => __($langGroup, 'Escribir y programar una publicación.')],
]);
$this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
    'title' => __($langGroup, 'Exportar el informe'),
    'figure' => ['disabled' => true],
]);
$this->helpController->_render('panel/built-in/statistics/statistics-section.php', [
    'content' => (string) ob_get_clean(),
]);
