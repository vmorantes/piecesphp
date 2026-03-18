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

    /**
     * Despacha un evento por defecto.
     *
     * @param string $defaultEventName El nombre del evento por defecto que se va a despachar (self::DEFAULT_EVENTS).
     * @param mixed $payload Los datos que se van a enviar a los escuchadores. Opcional.
     * @return void
     */
    public static function defaultDispatch(string $defaultEventName, $payload = null): void
    {
        if (array_key_exists($defaultEventName, self::DEFAULT_EVENTS)) {
            $event = self::DEFAULT_EVENTS[$defaultEventName];
            self::dispatch($event['context'], $event['event'], $payload);
        }
    }

    /**
     * Registra un nuevo escuchador de eventos por defecto.
     *
     * Este método permite registrar una función callback para un evento específico.
     *
     * @param string $defaultEventName El nombre del evento por defecto que se va a despachar (self::DEFAULT_EVENTS).
     * @param callable $callback La función callback que se ejecutará cuando se dispare el evento.
     * @return void
     */
    public static function defaultListen(string $defaultEventName, callable $callback): void
    {
        if (array_key_exists($defaultEventName, self::DEFAULT_EVENTS)) {
            $event = self::DEFAULT_EVENTS[$defaultEventName];
            self::listen($event['event'], $callback, $event['context']);
        }
    }

    /* Eventos por defecto del sistema */
    //Se dipara cuando se registran las rutas del sistema
    const EVENT_INIT_ROUTES_NAME = 'EVENT_INIT_ROUTES';
    const EVENT_INIT_ROUTES = [
        'event' => 'InitRoutes',
        'context' => 'AppRoutes',
    ];
    //Se dipara cuando se registran las rutas del sistema
    const EVENT_ADD_DYNAMIC_TRANSLATIONS_NAME = 'EVENT_ADD_DYNAMIC_TRANSLATIONS';
    const EVENT_ADD_DYNAMIC_TRANSLATIONS = [
        'event' => 'added',
        'context' => 'AddDynamicTransaltions',
    ];

    /**
     * Eventos por defecto del sistema. Se estructuran de la siguiente manera:
     * ["NOMBRE_CLAVE_EVENTO" => ["event" => "NOMBRE_EVENTO", "context" => "CONTEXTO"]]
     * Donde NOMBRE_CLAVE_EVENTO es la clave que se usa para despachar el evento por defecto (self::defaultDispatch)
     * y NOMBRE_EVENTO y CONTEXTO son los que se usan para despachar el evento (self::dispatch).
     * @return array<string,array{event:string,context:string}>
     */
    const DEFAULT_EVENTS = [
        self::EVENT_INIT_ROUTES_NAME => self::EVENT_INIT_ROUTES,
        self::EVENT_ADD_DYNAMIC_TRANSLATIONS_NAME => self::EVENT_ADD_DYNAMIC_TRANSLATIONS,
    ];

}