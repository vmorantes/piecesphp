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
 * @var string $title
 * @var string $description
 * @var string $indexLink
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
//Un nombre de tabla solo se parte tras un guion bajo, nunca a mitad de palabra.
$tableName = fn(string $table): string => str_replace('_', '_<wbr>', $escape($table));
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
        <div class="description backup-help"><?= $escape($help); ?> <span class="ui muted text">(<?= $escape($min); ?>&#8288;–&#8288;<?= $escape($max); ?>)</span></div>
    </div>
    <?php
        return (string) ob_get_clean();
        };
    ?>
<section class="module-view-container backups-view" data-backups-view>

    <?php
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);
    ?>

    <?php
        $nextDetail = '';
        if (!$policy['enabled']) {
            $nextValue = __($langGroup, 'Apagado');
        } elseif ($status['due']) {
            $nextValue = __($langGroup, 'Toca ya');
        } else {
            $nextValue = $status['next'] !== null ? $status['next']->format('d/m/Y') : '—';
            $nextDetail = $status['next'] !== null ? $status['next']->format('H:i') : '';
        }
        $figures = [
            [__($langGroup, 'Respaldos guardados'), (string) $status['total'], ''],
            [__($langGroup, 'Ocupan'), $readableSize($status['bytes']), ''],
            [__($langGroup, 'Último respaldo'), $status['latest'] !== null ? $status['latest']->format('d/m/Y') : '—', $status['latest'] !== null ? $status['latest']->format('H:i') : ''],
            [__($langGroup, 'Siguiente respaldo'), $nextValue, $nextDetail],
        ];
    ?>
    <section aria-label="<?= $escape(__($langGroup, 'Cómo está ahora')); ?>">

        <div class="ui four doubling stackable cards">
            <?php foreach ($figures as [$label, $value, $detail]): ?>
            <?php
                $helpController->_render('panel/built-in/statistics/statistic-card.php', [
                    'title' => $label,
                    'figure' => ['value' => $value, 'description' => $detail, 'variant' => 'compact'],
                ]);
            ?>
            <?php endforeach; ?>
        </div>

        <?php if ($status['total'] === 0): ?>
        <div class="ui warning message"><?= __($langGroup, 'Todavía no hay ningún respaldo hecho por el sistema.'); ?></div>
        <?php endif; ?>

        <div class="ui <?= $plan['delete'] > 0 ? 'warning' : 'success'; ?> message" role="status" data-backup-plan>
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

    </section>

    <div class="ui hidden divider"></div>

    <form class="ui form segment" action="<?= $escape($actionURL); ?>" method="POST" data-backups-form>

        <h2 class="ui header"><?= __($langGroup, 'La política'); ?></h2>

        <div class="field">
            <div class="ui toggle checkbox">
                <input type="checkbox" data-backup-field="enabled"<?= $policy['enabled'] ? ' checked' : ''; ?>>
                <label><?= __($langGroup, 'Respaldar la base de datos automáticamente'); ?></label>
            </div>
            <div class="description backup-help"><?= __($langGroup, 'Si se apaga, no se hace ningún respaldo nuevo y tampoco se borra ninguno.'); ?></div>
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
            <div class="description backup-help"><?= __($langGroup, 'Solo se borra tras un respaldo correcto, y solo los que escribió el sistema. Si se apaga, se guardan todos para siempre.'); ?></div>
        </div>

        <div class="three fields">
            <?= $numberField('keep_daily', __($langGroup, 'Un respaldo al día, durante'), __($langGroup, 'Días con respaldo que se conservan; 0 apaga este nivel.')); ?>
            <?= $numberField('keep_weekly', __($langGroup, 'Uno a la semana, durante'), __($langGroup, 'Semanas con respaldo que se conservan; 0 apaga este nivel.')); ?>
            <?= $numberField('keep_monthly', __($langGroup, 'Uno al mes, durante'), __($langGroup, 'Meses con respaldo que se conservan; 0 apaga este nivel.')); ?>
        </div>

        <div class="ui section divider"></div>

        <h2 class="ui header"><?= __($langGroup, 'Tablas que salen sin sus filas'); ?></h2>

        <?php //Dentro de `ui form`, Fomantic oculta los mensajes que no llevan `visible`. ?>
        <div class="ui warning visible message">
            <?= __($langGroup, 'La tabla se guarda con su estructura, pero vacía: al restaurar, sus datos NO vuelven. Sirve para no sacar datos sensibles del servidor.'); ?>
        </div>

        <?php if (count($codeExcluded) > 0): ?>
        <div class="backup-code-excluded">
        <p><strong><?= __($langGroup, 'Fijadas desde el código, no se pueden cambiar aquí:'); ?></strong></p>
        <ul class="ui list">
            <?php foreach ($codeExcluded as $table => $reason): ?>
            <li><code><?= $tableName($table); ?></code> — <?= $escape($reason); ?></li>
            <?php endforeach; ?>
        </ul>
        </div>
        <?php endif; ?>

        <?php if (count($tables) === 0): ?>
        <div class="ui visible message"><?= __($langGroup, 'No se pudieron leer las tablas de la base de datos.'); ?></div>
        <?php else: ?>
        <div class="ui segment backup-tables" data-backup-tables>
            <?php foreach ($tables as $table): ?>
            <?php if (array_key_exists($table, $codeExcluded)) {
                    continue;
            } ?>
            <div class="field">
                <div class="ui checkbox">
                    <input type="checkbox" data-backup-table value="<?= $escape($table); ?>"<?= in_array($table, $policy['data_excluded_tables'], true) ? ' checked' : ''; ?>>
                    <label><?= $tableName($table); ?></label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php
            $helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
                'saveAttributes' => ['data-backups-save' => ''],
                'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
            ]);
        ?>

    </form>

</section>
