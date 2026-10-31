<div class="ui form">
    <div class="inline field">
        <?php
            $this->helpController->_render('panel/built-in/content/color-field.php', [
                'id' => 'example-color',
                'inputName' => 'color',
                'value' => '#356DCA',
                'label' => __($langGroup, 'Color de la categoría'),
            ]);
        ?>
    </div>
    <div class="inline field">
        <?php
            $this->helpController->_render('panel/built-in/content/color-field.php', [
                'id' => 'example-color-locked',
                'value' => '#7A5AF8',
                'label' => __($langGroup, 'Color fijo'),
                'disabled' => true,
            ]);
        ?>
    </div>
</div>
