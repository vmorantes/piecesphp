<?php

/**
 * SessionlessDatabaseTasks.php
 */

namespace Terminal;

use Terminal\Tasks\DbBackupTask;
use Terminal\Tasks\DbRestoreTask;
use Terminal\Tasks\DbUpdateTask;

/**
 * Las tres órdenes de la base que la terminal despacha antes de leer al usuario (ADR 0079): contra la base de una versión
 * vieja, la lectura del root cae, y estas son justo las que hacen falta para ponerla al día.
 *
 * @package     Terminal
 */
class SessionlessDatabaseTasks
{
    /**
     * Lista cerrada: añadir una acción aquí la deja correr sin usuario ni middleware.
     */
    const TASKS = [
        'db-update' => DbUpdateTask::class,
        'db-backup' => DbBackupTask::class,
        'db-restore' => DbRestoreTask::class,
    ];

    /**
     * La clase de la tarea que se despacha sin usuario, o null. Pura: no lee nada fuera de sus argumentos.
     *
     * @param string $sapi PHP_SAPI
     * @param bool $isTerminal
     * @param string $action El nombre de la acción, sin el prefijo de las rutas de la terminal
     * @param array<string,mixed>|null $routeInfo Lo que devuelve get_route_info() para esa acción
     * @return class-string|null
     */
    public static function taskFor(string $sapi, bool $isTerminal, string $action, ?array $routeInfo): ?string
    {
        if ($sapi !== 'cli' || !$isTerminal || !array_key_exists($action, self::TASKS) || $routeInfo === null) {
            return null;
        }
        $class = self::TASKS[$action];
        //Un clon que reutilice el nombre para otra cosa no entra: la ruta tiene que apuntar a esta misma clase.
        if (($routeInfo['controller'] ?? null) !== $class . '::main') {
            return null;
        }
        //Despachar sin usuario se salta los middlewares de la ruta: si el clon le puso uno, la tarea arranca por el camino normal.
        if (($routeInfo['middlewares'] ?? []) !== []) {
            return null;
        }
        return $class;
    }
}
