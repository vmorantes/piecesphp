<?php
//La pieza pinta el field entero. El ojo lo mueve el panel: el módulo no escribe JavaScript.
?>
<div class="ui form">
    <?php
        $this->helpController->_render('panel/built-in/content/password-field.php', [
            'id' => 'design-sheet-password',
            'inputName' => 'password',
            'label' => __($langGroup, 'Contraseña'),
            'autocomplete' => 'new-password',
        ]);
    ?>
</div>
