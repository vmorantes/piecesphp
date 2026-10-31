<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * La pestaña «Tokens y códigos» de «Registros»: los vigentes y, debajo, su historia. Bloques hermanos, sin marco
 * alrededor (el PO, 2026-10-10).
 *
 * @var string $langGroup
 * @var string $processTableLink
 * @var string $historyTableLink Vacía si el registro de acciones está apagado
 * @var bool $isRoot
 */
?>
<h2 class="ui header"><?= __($langGroup, 'Vigentes'); ?></h2>
<?php if (!$isRoot) : ?>
<p><?= __($langGroup, 'Ver el valor de un código, invalidarlo o restablecer un doble factor es solo del usuario principal.'); ?></p>
<?php endif; ?>
<div class="ui fluid card container-standard-table">
    <table url="<?= escape_html($processTableLink); ?>" class="ui single line selectable very basic table content-table" data-search-placeholder="<?= escape_html(__($langGroup, 'Tipo o usuario')); ?>" data-tokens-table>
        <thead>
            <tr>
                <th><?= __($langGroup, 'Tipo'); ?></th>
                <th><?= __($langGroup, 'Usuario'); ?></th>
                <th><?= __($langGroup, 'Creado'); ?></th>
                <th><?= __($langGroup, 'Caduca'); ?></th>
                <th class-name="collapsing right aligned" order="false" search="false"><?= __($langGroup, 'Acciones'); ?></th>
            </tr>
        </thead>
    </table>
</div>

<h2 class="ui header"><?= __($langGroup, 'Historia'); ?></h2>
<?php if ($historyTableLink === '') : ?>
<p><?= __($langGroup, 'No hay historia: el registro de acciones está apagado en esta instalación.'); ?></p>
<?php else : ?>
<div class="ui fluid card container-standard-table">
    <table url="<?= escape_html($historyTableLink); ?>" class="ui selectable very basic table content-table" data-search-placeholder="<?= escape_html(__($langGroup, 'Evento o usuario')); ?>" data-tokens-history>
        <thead>
            <tr>
                <th><?= __($langGroup, 'Fecha'); ?></th>
                <th><?= __($langGroup, 'Evento'); ?></th>
                <th><?= __($langGroup, 'Usuario'); ?></th>
                <th><?= __($langGroup, 'Quién'); ?></th>
            </tr>
        </thead>
    </table>
</div>
<?php endif; ?>
