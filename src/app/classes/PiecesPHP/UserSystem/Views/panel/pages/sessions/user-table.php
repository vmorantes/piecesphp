<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * Las sesiones abiertas de un usuario en su ficha (Editar usuario › Sesiones): la tabla de Registros › Sesiones abiertas,
 * filtrada por su id. La primera columna (usuario) va oculta: es siempre el mismo. La arranca sessions.js.
 *
 * @var string $langGroup
 * @var string $processTableLink
 */
?>
<div class="ui fluid card container-standard-table">
    <table url="<?= escape_html($processTableLink); ?>" class="ui selectable very basic table content-table" data-search-placeholder="<?= escape_html(__($langGroup, 'Navegador o IP')); ?>" data-user-sessions-table>
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
