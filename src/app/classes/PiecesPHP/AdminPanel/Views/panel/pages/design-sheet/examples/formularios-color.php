<div class="ui form">
    <div class="inline field">
        <?php
            $this->helpController->_render('panel/built-in/content/color-field.php', [
                'id' => 'example-color',
                'inputName' => 'color',
                'value' => '#00008B',
                'label' => __($langGroup, 'Color de la categoría'),
            ]);
        ?>
    </div>
    <div class="inline field">
        <?php
            $this->helpController->_render('panel/built-in/content/color-field.php', [
                'id' => 'example-color-locked',
                'value' => '#0B7A77',
                'label' => __($langGroup, 'Color fijo'),
                'disabled' => true,
            ]);
        ?>
    </div>
</div>
