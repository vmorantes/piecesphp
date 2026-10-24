<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * La pestaña «Tareas programadas» de «Registros»: cada tarea con su estado y, debajo, el libro de ejecuciones (G10).
 *
 * @var string $langGroup
 * @var string $processTableLink
 * @var bool $tableExists Sin la tabla, el libro no anota nada y hay que decirlo
 * @var string $updateFile
 * @var array<int,array{name:string,schedule:string,lastResult:string|null,lastAt:string,lastError:string,next:string}> $tasks
 * @var array{line:string,url:string,keyConfigured:bool,lastRunAgo:string|null,open:bool} $cron La chuleta: nunca la llave
 */
$cronActive = $cron['open'] ? ' active' : '';
?>
<p><?= __($langGroup, 'Las tareas que el sistema ejecuta solo, y cada vez que de verdad se ejecutaron.'); ?></p>

<div class="ui styled fluid accordion" data-task-runs-cron>
    <div class="title<?= $cronActive; ?>"><i class="dropdown icon"></i><?= __($langGroup, 'Cómo configurar el cron de esta instalación'); ?></div>
    <div class="content<?= $cronActive; ?>">
        <p><?= __($langGroup, 'La línea recomendada, en el crontab del servidor:'); ?></p>
        <div class="ui secondary segment"><code data-task-runs-cron-line><?= escape_html($cron['line']); ?></code></div>
        <p><?= __($langGroup, 'Si en el servidor PHP 8.5 se llama de otra forma, pon la ruta de su binario en lugar de php8.5. Nunca con --local en un servidor: apuntaría las tareas a la base de desarrollo.'); ?></p>
        <p><?= __($langGroup, 'Sin acceso al crontab, por HTTP, con la llave solo en la cabecera:'); ?></p>
        <div class="ui secondary segment"><code data-task-runs-cron-url>curl -X GET -H "Cron-Job-Key: &lt;LLAVE&gt;" <?= escape_html($cron['url']); ?></code></div>
        <p>
            <?= $cron['keyConfigured'] ? __($langGroup, 'Llave configurada: sí.') : __($langGroup, 'Llave configurada: no. Sin llave, la ruta responde 403.'); ?>
            <?= __($langGroup, 'Se configura en secure-keys/cronjob: el CronJobKey de config.php no cuenta, porque api-keys.php lo sustituye siempre por esa llave.'); ?>
        </p>
        <p><?= $cron['lastRunAgo'] !== null ? sprintf(__($langGroup, 'Última ejecución anotada: %s.'), escape_html($cron['lastRunAgo'])) : __($langGroup, 'Última ejecución anotada: ninguna.'); ?></p>
    </div>
</div>

<h3 class="ui header"><?= __($langGroup, 'Estado actual'); ?></h3>
<div class="container-standard-table">
    <table class="ui basic table" data-task-runs-state>
        <thead>
            <tr>
                <th><?= __($langGroup, 'Tarea'); ?></th>
                <th><?= __($langGroup, 'Programación'); ?></th>
                <th><?= __($langGroup, 'Último resultado'); ?></th>
                <th><?= __($langGroup, 'Próxima franja'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tasks as $task) : ?>
            <tr>
                <td><?= escape_html($task['name']); ?></td>
                <td><?= escape_html($task['schedule']); ?></td>
                <td>
                    <?php if ($task['lastResult'] === null) : ?>
                    <?= __($langGroup, 'Sin ejecuciones anotadas'); ?>
                    <?php else : ?>
                    <span class="ui <?= $task['lastResult'] === 'success' ? 'green' : 'red'; ?> label"><?= $task['lastResult'] === 'success' ? __($langGroup, 'Ejecutada') : __($langGroup, 'Fallida'); ?></span>
                    <?= escape_html($task['lastAt']); ?>
                    <?php if ($task['lastError'] !== '') : ?>
                    <small class="task-runs-message"><?= escape_html($task['lastError']); ?></small>
                    <?php endif; ?>
                    <?php endif; ?>
                </td>
                <td><?= escape_html($task['next'] !== '' ? $task['next'] : '—'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h3 class="ui header"><?= __($langGroup, 'Ejecuciones'); ?></h3>
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
                <option value="<?= escape_html($task['name']); ?>"><?= escape_html($task['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="four wide field">
            <label><?= __($langGroup, 'Resultado'); ?></label>
            <select name="status" class="ui dropdown">
                <option value=""><?= __($langGroup, 'Todos'); ?></option>
                <option value="executed"><?= __($langGroup, 'Ejecutada'); ?></option>
                <option value="failed"><?= __($langGroup, 'Fallida'); ?></option>
            </select>
        </div>
    </div>
    <button type="submit" class="ui button brand-color"><i class="filter icon"></i><?= __($langGroup, 'Filtrar'); ?></button>
    <button type="reset" class="ui basic button"><?= __($langGroup, 'Limpiar'); ?></button>
</form>
<div class="container-standard-table">
    <table url="<?= escape_html($processTableLink); ?>" class="ui basic table content-table" data-task-runs-table>
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
