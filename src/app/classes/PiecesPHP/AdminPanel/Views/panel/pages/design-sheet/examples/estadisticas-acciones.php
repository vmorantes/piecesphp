<?php
//Lo que se hace desde un tablero: el acceso directo del tema (la tarjeta entera es el enlace). Sin permiso, la tarjeta
//apagada del tema (`ui disabled card`) y sin enlace.
ob_start();
$this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
    'title' => __($langGroup, 'Nueva publicación'),
    'figure' => ['url' => '#', 'description' => __($langGroup, 'Escribir y programar una publicación.')],
]);
?>
<div class="ui disabled statistic card" aria-disabled="true">
    <div class="content">
        <div class="header"><?= escape_html(__($langGroup, 'Exportar el informe')); ?></div>
    </div>
</div>
<?php
$this->helpController->_render('panel/built-in/statistics/statistics-section.php', [
    'content' => (string) ob_get_clean(),
]);
