<form method="POST" action="#" class="ui form initial" data-content-form>
    <div class="container-standard-form">
        <div class="form-section-title"><?= __($langGroup, 'Título de un bloque de formulario'); ?></div>
        <div class="field required">
            <label><?= __($langGroup, 'Título'); ?></label>
            <input type="text" name="title" maxlength="300" required>
        </div>
    </div>
    <?php
        //La barra va DENTRO del `form`, al final. Eliminar es `type="button"`: si el JavaScript no cargara, un `submit` guardaría.
        $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
            'saveText' => __($langGroup, 'Guardar'),
            'secondaryActions' => [
                ['text' => __($langGroup, 'Cancelar'), 'url' => '#', 'icon' => 'times'],
                ['text' => __($langGroup, 'Eliminar'), 'type' => 'button', 'icon' => 'trash alternate', 'danger' => true],
            ],
        ]);
    ?>
</form>
