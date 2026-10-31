<?php
//La tarjeta de cifra del tema. `statistic-card.php` con `figure`; sin él, sigue siendo la pieza de las estadísticas.
?>
<div class="ui three stackable cards">
    <?php
        $this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
            'title' => __($langGroup, 'Respaldos guardados'),
            'figure' => ['value' => '96', 'variant' => 'compact'],
        ]);
        $this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
            'title' => __($langGroup, 'Último respaldo'),
            'help' => __($langGroup, 'Tarjeta de cifra'),
            'figure' => ['value' => '16/10/2026', 'description' => '18:58'],
        ]);
        $this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
            'title' => __($langGroup, 'Próxima ejecución'),
            'figure' => ['value' => '03:30', 'label' => '31-10-2026', 'variant' => 'tall', 'size' => 'huge marked', 'meta' => __($langGroup, 'Tarjeta de cifra')],
        ]);
    ?>
</div>
