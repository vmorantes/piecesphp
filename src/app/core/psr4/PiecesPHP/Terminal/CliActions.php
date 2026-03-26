<?php

/**
 * CliActions.php
 */

namespace PiecesPHP\Terminal;

use PiecesPHP\TerminalData;

/**
 * CliActions
 *
 * Manejador agnóstico de acciones por consola.
 * Permite registrar handlers y ejecutarlos bajo demanda.
 *
 * @package     PiecesPHP\Terminal
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class CliActions
{
    /**
     * @var string Identificador único de la acción
     */
    protected string $name;

    /**
     * @var callable Función que ejecuta la acción
     */
    protected $handler;

    /**
     * @var string Descripción de la acción para ayuda
     */
    protected string $description = '';

    /**
     * @var array Configuraciones adicionales
     */
    protected array $options = [];

    /**
     * @var string Clave de configuración para el registro global
     */
    protected static string $configKey = 'SystemCliActions';

    /**
     * @param string $name
     * @param callable $handler
     */
    public function __construct(string $name, callable $handler)
    {
        $this->name = $name;
        $this->handler = $handler;
    }

    /**
     * Crea una nueva instancia de CliAction
     *
     * @param string $name
     * @param callable $handler
     * @return self
     */
    public static function make(string $name, callable $handler): self
    {
        return new self($name, $handler);
    }

    /**
     * Establece la descripción de la acción
     *
     * @param string $description
     * @return self
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Obtiene el nombre de la acción
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Obtiene la descripción
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Ejecuta el handler de la acción
     *
     * @param mixed ...$params
     * @return mixed
     */
    public function execute(...$params): mixed
    {
        return call_user_func($this->handler, ...$params);
    }

    /**
     * Registra la acción en el repositorio global
     *
     * @return self
     */
    public function register(): self
    {
        if (TerminalData::instance()->isTerminal()) {
            $actions = self::getActions();
            $actions[$this->name] = $this;
            set_config(self::$configKey, $actions);
        }
        return $this;
    }

    /**
     * Obtiene todas las acciones registradas
     *
     * @return array<string, CliActions>
     */
    public static function getActions(): array
    {
        $actions = get_config(self::$configKey);
        return is_array($actions) ? $actions : [];
    }

    /**
     * Obtiene una acción por su nombre
     *
     * @param string $name
     * @return self|null
     */
    public static function get(string $name): ?self
    {
        $actions = self::getActions();
        return $actions[$name] ?? null;
    }

    /**
     * Verifica si una acción existe
     *
     * @param string $name
     * @return bool
     */
    public static function exists(string $name): bool
    {
        $actions = self::getActions();
        return array_key_exists($name, $actions);
    }

    /**
     * Ejecuta una acción registrada
     *
     * @param string $name
     * @param mixed ...$params
     * @return mixed
     */
    public static function run(string $name, ...$params): mixed
    {
        $action = self::get($name);
        if ($action instanceof self) {
            return $action->execute(...$params);
        }
        return null;
    }

    /**
     * Elimina una acción del registro
     *
     * @param string $name
     * @return bool
     */
    public static function remove(string $name): bool
    {
        $actions = self::getActions();
        if (array_key_exists($name, $actions)) {
            unset($actions[$name]);
            set_config(self::$configKey, $actions);
            return true;
        }
        return false;
    }
}
