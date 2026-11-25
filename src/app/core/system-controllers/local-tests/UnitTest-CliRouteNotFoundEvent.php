<?php

//Una acción de terminal desconocida dispara EVENT_CLI_ROUTE_NOT_FOUND si hay escuchador; si no, la lista y 1. Universo: dos
//bin/cli hijos; el escuchador lo registra este archivo al arrancar, solo con PCSPHP_TEST_CLI_ROUTE_NOT_FOUND=1.
//@areas nucleo

use PiecesPHP\Core\BaseEventDispatcher;
use PiecesPHP\Terminal\CliActions;

if (getenv('PCSPHP_TEST_CLI_ROUTE_NOT_FOUND') === '1' && \PiecesPHP\TerminalData::getInstance()->isTerminal() === true) {
    BaseEventDispatcher::defaultListen(BaseEventDispatcher::EVENT_CLI_ROUTE_NOT_FOUND_NAME, function ($actionName) {
        echo 'zz-escuchador-cli-route-not-found:' . (is_string($actionName) ? $actionName : gettype($actionName)) . "\n";
    });
}

CliActions::make('unit-tests:core/cli-route-not-found-event', function ($args) {

    echoTerminal("\e[33m[TEST:CliRouteNotFoundEvent] Una acción desconocida dispara su evento si alguien lo escucha\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
        return $condition;
    };
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal(' ');
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];
    };

    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    $accion = 'zz-accion-inexistente-' . bin2hex(random_bytes(3));
    $lanzar = function (bool $conEscuchador) use ($proyecto, $accion): array {
        $comando = ($conEscuchador ? 'PCSPHP_TEST_CLI_ROUTE_NOT_FOUND=1 ' : 'PCSPHP_TEST_CLI_ROUTE_NOT_FOUND= ') . escapeshellarg("{$proyecto}/bin/cli") . ' ' . escapeshellarg($accion);
        $salida = [];
        $codigo = -1;
        //RETORNO-IGNORADO: la salida y el código llegan por referencia.
        exec($comando . ' 2>&1', $salida, $codigo);
        return ['codigo' => $codigo, 'texto' => implode("\n", $salida)];
    };
    $corto = fn (string $texto): string => mb_substr(preg_replace('/\s+/', ' ', $texto) ?? '', 0, 200);

    $con = $lanzar(true);
    $check(str_contains($con['texto'], 'zz-escuchador-cli-route-not-found'), 'e1 con escuchador, la acción desconocida lo dispara', $corto($con['texto']));
    $check(!str_contains($con['texto'], 'Acción no reconocida'), 'e2 y no imprime «Acción no reconocida»', $corto($con['texto']));
    $check($con['codigo'] === 0, 'e3 y sale con 0: lo desconocido lo decide el escuchador', "salió con {$con['codigo']}");
    $check(preg_match('/^zz-escuchador-cli-route-not-found:' . preg_quote($accion, '/') . '$/m', $con['texto']) === 1, 'e4 y el escuchador recibe el nombre exacto de la acción', $corto($con['texto']));

    $sin = $lanzar(false);
    $check(str_contains($sin['texto'], "Acción no reconocida: {$accion}"), 's1 sin escuchador, «Acción no reconocida» y la lista, como siempre', $corto($sin['texto']));
    $check(!str_contains($sin['texto'], 'zz-escuchador-cli-route-not-found'), 's2 y nada del escuchador');
    $check($sin['codigo'] === 1, 's3 y sale con 1', "salió con {$sin['codigo']}");

    return $balance();

})->setDescription('Una acción de terminal desconocida dispara EVENT_CLI_ROUTE_NOT_FOUND si tiene escuchadores (sin la lista y con 0); sin ellos, «Acción no reconocida» y 1. Lanza dos bin/cli hijos.')->setEffects([CliActions::EFFECT_NONE])->register();
