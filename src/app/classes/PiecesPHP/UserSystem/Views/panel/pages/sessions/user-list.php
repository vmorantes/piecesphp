<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\Utilities\Helpers\StatusLabel;

/**
 * Las sesiones abiertas de un usuario («Mis sesiones» y su ficha). Lo pinta UserSessionsController::userSessionsHTML():
 * solo lo que quien mira puede ver.
 *
 * @var string $langGroup
 * @var array<int,array{browser:string,ip:string,createdAt:string,lastActivityAt:string,expiresAt:string,current:bool,actions:string}> $rows
 * @var bool $mine
 * @var string $closeOthersURL
 */
?>
<div data-sessions-list>
    <?php if ($rows === []): ?>
    <div class="ui message"><?= __($langGroup, 'No hay sesiones abiertas.'); ?></div>
    <?php else: ?>
    <table class="ui very basic table content-table">
        <thead>
            <tr>
                <th><?= __($langGroup, 'Navegador'); ?></th>
                <th><?= __($langGroup, 'IP'); ?></th>
                <th><?= __($langGroup, 'Inicio'); ?></th>
                <th><?= __($langGroup, 'Última actividad'); ?></th>
                <th><?= __($langGroup, 'Caduca'); ?></th>
                <th class="collapsing"><?= __($langGroup, 'Acciones'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr data-session-current="<?= $row['current'] ? 'yes' : 'no'; ?>">
                <td data-label="<?= escape_html(__($langGroup, 'Navegador')); ?>">
                    <?= escape_html($row['browser']); ?>
                    <?php if ($row['current']): ?>
                    <?= StatusLabel::html(__($langGroup, 'Esta sesión'), StatusLabel::POSITIVE); ?>
                    <?php endif; ?>
                </td>
                <td data-label="<?= escape_html(__($langGroup, 'IP')); ?>"><?= escape_html($row['ip']); ?></td>
                <td data-label="<?= escape_html(__($langGroup, 'Inicio')); ?>"><?= escape_html($row['createdAt']); ?></td>
                <td data-label="<?= escape_html(__($langGroup, 'Última actividad')); ?>"><?= escape_html($row['lastActivityAt']); ?></td>
                <td data-label="<?= escape_html(__($langGroup, 'Caduca')); ?>"><?= escape_html($row['expiresAt']); ?></td>
                <td><?= $mine && $row['current'] ? '' : $row['actions']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <?php if ($mine && count($rows) > 1): ?>
    <button type="button" class="ui button red" data-session-close-others="<?= escape_html($closeOthersURL); ?>" data-confirmation-message="<?= escape_html(__($langGroup, 'Se cerrarán todas tus sesiones menos ésta.')); ?>"><i class="sign out alternate icon"></i><?= __($langGroup, 'Cerrar las demás sesiones'); ?></button>
    <?php endif; ?>
    <?php require __DIR__ . '/extend-modal.php'; ?>
</div>
