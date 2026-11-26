<?php

/**
 * DevServer.php
 */

namespace PiecesPHP\Core\Mcp;

use PiecesPHP\Core\Atlas\Atlas;
use PiecesPHP\Core\Roles;
use Terminal\Tasks\RouteInventoryTask;

/**
 * DevServer - El servidor MCP de solo lectura para quien desarrolla sobre PiecesPHP (ADR 0075, punto 5; bin/mcp).
 *
 * Habla JSON-RPC 2.0: handle() recibe un mensaje ya decodificado y devuelve la respuesta, o null si era una
 * notificación. No lee ni escribe la entrada y la salida (eso es la acción mcp-dev), no escribe nada en ningún sitio y
 * no toca la base, los secretos ni la carpeta de claves: solo el registro de rutas, los roles, openapi.json, los .md de
 * source-docs/project/docs/ y, para el mapa del código (ADR 0091), los tokens del PHP de src/app, que no ejecuta.
 *
 * @package     PiecesPHP\Core\Mcp
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class DevServer
{
    const PROTOCOL_VERSION = McpProtocol::PROTOCOL_VERSION;
    const SUPPORTED_PROTOCOL_VERSIONS = McpProtocol::SUPPORTED_PROTOCOL_VERSIONS;
    const SERVER_NAME = 'piecesphp-dev';
    const DOCS_SEARCH_LIMIT = 50;

    const PARSE_ERROR = McpProtocol::PARSE_ERROR;
    const INVALID_REQUEST = McpProtocol::INVALID_REQUEST;
    const METHOD_NOT_FOUND = McpProtocol::METHOD_NOT_FOUND;
    const INVALID_PARAMS = McpProtocol::INVALID_PARAMS;

    /**
     * La respuesta a un mensaje JSON-RPC 2.0; null si es una notificación (sin id), que no se responde.
     *
     * @param array<mixed> $message
     * @return array<string,mixed>|null
     */
    public static function handle(array $message): ?array
    {
        $serverInfo = ['name' => self::SERVER_NAME, 'version' => defined('APP_VERSION') ? (string) APP_VERSION : ''];
        return McpProtocol::handle($message, $serverInfo, [self::class, 'toolList'], [self::class, 'call']);
    }

    /**
     * La respuesta a una línea que no es JSON.
     *
     * @return array<string,mixed>
     */
    public static function parseError(): array
    {
        return McpProtocol::parseError();
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
                'name' => 'route_explain',
                'description' => 'Explica de dónde sale el permiso de una ruta para un tipo de usuario (o para todos): dónde se declara, la expresión de sus roles y la constante de la que salen (con cuántas rutas la usan), las vías que lo conceden (declaración, listas de roles.php, segmento del tipo, routes de user-types.php), lo que lo estrecha (sesión, _allowedRoute, módulo) y cómo lo concede, retira o estrecha un proyecto: archivo:línea, de quién es el archivo y qué pasa al actualizar. Con su precisión y lo no visto.',
                'inputSchema' => ['type' => 'object', 'properties' => ['route' => ['type' => 'string', 'minLength' => 1, 'description' => 'El nombre de la ruta.'], 'userType' => ['type' => ['integer', 'string'], 'description' => 'El tipo de usuario: su número (0 es root) o el nombre de su rol. Sin él, todos los tipos con rol.']], 'required' => ['route'], 'additionalProperties' => false],
            ],
            [
                'name' => 'symbol_uses',
                'description' => 'Para una constante de clase (Clase\\Completa::NOMBRE): dónde se declara, de quién es el archivo, su valor, el comentario pegado, cada uso con archivo:línea y su contexto (declaración de ruta, menú u otro) y las rutas que toman de ella sus roles.',
                'inputSchema' => ['type' => 'object', 'properties' => ['symbol' => ['type' => 'string', 'minLength' => 3, 'description' => 'La constante, como PiecesPHP\\Settings\\Controllers\\SettingsController::ROLES_SECURITY_AND_AI.']], 'required' => ['symbol'], 'additionalProperties' => false],
            ],
            [
                'name' => 'docs_search',
                'description' => 'Busca un texto, sin expresiones regulares y sin distinguir mayúsculas, en la documentación para desarrolladores (los .md de source-docs/project/docs/). Devuelve el archivo, la línea y su texto, como mucho ' . self::DOCS_SEARCH_LIMIT . ' resultados.',
                'inputSchema' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string', 'minLength' => 1, 'description' => 'El texto que buscar.']], 'required' => ['query'], 'additionalProperties' => false],
            ],
        ];
    }

    /**
     * La respuesta de tools/call (la llama McpProtocol::handle()).
     *
     * @internal
     * @param mixed $id
     * @param string $name
     * @param array<mixed>|null $arguments
     * @return array<string,mixed>
     */
    public static function call(mixed $id, string $name, ?array $arguments): array
    {
        if (!in_array($name, array_column(self::toolList(), 'name'), true)) {
            return McpProtocol::unknownTool($id, $name);
        }
        if ($arguments === null) {
            return McpProtocol::badArguments($id);
        }
        try {
            $output = match ($name) {
                'routes' => self::routes($arguments),
                'permissions' => self::permissions($arguments),
                'openapi' => self::openapi($arguments),
                'docs_search' => self::docsSearch($arguments),
                'route_explain' => self::routeExplain($arguments),
                'symbol_uses' => self::symbolUses($arguments),
                default => throw new \InvalidArgumentException("Herramienta desconocida: {$name}."),
            };
        } catch (\InvalidArgumentException $e) {
            return McpProtocol::error($id, self::INVALID_PARAMS, $e->getMessage());
        } catch (\Throwable $e) {
            //Un fallo al calcular no tumba el servidor ni escribe en el registro: se devuelve como error de la herramienta.
            return McpProtocol::toolResult($id, 'La herramienta «' . $name . '» falló al calcular (' . get_class($e) . ').', true);
        }
        return McpProtocol::toolResult($id, $output['data'], $output['isError']);
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
     * @param array<mixed> $arguments
     * @return array{data:mixed,isError:bool}
     */
    private static function routeExplain(array $arguments): array
    {
        $route = $arguments['route'] ?? null;
        $userType = $arguments['userType'] ?? null;
        if (!is_string($route) || trim($route) === '') {
            throw new \InvalidArgumentException('«route» es obligatorio y tiene que ser texto.');
        }
        if ($userType !== null && !is_int($userType) && !is_string($userType)) {
            throw new \InvalidArgumentException('«userType» tiene que ser un número o el nombre de un rol.');
        }
        $explained = Atlas::routeExplain($route, $userType);
        if ($explained === null) {
            return ['data' => "No hay ninguna ruta registrada con el nombre «{$route}».", 'isError' => true];
        }
        return ['data' => $explained, 'isError' => isset($explained['error'])];
    }

    /**
     * @param array<mixed> $arguments
     * @return array{data:mixed,isError:bool}
     */
    private static function symbolUses(array $arguments): array
    {
        $symbol = $arguments['symbol'] ?? null;
        if (!is_string($symbol) || trim($symbol) === '') {
            throw new \InvalidArgumentException('«symbol» es obligatorio y tiene que ser texto.');
        }
        $uses = Atlas::symbolUses($symbol);
        if ($uses === null) {
            return ['data' => "«{$symbol}» no es una constante de clase conocida (R1 solo indexa constantes de clase, como Clase\\Completa::NOMBRE).", 'isError' => true];
        }
        return ['data' => $uses, 'isError' => false];
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
}
