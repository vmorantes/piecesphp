<?php
//Sin `action`, la pantalla recibe el archivo en `editor.onApply()`; con `action`, el editor lo envía al aplicar.
//Dentro de un formulario va con `image-field.php`: con `required`, sin imagen no se guarda.
?>
<p><?= __($langGroup, 'Horizontal, la de siempre: la vista previa a un lado. Para una imagen por tarjeta en formularios y pantallas como Fondos o Identidad y SEO.'); ?></p>
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
<div class="ui section divider"></div>
<p><?= __($langGroup, 'Con las vistas previas debajo (previewsBelow): el texto arriba y las vistas previas del mismo tamaño, lado a lado. Para una imagen con más de una versión (modo claro y oscuro) o que puede ser apaisada o alta, como en Imágenes de marca.'); ?></p>
<div class="ui two stackable cards">
    <?php
        //La vista oscura de muestra no se sube: en Imágenes de marca es otra tarjeta del editor, anidada.
        $this->helpController->_render('panel/built-in/content/image-card.php', [
            'id' => 'design-sheet-image-below',
            'title' => __($langGroup, 'Logo'),
            'description' => __($langGroup, 'Dónde se usa la imagen en la plataforma.'),
            'hint' => __($langGroup, 'Preferiblemente con fondo transparente'),
            'image' => get_config('logo'),
            'field' => 'logoBelow',
            'width' => 400,
            'height' => 400,
            'previewsBelow' => true,
            'previewCaption' => __($langGroup, 'Modo claro'),
            'extraPreviewHtml' => '<div class="ui fluid card"><span class="ui mini label content-image-card-caption">' . escape_html(__($langGroup, 'Modo oscuro')) . '</span><div class="inverted checkered image"><img width="400" height="400" src="' . escape_html((string) get_config('logo')) . '" alt=""></div></div>',
        ]);
        $this->helpController->_render('panel/built-in/content/image-card.php', [
            'id' => 'design-sheet-image-below-single',
            'title' => __($langGroup, 'Sin modo oscuro'),
            'description' => __($langGroup, 'Una sola vista previa, a todo el ancho; sin medida, para una imagen de cualquier tamaño.'),
            'image' => get_config('logo'),
            'field' => 'logoBelowSingle',
            'width' => 400,
            'height' => 400,
            'previewsBelow' => true,
            'showSize' => false,
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
