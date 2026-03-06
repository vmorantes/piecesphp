<?php

/**
 * CronJobTaskAdapter.php
 */

namespace API\Adapters;

use DateTime;
use Exception;

/**
 * CronJobTaskAdapter
 *
 * Wrapper para representar una tarea que será ejecutada a través de un CronJob.
 * Permite definir una condición de ejecución, la función encargada de ejecutar
 * el proceso y retornar una respuesta estándar comprensiva.
 *
 * @package     API\Adapters
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class CronJobTaskAdapter
{
    /**
     * @var string Nombre identificador de la tarea para el TasksRuns
     */
    protected string $name;

    /**
     * @var callable Función que se evalúa para saber si se debe ejecutar la tarea
     */
    protected $executionCondition;

    /**
     * @var callable Función que ejecuta la tarea
     */
    protected $taskHandler;

    /**
     * @var DateTime Instancia inyectable para evaluar fechas. Principalmente usada para testing y métodos de encolado.
     */
    protected DateTime $evalDate;

    /**
     * @param string $name
     * @param callable $taskHandler
     * @param callable|null $executionCondition Opcional. Si retorna false, la tarea se omite.
     */
    public function __construct(string $name, callable $taskHandler,  ? callable $executionCondition = null)
    {
        $this->name = $name;
        $this->taskHandler = $taskHandler;
        $this->executionCondition = $executionCondition ?? fn() => true;
        $this->evalDate = new DateTime();
    }

    /**
     * Obtiene el nombre de la tarea.
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Permite inyectar una fecha específica para la evaluación en lugar de "ahora".
     *
     * @param DateTime $date
     * @return self
     */
    public function setEvalDate(DateTime $date): self
    {
        $this->evalDate = $date;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getEvalDate(): DateTime
    {
        return $this->evalDate;
    }

    // ─── Helpers Estructurales (Fluent Scheduling Interface) ─────────────────────────────────────────────────────────

    /**
     * Retorna una instancia usando fábrica para habilitar el uso fluido desde la creación sin paréntesis sueltos
     */
    public static function make(string $name, callable $taskHandler): self
    {
        return new self($name, $taskHandler);
    }

    /**
     * Ejecuta la tarea si pasó un múltiplo de X minutos frente a la hora base.
     * @param int $minutes
     * @return self
     */
    public function everyMinutes(int $minutes): self
    {
        $this->executionCondition = function () use ($minutes) {
            $currentMinute = (int) $this->getEvalDate()->format('i');
            return $currentMinute % $minutes === 0;
        };
        return $this;
    }

    /**
     * Ejecuta la tarea siempre a un minuto fijo de cada hora (Ej: al minuto 15 de toda hora).
     * @param int $minute
     * @return self
     */
    public function onMinute(int $minute): self
    {
        $this->executionCondition = function () use ($minute) {
            $currentMinute = (int) $this->getEvalDate()->format('i');
            return $currentMinute === $minute;
        };
        return $this;
    }

    /**
     * Ejecuta la tarea al inicio de la hora. Equivalente a ->onMinute(0)
     * @return self
     */
    public function hourly(): self
    {
        return $this->onMinute(0);
    }

    /**
     * Ejecuta la tarea todos los días a una hora exacta H:i
     * @param string $time Ej: "03:30" o "15:00"
     * @return self
     */
    public function dailyAt(string $time): self
    {
        $this->executionCondition = function () use ($time) {
            return $this->getEvalDate()->format('H:i') === $time;
        };
        return $this;
    }

    /**
     * Ejecuta la tarea un día específico de la semana a una hora exacta
     * @param int $dayOfWeek 0 (Domingo) al 6 (Sábado)
     * @param string $time Ej: "03:30"
     * @return self
     */
    public function weeklyOn(int $dayOfWeek, string $time = '00:00'): self
    {
        $this->executionCondition = function () use ($dayOfWeek, $time) {
            $isCorrectDay = (int) $this->getEvalDate()->format('w') === $dayOfWeek;
            $isCorrectTime = $this->getEvalDate()->format('H:i') === $time;
            return $isCorrectDay && $isCorrectTime;
        };
        return $this;
    }

    /**
     * Agrega una condición adicional usando un operador lógico AND virtual
     * @param callable $condition Recibe por parámetro la propia instancia de CronJobTaskAdapter para acceder a evalDate
     * @return self
     */
    public function when(callable $condition): self
    {
        $previousCondition = $this->executionCondition;
        $this->executionCondition = function () use ($previousCondition, $condition) {
            $prevValue = (bool) call_user_func($previousCondition);
            // Pasamos $this a la nueva condición para que puedan usar public methods como getEvalDate() localmente.
            return $prevValue && (bool) call_user_func($condition, $this);
        };
        return $this;
    }

    // ───────────────────────────────────────────────────────────────────────────────────────────────────────────────

    /**
     * Verifica si la tarea debe ejecutarse.
     *
     * @return bool
     */
    public function shouldExecute(): bool
    {
        return (bool) call_user_func($this->executionCondition);
    }

    /**
     * Ejecuta la tarea y devuelve un arreglo comprensivo.
     *
     * @return array
     */
    public function execute(): array
    {
        if (!$this->shouldExecute()) {
            return [
                'success' => false,
                'message' => 'Omitida. No cumple con la condición de ejecución.',
                'skipped' => true,
            ];
        }

        try {
            $result = call_user_func($this->taskHandler);

            // Aseguramos que el resultado contenga las propiedades mínimas requeridas.
            $success = is_array($result) && array_key_exists('success', $result) ? (bool) $result['success'] : true;
            $message = is_array($result) && array_key_exists('message', $result) ? (string) $result['message'] : 'Tarea ejecutada con éxito.';

            $baseResponse = [
                'success' => $success,
                'message' => $message,
                'skipped' => false,
            ];

            return array_merge($baseResponse, is_array($result) ? $result : ['output' => $result]);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Excepción durante la ejecución: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'skipped' => false,
            ];
        }
    }
}