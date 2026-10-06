<?php

/**
 * ProcessQueueTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\QueueHandlerResponse;
use PiecesPHP\Terminal\QueueTask;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use Terminal\Mappers\QueueJobMapper;

/**
 * ProcessQueueTask
 *
 * Procesa los elementos pendientes en la cola de tareas.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 * @see https://misc.flogisoft.com/bash/tip_colors_and_formatting Colores para texto de terminal
 */
class ProcessQueueTask extends TerminalTaskAbstract
{
    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        //Procesar entrada
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'process-queue';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        //Establecer propiedades
        $this->description = new StringArray([
            "Procesa las tareas pendientes en la cola (pcsphp_jobs_queue).\r\n",
            "\tParámetros:\r\n",
            "\t  limit (opcional): Cantidad máxima de tareas a procesar.\r\n",
        ]);
        $this->route = "{$startRoute}/process-queue[/]";
        $this->controller = self::class . '::main';
        $this->name = $name;
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray($permissions);
        $this->defaultParamsValues = [
            '--limit' => 60,
        ];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $parameters = empty($parameters) ? TerminalData::instance()->arguments() : array_merge($parameters, TerminalData::instance()->arguments());
        $limit = isset($parameters['--limit']) ? (int) $parameters['--limit'] : 60;

        $result = self::processPending($limit, function (string $line): void {
            echoTerminal($line);
        });
        if ($result['aborted']) {
            return;
        }
        if (count($result['messages']) > 1) {
            echoTerminal(implode("\r\n", $result['messages']));
        }

