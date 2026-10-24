<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * La pestaña «Sesiones abiertas» de «Registros»: filtros, la tabla y la ventana de extender. Bloques hermanos, sin marco
 * alrededor (el PO, 2026-10-10).
 *
 * @var string $langGroup
 * @var string $processTableLink
 */
?>
<form class="ui form segment" data-sessions-filters>
    <div class="fields">
        <div class="eight wide field">
            <label><?= __($langGroup, 'Usuario'); ?></label>
            <div class="ui input">
                <input type="text" name="user">
            </div>
        </div>
        <div class="four wide field" calendar-js calendar-type="date">
            <label><?= __($langGroup, 'Iniciadas desde'); ?></label>
            <div class="ui input left icon">
                <i class="calendar icon"></i>
                <input type="text" name="from" autocomplete="off">
            </div>
        </div>
    </div>
    <button type="submit" class="ui button brand-color"><i class="filter icon"></i><?= __($langGroup, 'Filtrar'); ?></button>
    <button type="reset" class="ui basic button"><?= __($langGroup, 'Limpiar'); ?></button>
</form>

<div class="container-standard-table">
    <table url="<?= escape_html($processTableLink); ?>" class="ui basic table content-table" data-sessions-table>
        <thead>
            <tr>
                <th><?= __($langGroup, 'Usuario'); ?></th>
                <th><?= __($langGroup, 'Navegador'); ?></th>
                <th><?= __($langGroup, 'IP'); ?></th>
                <th><?= __($langGroup, 'Inicio'); ?></th>
                <th><?= __($langGroup, 'Última actividad'); ?></th>
                <th><?= __($langGroup, 'Caduca'); ?></th>
                <th class="collapsing" order="false" search="false"><?= __($langGroup, 'Acciones'); ?></th>
            </tr>
        </thead>
    </table>
</div>

<?php require __DIR__ . '/extend-modal.php'; ?>
