<?php
//El interruptor con su ayuda. La pantalla lo inicia con `.checkbox()`. El input numérico lee en vivo su equivalente.
?>
<div class="ui form">
    <?php
        $this->helpController->_render('panel/built-in/content/toggle-field.php', [
            'id' => 'design-sheet-toggle',
            'inputName' => 'enabled',
            'label' => __($langGroup, 'Activar traducción con IA'),
            'description' => __($langGroup, 'La ayuda dice qué pasa al encenderlo.'),
            'checked' => true,
        ]);
    ?>
    <div class="field">
        <label for="design-sheet-days"><?= __($langGroup, 'Días'); ?></label>
        <input type="number" id="design-sheet-days" value="31" min="0" data-live-reading="days" data-live-reading-zero="<?= escape_html(__($langGroup, 'Sin límite')); ?>">
        <div class="description"><span><?= __($langGroup, '0 = sin límite'); ?></span><span class="counter" data-live-reading-for="design-sheet-days" aria-live="polite"></span></div>
    </div>
</div>
