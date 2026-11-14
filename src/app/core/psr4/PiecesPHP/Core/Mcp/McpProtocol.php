<?php

/**
 * McpProtocol.php
 */

namespace PiecesPHP\Core\Mcp;

/**
 * McpProtocol - Lo común de los servidores MCP del framework (ADR 0075): JSON-RPC 2.0, la negociación de initialize,
 * el despacho de los métodos, el sobre de tools/call y la validación de argumentos.
 *
 * No sabe qué herramientas hay ni quién pregunta: quien lo usa le pasa la lista y la llamada. Lo usan DevServer (bin/mcp)
 * y PanelServer (la ruta del panel).
 *
 * @package     PiecesPHP\Core\Mcp
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class McpProtocol
{
    /**
     * El grupo de traducción de todo texto que puede llegar a un cliente MCP (lo inyecta McpLang).
     */
    const LANG_GROUP = 'mcp';

    const PROTOCOL_VERSION = '2025-06-18';
    const SUPPORTED_PROTOCOL_VERSIONS = ['2025-06-18', '2025-03-26', '2024-11-05'];

    const PARSE_ERROR = -32700;
    const INVALID_REQUEST = -32600;
    const METHOD_NOT_FOUND = -32601;
    const INVALID_PARAMS = -32602;

    /**
     * Las palabras de JSON Schema que admite un inputSchema (el subconjunto que usan las herramientas, nada más).
     */
    const SCHEMA_OBJECT_KEYS = ['type', 'properties', 'required', 'additionalProperties', 'description'];
    const SCHEMA_PROPERTY_KEYS = ['type', 'description', 'enum', 'minimum', 'maximum', 'maxLength', 'minLength', 'default'];
    const SCHEMA_PROPERTY_TYPES = ['string', 'integer', 'boolean'];

    /**
     * La respuesta a un mensaje JSON-RPC 2.0; null si es una notificación (sin id), que no se responde.
     *
     * @param array<mixed> $message
     * @param array{name:string,version:string} $serverInfo
     * @param callable():array<int,array<string,mixed>> $listTools La lista de tools/list.
     * @param callable(mixed,string,array<mixed>|null):array<string,mixed> $callTool La respuesta entera de tools/call: recibe el id, el nombre y los argumentos (null si no eran un objeto: badArguments()).
     * @return array<string,mixed>|null
     */
    public static function handle(array $message, array $serverInfo, callable $listTools, callable $callTool): ?array
    {
        $isNotification = !array_key_exists('id', $message);
        $id = $message['id'] ?? null;
        //JSON-RPC 2.0: el id es texto, entero o null. Un número desbordado llega como INF y no se podría responder.
        if (!$isNotification && !is_string($id) && !is_int($id) && $id !== null) {
            return self::error(null, self::INVALID_REQUEST, self::text('El id de una petición JSON-RPC 2.0 tiene que ser texto, un entero o null.'));
        }
        if (($message['jsonrpc'] ?? null) !== '2.0' || !is_string($message['method'] ?? null)) {
            return $isNotification ? null : self::error($id, self::INVALID_REQUEST, self::text('Petición JSON-RPC 2.0 no válida.'));
        }
        if ($isNotification) {
            return null;
        }
        $params = $message['params'] ?? [];
        $params = is_array($params) ? $params : [];
        if ($message['method'] === 'tools/call') {
            $name = $params['name'] ?? null;
            $arguments = $params['arguments'] ?? [];
            if (!is_string($name)) {
                return self::unknownTool($id, null);
            }
            //Quien llama comprueba antes si la herramienta existe: una desconocida responde igual con argumentos o sin ellos.
            return ($callTool)($id, $name, is_array($arguments) ? $arguments : null);
        }
        return match ($message['method']) {
            'initialize' => self::result($id, self::initialize($params, $serverInfo)),
            'ping' => self::result($id, (object) []),
            'tools/list' => self::result($id, ['tools' => ($listTools)()]),
            default => self::error($id, self::METHOD_NOT_FOUND, self::text('Método desconocido: ${method}.', ['${method}' => $message['method']])),
        };
    }

    /**
     * La respuesta a algo que no es JSON.
     *
     * @param string|null $message Ya traducido; sin él, «La línea no es JSON válido.»
     * @return array<string,mixed>
     */
    public static function parseError(?string $message = null): array
    {
        return self::error(null, self::PARSE_ERROR, $message ?? self::text('La línea no es JSON válido.'));
    }

    /**
     * Un texto de este grupo, traducido, con sus parámetros (`${nombre}`).
     *
     * @param string $key
     * @param array<string,string|int> $pairs
     * @return string
     */
    public static function text(string $key, array $pairs = []): string
    {
        return strtr(__(self::LANG_GROUP, $key), array_map('strval', $pairs));
    }

    /**
     * La respuesta a una herramienta que no existe (o que quien pregunta no puede usar: la misma, para no revelar qué hay).
     *
     * @param mixed $id
     * @param string|null $name
     * @return array<string,mixed>
     */
    public static function unknownTool(mixed $id, ?string $name): array
    {
        return self::error($id, self::INVALID_PARAMS, self::text('Herramienta desconocida: ${name}.', ['${name}' => $name ?? self::text('(sin nombre)')]));
    }

    /**
     * La respuesta a unos argumentos que no son un objeto.
     *
     * @param mixed $id
     * @return array<string,mixed>
     */
    public static function badArguments(mixed $id): array
    {
        return self::error($id, self::INVALID_PARAMS, self::text('Los argumentos tienen que ser un objeto.'));
    }

    /**
     * El resultado de tools/call: el dato en texto JSON y, si es un objeto, también en structuredContent. Lo que no es
     * UTF-8 se sustituye: si no, la respuesta entera no se podría codificar.
     *
     * @param mixed $id
     * @param mixed $data
     * @param bool $isError
     * @return array<string,mixed>
     * @throws \JsonException si el dato no se puede codificar (por ejemplo, un INF)
     */
    public static function toolResult(mixed $id, mixed $data, bool $isError): array
    {
        $text = json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_THROW_ON_ERROR);
        $result = ['content' => [['type' => 'text', 'text' => $text]]];
        //structuredContent es un objeto JSON en MCP: un texto o una lista no van ahí. Sale del texto ya saneado.
        if (is_array($data) && ($data === [] || !array_is_list($data))) {
            $result['structuredContent'] = $data === [] ? (object) [] : json_decode($text, false, 512, \JSON_THROW_ON_ERROR);
        }
        $result['isError'] = $isError;
        return self::result($id, $result);
    }

    /**
     * @param mixed $id
     * @param mixed $result
     * @return array<string,mixed>
     */
    public static function result(mixed $id, mixed $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    /**
     * @param mixed $id
     * @param int $code
     * @param string $message
     * @return array<string,mixed>
     */
    public static function error(mixed $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }

    /**
     * Por qué un inputSchema no está dentro del subconjunto admitido; null si lo está.
     *
     * @param array<mixed> $schema
     * @return string|null
     */
    public static function schemaProblem(array $schema): ?string
    {
        if (($schema['type'] ?? null) !== 'object') {
            return 'el esquema tiene que ser de tipo object';
        }
        $extra = array_diff(array_keys($schema), self::SCHEMA_OBJECT_KEYS);
        if ($extra !== []) {
            return 'palabras no admitidas en el esquema: ' . implode(', ', $extra);
        }
        if (($schema['additionalProperties'] ?? null) !== false) {
            return 'el esquema tiene que declarar additionalProperties false';
        }
        $properties = $schema['properties'] ?? [];
        if (!is_array($properties) || ($properties !== [] && array_is_list($properties))) {
            return 'properties tiene que ser un objeto';
        }
        foreach ($properties as $name => $property) {
            if (!is_string($name) || !is_array($property)) {
                return 'cada propiedad tiene que ser un objeto con nombre';
            }
            $extra = array_diff(array_keys($property), self::SCHEMA_PROPERTY_KEYS);
            if ($extra !== []) {
                return "palabras no admitidas en «{$name}»: " . implode(', ', $extra);
            }
            $type = $property['type'] ?? null;
            if (!is_string($type) || !in_array($type, self::SCHEMA_PROPERTY_TYPES, true)) {
                return "«{$name}» tiene que ser de tipo string, integer o boolean";
            }
            if (array_key_exists('enum', $property) && (!is_array($property['enum']) || $property['enum'] === [] || !array_is_list($property['enum']))) {
                return "el enum de «{$name}» tiene que ser una lista no vacía";
            }
            foreach (['minimum', 'maximum'] as $bound) {
                if (array_key_exists($bound, $property) && ($type !== 'integer' || !is_int($property[$bound]))) {
                    return "{$bound} solo vale con un entero en una propiedad integer («{$name}»)";
                }
            }
            foreach (['maxLength', 'minLength'] as $bound) {
                if (array_key_exists($bound, $property) && ($type !== 'string' || !is_int($property[$bound]) || $property[$bound] < 0)) {
                    return "{$bound} solo vale con un entero no negativo en una propiedad string («{$name}»)";
                }
            }
        }
        $required = $schema['required'] ?? [];
        if (!is_array($required) || !array_is_list($required)) {
            return 'required tiene que ser una lista';
        }
        foreach ($required as $name) {
            if (!is_string($name) || !array_key_exists($name, $properties)) {
                return 'required nombra una propiedad que no existe: ' . (is_string($name) ? $name : '(no es texto)');
            }
        }
        return null;
    }

    /**
     * Por qué unos argumentos no cumplen el esquema (que ya pasó schemaProblem()); null si lo cumplen. El mensaje nombra
     * el argumento.
     *
     * @param array<string,mixed> $schema
     * @param array<mixed> $arguments
     * @return string|null
     */
    public static function argumentsProblem(array $schema, array $arguments): ?string
    {
        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
        if ($arguments !== [] && array_is_list($arguments)) {
            return self::text('Los argumentos tienen que ser un objeto.');
        }
        foreach (array_keys($arguments) as $name) {
            if (!array_key_exists((string) $name, $properties)) {
                return self::text('Argumento no admitido: «${name}».', ['${name}' => (string) $name]);
            }
        }
        foreach (is_array($schema['required'] ?? null) ? $schema['required'] : [] as $name) {
            if (!array_key_exists($name, $arguments)) {
                return self::text('Falta el argumento «${name}».', ['${name}' => $name]);
            }
        }
        foreach ($arguments as $name => $value) {
            $property = $properties[$name];
            $type = $property['type'];
            $valid = match ($type) {
                'string' => is_string($value),
                'integer' => is_int($value),
                'boolean' => is_bool($value),
                default => false,
            };
            if (!$valid) {
                return self::text('«${name}» tiene que ser de tipo ${type}.', ['${name}' => $name, '${type}' => $type]);
            }
            if (array_key_exists('enum', $property) && !in_array($value, $property['enum'], true)) {
                return self::text('«${name}» tiene que ser uno de: ${values}.', ['${name}' => $name, '${values}' => implode(', ', array_map(fn($v): string => (string) json_encode($v), $property['enum']))]);
            }
            if (is_int($value) && array_key_exists('minimum', $property) && $value < $property['minimum']) {
                return self::text('«${name}» tiene que ser como mínimo ${n}.', ['${name}' => $name, '${n}' => $property['minimum']]);
            }
            if (is_int($value) && array_key_exists('maximum', $property) && $value > $property['maximum']) {
                return self::text('«${name}» tiene que ser como máximo ${n}.', ['${name}' => $name, '${n}' => $property['maximum']]);
            }
            if (is_string($value) && array_key_exists('maxLength', $property) && mb_strlen($value) > $property['maxLength']) {
                return self::text('«${name}» admite como mucho ${n} caracteres.', ['${name}' => $name, '${n}' => $property['maxLength']]);
            }
            if (is_string($value) && array_key_exists('minLength', $property) && mb_strlen($value) < $property['minLength']) {
                return self::text('«${name}» necesita al menos ${n} caracteres.', ['${name}' => $name, '${n}' => $property['minLength']]);
            }
        }
        return null;
    }

    /**
     * @param array<mixed> $params
     * @param array{name:string,version:string} $serverInfo
     * @return array<string,mixed>
     */
    private static function initialize(array $params, array $serverInfo): array
    {
        $requested = $params['protocolVersion'] ?? null;
        return [
            'protocolVersion' => is_string($requested) && in_array($requested, self::SUPPORTED_PROTOCOL_VERSIONS, true) ? $requested : self::PROTOCOL_VERSION,
            'capabilities' => ['tools' => (object) []],
            'serverInfo' => $serverInfo,
        ];
    }
}
