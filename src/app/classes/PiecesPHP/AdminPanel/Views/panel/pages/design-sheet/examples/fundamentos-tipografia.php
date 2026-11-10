<?php
$this->helpController->_render('panel/built-in/content/page-header.php', [
    'title' => __($langGroup, 'Título de una pantalla'),
    'description' => __($langGroup, 'La frase que dice para qué sirve la pantalla.'),
]);
?>
<p><?= __($langGroup, 'Texto corriente: el de los párrafos, las celdas y las etiquetas.'); ?></p>
<p>
    <a href="#"><?= __($langGroup, 'Un título enlazado'); ?></a>
    <br>
    <span class="ui small grey text"><?= __($langGroup, 'Texto pequeño y gris, debajo de un título'); ?></span>
</p>
