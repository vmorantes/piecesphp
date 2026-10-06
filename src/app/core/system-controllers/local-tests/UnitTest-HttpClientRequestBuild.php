<?php

//Sin red: `request()` escribe URI, cuerpo y cabeceras ANTES de su `file_get_contents()`,
//y el destino es un `data://`. Ver T130.

use PiecesPHP\Core\Http\HttpClient;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/http-client-request-build', function ($args) {

    echoTerminal("\e[33m[TEST:HttpClientRequestBuild] Cómo se construye la petición, sin salir a la red\e[39m");
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

    //`data://` lo sirve el propio PHP: no hay socket, ni DNS, ni puerto que abrir.
    $destino = 'data://text/plain,OK';

    $cliente = new HttpClient($destino);
    $cliente->setDefaultRequestHeaders([
        'Authorization' => 'Bearer TOKEN_POR_DEFECTO',
        'Accept' => 'application/json',
    ]);

    //─── 1/4 · El GET pone lo suyo en la URI, no en el cuerpo ──────────────────────────
    echoTerminal('[1/5] GET: los parámetros van a la URI y el cuerpo queda vacío');

    $cliente->request('', 'GET', ['search' => 'test@example.com', 'limit' => 1]);
    $uri = $cliente->getRequestURI();

    $check(str_contains($uri, 'search=test%40example.com'), 'la arroba viaja codificada en la URI', $uri);
    $check(str_contains($uri, 'limit=1'), 'y el segundo parámetro también', $uri);
    //Sin esto, un GET podría llevar los datos DOS veces: en la URI y en el cuerpo.
    $check($cliente->getRequestBody() === '', 'un GET no lleva cuerpo', var_export($cliente->getRequestBody(), true));
    echoTerminal(' ');

    //─── 2/4 · El POST codifica según el Content-Type que se le declare ────────────────
    echoTerminal('[2/5] POST: el Content-Type decide cómo se codifica el cuerpo');

    $cliente->request('', 'POST', ['name' => 'Test Item', 'value' => 123], ['Content-Type' => 'application/json']);
    $cuerpoJson = $cliente->getRequestBody();

    $check(json_decode(is_string($cuerpoJson) ? $cuerpoJson : '', true) !== null,
        'con application/json el cuerpo es JSON válido', is_string($cuerpoJson) ? $cuerpoJson : gettype($cuerpoJson));
    $check(is_string($cuerpoJson) && str_contains($cuerpoJson, '"name":"Test Item"'),
        'y conserva los valores que se le pasaron');

    //El otro camino del `switch`: sin Content-Type declarado manda el de por defecto.
    $cliente->request('', 'POST', ['name' => 'Test Item']);
    $cuerpoForm = $cliente->getRequestBody();
    $check(is_string($cuerpoForm) && str_contains($cuerpoForm, 'name=Test+Item'),
        'sin declararlo, el cuerpo sale urlencoded', is_string($cuerpoForm) ? $cuerpoForm : gettype($cuerpoForm));
    echoTerminal(' ');

    //─── 3/4 · override_defaults = true: las de por defecto se pierden ─────────────────
    echoTerminal('[3/5] override_defaults = true reemplaza, no fusiona');

    $cliente->request('', 'GET', [], ['Accept' => 'text/plain'], true, true);
    $cabeceras = $cliente->getRequestHeaders();

    $check(($cabeceras['Accept'] ?? '') === 'text/plain', 'la cabecera propia manda',
        (string) ($cabeceras['Accept'] ?? 'ausente'));
    $check(!isset($cabeceras['Authorization']), 'y la de por defecto desaparece, que es lo declarado',
        (string) ($cabeceras['Authorization'] ?? 'ausente'));
    echoTerminal(' ');

    //─── 4/4 · override_defaults = false: fusiona ──────────────────────────────────────
    echoTerminal('[4/5] override_defaults = false conserva las de por defecto');

    $cliente->request('', 'POST', ['hi' => 1], ['Content-Type' => 'application/json'], true, false);
    $cabeceras = $cliente->getRequestHeaders();

    $check(($cabeceras['Content-Type'] ?? '') === 'application/json', 'la cabecera propia entra',
        (string) ($cabeceras['Content-Type'] ?? 'ausente'));
    $check(($cabeceras['Authorization'] ?? '') === 'Bearer TOKEN_POR_DEFECTO',
        'y la de por defecto sobrevive a la fusión',
        (string) ($cabeceras['Authorization'] ?? 'ausente'));

    echoTerminal(' ');

    //─── 5/5 · Cada cliente conserva SU URL base ───────────────────────────────────────
    echoTerminal('[5/5] Dos clientes a la vez no se pisan la URL base');

    $primero = new HttpClient('data://text/plain,PRIMERO');
    //Construir el segundo es el gesto que rompia: con `$baseURL` estatica, este constructor
    //reescribia la base del primero. Ver T133.
    $segundo = new HttpClient('data://text/plain,SEGUNDO');

    $segundo->request('', 'GET');
    $uriSegundo = $segundo->getRequestURI();
    $primero->request('', 'GET');
    $uriPrimero = $primero->getRequestURI();

    $check(str_contains($uriPrimero, 'PRIMERO'),
        'el primero sigue apuntando a donde se le dijo', $uriPrimero);
    $check(str_contains($uriSegundo, 'SEGUNDO'),
        'y el segundo a la suya', $uriSegundo);
    $check($uriPrimero !== $uriSegundo, 'que no son la misma', $uriPrimero . ' vs ' . $uriSegundo);

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('HttpClient construye la URI, el cuerpo y las cabeceras como declara, sin salir a la red.')->setEffects([CliActions::EFFECT_NONE])->register();
