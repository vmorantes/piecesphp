<?php

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

    $response = [
        'success' => $success,
        'message' => $message,
        'extra_data' => [],
    ];

    return $response;
})->dailyAt("00:00");

//Ejemplo de tarea larga que cuida la conexión: destroyDb() antes del trabajo y restoreInstancesDb() después, para
//que la base no corte por timeout. Para usarla, descomenta el bloque y cambia su nombre, su trabajo y su horario.
//@codigo-comentado · Ejemplo documentado de tarea larga; registrada, corría a diario sin hacer nada.
//$cronjobs[] = CronJobTask::make('Ejemplo', function () {
//
//    //NOTE: Antes de operaciones largas: se destruye la conexión BD para evitar por timeout
//    \PiecesPHP\Core\BaseModel::destroyDb(
//        \PiecesPHP\Core\Config::app_db('default')['db'],
//        \PiecesPHP\Core\Config::app_db('default')['host']
//    );
//
//    $response = [
//        'success' => true,
//        'message' => 'Proceso completado correctamente.',
//        'extra_data' => [],
//    ];
//
//    //NOTE: Después de operaciones largas (o cuando se requiera): se restaura la conexión BD
//    \PiecesPHP\Core\BaseModel::restoreInstancesDb(
//        \PiecesPHP\Core\Config::app_db('default')['db'],
//        \PiecesPHP\Core\Config::app_db('default')['host']
//    );
//
//    return $response;
//})->dailyAt("00:00");

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

//Asignación global
CronJobTask::addCronJobs($cronjobs);
