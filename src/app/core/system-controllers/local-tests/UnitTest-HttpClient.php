<?php

//Solo lo que necesita red. Lo que no —cómo se construye la petición— vive en
//`core/http-client-request-build`, que sí entra en `gates`. Ver T130.

use PiecesPHP\Core\Http\HttpClient;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/http-client', function ($args) {

    echoTerminal("\e[33m[TEST:HttpClient] La petición sale, la respuesta vuelve, y el tiempo se honra\e[39m");
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

    //Reservado por la RFC 2606. NO un token de webhook.site: caducan, y al caducar mienten.
    $destino = 'https://example.com';

    //─── 1/2 · La respuesta vuelve ─────────────────────────────────────────────────────
    echoTerminal('[1/2] Un GET real trae estado y cuerpo');

    $cliente = new HttpClient($destino);
    $cliente->setDefaultRequestHeaders(['Accept' => 'text/html']);
    $cliente->timeout(15);
    $cuerpo = $cliente->request('', 'GET', ['q' => 'prueba']);
    $estado = $cliente->getResponseStatus();

    $check($estado !== null, 'la respuesta trae código de estado', var_export($estado, true));
    $check(is_string($cuerpo) && mb_strlen($cuerpo) > 0, 'y un cuerpo no vacío',
        is_string($cuerpo) ? mb_strlen($cuerpo) . ' bytes' : var_export($cuerpo, true));
    echoTerminal('   URI: ' . $cliente->getRequestURI());
    echoTerminal('   Estado: ' . ($estado ?? 'sin respuesta'));
    echoTerminal(' ');

    //─── 2/2 · El tiempo de espera se honra ────────────────────────────────────────────
    echoTerminal('[2/2] El tiempo de espera corta la petición');

    //OJO AL ORDEN: `HttpClient::$baseURL` es ESTÁTICA, así que construir este cliente
    //reescribe la base del anterior. Todo lo que use `$cliente` tiene que estar hecho ya.
    $lento = new HttpClient('http://10.255.255.1');
    $lento->timeout(2);

    $inicio = microtime(true);
    $respuesta = @$lento->request('', 'GET');
    $duracion = microtime(true) - $inicio;

    $check($duracion >= 2, 'espera lo declarado antes de rendirse', round($duracion, 2) . ' s');
    //Sin la cota superior, un tiempo de espera ignorado del todo también «pasaría».
    $check($duracion < 6, 'y no se pasa de largo', round($duracion, 2) . ' s');
    $check($respuesta === false || $respuesta === '', 'y no devuelve un cuerpo inventado',
        var_export($respuesta, true));

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('HttpClient sale a la red de verdad: la respuesta vuelve y el tiempo de espera se honra.')->setEffects([CliActions::EFFECT_NETWORK])->register();
