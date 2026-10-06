<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $actionURL
 * @var array{enabled:bool,interval_minutes:int,rotate:bool,keep_recent:int,keep_daily:int,keep_weekly:int,keep_monthly:int,data_excluded_tables:string[]} $policy
 * @var array<string,array{0:int,1:int}> $limits
 * @var array{total:int,bytes:int,latest:?DateTimeImmutable,next:?DateTimeImmutable,due:bool} $status
 * @var array{keep:int,delete:int,ignored:int} $plan
 * @var string[] $tables
 * @var array<string,string> $codeExcluded
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
$readableSize = function (int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unit = 0;
    $size = (float) $bytes;
    while ($size >= 1024 && $unit < count($units) - 1) {
        $size /= 1024;
        $unit++;
    }
    return number_format($size, $unit === 0 ? 0 : 1) . ' ' . $units[$unit];
};
$numberField = function (string $name, string $label, string $help) use ($escape, $policy, $limits): string {
    [$min, $max] = $limits[$name];
    ob_start();
    ?>
    <div class="field">
        <label for="backup-<?= $escape($name); ?>"><?= $escape($label); ?></label>
        <input type="number" id="backup-<?= $escape($name); ?>" data-backup-field="<?= $escape($name); ?>"
            value="<?= $escape($policy[$name]); ?>" min="<?= $escape($min); ?>" max="<?= $escape($max); ?>" step="1" required>
        <small class="backup-help"><?= $escape($help); ?> <em>(<?= $escape($min); ?>–<?= $escape($max); ?>)</em></small>
    </div>
    <?php
    return (string) ob_get_clean();
};
?>
<main class="backups-view" data-backups-view>

    <section class="main-body-header">

        <div class="head">
            <h2 class="tittle"><?= __($langGroup, 'Respaldos'); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Sistema'); ?></span>
        </div>

        <div class="body-card">

            <div class="ui info message">
                <?= __($langGroup, 'Un respaldo contiene toda la base de datos. Esta pantalla decide cada cuánto se hace, cuántos se guardan y qué tablas salen sin sus filas.'); ?>
            </div>

            <h3 class="ui dividing header"><?= __($langGroup, 'Cómo está ahora'); ?></h3>

            <div class="ui four statistics backup-status">
                <div class="statistic">
                    <div class="value"><?= $escape($status['total']); ?></div>
                    <div class="label"><?= __($langGroup, 'Respaldos guardados'); ?></div>
                </div>
                <div class="statistic">
                    <div class="value"><?= $escape($readableSize($status['bytes'])); ?></div>
                    <div class="label"><?= __($langGroup, 'Ocupan'); ?></div>
                </div>
                <div class="statistic">
                    <div class="value"><?= $status['latest'] !== null ? $escape($status['latest']->format('d/m/Y H:i')) : '—'; ?></div>
                    <div class="label"><?= __($langGroup, 'Último respaldo'); ?></div>
                </div>
                <div class="statistic">
                    <div class="value">
                        <?php if (!$policy['enabled']): ?>
                            <?= __($langGroup, 'Apagado'); ?>
                        <?php elseif ($status['due']): ?>
                            <?= __($langGroup, 'Toca ya'); ?>
                        <?php else: ?>
                            <?= $status['next'] !== null ? $escape($status['next']->format('d/m/Y H:i')) : '—'; ?>
                        <?php endif; ?>
                    </div>
                    <div class="label"><?= __($langGroup, 'Siguiente respaldo'); ?></div>
                </div>
            </div>

            <?php if ($status['total'] === 0): ?>
            <div class="ui warning message"><?= __($langGroup, 'Todavía no hay ningún respaldo hecho por el sistema.'); ?></div>
            <?php endif; ?>

            <h3 class="ui dividing header"><?= __($langGroup, 'Qué haría la conservación ahora mismo'); ?></h3>

            <div class="ui <?= $plan['delete'] > 0 ? 'warning' : 'success'; ?> message" data-backup-plan>
                <p>
                    <?= sprintf(
                        __($langGroup, 'Con la política guardada se conservarían %d respaldo(s) y se borrarían %d. Otros %d no se tocan nunca, porque no los escribió el sistema.'),
                        $plan['keep'],
                        $plan['delete'],
                        $plan['ignored']
                    ); ?>
                </p>
                <?php if ($plan['delete'] > 0 && $policy['rotate']): ?>
                <p><strong><?= sprintf(__($langGroup, 'El próximo respaldo correcto borrará esos %d archivos.'), $plan['delete']); ?></strong></p>
                <?php elseif ($plan['delete'] > 0): ?>
                <p><strong><?= __($langGroup, 'No se borrarán mientras «Borrar los respaldos que sobran» esté apagado.'); ?></strong></p>
                <?php endif; ?>
            </div>

            <form class="ui form" action="<?= $escape($actionURL); ?>" method="POST" data-backups-form>

                <h3 class="ui dividing header"><?= __($langGroup, 'La política'); ?></h3>

                <div class="field">
                    <div class="ui toggle checkbox">
                        <input type="checkbox" data-backup-field="enabled"<?= $policy['enabled'] ? ' checked' : ''; ?>>
                        <label><?= __($langGroup, 'Respaldar la base de datos automáticamente'); ?></label>
                    </div>
                    <small class="backup-help"><?= __($langGroup, 'Si se apaga, no se hace ningún respaldo nuevo y tampoco se borra ninguno.'); ?></small>
                </div>

                <div class="two fields">
                    <?= $numberField('interval_minutes', __($langGroup, 'Cada cuántos minutos'), __($langGroup, 'El respaldo sale cuando el último ya tiene esta edad; 1440 es un día.')); ?>
                    <?= $numberField('keep_recent', __($langGroup, 'Guardar los últimos'), __($langGroup, 'Los más recientes, siempre, pase lo que pase con los demás niveles.')); ?>
                </div>

                <div class="field">
                    <div class="ui toggle checkbox">
                        <input type="checkbox" data-backup-field="rotate"<?= $policy['rotate'] ? ' checked' : ''; ?>>
                        <label><?= __($langGroup, 'Borrar los respaldos que sobran'); ?></label>
                    </div>
                    <small class="backup-help"><?= __($langGroup, 'Solo se borra tras un respaldo correcto, y solo los que escribió el sistema. Si se apaga, se guardan todos para siempre.'); ?></small>
                </div>

                <div class="three fields">
                    <?= $numberField('keep_daily', __($langGroup, 'Un respaldo al día, durante'), __($langGroup, 'Días con respaldo que se conservan; 0 apaga este nivel.')); ?>
                    <?= $numberField('keep_weekly', __($langGroup, 'Uno a la semana, durante'), __($langGroup, 'Semanas con respaldo que se conservan; 0 apaga este nivel.')); ?>
                    <?= $numberField('keep_monthly', __($langGroup, 'Uno al mes, durante'), __($langGroup, 'Meses con respaldo que se conservan; 0 apaga este nivel.')); ?>
                </div>

                <h3 class="ui dividing header"><?= __($langGroup, 'Tablas que salen sin sus filas'); ?></h3>

                <div class="ui warning message">
                    <?= __($langGroup, 'La tabla se guarda con su estructura, pero vacía: al restaurar, sus datos NO vuelven. Sirve para no sacar datos sensibles del servidor.'); ?>
                </div>

                <?php if (count($codeExcluded) > 0): ?>
                <div class="backup-code-excluded">
                    <p><strong><?= __($langGroup, 'Fijadas desde el código, no se pueden cambiar aquí:'); ?></strong></p>
                    <ul>
                        <?php foreach ($codeExcluded as $table => $reason): ?>
                        <li><code><?= $escape($table); ?></code> — <?= $escape($reason); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (count($tables) === 0): ?>
                <div class="ui message"><?= __($langGroup, 'No se pudieron leer las tablas de la base de datos.'); ?></div>
                <?php else: ?>
                <div class="backup-tables" data-backup-tables>
                    <?php foreach ($tables as $table): ?>
                    <?php if (array_key_exists($table, $codeExcluded)) {
                        continue;
                    } ?>
                    <div class="field">
                        <div class="ui checkbox">
                            <input type="checkbox" data-backup-table value="<?= $escape($table); ?>"<?= in_array($table, $policy['data_excluded_tables'], true) ? ' checked' : ''; ?>>
                            <label><code><?= $escape($table); ?></code></label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="main-buttons">
                    <button type="submit" class="ui button brand-color" data-backups-save><?= __($langGroup, 'Guardar'); ?></button>
                </div>

            </form>

        </div>

    </section>

</main>
