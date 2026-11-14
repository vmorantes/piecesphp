<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * La pestaña «Sesiones abiertas» de «Registros»: filtros, la tabla y la ventana de extender. Bloques hermanos, sin marco
 * alrededor (el PO, 2026-10-10).
 *
 * @var string $langGroup
 * @var string $processTableLink
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $pieces Pinta las piezas del panel
 */
?>
<form class="ui form segment" data-sessions-filters>
    <div class="fields">
        <div class="five wide field">
            <label for="sessions-filter-user"><?= __($langGroup, 'Usuario'); ?></label>
            <input type="text" id="sessions-filter-user" name="user" placeholder="<?= escape_html(__($langGroup, 'Usuario o correo')); ?>">
        </div>
        <div class="five wide field" calendar-js calendar-type="date">
            <label><?= __($langGroup, 'Iniciadas desde'); ?></label>
            <div class="ui input left icon">
                <i class="calendar icon"></i>
                <input type="text" name="from" autocomplete="off">
            </div>
        </div>
    </div>
    <?php $pieces->_render('panel/built-in/content/filter-actions.php'); ?>
</form>

<div class="ui fluid card container-standard-table">
    <table url="<?= escape_html($processTableLink); ?>" class="ui selectable very basic table content-table" data-search-placeholder="<?= escape_html(__($langGroup, 'Usuario, navegador o IP')); ?>" data-sessions-table>
        <thead>
            <tr>
                <th><?= __($langGroup, 'Usuario / correo'); ?></th>
                <th><?= __($langGroup, 'Navegador'); ?></th>
                <th><?= __($langGroup, 'IP'); ?></th>
                <th><?= __($langGroup, 'Inicio'); ?></th>
                <th><?= __($langGroup, 'Última actividad'); ?></th>
                <th><?= __($langGroup, 'Caduca'); ?></th>
                <th class-name="collapsing right aligned" order="false" search="false"><?= __($langGroup, 'Acciones'); ?></th>
            </tr>
        </thead>
    </table>
</div>

<?php require __DIR__ . '/extend-modal.php'; ?>
