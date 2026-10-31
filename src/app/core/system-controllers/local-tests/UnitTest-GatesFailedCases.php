<?php

//gates dice QUÉ casos de una suite cayeron (G18): GatesTask::failedCases(), que es pura, y runSuite() de punta a punta
//contra esta misma suite en modo demostración (una variable de entorno), que cae con colores a propósito.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\GatesTask;

CliActions::make('unit-tests:core/gates-failed-cases', function ($args) {

    //MODO DEMOSTRACIÓN: solo cuando la propia suite se llama por runSuite(). Cae con dos casos, con sus colores.
    if (getenv('PCSPHP_ZZ_GATES_FAILED_CASES_DEMO') === '1') {
        echoTerminal("   \e[32m[PASÓ]\e[39m zz-demo bien");
        echoTerminal("   \e[31m[FALLÓ]\e[39m zz-demo caso uno — con su detalle en la línea");
        echoTerminal("   \e[31m[FALLÓ]\e[39m zz-demo caso dos");
        echoTerminal("      - zz-demo detalle del dos");
        echoTerminal("\e[31m BALANCE FINAL: 1/3 PASADAS, 2 FALLIDAS \e[39m");
        return ['success' => false, 'message' => '1/3'];
    }

    echoTerminal("\e[33m[TEST:GatesFailedCases] Una suite que cae dice en gates qué casos cayeron\e[39m");
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
    //Los casos de esta suite no se pegan aquí con su marca: si no, la salida de esta suite engañaría a gates.
    $marca = '[' . 'FALLÓ' . ']';
    //Ni el balance: gates toma el PRIMER «BALANCE FINAL» de la salida, y un detalle no puede pasar por el de la suite.
    $muestra = fn (array $lineas): string => str_replace('BALANCE FINAL', 'BALANCE_FINAL', (string) json_encode($lineas, JSON_UNESCAPED_UNICODE));

    echoTerminal('[a] GatesTask::failedCases(), pura');
    $sinFallos = "   [PASÓ] uno\n   [PASÓ] dos\n BALANCE FINAL: 2/2 PASADAS";
    $check(GatesTask::failedCases($sinFallos) === [], 'a1 sin fallos, nada', $muestra(GatesTask::failedCases($sinFallos)));

    $dos = "   [PASÓ] bien\n   {$marca} uno — su detalle\n   {$marca} dos\n      - el detalle del dos\n   [PASÓ] otro\n BALANCE FINAL: 2/4 PASADAS";
    $esperado = ["{$marca} uno — su detalle", "{$marca} dos", '  - el detalle del dos'];
    $check(GatesTask::failedCases($dos) === $esperado, 'a2 DISCRIMINANTE: dos casos, en orden, el segundo con su línea «- …»', $muestra(GatesTask::failedCases($dos)));

    $doce = '';
    for ($i = 1; $i <= 12; $i++) {
        $doce .= "   {$marca} caso {$i}\n";
    }
    $doceCasos = GatesTask::failedCases($doce);
    $check(count($doceCasos) === 11 && $doceCasos[9] === "{$marca} caso 10" && $doceCasos[10] === '… y 2 más', 'a3 con 12, los 10 primeros y «… y 2 más»', $muestra($doceCasos));
    $check(GatesTask::failedCases("   {$marca} solo\n   - no es suyo: va tras otra línea\n") === ["{$marca} solo", '  - no es suyo: va tras otra línea'] && GatesTask::failedCases("   {$marca} solo\n   [PASÓ] siguiente\n") === ["{$marca} solo"],
        'a4 el detalle es solo la línea que lo sigue y empieza por «- »');
    $conColor = "   \e[31m{$marca}\e[39m con color";
    $check(GatesTask::failedCases($conColor) === ["\e[31m{$marca}\e[39m con color"], 'a5 failedCases() no limpia colores: confía en que runSuite() le da la salida plana (lo mira b)');

    echoTerminal(' ');
    echoTerminal('[b] runSuite() de punta a punta, contra esta suite en modo demostración');
    $check(putenv('PCSPHP_ZZ_GATES_FAILED_CASES_DEMO=1'), 'b0 el modo demostración se activa para el proceso hijo');
    try {
        $runSuite = new \ReflectionMethod(GatesTask::class, 'runSuite');
        $resultado = $runSuite->invoke(null, dirname(rtrim(str_replace('\\', '/', basepath('')), '/')), 'unit-tests:core/gates-failed-cases');
    } finally {
        //RETORNO-IGNORADO: quitar la variable solo deja el entorno como estaba; si fallara, nadie más la lee.
        putenv('PCSPHP_ZZ_GATES_FAILED_CASES_DEMO');
    }
    $casos = is_array($resultado) ? ($resultado['failedCases'] ?? null) : null;
    $check(is_array($resultado) && ($resultado['failures'] ?? 0) === 2 && ($resultado['balance'] ?? '') === 'BALANCE FINAL: 1/3 PASADAS, 2 FALLIDAS',
        'b1 la demostración cae con 2 fallos, y su balance llega entero', $muestra(is_array($resultado) ? $resultado : []));
    $check($casos === ["{$marca} zz-demo caso uno — con su detalle en la línea", "{$marca} zz-demo caso dos", '  - zz-demo detalle del dos'],
        'b2 DISCRIMINANTE: runSuite() devuelve los dos casos, sin colores (la salida que llega ya va limpia)', $muestra(is_array($casos) ? $casos : []));

    echoTerminal(' ');
    echoTerminal('[c] GatesTask::parseBalance(): la frase entera de cada forma que imprimen las suites, y los fallos de siempre');
    $formas = [
        'c1 «N/M PASADAS», la de una suite que pasa (y la de 17 suites también al caer)' => ["\n BALANCE FINAL: 3/3 PASADAS \n", 'BALANCE FINAL: 3/3 PASADAS', 0],
        'c2 DISCRIMINANTE: «N/M PASADAS, K FALLIDAS», entera' => ["\n BALANCE FINAL: 2/7 PASADAS, 5 FALLIDAS \n", 'BALANCE FINAL: 2/7 PASADAS, 5 FALLIDAS', 5],
        'c3 «N/M PASADAS, K OMITIDAS», la de mapper-finders' => ["\n BALANCE FINAL: 19/19 PASADAS, 2 OMITIDAS \n", 'BALANCE FINAL: 19/19 PASADAS, 2 OMITIDAS', 0],
        'c4 las dos coletillas a la vez, aunque hoy ninguna suite las junte' => [" BALANCE FINAL: 17/19 PASADAS, 2 FALLIDAS, 1 OMITIDAS", 'BALANCE FINAL: 17/19 PASADAS, 2 FALLIDAS, 1 OMITIDAS', 2],
        'c5 «Total: T | Pasaron: P | Fallaron: F»' => ["   Total: 8 | Pasaron: 6 | Fallaron: 2\n", 'Total: 8 | Pasaron: 6 | Fallaron: 2', 2],
    ];
    foreach ($formas as $nombre => [$salida, $frase, $fallos]) {
        $leido = GatesTask::parseBalance($salida);
        $check($leido === ['balance' => $frase, 'failures' => $fallos], $nombre, $muestra($leido ?? ['null']));
    }
    $check(GatesTask::parseBalance("   [PASÓ] algo\n sin balance\n") === null, 'c6 sin balance, null: la suite no dijo si pasó');

    return $balance();

})->setDescription('gates dice qué casos de una suite cayeron: GatesTask::failedCases() saca las líneas [FALLÓ] y su detalle, con un tope de 10, y runSuite() se la pasa ya sin colores; GatesTask::parseBalance() da el balance entero, con FALLIDAS y OMITIDAS.')->setEffects([CliActions::EFFECT_NONE])->register();
