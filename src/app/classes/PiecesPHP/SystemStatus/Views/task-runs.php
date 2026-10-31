<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * La pestaña «Tareas programadas» de «Registros»: cada tarea con su estado y, debajo, el libro de ejecuciones (G10).
 *
 * @var string $langGroup
 * @var string $processTableLink
 * @var bool $tableExists Sin la tabla, el libro no anota nada y hay que decirlo
 * @var string $updateFile
 * @var array<int,array{name:string,label:string,schedule:string,lastResult:string|null,lastAt:string,lastError:string,next:string}> $tasks
 * @var array{line:string,url:string,keyConfigured:bool,lastRunAgo:string|null,open:bool} $cron La chuleta: nunca la llave
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $pieces Pinta las piezas del panel
 */
use PiecesPHP\Core\Utilities\Helpers\StatusLabel;

$tabClass = fn(string $tab): string => $tab === 'state' ? ' active' : '';
?>
<p><?= __($langGroup, 'Las tareas que el sistema ejecuta solo, y cada vez que de verdad se ejecutaron.'); ?></p>

<?php if ($cron['open']) : ?>
<?php //Sin el cron en marcha hay que decirlo: las instrucciones van en su pestaña, no a la vista. ?>
<div class="ui warning message" data-task-runs-cron-pending><?= __($langGroup, 'Aún no hay ejecuciones anotadas: mire la pestaña Instrucciones para poner el cron.'); ?></div>
<?php endif; ?>

<div class="ui secondary pointing menu" data-task-runs-tabs>
    <a class="item<?= $tabClass('state'); ?>" data-tab="state"><i class="clock icon" aria-hidden="true"></i><?= __($langGroup, 'Estado actual'); ?></a>
    <a class="item<?= $tabClass('runs'); ?>" data-tab="runs"><i class="clipboard list icon" aria-hidden="true"></i><?= __($langGroup, 'Ejecuciones'); ?></a>
    <a class="item<?= $tabClass('instructions'); ?>" data-tab="instructions"><i class="book icon" aria-hidden="true"></i><?= __($langGroup, 'Instrucciones'); ?></a>
</div>

<div class="ui<?= $tabClass('state'); ?> tab" data-tab="state">
    <section class="ui three doubling stackable cards" aria-label="<?= escape_html(__($langGroup, 'Estado de las tareas')); ?>" data-task-runs-state>
        <?php foreach ($tasks as $task) : ?>
        <?php
            ob_start();
        ?>
        <div class="ui flex">
            <?php if ($task['lastResult'] === null) : ?>
            <?= StatusLabel::neutral(__($langGroup, 'Sin ejecuciones anotadas')); ?>
            <?php else : ?>
            <?= $task['lastResult'] === 'success' ? StatusLabel::html(__($langGroup, 'Ejecutada'), StatusLabel::POSITIVE) : StatusLabel::html(__($langGroup, 'Fallida'), StatusLabel::NEGATIVE); ?>
            <small class="ui muted text"><?= escape_html($task['lastAt']); ?></small>
            <?php endif; ?>
        </div>
        <?php if ($task['lastError'] !== '') : ?>
        <small class="task-runs-message ui muted text"><?= escape_html($task['lastError']); ?></small>
        <?php endif; ?>
        <?php
            $taskResult = (string) ob_get_clean();
            //`d-m-Y H:i`: la hora, grande; la fecha, debajo.
            $nextParts = $task['next'] !== '' ? explode(' ', $task['next'], 2) : [];
            $pieces->_render('panel/built-in/statistics/statistic-card.php', [
                'title' => $task['label'],
                'figure' => [
                    'variant' => 'tall',
                    'size' => 'huge marked',
                    'meta' => $task['schedule'],
                    'value' => $nextParts[1] ?? '—',
                    'label' => count($nextParts) === 2 ? sprintf(__($langGroup, 'Próxima franja · %s'), $nextParts[0]) : __($langGroup, 'Sin franja programada'),
                    'content' => $taskResult,
                ],
            ]);
        ?>
        <?php endforeach; ?>
    </section>
