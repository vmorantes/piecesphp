<?php
//Migas, título, una frase, UNA acción principal y las demás como botones básicos. Las migas van como arreglo y la
//pieza las escapa: un texto que escribió un usuario se pasa tal cual.
$this->helpController->_render('panel/built-in/content/page-header.php', [
    'breadcrumbs' => [
        __($langGroup, 'Inicio') => ['url' => '#'],
        __($langGroup, 'Publicaciones'),
    ],
    'title' => __($langGroup, 'Publicaciones'),
    'description' => __($langGroup, 'Lo que se publica en el sitio, con su categoría y su fecha.'),
    'primaryAction' => ['text' => __($langGroup, 'Nueva publicación'), 'url' => '#', 'icon' => 'plus'],
    'secondaryActions' => [['text' => __($langGroup, 'Categorías'), 'url' => '#', 'icon' => 'tags']],
]);
