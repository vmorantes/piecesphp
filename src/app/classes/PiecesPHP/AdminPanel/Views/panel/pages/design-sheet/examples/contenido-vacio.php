<?php
//Cuando no hay ningún registro todavía; una búsqueda sin resultados la avisa DataTables.
$this->helpController->_render('panel/built-in/content/empty-state.php', [
    'text' => __($langGroup, 'Aún no hay noticias.'),
    'icon' => 'newspaper outline',
    'action' => ['text' => __($langGroup, 'Nueva noticia'), 'url' => '#'],
]);
