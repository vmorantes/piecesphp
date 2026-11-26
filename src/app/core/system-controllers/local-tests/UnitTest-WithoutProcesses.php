<?php

//H508: con exec(), shell_exec() y proc_open() desactivadas (como Hestia en producción), verify-integrity y clone-compare
//llegan a su resumen: lo que lanza procesos sale «[NO APLICA]» con su motivo, sin error fatal. En subprocesos. No escribe.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;
use Terminal\Shell;

CliActions::make('unit-tests:core/without-processes', function ($args) {

    echoTerminal("\e[33m[TEST:WithoutProcesses] Sin exec(), shell_exec() ni proc_open(): las tareas llegan a su resumen\e[39m");
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

    $src = rtrim(str_replace('\\', '/', basepath('')), '/');
    //La orden de bin/cli, con las tres funciones desactivadas en ese PHP.
    $run = function (string ...$command) use ($src): array {
        $proceso = proc_open([\PHP_BINARY, '-d', 'disable_functions=exec,shell_exec,proc_open', 'index.php', 'cli', '--local', ...array_values($command)], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tubos, $src);
        $salida = '';
        $estado = -1;
        if (is_resource($proceso)) {
            //RETORNO-IGNORADO: el subproceso no lee nada; cerrar su entrada solo evita que espere.
            fclose($tubos[0]);
            $salida = (string) stream_get_contents($tubos[1]) . (string) stream_get_contents($tubos[2]);
            //RETORNO-IGNORADO: tubos propios de la prueba, ya leídos.
            fclose($tubos[1]);
            //RETORNO-IGNORADO: tubos propios de la prueba, ya leídos.
            fclose($tubos[2]);
            $estado = proc_close($proceso);
        }
        return ['estado' => $estado, 'salida' => $salida];
    };

    try {
        echoTerminal('[a] Shell');
        $check(Shell::processes() === true, 'a1 CANARIO: en este PHP se pueden lanzar procesos (si no, las pruebas de abajo no prueban nada)');
        $sonda = $run('help');
        $check(str_contains($sonda['salida'], 'help') || $sonda['estado'] === 0, 'a2 el subproceso arranca con las funciones desactivadas', mb_substr($sonda['salida'], 0, 200));
        echoTerminal(' ');

        echoTerminal('[b] verify-integrity');
        $verify = $run('verify-integrity');
        $noAplica = substr_count($verify['salida'], Shell::NOT_APPLICABLE . ' ');
        $check(!str_contains($verify['salida'], 'Call to undefined function') && str_contains($verify['salida'], 'Verificando integridad del código, tarea finalizada'), 'b1 llega a su resumen, sin error fatal', mb_substr((string) preg_replace('/\s+/', ' ', substr($verify['salida'], -400)), 0, 300));
        $check($noAplica >= 30 && str_contains($verify['salida'], Shell::NOT_APPLICABLE . ' 70:') && str_contains($verify['salida'], Shell::NOT_APPLICABLE . ' 17:'), "b2 lo que lanza procesos sale «[NO APLICA]» con su número ({$noAplica}), también la 70 (por FormActionFields) y la 17");
        $check(!str_contains($verify['salida'], 'EN LA DISTRIBUCIÓN:') && str_contains($verify['salida'], 'shell_exec()'), 'b3 dice que faltan funciones de PHP, no que esto sea la distribución');
        $check($verify['estado'] !== 0, 'b4 y sale con un código distinto de 0: lo que no se hizo no avala nada (LEY 13)', "salida {$verify['estado']}");
        echoTerminal(' ');

        echoTerminal('[c] clone-compare');
        $compare = $run('clone-compare', 'ruta=' . sys_get_temp_dir());
        $check($compare['estado'] === 0 && str_contains($compare['salida'], Shell::NOT_APPLICABLE . ' clone-compare: ') && !str_contains($compare['salida'], 'Call to undefined function'), 'c1 no aplica, con su motivo y sin error fatal', mb_substr($compare['salida'], 0, 200));

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('H508: sin exec(), shell_exec() ni proc_open(), verify-integrity y clone-compare llegan a su resumen con «[NO APLICA]» y su motivo, sin error fatal.')->setEffects([CliActions::EFFECT_NONE])->register();