        exit(0);
    }

    /**
     * Procesa lo pendiente de la cola y devuelve lo que pasó. Lo llaman la tarea process-queue y el cronjob del sistema.
     *
     * Las ejecuciones van de una en una y por orden de llegada (1 activa y hasta 5 esperando). Cada una mantiene su turno
     * con flock mientras vive: el turno de un proceso muerto se suelta solo y quien espera lo borra.
     *
     * @param int $limit Cuántas tareas como mucho.
     * @param (callable(string):void)|null $echo Lo que se dice mientras espera turno o al abortar; null, nada.
     * @return array{aborted: bool, processed: int, messages: string[]}
     */
    //—— La constante y el método de recuperación van antes, para leerse en orden ——

    /**
     * Minutos tras los que un trabajo «en curso» se da por abandonado.
     *
     * Generoso a propósito: un trabajo vivo y lento NO se puede tocar.
     */
    const ABANDONED_AFTER_MINUTES = 30;

    /**
     * Devuelve a la cola los trabajos cuyo proceso murió a mitad.
     *
     * La edad sale de `startedAt`, que es lo que escribe el procesador al marcar el trabajo en
     * curso. Con intentos restantes vuelven a `pending`; sin ellos, a `failed`.
     *
     * @return string[] Una línea por trabajo recuperado, para el mensaje de la pasada.
     */
    public static function reclaimAbandoned(): array
    {
        $messages = [];

        try {
            $model = QueueJobMapper::model();
            $database = $model->getDatabase();
            if ($database === null) {
                return $messages;
            }

            $sql = "SELECT id FROM " . QueueJobMapper::TABLE . "
                    WHERE status = ?
                    AND startedAt IS NOT NULL
                    AND startedAt < ?
                    ORDER BY startedAt ASC";
            $limit = date('Y-m-d H:i:s', time() - (self::ABANDONED_AFTER_MINUTES * 60));
            $statement = $database->prepare($sql);
            $statement->execute([QueueJobMapper::STATUS_RUNNING, $limit]);

            foreach ($statement->fetchAll(\PDO::FETCH_OBJ) as $row) {
                $task = new QueueJobMapper($row->id);
                $attempts = (int) $task->attempts;
                $maxAttempts = (int) $task->maxAttempts;

                if ($attempts < $maxAttempts) {
                    $task->status = QueueJobMapper::STATUS_PENDING;
                    $task->scheduledAt = null;
                    $messages[] = "\e[33m   [RECUPERADA] Tarea ID {$task->id} [{$task->name}] llevaba más de " . self::ABANDONED_AFTER_MINUTES . " min en curso: su proceso murió. Vuelve a la cola (intento {$attempts}/{$maxAttempts}).\e[39m";
                } else {
                    $task->status = QueueJobMapper::STATUS_FAILED;
                    $task->errorMessage = 'El proceso que la ejecutaba murió y se agotaron los intentos (' . $maxAttempts . ').';
                    $task->finishedAt = date('Y-m-d H:i:s');
                    $messages[] = "\e[31m   [ABANDONADA] Tarea ID {$task->id} [{$task->name}]: su proceso murió y no quedan intentos ({$attempts}/{$maxAttempts}). Marcada como fallida.\e[39m";
                }

                //RETORNO-IGNORADO: la conexión va en ERRMODE_EXCEPTION, así que un fallo LANZA.
                $task->update();
            }
        } catch (\Throwable $e) {
            //Que no se pueda recuperar una fila NO puede impedir procesar la cola.
            $messages[] = "\e[31m   [AVISO] No se pudieron recuperar los trabajos abandonados: {$e->getMessage()}\e[39m";
            log_exception($e);
        }

        return $messages;
    }

    public static function processPending(int $limit = 60, ?callable $echo = null): array
    {
        $echo ??= function (string $line): void {
        };
        $processed = 0;
        $titleTask = "Procesando Cola de Tareas";
        //Gestión de cola de ejecución (Máximo 1 activa + 5 en espera, orden FIFO)
        $lockDir = basepath('tmp/process_queue_locks');
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
            chmod($lockDir, 0755);
        }

        //Registrar mi intento de ejecución: el turno se mantiene con flock mientras este proceso vive.
        $myLockFile = "{$lockDir}/" . microtime(true) . "_" . getmypid() . ".lock";
        $myLock = fopen($myLockFile, 'c');
        if ($myLock === false || !flock($myLock, \LOCK_EX)) {
            $echo("\e[31m[!] No se pudo tomar el turno en la cola ({$myLockFile}). Abortando.\e[39m");
            return ['aborted' => true, 'processed' => 0, 'messages' => []];
        }
        chmod($myLockFile, 0664);

        //Comprobar posición en la cola
        $waitLimit = 30; //Segundos máximos de espera por cada avance en la cola
        $waitedOnSamePosition = 0;
        $lastPosition = null;
        $maxProcesses = 6; //1 activo + 5 en espera

        while (true) {
            $locks = glob("{$lockDir}/*.lock") ?: [];
            sort($locks); // Asegura el orden FIFO por la marca de tiempo en el nombre del archivo
            $locks = self::withoutDeadTurns($locks, $myLockFile);
            $myPosition = array_search($myLockFile, $locks);

            //Si somos demasiados, abortar los últimos en llegar
            if (count($locks) > $maxProcesses) {
                if ($myPosition >= $maxProcesses) {
                    self::releaseTurn($myLock, $myLockFile);
                    $echo("\e[31m[!] Hay demasiadas colas esperando (límite:" . ($maxProcesses - 1) . "en espera). Abortando ejecución.\e[39m");
                    return ['aborted' => true, 'processed' => 0, 'messages' => []];
                }
            }

            //Si somos el primero (posición 0), es nuestro turno
            if ($myPosition === 0) {
                break;
            }

            //Comprobar si la cola se ha movido para reiniciar el tiempo de espera
            if ($lastPosition !== null && $myPosition < $lastPosition) {
                $waitedOnSamePosition = 0; // Se ha movido el anterior, reiniciamos el cronómetro de paciencia
            }

            //Si ha pasado mucho tiempo esperando en la misma posición, abortar (cola estancada)
            if ($waitedOnSamePosition >= $waitLimit) {
                self::releaseTurn($myLock, $myLockFile);
                $echo("\e[31m[!] Tiempo agotado esperando avance de la cola ({$waitLimit}s). Abortando.\e[39m");
                return ['aborted' => true, 'processed' => 0, 'messages' => []];
            }

            if ($lastPosition === null) {
                $echo("\e[33m[!] Hay otra cola en ejecución. Esperando turno (FIFO)...\e[39m");
            }

            $lastPosition = $myPosition;
            sleep(1);
            $waitedOnSamePosition++;
        }

        $message = [
            "\e[32m*** {$titleTask} ***\e[39m",
        ];

        //Antes de tomar nada nuevo: los trabajos que quedaron «en curso» porque su proceso murió.
        foreach (self::reclaimAbandoned() as $reclaimed) {
            $message[] = $reclaimed;
        }

        try {
            $model = QueueJobMapper::model();
            $now = date('Y-m-d H:i:s');
            // Filtrar por estado pendiente Y (que no tenga fecha programada O que ya haya pasado la fecha)
            $sql = "SELECT id FROM " . QueueJobMapper::TABLE . "
                    WHERE status = ?
                    AND (scheduledAt IS NULL OR scheduledAt <= ?)
                    ORDER BY createdAt ASC
                    LIMIT " . $limit;

            $pendingTasks = $model->getDatabase()->prepare($sql);
            $pendingTasks->execute([QueueJobMapper::STATUS_PENDING, $now]);
            $pendingTasks = $pendingTasks->fetchAll(\PDO::FETCH_OBJ);

            $handlers = QueueTask::getQueueHandlers();

            if (empty($pendingTasks)) {
                $message[] = "\e[33mNo hay tareas pendientes en la cola.\e[39m";
            } else {
                foreach ($pendingTasks as $taskData) {
                    $task = new QueueJobMapper($taskData->id);
                    $handlerName = $task->name;

                    if (isset($handlers[$handlerName])) {
                        $message[] = "\e[34m-> Tarea ID {$task->id} [{$handlerName}]: Procesando...\e[39m";

                        // Marcar como en ejecución
                        $task->status = QueueJobMapper::STATUS_RUNNING;
                        $task->startedAt = date('Y-m-d H:i:s');
                        $task->attempts = (int) $task->attempts + 1;
                        $task->update();
                        $processed++;

                        try {
                            $handler = $handlers[$handlerName];

                            // El mapper ya decodifica el campo 'json', pero nos aseguramos
                            $data = $task->data;
                            if (is_string($data)) {
                                $data = json_decode($data, true);
                            } elseif ($data instanceof \stdClass) {
                                $data = json_decode(json_encode($data), true);
                            }

                            $result = $handler->execute($data);
                            /** @var QueueHandlerResponse $result */;

                            if ($result->isSuccess() && !$result->isRetry()) {
                                $task->status = QueueJobMapper::STATUS_COMPLETED;
                                $task->finishedAt = date('Y-m-d H:i:s');
                                $task->errorMessage = null;
                                $message[] = "\e[32m   [OK] {$result->getMessage()}\e[39m";
                            } else {
                                // Lógica de reintento / posposición
                                $attempts = (int) $task->attempts;
                                $maxAttempts = (int) $task->maxAttempts;

                                if ($attempts < $maxAttempts) {
                                    // Retroceso exponencial (1, 5, 15, 30, 60... minutos)
                                    $backoffMinutes = [1, 5, 15, 30, 60];
                                    $delayIndex = min($attempts - 1, count($backoffMinutes) - 1);
                                    $delay = $backoffMinutes[$delayIndex];

                                    // Si el handler sugiere un delay específico, lo usamos
                                    if ($result->getDelay() >= 0) {
                                        $delay = $result->getDelay();
                                    }

                                    $scheduledAt = new \DateTime();
                                    $scheduledAt->modify("+{$delay} minutes");

                                    $task->status = QueueJobMapper::STATUS_PENDING;
                                    $task->scheduledAt = $scheduledAt->format('Y-m-d H:i:s');
                                    $task->errorMessage = $result->getMessage();

                                    if ($result->isRetry() && $result->isSuccess()) {
                                        $message[] = "\e[34m   [WAIT] Tarea pospuesta voluntariamente, reintentando en {$delay} min (Intento {$attempts}/{$maxAttempts}). Info: {$result->getMessage()}\e[39m";
                                    } else {
                                        $message[] = "\e[33m   [REINTENTO] Falló, reintentando en {$delay} min (Intento {$attempts}/{$maxAttempts}). Error: {$result->getMessage()}\e[39m";
                                    }
                                } else {
                                    $task->status = QueueJobMapper::STATUS_FAILED;
                                    $task->errorMessage = $result->getMessage();
                                    $message[] = "\e[31m   [ERROR FINAL] Se agotaron los reintentos ({$maxAttempts}). Error: {$result->getMessage()}\e[39m";
                                }
                            }
                        } catch (\Throwable $e) {
                            $task->status = QueueJobMapper::STATUS_FAILED;
                            $task->errorMessage = "Excepción durante ejecución: " . $e->getMessage();
                            $message[] = "\e[31m   [EXCEPCIÓN] {$e->getMessage()}\e[39m";
                            log_exception($e);
                        }

                        $task->update();
                    } else {
                        $message[] = "\e[31m-> Tarea ID {$task->id}: Handler '{$handlerName}' no registrado.\e[39m";
                        $task->status = QueueJobMapper::STATUS_FAILED;
                        $task->errorMessage = "Handler '{$handlerName}' no registrado.";
                        $task->update();
                    }
                }
            }
        } catch (\Exception $e) {
            $message[] = "\e[31mHa ocurrido un error general: {$e->getMessage()}\e[39m";
            log_exception($e);
        } finally {
            self::releaseTurn($myLock, $myLockFile);
        }

        $message[] = "\e[32m*** {$titleTask}, tarea finalizada ***\e[39m";
        return ['aborted' => false, 'processed' => $processed, 'messages' => $message];
    }

    /**
     * Los turnos sin dueño vivo fuera: si se puede tomar el flock de un turno ajeno, su proceso murió sin soltarlo.
     *
     * @param string[] $locks
     * @param string $mine
     * @return string[]
     */
    private static function withoutDeadTurns(array $locks, string $mine): array
    {
        $alive = [];
        foreach ($locks as $lock) {
            if ($lock === $mine) {
                $alive[] = $lock;
                continue;
            }
            $handle = @fopen($lock, 'c');
            if ($handle !== false && flock($handle, \LOCK_EX | \LOCK_NB)) {
                //Nadie lo tiene: es de un proceso muerto.
                //RETORNO-IGNORADO: si no se borra ahora, lo borra el siguiente que pase por aquí.
                @unlink($lock);
                //RETORNO-IGNORADO: soltar y cerrar el turno de un proceso muerto no puede fallar de forma que importe.
                flock($handle, \LOCK_UN);
                fclose($handle);
                continue;
            }
            if ($handle !== false) {
                //RETORNO-IGNORADO: solo se miraba el turno ajeno; cerrarlo no cambia nada.
                fclose($handle);
            }
            $alive[] = $lock;
        }
        return $alive;
    }

    /**
     * @param resource|false $handle
     * @param string $path
     * @return void
     */
    private static function releaseTurn($handle, string $path): void
    {
        if (file_exists($path)) {
            //RETORNO-IGNORADO: un turno que no se borra lo retira el siguiente al ver su flock libre.
            unlink($path);
        }
        if (is_resource($handle)) {
            //RETORNO-IGNORADO: al cerrar el proceso el sistema suelta el flock de todos modos.
            flock($handle, \LOCK_UN);
            fclose($handle);
        }
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new ProcessQueueTask($startRoute, $namePrefix);
        $route = new Route(
            $instance->route,
            $instance->controller,
            $instance->name,
            $instance->method,
            $instance->requireLogin,
            null,
            $instance->rolesAllowed->getArrayCopy(),
            $instance->defaultParamsValues,
            $instance->middlewares
        );
        return $route;
    }

}
