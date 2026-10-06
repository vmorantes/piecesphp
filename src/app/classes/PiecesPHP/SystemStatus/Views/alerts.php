<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $title
 * @var array<int,array{key:string,severity:string,message:string,hidden:bool,dismissible:bool,fixURL:string,fixLabel:string}> $rows
 * @var bool $isRoot Ve el interruptor Ocultar/Mostrar en los ocultables
 * @var string $toggleURL
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
$severities = [
    'danger' => ['red', __($langGroup, 'Grave')],
    'warning' => ['orange', __($langGroup, 'Atención')],
    'info' => ['blue', __($langGroup, 'Información')],
];
?>
<main class="system-alerts-view" data-system-alerts data-toggle-url="<?= $escape($toggleURL); ?>">

    <section class="main-body-header">

        <div class="head">
            <h2 class="tittle"><?= $escape($title); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Sistema'); ?></span>
        </div>

        <div class="body-card max">

        <div>

        <p><?= __($langGroup, 'Lo que el sistema necesita que alguien revise.'); ?></p>

        <?php if (count($rows) === 0): ?>
        <p><?= __($langGroup, 'No hay avisos activos.'); ?></p>
        <?php else: ?>
        <table class="ui basic table" data-system-alerts-table>
            <thead>
                <tr>
                    <th><?= __($langGroup, 'Gravedad'); ?></th>
                    <th><?= __($langGroup, 'Mensaje'); ?></th>
                    <th><?= __($langGroup, 'Estado'); ?></th>
                    <th><?= __($langGroup, 'Cómo arreglarlo'); ?></th>
                    <?php if ($isRoot): ?>
                    <th><?= __($langGroup, 'Ocultar'); ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <?php [$color, $severityLabel] = $severities[$row['severity']] ?? ['grey', $row['severity']]; ?>
                <tr data-system-alert-row="<?= $escape($row['key']); ?>">
                    <td><span class="ui <?= $escape($color); ?> label"><?= $escape($severityLabel); ?></span></td>
                    <td><?= $escape($row['message']); ?></td>
                    <td data-system-alert-state><?= $row['hidden'] ? __($langGroup, 'Oculto') : __($langGroup, 'Activo'); ?></td>
                    <td>
                        <?php if ($row['fixURL'] !== ''): ?>
                        <a class="ui button brand-color alt" href="<?= $escape($row['fixURL']); ?>"><?= $escape($row['fixLabel']); ?></a>
                        <?php endif; ?>
                    </td>
                    <?php if ($isRoot): ?>
                    <td>
                        <?php if ($row['dismissible']): ?>
                        <div class="ui toggle checkbox">
                            <input type="checkbox" id="system-alert-<?= $escape($row['key']); ?>" aria-label="<?= $escape(__($langGroup, 'Ocultar') . ': ' . $row['key']); ?>" data-system-alert-toggle data-key="<?= $escape($row['key']); ?>"<?= $row['hidden'] ? ' checked' : ''; ?>>
                            <label for="system-alert-<?= $escape($row['key']); ?>"></label>
                        </div>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        </div>

        </div>

    </section>

</main>
