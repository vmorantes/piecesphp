<?php
//Los botones al final de un formulario de filtros: `reset` si el JavaScript filtra sin recargar, `clearUrl` si va por GET.
?>
<form class="ui form segment" onsubmit="return false">
    <div class="fields">
        <div class="five wide field">
            <label for="design-sheet-filter"><?= __($langGroup, 'Texto buscado'); ?></label>
            <input type="text" id="design-sheet-filter">
        </div>
    </div>
    <?php $this->helpController->_render('panel/built-in/content/filter-actions.php'); ?>
</form>
