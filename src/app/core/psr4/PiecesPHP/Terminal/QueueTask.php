<?php

/**
 * QueueTask.php
 */

namespace PiecesPHP\Terminal;

use Exception;
use Terminal\Mappers\QueueJobMapper;

/**
 * QueueTask
 *
 * Wrapper para representar un manejador de cola y despachar tareas para su procesamiento.
 * Similar a CronJobTask pero para ejecución asíncrona mediante base de datos.
 *
 * @package     PiecesPHP\Terminal
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class QueueTask
{
    protected static string $configKey = 'SystemQueues';

    /**
     * @var string Nombre identificador del manejador
     */
    protected string $name;

    /**
     * @var callable Función que ejecuta la tarea. Recibe los datos de la tarea como parámetro.
     */
    protected $taskHandler;

    /**
     * @param string $name
     * @param callable $taskHandler
     */
    public function __construct(string $name, callable $taskHandler)
    {
        $this->name = $name;
        $this->taskHandler = $taskHandler;
    }

    /**
     * Obtiene el nombre del manejador.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retorna una instancia usando fábrica para habilitar el uso fluido.
     */
    public static function make(string $name, callable $taskHandler): self
    {
        return new self($name, $taskHandler);
    }

    /**
     * Ejecuta el manejador con datos específicos.
     *
     * @param mixed $data
     * @return array
     */
    /**
     * Ejecuta el manejador con datos específicos.
     *
     * @param mixed $data
     * @return QueueHandlerResponse
     */
    public function execute($data = null): QueueHandlerResponse
    {
        try {
            $result = call_user_func($this->taskHandler, $data);

            if ($result instanceof QueueHandlerResponse) {
                return $result;
            }

            if (is_array($result)) {
                return new QueueHandlerResponse(
                    $result['success'] ?? true,
                    $result['message'] ?? '',
                    $result['retry'] ?? false,
                    $result['delay'] ?? 0,
                    $result['data'] ?? []
                );
            }

            return QueueHandlerResponse::success('Tarea completada.', is_array($result) ? $result : ['output' => $result]);
        } catch (Exception $e) {
            return QueueHandlerResponse::fail('Excepción: ' . $e->getMessage(), false, 0, [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Despacha una tarea a la cola.
     *
     * @param string $name Nombre del manejador que debe procesarla
     * @param mixed $data Datos necesarios para la ejecución
     * @param int $maxAttempts Número máximo de intentos (por defecto 3)
     * @param string|null $scheduledAt Fecha programada de ejecución (null para inmediata)
     * @return int|null ID de la tarea creada
     */
    public static function dispatch(string $name, $data = null, int $maxAttempts = 3, ?string $scheduledAt = null): ?int
    {
        $task = new QueueJobMapper();
        $task->name = $name;
        $task->data = json_encode($data);
        $task->status = QueueJobMapper::STATUS_PENDING;
        $task->attempts = 0;
        $task->maxAttempts = $maxAttempts;
        $task->scheduledAt = $scheduledAt;
        $task->createdAt = date('Y-m-d H:i:s');

        if ($task->save()) {
            return $task->id;
        }

        return null;
    }

    /**
     * Registra múltiples manejadores de cola.
     * @param array<QueueTask> $queueTasks
     * @return void
     */
    public static function addQueueHandlers(array $queueTasks): void
    {
        foreach ($queueTasks as $queueTask) {
            self::addQueueHandler($queueTask);
        }
    }

    /**
     * Registra un manejador de cola.
     * @param QueueTask $queueTask
     * @return string
     */
    public static function addQueueHandler(QueueTask $queueTask): string
    {
        $handlers = self::getQueueHandlers();
        $handlers[$queueTask->getName()] = $queueTask;
        set_config(self::$configKey, $handlers);
        return $queueTask->getName();
    }

    /**
     * Obtiene todos los manejadores de cola registrados.
     * @return array<string, QueueTask>
     */
    public static function getQueueHandlers(): array
    {
        $handlers = get_config(self::$configKey);
        return is_array($handlers) ? $handlers : [];
    }
}