</div>

<div class="ui<?= $tabClass('runs'); ?> tab" data-tab="runs">
<?php if (!$tableExists) : ?>
<div class="ui red message">
    <p><?= sprintf(__($langGroup, 'Falta la tabla del libro de tareas, así que no se anota ninguna ejecución. Aplique «%s» en la base de datos de esta instalación.'), escape_html($updateFile)); ?></p>
</div>
<?php else : ?>
<form class="ui form segment" data-task-runs-filters>
    <div class="fields">
        <div class="eight wide field">
            <label><?= __($langGroup, 'Tarea'); ?></label>
            <select name="task" class="ui dropdown">
                <option value=""><?= __($langGroup, 'Todas'); ?></option>
                <?php foreach ($tasks as $task) : ?>
                <option value="<?= escape_html($task['name']); ?>"><?= escape_html($task['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="four wide field">
            <label><?= __($langGroup, 'Resultado'); ?></label>
            <select name="status" class="ui dropdown">
                <option value=""><?= __($langGroup, 'Todos'); ?></option>
                <?php foreach (\PiecesPHP\SystemStatus\Controllers\SystemStatusController::taskRunStatusLabels() as $statusValue => [, $statusLabel]) : ?>
                <option value="<?= escape_html($statusValue); ?>"><?= escape_html($statusLabel); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php $pieces->_render('panel/built-in/content/filter-actions.php'); ?>
</form>
<div class="ui fluid card container-standard-table">
    <table url="<?= escape_html($processTableLink); ?>" class="ui selectable very basic table content-table" data-task-runs-table>
        <thead>
            <tr>
                <th><?= __($langGroup, 'Inicio'); ?></th>
                <th><?= __($langGroup, 'Tarea'); ?></th>
                <th><?= __($langGroup, 'Origen'); ?></th>
                <th><?= __($langGroup, 'Resultado'); ?></th>
                <th><?= __($langGroup, 'Intento'); ?></th>
                <th><?= __($langGroup, 'Duración'); ?></th>
            </tr>
        </thead>
    </table>
</div>
<?php endif; ?>
</div>

<div class="ui<?= $tabClass('instructions'); ?> tab" data-tab="instructions">
    <section class="ui segment" aria-label="<?= escape_html(__($langGroup, 'Cómo configurar el cron de esta instalación')); ?>" data-task-runs-cron>
        <p><?= __($langGroup, 'La línea recomendada, en el crontab del servidor:'); ?></p>
        <pre class="ui code segment"><code data-task-runs-cron-line><?= escape_html($cron['line']); ?></code></pre>
        <p><?= sprintf(__($langGroup, 'Si en el servidor PHP 8.5 se llama de otra forma, pon la ruta de su binario en lugar de php8.5. Nunca con %s en un servidor: apuntaría las tareas a la base de desarrollo.'), '<code style="white-space: nowrap;">--local</code>'); ?></p>
        <p><?= __($langGroup, 'Sin acceso al crontab, por HTTP, con la llave solo en la cabecera:'); ?></p>
        <pre class="ui code segment"><code data-task-runs-cron-url>curl -X GET -H "Cron-Job-Key: <?= escape_html(__($langGroup, '<LLAVE>')); ?>" <?= escape_html($cron['url']); ?></code></pre>
        <p>
            <?= $cron['keyConfigured'] ? __($langGroup, 'Llave configurada: sí.') : __($langGroup, 'Llave configurada: no. Sin llave, la ruta responde 403.'); ?>
            <?= __($langGroup, 'Se configura en secure-keys/cronjob: el CronJobKey de config.php no cuenta, porque api-keys.php lo sustituye siempre por esa llave.'); ?>
        </p>
        <p><?= $cron['lastRunAgo'] !== null ? sprintf(__($langGroup, 'Última ejecución anotada: %s.'), escape_html($cron['lastRunAgo'])) : __($langGroup, 'Última ejecución anotada: ninguna.'); ?></p>
    </section>
</div>
