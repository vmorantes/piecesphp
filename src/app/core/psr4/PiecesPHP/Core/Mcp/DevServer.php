<?php

/**
 * DevServer.php
 */

namespace PiecesPHP\Core\Mcp;

use PiecesPHP\Core\Roles;
use Terminal\Tasks\RouteInventoryTask;

/**
 * DevServer - El servidor MCP de solo lectura para quien desarrolla sobre PiecesPHP (ADR 0075, punto 5; bin/mcp).
 *
 * Habla JSON-RPC 2.0: handle() recibe un mensaje ya decodificado y devuelve la respuesta, o null si era una
 * notificación. No lee ni escribe la entrada y la salida (eso es la acción mcp-dev), no escribe nada en ningún sitio y
 * no toca la base, los secretos ni la carpeta de claves: solo el registro de rutas, los roles, openapi.json y los .md de
 * source-docs/project/docs/.
 *
 * @package     PiecesPHP\Core\Mcp
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class DevServer
{
    const PROTOCOL_VERSION = '2025-06-18';
    const SUPPORTED_PROTOCOL_VERSIONS = ['2025-06-18', '2025-03-26', '2024-11-05'];
    const SERVER_NAME = 'piecesphp-dev';
    const DOCS_SEARCH_LIMIT = 50;

    const PARSE_ERROR = -32700;
    const INVALID_REQUEST = -32600;
    const METHOD_NOT_FOUND = -32601;
    const INVALID_PARAMS = -32602;

    /**
     * La respuesta a un mensaje JSON-RPC 2.0; null si es una notificación (sin id), que no se responde.
     *
     * @param array<mixed> $message
     * @return array<string,mixed>|null
     */
    public static function handle(array $message): ?array
    {
        $isNotification = !array_key_exists('id', $message);
        $id = $message['id'] ?? null;
        if (($message['jsonrpc'] ?? null) !== '2.0' || !is_string($message['method'] ?? null)) {
            return $isNotification ? null : self::error($id, self::INVALID_REQUEST, 'Petición JSON-RPC 2.0 no válida.');
        }
        if ($isNotification) {
            return null;
        }
        $params = $message['params'] ?? [];
        $params = is_array($params) ? $params : [];
        return match ($message['method']) {
            'initialize' => self::result($id, self::initialize($params)),
            'ping' => self::result($id, (object) []),
            'tools/list' => self::result($id, ['tools' => self::toolList()]),
            'tools/call' => self::call($id, $params),
            default => self::error($id, self::METHOD_NOT_FOUND, "Método desconocido: {$message['method']}."),
        };
    }

    /**
     * La respuesta a una línea que no es JSON.
     *
     * @return array<string,mixed>
     */
    public static function parseError(): array
    {
        return self::error(null, self::PARSE_ERROR, 'La línea no es JSON válido.');
    }

    /**
     * Las herramientas, con su descripción y el esquema JSON de sus argumentos.
     *
     * @return array<int,array{name:string,description:string,inputSchema:array<string,mixed>}>
     */
    public static function toolList(): array
    {
        return [
            [
                'name' => 'routes',
                'description' => 'Lista las rutas registradas en el framework: su nombre (que es también su permiso), método, patrón, controlador si se conoce, si exige sesión y los tipos de usuario que declara. Con «filter», solo las que lo contienen en el nombre o en el patrón.',
                'inputSchema' => ['type' => 'object', 'properties' => ['filter' => ['type' => 'string', 'description' => 'Texto que buscar en el nombre o el patrón, sin distinguir mayúsculas.']], 'additionalProperties' => false],
            ],
            [
                'name' => 'permissions',
                'description' => 'Dice qué rutas concede un tipo de usuario (por su número o por el nombre de su rol), o qué tipos de usuario conceden una ruta. Pide «userType» o «route», no los dos.',
                'inputSchema' => ['type' => 'object', 'properties' => ['userType' => ['type' => ['integer', 'string'], 'description' => 'El tipo de usuario: su número (0 es root) o el nombre de su rol.'], 'route' => ['type' => 'string', 'description' => 'El nombre de una ruta.']], 'additionalProperties' => false],
            ],
            [
                'name' => 'openapi',
                'description' => 'Devuelve una operación de la API HTTP pública (source-docs/api/openapi.json) por su ruta y su método; sin ruta, la lista de rutas con sus métodos y su resumen.',
                'inputSchema' => ['type' => 'object', 'properties' => ['path' => ['type' => 'string', 'description' => 'La ruta tal como figura en openapi.json, como /core/api/publications/publications/list/.'], 'method' => ['type' => 'string', 'description' => 'El método HTTP, como get o post. Sin él, todos los de esa ruta.']], 'additionalProperties' => false],
            ],
            [
                'name' => 'docs_search',
                'description' => 'Busca un texto, sin expresiones regulares y sin distinguir mayúsculas, en la documentación para desarrolladores (los .md de source-docs/project/docs/). Devuelve el archivo, la línea y su texto, como mucho ' . self::DOCS_SEARCH_LIMIT . ' resultados.',
                'inputSchema' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string', 'minLength' => 1, 'description' => 'El texto que buscar.']], 'required' => ['query'], 'additionalProperties' => false],
            ],
        ];
    }

    /**
     * @param array<mixed> $params
     * @return array<string,mixed>
     */
    private static function initialize(array $params): array
    {
        $requested = $params['protocolVersion'] ?? null;
        return [
            'protocolVersion' => is_string($requested) && in_array($requested, self::SUPPORTED_PROTOCOL_VERSIONS, true) ? $requested : self::PROTOCOL_VERSION,
            'capabilities' => ['tools' => (object) []],
            'serverInfo' => ['name' => self::SERVER_NAME, 'version' => defined('APP_VERSION') ? (string) APP_VERSION : ''],
        ];
    }

    /**
     * @param mixed $id
     * @param array<mixed> $params
     * @return array<string,mixed>
     */
    private static function call(mixed $id, array $params): array
    {
        $name = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];
        if (!is_string($name) || !in_array($name, array_column(self::toolList(), 'name'), true)) {
            return self::error($id, self::INVALID_PARAMS, 'Herramienta desconocida: ' . (is_string($name) ? $name : '(sin nombre)') . '.');
        }
        if (!is_array($arguments)) {
            return self::error($id, self::INVALID_PARAMS, 'Los argumentos tienen que ser un objeto.');
        }
        try {
            $output = match ($name) {
                'routes' => self::routes($arguments),
                'permissions' => self::permissions($arguments),
                'openapi' => self::openapi($arguments),
                'docs_search' => self::docsSearch($arguments),
                default => throw new \InvalidArgumentException("Herramienta desconocida: {$name}."),
            };
        } catch (\InvalidArgumentException $e) {
            return self::error($id, self::INVALID_PARAMS, $e->getMessage());
        }
        return self::result($id, [
            'content' => [['type' => 'text', 'text' => (string) json_encode($output['data'], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE)]],
            'isError' => $output['isError'],
        ]);
    }

    /**
     * @param array<mixed> $arguments
     * @return array{data:mixed,isError:bool}
     */
    private static function routes(array $arguments): array
    {
        $filter = $arguments['filter'] ?? '';
        if (!is_string($filter)) {
            throw new \InvalidArgumentException('«filter» tiene que ser texto.');
        }
        $inventory = RouteInventoryTask::inventory(false);
        if ($inventory === null) {
            return ['data' => 'El framework no devolvió ningún registro de rutas.', 'isError' => true];
        }
        $registry = get_routes();
        $registry = is_array($registry) ? $registry : [];
        $routes = [];
        foreach ($inventory as $route) {
            if ($filter !== '' && mb_stripos($route['name'], $filter) === false && mb_stripos($route['pattern'], $filter) === false) {
                continue;
            }
            $controller = $registry[$route['name']]['controller'] ?? null;
            $routes[] = [
                'name' => $route['name'],
                'method' => $route['method'],
                'pattern' => $route['pattern'],
                'controller' => is_string($controller) ? $controller : null,
                'requireLogin' => $route['requireLogin'],
                'rolesAllowed' => $route['rolesAllowed'],
            ];
        }
        return ['data' => ['total' => count($routes), 'routes' => $routes], 'isError' => false];
    }

    /**
     * @param array<mixed> $arguments
     * @return array{data:mixed,isError:bool}
     */
    private static function permissions(array $arguments): array
    {
        $userType = $arguments['userType'] ?? null;
        $route = $arguments['route'] ?? null;
        if (($userType === null) === ($route === null)) {
            throw new \InvalidArgumentException('Pide «userType» o «route», uno de los dos.');
        }
        $inventory = RouteInventoryTask::inventory(false) ?? [];
        if ($route !== null) {
            if (!is_string($route)) {
                throw new \InvalidArgumentException('«route» tiene que ser texto.');
            }
            if (!in_array($route, array_column($inventory, 'name'), true)) {
                return ['data' => "No hay ninguna ruta registrada con el nombre «{$route}».", 'isError' => true];
            }
            $granted = [];
            foreach (Roles::getRoles() as $role) {
                if (Roles::hasPermissions($route, (int) $role['code'], true)) {
                    $granted[] = ['code' => (int) $role['code'], 'name' => $role['name']];
                }
            }
            return ['data' => ['route' => $route, 'userTypes' => $granted], 'isError' => false];
        }
        if (!is_int($userType) && !is_string($userType)) {
            throw new \InvalidArgumentException('«userType» tiene que ser un número o el nombre de un rol.');
        }
        $role = is_int($userType) || ctype_digit($userType) ? Roles::getRole((int) $userType) : Roles::getRole($userType);
        if ($role === null) {
            return ['data' => "No hay ningún tipo de usuario «{$userType}».", 'isError' => true];
        }
        $granted = array_values(array_filter(array_column($inventory, 'name'), fn (string $name): bool => Roles::hasPermissions($name, (int) $role['code'], true)));
        return ['data' => ['userType' => (int) $role['code'], 'name' => $role['name'], 'total' => count($granted), 'routes' => $granted], 'isError' => false];
    }

    /**
     * @param array<mixed> $arguments
     * @return array{data:mixed,isError:bool}
     */
    private static function openapi(array $arguments): array
    {
        $path = $arguments['path'] ?? null;
        $method = $arguments['method'] ?? null;
        if (($path !== null && !is_string($path)) || ($method !== null && !is_string($method))) {
            throw new \InvalidArgumentException('«path» y «method» tienen que ser texto.');
        }
        $file = self::repositoryPath('source-docs/api/openapi.json');
        $document = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        if (!is_array($document) || !is_array($document['paths'] ?? null)) {
            return ['data' => 'Este clon no trae source-docs/api/openapi.json (o no se puede leer).', 'isError' => true];
        }
        if ($path === null) {
            $list = [];
            foreach ($document['paths'] as $route => $operations) {
                foreach (is_array($operations) ? $operations : [] as $verb => $operation) {
                    $list[] = ['path' => $route, 'method' => $verb, 'summary' => is_array($operation) ? ($operation['summary'] ?? '') : ''];
                }
            }
            return ['data' => ['total' => count($list), 'operations' => $list], 'isError' => false];
        }
        $operations = $document['paths'][$path] ?? null;
        if (!is_array($operations)) {
            return ['data' => "openapi.json no tiene la ruta «{$path}».", 'isError' => true];
        }
        if ($method === null) {
            return ['data' => [$path => $operations], 'isError' => false];
        }
        $operation = $operations[mb_strtolower($method)] ?? null;
        if (!is_array($operation)) {
            return ['data' => "La ruta «{$path}» no tiene el método «{$method}» en openapi.json.", 'isError' => true];
        }
        return ['data' => [$path => [mb_strtolower($method) => $operation]], 'isError' => false];
    }

    /**
     * @param array<mixed> $arguments
     * @return array{data:mixed,isError:bool}
     */
    private static function docsSearch(array $arguments): array
    {
        $query = $arguments['query'] ?? null;
        if (!is_string($query) || trim($query) === '') {
            throw new \InvalidArgumentException('«query» es obligatorio y no puede ir vacío.');
        }
        $root = self::repositoryPath('source-docs/project/docs');
        if (!is_dir($root)) {
            return ['data' => 'Este clon no trae source-docs/project/docs/.', 'isError' => true];
        }
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && mb_strtolower($file->getExtension()) === 'md') {
                $files[] = $file->getPathname();
            }
        }
        sort($files);
        $results = [];
        $more = false;
        foreach ($files as $file) {
            $lines = @file($file, \FILE_IGNORE_NEW_LINES);
            foreach (is_array($lines) ? $lines : [] as $index => $line) {
                if (mb_stripos($line, $query) === false) {
                    continue;
                }
                if (count($results) >= self::DOCS_SEARCH_LIMIT) {
                    $more = true;
                    break 2;
                }
                $results[] = ['file' => ltrim(str_replace('\\', '/', mb_substr($file, mb_strlen($root))), '/'), 'line' => $index + 1, 'text' => mb_substr(trim($line), 0, 300)];
            }
        }
        return ['data' => ['total' => count($results), 'truncated' => $more, 'results' => $results], 'isError' => false];
    }

    /**
     * Una ruta del repositorio (la carpeta de encima de src/).
     *
     * @param string $relative
     * @return string
     */
    private static function repositoryPath(string $relative): string
    {
        return dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/' . $relative;
    }

    /**
     * @param mixed $id
     * @param mixed $result
     * @return array<string,mixed>
     */
    private static function result(mixed $id, mixed $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    /**
     * @param mixed $id
     * @param int $code
     * @param string $message
     * @return array<string,mixed>
     */
    private static function error(mixed $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }
}
