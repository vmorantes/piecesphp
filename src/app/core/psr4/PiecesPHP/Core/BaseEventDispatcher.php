<?php

/**
 * BaseEventDispatcher.php
 */
namespace PiecesPHP\Core;

/**
 * BaseEventDispatcher - Manejador de eventos básico
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class BaseEventDispatcher
{
    protected static array $listeners = [];

    /**
     * Registra un nuevo escuchador de eventos.
     *
     * Este método permite registrar una función callback para un evento específico. El contexto es opcional y se utiliza para agrupar los escuchadores de eventos.
     *
     * @param string $event El nombre del evento al que se suscribe el escuchador.
     * @param callable $callback La función callback que se ejecutará cuando se dispare el evento.
     * @param string|null $context El contexto en el que se registra el escuchador. Si no se proporciona, se genera uno automáticamente.
     * @return string El contexto en el que se registró el escuchador.
     */
    public static function listen(string $event, callable $callback, ?string $context = null): string
    {
        $context = !is_null($context) ? $context : uniqid();
        if (!array_key_exists($context, self::$listeners)) {
            self::$listeners[$context] = [];
        }
        if (!array_key_exists($event, self::$listeners[$context])) {
            self::$listeners[$context][$event] = [];
        }
        self::$listeners[$context][$event][] = $callback;
        return $context;
    }

    /**
     * Despacha un evento.
     *
     * Este método permite despachar un evento a todos los escuchadores registrados en un contexto específico.
     *
     * @param string $context El contexto en el que se registraron los escuchadores.
     * @param string $event El nombre del evento que se va a despachar.
     * @param mixed $payload Los datos que se van a enviar a los escuchadores. Opcional.
     * @return void
     */
    public static function dispatch(string $context, string $event, $payload = null): void
    {
        if (array_key_exists($context, self::$listeners)) {
            $eventListeners = self::$listeners[$context];
            if (array_key_exists($event, $eventListeners)) {
                $callbacks = $eventListeners[$event];
                foreach ($callbacks as $callback) {
                    $callback($payload);
                }
            }
        }
    }

}
