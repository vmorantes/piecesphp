<?php

/**
 * TaskRunsSeeder.php
 */

namespace Terminal\Seeds;

use PiecesPHP\Terminal\CronJobTask;
use PiecesPHP\Terminal\TaskRunMapper;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * TaskRunsSeeder.
 *
 * Ejecuciones para Registros › Tareas programadas: hechas, fallidas, con el cerrojo tomado y ya hechas, por terminal y
 * por HTTP, en los últimos 30 días.
 *
 * El nombre de la tarea lleva el prefijo y no es el de ninguna tarea registrada, y por eso no cuenta ni en el cerrojo
 * de `CronJobTask` (decide por nombre y franja si una tarea ya corrió: con un nombre de verdad se saltaría su ejecución)
 * ni en la ayuda del cron (`SystemStatusController::registeredRuns()`), que diría que el cron corre.
 *
 * @package     Terminal\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class TaskRunsSeeder extends ModuleSeeder
{

    const TASKS = ['procesar-cola', 'limpiar-cache', 'purgar-registros', 'enviar-resumen'];

    const OUTCOMES = [
        [CronJobTask::STATUS_EXECUTED, 'Hecho.'],
        [CronJobTask::STATUS_EXECUTED, 'Hecho: 12 elementos procesados.'],
        [CronJobTask::STATUS_FAILED, 'La conexión con el servidor de correo se agotó.'],
        [CronJobTask::STATUS_LOCKED, 'Otra ejecución tenía el cerrojo.'],
        [CronJobTask::STATUS_ALREADY_DONE, 'Ya se hizo en esta franja.'],
    ];

    public function mapper(): string
    {
        return TaskRunMapper::class;
    }

    /**
     * El nombre de la tarea, con el prefijo: no coincide con ninguna registrada.
     *
     * @return string
     */
    public function markerColumn(): string
    {
        return 'taskName';
    }

    public function defaultCount(): int
    {
        return 15;
    }

    /**
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed>
     */
    public function row(int $index, SeedContext $context): array
    {
        [$status, $message] = self::OUTCOMES[$index % count(self::OUTCOMES)];
        $startedAt = (new \DateTime('today'))->modify('-' . (1 + ($index * 2) % 29) . ' days')->setTime(2 + $index % 4, 0);
        $durationMs = $status === CronJobTask::STATUS_EXECUTED || $status === CronJobTask::STATUS_FAILED ? 200 + ($index * 337) % 9000 : 3;
        return [
            'taskName' => $this->text(self::TASKS[$index % count(self::TASKS)], intdiv($index, count(self::TASKS))),
            'slot' => clone $startedAt,
            'triggeredBy' => $index % 3 === 0 ? TaskRunMapper::TRIGGER_HTTP : TaskRunMapper::TRIGGER_CLI,
            'status' => $status,
            'attempt' => $status === CronJobTask::STATUS_FAILED ? 2 : 1,
            'startedAt' => $startedAt,
            'finishedAt' => (clone $startedAt)->modify('+' . max(1, intdiv($durationMs, 1000)) . ' seconds'),
            'durationMs' => $durationMs,
            'message' => $message,
            'host' => 'servidor-de-pruebas',
        ];
    }

}
