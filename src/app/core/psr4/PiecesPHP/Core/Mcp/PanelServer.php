<?php

/**
 * PanelServer.php
 */

namespace PiecesPHP\Core\Mcp;

use PiecesPHP\Core\Roles;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * PanelServer - El servidor MCP de la instalación (ADR 0075, punto 2): habla con la sesión y los permisos de quien
 * pregunta. Lo sirve la ruta 'mcp-panel-server'; handle() no lee la petición, recibe el mensaje ya decodificado.
 *
 * tools/list da solo las herramientas del registro que el usuario puede usar, y tools/call lo vuelve a comprobar. Una
 * herramienta sin permiso responde EXACTAMENTE igual que una que no existe: así no se revela qué hay.
 *
 * @package     PiecesPHP\Core\Mcp
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class PanelServer
{
    const SERVER_NAME = 'piecesphp-panel';

    /**
     * La respuesta a un mensaje JSON-RPC 2.0; null si es una notificación (sin id), que no se responde.
     *
     * @param array<mixed> $message
     * @param UserDataPackage $user
     * @return array<string,mixed>|null
     */
    public static function handle(array $message, UserDataPackage $user): ?array
    {
        $serverInfo = ['name' => self::SERVER_NAME, 'version' => defined('APP_VERSION') ? (string) APP_VERSION : ''];
        return McpProtocol::handle(
            $message,
            $serverInfo,
            fn(): array => array_values(array_map(fn(McpTool $tool): array => $tool->definition(), McpToolRegistry::visibleFor((int) $user->type))),
            fn(mixed $id, string $name, ?array $arguments): array => self::call($id, $name, $arguments, $user)
        );
    }

    /**
     * @param mixed $id
     * @param string $name
     * @param array<mixed>|null $arguments
     * @param UserDataPackage $user
     * @return array<string,mixed>
     */
    private static function call(mixed $id, string $name, ?array $arguments, UserDataPackage $user): array
    {
        $tool = McpToolRegistry::get($name);
        //La segunda comprobación: tools/list ya filtró, pero una llamada puede nombrar cualquier cosa.
        if ($tool === null || !Roles::hasPermissions($tool->permissionRoute(), (int) $user->type, true)) {
            return McpProtocol::unknownTool($id, $name);
        }
        if ($arguments === null) {
            return McpProtocol::badArguments($id);
        }
        $problem = McpProtocol::argumentsProblem($tool->inputSchema(), $arguments);
        if ($problem !== null) {
            return McpProtocol::error($id, McpProtocol::INVALID_PARAMS, $problem);
        }
        //El sobre va dentro: un dato que no se puede codificar falla igual que una excepción de la herramienta.
        try {
            return McpProtocol::toolResult($id, $tool->call($user, $arguments), false);
        } catch (\Throwable $e) {
            //Ni el mensaje ni la pila salen al cliente: van al registro de errores, como cualquier otra excepción.
            log_exception($e);
            return McpProtocol::toolResult($id, McpProtocol::text('La herramienta no pudo responder. El error quedó en el registro de errores de la instalación.'), true);
        }
    }
}
