<?php
//Sola en la primera columna de un ui stackable grid; el contenido, en la siguiente. Cada ancla es el id de una sección.
?>
<div class="ui stackable grid">
    <div class="four wide computer sixteen wide tablet column">
        <?php
            $this->helpController->_render('panel/built-in/content/section-nav.php', [
                'label' => __($langGroup, 'Secciones del ejemplo'),
                'items' => [
                    ['label' => __($langGroup, 'Otra página'), 'href' => '#'],
                    [
                        'label' => __($langGroup, 'Esta página'),
                        'children' => [
                            ['label' => __($langGroup, 'Primera sección'), 'anchor' => 'ejemplo-secciones-1'],
                            ['label' => __($langGroup, 'Segunda sección'), 'anchor' => 'ejemplo-secciones-2'],
                        ],
                    ],
                ],
            ]);
        ?>
    </div>
    <div class="twelve wide computer sixteen wide tablet column">
        <section class="ui segment" id="ejemplo-secciones-1">
            <h3 class="ui header"><?= __($langGroup, 'Primera sección'); ?></h3>
            <p><?= __($langGroup, 'En escritorio, el menú se queda fijo al desplazar; por debajo, un desplegable lleva a cada sección.'); ?></p>
        </section>
        <section class="ui segment" id="ejemplo-secciones-2">
            <h3 class="ui header"><?= __($langGroup, 'Segunda sección'); ?></h3>
            <p><?= __($langGroup, 'La que se ve se marca en el menú al desplazar.'); ?></p>
        </section>
    </div>
</div>
