<?php

use API\Adapters\CronJobTaskAdapter;
use API\Adapters\SpeechToTextGroqAdapter;
use Polls\Mappers\PollsDataMapper;

/**
 * @var array<int, CronJobTaskAdapter>
 */
$cronjobs = [];

//Ejemplo
$cronjobs[] = CronJobTaskAdapter::make('Ejemplo', function () {
    $response = [
        'success' => true,
        'message' => 'Proceso completado correctamente.',
        'extra_data' => [],
    ];
    return $response;
})->dailyAt("00:00");

//Asignación global
set_config('SystemCronjobs', $cronjobs);