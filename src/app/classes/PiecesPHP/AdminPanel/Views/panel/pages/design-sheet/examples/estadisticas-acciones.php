<?php
//Lo que se hace desde un tablero. Sin permiso, `not-allowed`: se ve apagada y no lleva a ningún sitio.
ob_start();
$this->helpController->_render('panel/built-in/statistics/action-card.php', [
    'url' => '#',
    'icon' => 'plus square outline',
    'title' => __($langGroup, 'Nueva publicación'),
    'description' => __($langGroup, 'Escribir y programar una publicación.'),
]);
$this->helpController->_render('panel/built-in/statistics/action-card.php', [
    'icon' => 'file alternate outline',
    'title' => __($langGroup, 'Exportar el informe'),
    'modifiers' => ['tall', 'horizontal-center', 'not-allowed'],
]);
$this->helpController->_render('panel/built-in/statistics/statistics-section.php', [
    'content' => (string) ob_get_clean(),
]);
