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
<h3 class="ui header"><?= __($langGroup, 'Vigentes'); ?></h3>
<?php if (!$isRoot) : ?>
<p><?= __($langGroup, 'Ver el valor de un código, invalidarlo o restablecer un doble factor es solo del usuario principal.'); ?></p>
<?php endif; ?>
<div class="container-standard-table">
    <table url="<?= escape_html($processTableLink); ?>" class="ui basic table content-table" data-tokens-table>
        <thead>
            <tr>
                <th><?= __($langGroup, 'Tipo'); ?></th>
                <th><?= __($langGroup, 'Usuario'); ?></th>
                <th><?= __($langGroup, 'Creado'); ?></th>
                <th><?= __($langGroup, 'Caduca'); ?></th>
                <th class="collapsing" order="false" search="false"><?= __($langGroup, 'Acciones'); ?></th>
            </tr>
        </thead>
    </table>
</div>

<h3 class="ui header"><?= __($langGroup, 'Historia'); ?></h3>
<?php if ($historyTableLink === '') : ?>
<p><?= __($langGroup, 'No hay historia: el registro de acciones está apagado en esta instalación.'); ?></p>
<?php else : ?>
<div class="container-standard-table">
    <table url="<?= escape_html($historyTableLink); ?>" class="ui basic table content-table" data-tokens-history>
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
