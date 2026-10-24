<div class="ui form">
    <div class="field required">
        <label><?= __($langGroup, 'Categoría'); ?></label>
        <select class="ui dropdown" name="category" required example-dropdown>
            <option value=""><?= __($langGroup, 'Elija una categoría'); ?></option>
            <option value="1"><?= __($langGroup, 'Informes'); ?></option>
            <option value="2"><?= __($langGroup, 'Guías'); ?></option>
        </select>
    </div>
</div>
