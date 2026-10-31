<?php
//La pieza pinta el botón con sus atributos; lo que hace al pulsarlo lo engancha el JavaScript del módulo.
$this->helpController->_render('panel/built-in/content/main-action.php', [
    'title' => __($langGroup, 'Borrar todo lo temporal'),
    'description' => __($langGroup, 'Vacía todas las cachés de una vez. El sitio irá algo más lento mientras se vuelven a crear.'),
    'buttonText' => __($langGroup, 'Borrar todo lo temporal'),
    'attributes' => ['data-confirm' => __($langGroup, '¿Borrar todas las copias temporales?')],
]);
//La destructiva: roja y básica, con su icono.
$this->helpController->_render('panel/built-in/content/main-action.php', [
    'title' => __($langGroup, 'Cerrar todas sus sesiones'),
    'description' => __($langGroup, 'Saca a este usuario de todos los dispositivos en los que haya entrado.'),
    'buttonText' => __($langGroup, 'Cerrar todas sus sesiones'),
    'variant' => 'negative',
    'icon' => 'sign out alternate',
]);
