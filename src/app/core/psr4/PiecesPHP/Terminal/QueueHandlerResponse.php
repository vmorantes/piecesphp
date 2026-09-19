<?php

/**
 * QueueHandlerResponse.php
 */

namespace PiecesPHP\Terminal;

/**
 * QueueHandlerResponse
 *
 * Clase estandarizada para las respuestas de los manejadores de cola.
 * Ayuda a documentar y facilitar la devolución de estados al procesador.
 *
 * @package     PiecesPHP\Terminal
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class QueueHandlerResponse
{
    /** @var bool Indica si la ejecución fue exitosa */
    public $success = true;

    /** @var string Mensaje descriptivo del resultado */
    public $message = '';

    /** @var bool Indica si se solicita un reintento (sin necesariamente ser un error fatal) */
    public $retry = false;

    /** @var int Tiempo en minutos para el próximo reintento */
    public $delay = 0;

    /** @var array Datos adicionales resultantes del procesamiento */
    public $data = [];

    /**
     * @param bool $success
     * @param string $message
     * @param bool $retry
     * @param int $delay
     * @param array $data
     */
    public function __construct(bool $success = true, string $message = '', bool $retry = false, int $delay = 0, array $data = [])
    {
        $this->success = $success;
        $this->message = $message;
        $this->retry = $retry;
        $this->delay = $delay;
        $this->data = $data;
    }

    /**
     * Respuesta de éxito total.
     */
    public static function success(string $message = 'Tarea completada con éxito.', array $data = []): self
    {
        return new self(true, $message, false, 0, $data);
    }

    /**
     * Respuesta de fallo (gatilla reintentos automáticos si maxAttempts > attempts).
     */
    public static function fail(string $message = 'La tarea ha fallado.', bool $retry = true, int $delay = 0, array $data = []): self
    {
        return new self(false, $message, $retry, $delay, $data);
    }

    /**
     * Respuesta de espera voluntaria (estado [WAIT]).
     * No cuenta como error fatal inicialmente pero permite reprogramar.
     */
    public static function wait(string $message = 'Tarea pospuesta voluntariamente.', int $delay = 5, array $data = []): self
    {
        return new self(true, $message, true, $delay, $data);
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return bool
     */
    public function isFail(): bool
    {
        return !$this->success;
    }

    /**
     * @return bool
     */
    public function isRetry(): bool
    {
        return $this->retry;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return int
     */
    public function getDelay(): int
    {
        return (int) $this->delay;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return is_array($this->data) ? $this->data : [];
    }

    /**
     * Convierte el objeto a un array compatible con el procesador actual.
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'retry' => $this->retry,
            'delay' => $this->delay,
            'data' => $this->data,
        ];
    }
}