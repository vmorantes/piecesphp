<?php
//Dentro de un `ui form segment`: el título, los campos y un `ui section divider` antes de la siguiente.
?>
<div class="ui form segment">
    <?php
        $this->helpController->_render('panel/built-in/content/section-header.php', [
            'title' => __($langGroup, 'Duración de la sesión'),
            'description' => __($langGroup, 'La frase dice para qué sirve la sección.'),
        ]);
    ?>
    <div class="field">
        <label for="design-sheet-section"><?= __($langGroup, 'Nombre'); ?></label>
        <input type="text" id="design-sheet-section">
    </div>
    <div class="ui section divider"></div>
    <?php
        $this->helpController->_render('panel/built-in/content/section-header.php', [
            'title' => __($langGroup, 'Otra sección'),
        ]);
    ?>
</div>
