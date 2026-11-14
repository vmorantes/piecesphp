<?php

/**
 * McpTool.php
 */

namespace PiecesPHP\Core\Mcp;

use PiecesPHP\UserSystem\UserDataPackage;

/**
 * McpTool - Una herramienta del servidor MCP del panel (ADR 0075): la declara el módulo dueño de lo que responde.
 *
 * La ve y la usa quien tiene permiso a `permissionRoute`: el nombre de la ruta es el permiso, como en todo el framework.
 * La función recibe al usuario que pregunta y los argumentos ya validados contra `inputSchema`, y devuelve lo que se
 * responde. Solo lectura: esta versión no tiene herramientas que escriban.
 *
 * @package     PiecesPHP\Core\Mcp
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class McpTool
{
    /**
     * La key es también el name de MCP.
     */
    const KEY_PATTERN = '/^[a-z][a-z0-9_]{0,63}$/';

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $description;

    /**
     * @var array<string,mixed>
     */
    private $inputSchema;

    /**
     * @var string
     */
    private $permissionRoute;

    /**
     * @var callable(UserDataPackage,array<string,mixed>):array<mixed>
     */
    private $handler;

    /**
     * @param string $key El name de MCP: ^[a-z][a-z0-9_]{0,63}$
     * @param string $title Ya traducido, con __()
     * @param string $description Ya traducida: qué hace, qué devuelve y que solo ve lo del usuario que pregunta
     * @param array<string,mixed> $inputSchema JSON Schema de tipo object, dentro de lo que admite McpProtocol::schemaProblem()
     * @param string $permissionRoute El nombre de la ruta cuyo permiso exige
     * @param callable(UserDataPackage,array<string,mixed>):array<mixed> $handler
     * @throws \InvalidArgumentException si la key o el esquema no valen
     */
    public function __construct(string $key, string $title, string $description, array $inputSchema, string $permissionRoute, callable $handler)
    {
        if (preg_match(self::KEY_PATTERN, $key) !== 1) {
            throw new \InvalidArgumentException("La key de una herramienta MCP tiene que casar con " . self::KEY_PATTERN . ", y llegó «{$key}».");
        }
        $problem = McpProtocol::schemaProblem($inputSchema);
        if ($problem !== null) {
            throw new \InvalidArgumentException("El inputSchema de la herramienta MCP «{$key}» no vale: {$problem}.");
        }
        if (trim($permissionRoute) === '') {
            throw new \InvalidArgumentException("La herramienta MCP «{$key}» necesita una ruta de permiso.");
        }
        $this->key = $key;
        $this->title = $title;
        $this->description = $description;
        $this->inputSchema = $inputSchema;
        $this->permissionRoute = $permissionRoute;
        $this->handler = $handler;
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return $this->description;
    }

    /**
     * @return array<string,mixed>
     */
    public function inputSchema(): array
    {
        return $this->inputSchema;
    }

    /**
     * @return string
     */
    public function permissionRoute(): string
    {
        return $this->permissionRoute;
    }

    /**
     * @param UserDataPackage $user
     * @param array<string,mixed> $arguments
     * @return array<mixed>
     */
    public function call(UserDataPackage $user, array $arguments): array
    {
        return ($this->handler)($user, $arguments);
    }

    /**
     * Lo que va en tools/list. Toda herramienta se anuncia de solo lectura: en esta versión no hay de las que escriben.
     *
     * @return array{name:string,title:string,description:string,inputSchema:array<string,mixed>,annotations:array{readOnlyHint:true}}
     */
    public function definition(): array
    {
        return [
            'name' => $this->key,
            'title' => $this->title,
            'description' => $this->description,
            'inputSchema' => $this->inputSchema,
            'annotations' => ['readOnlyHint' => true],
        ];
    }
}
