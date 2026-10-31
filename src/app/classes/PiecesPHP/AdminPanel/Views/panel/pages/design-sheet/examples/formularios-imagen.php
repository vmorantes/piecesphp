<?php
//Sin `action`, la pantalla recibe el archivo en `editor.onApply()`; con `action`, el editor lo envía al aplicar.
//Dentro de un formulario va con `image-field.php`: con `required`, sin imagen no se guarda.
?>
<div class="ui two stackable horizontal cards">
    <?php
        $this->helpController->_render('panel/built-in/content/image-card.php', [
            'id' => 'design-sheet-image',
            'title' => __($langGroup, 'Logo'),
            'description' => __($langGroup, 'Dónde se usa la imagen en la plataforma.'),
            'hint' => __($langGroup, 'Preferiblemente con fondo transparente'),
            'accept' => 'image/*,.ico',
            'image' => get_config('logo'),
            'field' => 'logo',
            'width' => 400,
            'height' => 400,
        ]);
        $this->helpController->_render('panel/built-in/content/image-card.php', [
            'id' => 'design-sheet-image-empty',
            'title' => __($langGroup, 'Sin imagen'),
            'description' => __($langGroup, 'La variante vacía: el mismo Cambiar imagen, sin Descargar.'),
            'accept' => 'image/*',
            'field' => 'sample',
            'width' => 500,
            'height' => 172,
        ]);
    ?>
</div>
<div class="ui hidden divider"></div>
<div class="ui form">
    <?php
        $this->helpController->_render('panel/built-in/content/image-field.php', [
            'card' => [
                'id' => 'design-sheet-image-field',
                'title' => __($langGroup, 'Imagen principal'),
                'hint' => __($langGroup, 'Preferiblemente con fondo transparente'),
                'field' => 'mainImage',
                'width' => 800,
                'height' => 600,
                'required' => true,
            ],
        ]);
    ?>
</div>
<?php $this->helpController->_render('panel/built-in/content/image-editor.php'); ?>
