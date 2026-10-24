<!-- `initial` y `data-content-form`: lo obligatorio vacío no sale en rojo al abrir, solo al intentar guardar. -->
<form method="POST" action="#" class="ui form initial" data-content-form>
    <div class="field required">
        <label><?= __($langGroup, 'Título'); ?></label>
        <input type="text" name="title" maxlength="300" required>
    </div>
    <div class="field">
        <label><?= __($langGroup, 'Resumen'); ?></label>
        <textarea name="summary" rows="3"></textarea>
    </div>
    <button class="ui button brand-color" type="submit"><?= __($langGroup, 'Guardar'); ?></button>
</form>
