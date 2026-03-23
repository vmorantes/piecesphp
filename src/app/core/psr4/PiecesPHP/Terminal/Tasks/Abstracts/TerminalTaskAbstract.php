<?php

/**
 * TerminalTaskAbstract.php
 */

namespace PiecesPHP\Terminal\Tasks\Abstracts;

use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Terminal\Tasks\Interfaces\TerminalTaskInterface;

/**
 * TerminalTaskAbstract.
 *
 * @package     PiecesPHP\Terminal\Tasks\Interfaces
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 * @see https://misc.flogisoft.com/bash/tip_colors_and_formatting Colores para texto de terminal
 */
abstract class TerminalTaskAbstract implements TerminalTaskInterface
{
    /**
     * Descipción de la tarea que se mostrará con implode
     */
    protected ?StringArray $description;
    /**
     * Endpoint de la tarea, string del segmento válido para Slim v4. Ejemplo: "db-backup[/]"
     */
    protected string $route;
    /**
     * Controlador de ruta válido para el sistema de rutas, un string invocable
     */
    protected string $controller;
    /**
     * Nombre de la ruta válido para el sistema de rutas
     */
    protected string $name;
    /**
     * Método HTTP
     */
    protected string $method;
    /**
     * Requiere login
     */
    protected bool $requireLogin;
    /**
     * Alias de la ruta (alias del name, legacy)
     */
    protected ?string $alias;
    /**
     * Roles permitidos
     */
    protected IntegerArray $rolesAllowed;
    /**
     * Valores por defecto de los parámetros
     * @var array<string, string>
     */
    protected array $defaultParamsValues;
    /**
     * Middlewares, string invocable o callable
     * @var array<string>|array<callable>
     */
    protected array $middlewares;

    public function getDescription(): StringArray
    {
        return $this->description !== null ? $this->description : new StringArray();
    }
    public function getRoute(): string
    {
        return $this->route;
    }
    public function getController(): string
    {
        return $this->controller;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getMethod(): string
    {
        return $this->method;
    }
    public function getRequireLogin(): bool
    {
        return $this->requireLogin;
    }
    public function getAlias(): ?string
    {
        return $this->alias;
    }
    public function getRolesAllowed(): IntegerArray
    {
        return $this->rolesAllowed;
    }
    public function getDefaultParamsValues(): array
    {
        return $this->defaultParamsValues;
    }
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
