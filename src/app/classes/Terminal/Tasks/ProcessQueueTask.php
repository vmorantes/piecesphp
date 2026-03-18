<?php

/**
 * ProcessQueueTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\QueueHandlerResponse;
use PiecesPHP\TerminalData;
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
            "Procesa las tareas pendientes en la cola (pcs_queue).\r\n",
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
            '--limit' => 10,
        ];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $parameters = empty($parameters) ? TerminalData::instance()->arguments() : array_merge($parameters, TerminalData::instance()->arguments());

        $limit = isset($parameters['--limit']) ? (int) $parameters['--limit'] : 10;
        $titleTask = "Procesando Cola de Tareas";
        $message = [
            "\e[32m*** {$titleTask} ***\e[39m",
        ];

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
        }

        $message[] = "\e[32m*** {$titleTask}, tarea finalizada ***\e[39m";
        if (count($message) > 1) {
            echoTerminal(implode("\r\n", $message));
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
