<?php
//La cabecera deja 1rem hasta lo que la sigue y, por debajo de 992 px, sus botones van a la izquierda separados 0.75rem
//(_content-screens.scss). La pantalla no añade márgenes propios: los pone la pieza.
$this->helpController->_render('panel/built-in/content/page-header.php', [
    'title' => __($langGroup, 'Título de una pantalla'),
    'description' => __($langGroup, 'La frase que dice para qué sirve la pantalla.'),
    'primaryAction' => ['text' => __($langGroup, 'Acción principal'), 'url' => '#', 'icon' => 'plus'],
    'secondaryActions' => [['text' => __($langGroup, 'Acción secundaria'), 'url' => '#', 'icon' => 'tags']],
]);
?>
<div class="ui secondary segment"><?= __($langGroup, 'Lo que sigue a la cabecera.'); ?></div>
