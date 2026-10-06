<?php

/**
 * @pcsphp-config framework
 * Qué conviene editar aquí: nada: son las tareas programadas del framework. Las tuyas van en config/extensions/cronjobs.php.
 */

use PiecesPHP\Core\Backups\BackupPolicy;
use PiecesPHP\Core\Backups\BackupRotation;
use PiecesPHP\Terminal\CronJobTask;
use Terminal\Tasks\DbBackupTask;

/**
 * @var array<int, CronJobTask>
 */
$cronjobs = [];

//Respaldar base de datos
$cronjobs[] = CronJobTask::make('Respaldar base de datos', function () {

    \PiecesPHP\Core\BaseModel::destroyDb(
        \PiecesPHP\Core\Config::app_db('default')['db'],
        \PiecesPHP\Core\Config::app_db('default')['host']
    );
    \PiecesPHP\Core\BaseModel::restoreInstancesDb(
        \PiecesPHP\Core\Config::app_db('default')['db'],
        \PiecesPHP\Core\Config::app_db('default')['host']
    );

    $controller = new DbBackupTask();
    $success = false;
    $message = 'Proceso completado correctamente.';
    try {
        $success = $controller->main(null, null, [], true);
    } catch (\Throwable $th) {
        $message = $th->getMessage();
    }

    //La conservación SOLO tras un respaldo correcto, y en la misma pasada. Decide BackupRotation,
    //que es donde la prueba puede comprobar que un fallo no borra nada. ADR 0038 §3.
    $rotation = BackupRotation::afterBackup(
        $success,
        DbBackupTask::lastWrittenFile(),
        BackupPolicy::dumpsDirectory(),
        BackupPolicy::current()
    );

    $response = [
        'success' => $success,
        'message' => $success ? $rotation['message'] : $message,
        'extra_data' => $rotation,
    ];

    return $response;
})->when(fn () => BackupPolicy::isDue());

//Rellenar los slugs que falten tras una importación o un alta directa en base
$cronjobs[] = CronJobTask::make('Rellenar slugs pendientes', function () {

    $summary = \Terminal\Jobs\PreferSlugsFiller::run();

    return [
        'success' => true,
        'message' => "Rellenados {$summary['filled']} slug(s) en {$summary['tables']} tabla(s).",
        'extra_data' => $summary['detail'],
    ];
})->dailyAt("00:10");

//Las carpetas de publicaciones cuya visibilidad cambió con la fecha (startDate o endDate): cada hora, al minuto 5
$cronjobs[] = CronJobTask::make('Sincronizar visibilidad de publicaciones', function () {

    $summary = \Publications\Controllers\PublicationsController::syncAllUploadsVisibility();

    return [
        'success' => $summary['failed'] === 0,
        'message' => "Revisadas {$summary['publications']} publicación(es) activas: {$summary['renamed']} archivo(s) renombrados, "
            . "{$summary['conflicts']} conflicto(s), {$summary['failed']} fallo(s).",
        'extra_data' => $summary,
    ];
})->onMinute(5);

//Drenar la cola en cada pasada del ejecutor, por el mismo camino que la tarea process-queue.
$cronjobs[] = CronJobTask::make('Procesar la cola', function () {

    $result = \Terminal\Tasks\ProcessQueueTask::processPending();

    return [
        'success' => !$result['aborted'],
        'message' => $result['aborted'] ? 'Otra ejecución tenía la cola: esta pasada no procesó nada.' : "Procesadas {$result['processed']} tarea(s) de la cola.",
        'extra_data' => $result['messages'],
    ];
})->when(fn () => true);

//Asignación global
CronJobTask::addCronJobs($cronjobs);
