<?php

//bin/mcp (ADR 0075, punto 5): el servidor MCP de solo lectura para quien desarrolla. Sobre DevServer::handle(), puro, y
//una pasada de punta a punta por bin/mcp con tres líneas. No escribe nada.
//@areas nucleo

use PiecesPHP\Core\Mcp\DevServer;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/mcp-dev', function ($args) {

    echoTerminal("\e[33m[TEST:McpDev] bin/mcp: el servidor MCP de solo lectura para quien desarrolla\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };
    $pedir = fn (string $method, array $params = [], int $id = 1): ?array => DevServer::handle(['jsonrpc' => '2.0', 'id' => $id, 'method' => $method, 'params' => $params]);
    $llamar = function (string $tool, array $arguments) use ($pedir): array {
        $respuesta = $pedir('tools/call', ['name' => $tool, 'arguments' => $arguments]);
        $resultado = is_array($respuesta['result'] ?? null) ? $respuesta['result'] : [];
        $texto = (string) ($resultado['content'][0]['text'] ?? '');
        return ['isError' => $resultado['isError'] ?? null, 'datos' => json_decode($texto, true), 'texto' => $texto, 'error' => $respuesta['error'] ?? null];
    };

    try {
        //─── a · El protocolo ───────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] El protocolo');
        $inicio = $pedir('initialize', ['protocolVersion' => '2025-03-26', 'capabilities' => [], 'clientInfo' => ['name' => 'zz', 'version' => '0']]);
        $check(($inicio['result']['protocolVersion'] ?? null) === '2025-03-26' && is_object($inicio['result']['capabilities']['tools'] ?? null) && ($inicio['result']['serverInfo']['name'] ?? null) === DevServer::SERVER_NAME, 'a1 initialize con una versión soportada: responde con esa');
        $inicio = $pedir('initialize', ['protocolVersion' => '1999-01-01']);
        $check(($inicio['result']['protocolVersion'] ?? null) === DevServer::PROTOCOL_VERSION, 'a2 initialize con una versión desconocida: responde con la suya', (string) json_encode($inicio));
        $check(DevServer::handle(['jsonrpc' => '2.0', 'method' => 'notifications/initialized']) === null, 'a3 la notificación (sin id) no se responde');
        $lista = $pedir('tools/list');
        $herramientas = $lista['result']['tools'] ?? null;
        $herramientas = is_array($herramientas) ? $herramientas : [];
        $nombres = array_column($herramientas, 'name');
        $conEsquema = array_filter($herramientas, fn (array $t): bool => ($t['inputSchema']['type'] ?? null) === 'object' && is_string($t['description'] ?? null) && $t['description'] !== '');
        $check($nombres === ['routes', 'permissions', 'openapi', 'docs_search'] && count($conEsquema) === 4, 'a4 tools/list: las cuatro, cada una con su descripción y su inputSchema', implode(', ', $nombres));
        $check(($pedir('zz/nada')['error']['code'] ?? null) === DevServer::METHOD_NOT_FOUND, 'a5 un método desconocido: -32601');
        $check(($pedir('tools/call', ['name' => 'zz-no-existe'])['error']['code'] ?? null) === DevServer::INVALID_PARAMS, 'a6 una herramienta desconocida: -32602');
        $check(($llamar('docs_search', [])['error']['code'] ?? null) === DevServer::INVALID_PARAMS, 'a7 docs_search sin query: -32602');
        $check(($llamar('permissions', ['userType' => 0, 'route' => 'admin'])['error']['code'] ?? null) === DevServer::INVALID_PARAMS, 'a8 permissions con los dos argumentos: -32602');
        $check((DevServer::parseError()['error']['code'] ?? null) === DevServer::PARSE_ERROR, 'a9 JSON roto: -32700');
        echoTerminal(' ');

        //─── b · Las herramientas ───────────────────────────────────────────────────────────────────────
        echoTerminal('[b] Cada herramienta, un acierto y un fallo');
        $rutas = $llamar('routes', ['filter' => 'admin']);
        $check($rutas['isError'] === false && in_array('admin', array_column($rutas['datos']['routes'] ?? [], 'name'), true), 'b1 routes con «admin»: está la ruta admin');
        $rutas = $llamar('routes', ['filter' => 'zz-ninguna-ruta-se-llama-asi']);
        $check($rutas['isError'] === false && ($rutas['datos']['total'] ?? null) === 0, 'b2 routes con un filtro que no casa: ninguna');
        $root = $llamar('permissions', ['userType' => 0]);
        $check($root['isError'] === false && ($root['datos']['userType'] ?? null) === 0 && in_array('system-status-health', $root['datos']['routes'] ?? [], true), 'b3 permissions de root: concede system-status-health');
        $ruta = $llamar('permissions', ['route' => 'system-status-health']);
        $check($ruta['isError'] === false && array_column($ruta['datos']['userTypes'] ?? [], 'code') === [0], 'b4 permissions de system-status-health: solo root', $ruta['texto']);
        $check($llamar('permissions', ['userType' => 9999])['isError'] === true, 'b5 permissions de un tipo que no existe: isError');
        $api = $llamar('openapi', ['path' => '/core/api/publications/publications/list/', 'method' => 'get']);
        $check($api['isError'] === false && is_array($api['datos']['/core/api/publications/publications/list/']['get'] ?? null), 'b6 openapi de una ruta que existe: su operación');
        $check($llamar('openapi', ['path' => '/zz/no-existe/'])['isError'] === true, 'b7 openapi de una que no existe: isError');
        $docs = $llamar('docs_search', ['query' => 'allowed_hosts']);
        $archivos = array_column($docs['datos']['results'] ?? [], 'file');
        $check($docs['isError'] === false && in_array('piecesphp/content/configuration.md', $archivos, true), 'b8 docs_search de «allowed_hosts»: sale configuration.md', implode(', ', $archivos));
        $docs = $llamar('docs_search', ['query' => 'zz-texto-que-no-esta-en-ninguna-guia']);
        $check($docs['isError'] === false && ($docs['datos']['total'] ?? null) === 0, 'b9 docs_search de un texto que no está: ninguno');
        echoTerminal(' ');

        //─── c · De punta a punta, por bin/mcp ─────────────────────────────────────────────────────────
        echoTerminal('[c] bin/mcp con tres líneas: dos respuestas y nada más');
        $entrada = implode("\n", [
            '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"zz","version":"0"}}}',
            '{"jsonrpc":"2.0","method":"notifications/initialized"}',
            '{"jsonrpc":"2.0","id":2,"method":"tools/list"}',
        ]) . "\n";
        $bin = dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/bin/mcp';
        $proceso = proc_open([$bin], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tubos);
        $salida = '';
        $errores = '';
        $codigo = -1;
        if (is_resource($proceso)) {
            //RETORNO-IGNORADO: si no se escribe, bin/mcp no responde y c1 lo dice.
            fwrite($tubos[0], $entrada);
            //RETORNO-IGNORADO: cerrar la entrada es la señal de fin para bin/mcp; un fallo lo verían c1 y c2.
            fclose($tubos[0]);
            $salida = (string) stream_get_contents($tubos[1]);
            $errores = (string) stream_get_contents($tubos[2]);
            //RETORNO-IGNORADO: tubos propios de la prueba, ya leídos.
            fclose($tubos[1]);
            //RETORNO-IGNORADO: tubos propios de la prueba, ya leídos.
            fclose($tubos[2]);
            $codigo = proc_close($proceso);
        }
        $lineas = array_values(array_filter(explode("\n", $salida), fn (string $l): bool => $l !== ''));
        $decodificadas = array_map(fn (string $l): mixed => json_decode($l, true), $lineas);
        $validas = array_filter($decodificadas, fn (mixed $d): bool => is_array($d) && ($d['jsonrpc'] ?? null) === '2.0');
        $check($codigo === 0 && count($lineas) === 2 && count($validas) === 2, 'c1 dos líneas JSON-RPC válidas en la salida', "salida {$codigo}, " . count($lineas) . ' línea(s): ' . mb_substr($salida, 0, 200));
        $check(($decodificadas[0]['id'] ?? null) === 1 && ($decodificadas[1]['id'] ?? null) === 2 && count($decodificadas[1]['result']['tools'] ?? []) === 4, 'c2 la de initialize y la de tools/list, en orden; la notificación, sin respuesta');
        $check($errores === '', 'c3 y nada en la salida de error', mb_substr($errores, 0, 200));

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('bin/mcp (ADR 0075): DevServer::handle() con initialize, la notificación, tools/list, las cuatro herramientas y los errores JSON-RPC; y bin/mcp de punta a punta con tres líneas.')->setEffects([CliActions::EFFECT_NONE])->register();
