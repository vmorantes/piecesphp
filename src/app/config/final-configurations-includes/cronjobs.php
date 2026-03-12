<?php

use PiecesPHP\Terminal\CronJobTask;

/**
 * @var array<int, CronJobTask>
 */
$cronjobs = [];

//Ejemplo
$cronjobs[] = CronJobTask::make('Ejemplo', function () {

    //NOTE: Antes de operaciones largas: se destruye la conexión BD para evitar por timeout
    \PiecesPHP\Core\BaseModel::destroyDb(
        \PiecesPHP\Core\Config::app_db('default')['db'],
        \PiecesPHP\Core\Config::app_db('default')['host']
    );

    $response = [
        'success' => true,
        'message' => 'Proceso completado correctamente.',
        'extra_data' => [],
    ];

    //NOTE: Después de operaciones largas (o cuando se requiera): se restaura la conexión BD
    \PiecesPHP\Core\BaseModel::restoreInstancesDb(
        \PiecesPHP\Core\Config::app_db('default')['db'],
        \PiecesPHP\Core\Config::app_db('default')['host']
    );

    return $response;
})->dailyAt("00:00");

//Asignación global
CronJobTask::addCronJobs($cronjobs);