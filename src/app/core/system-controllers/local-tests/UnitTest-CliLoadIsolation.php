<?php

//Una suite o una tarea rota no tumba bin/cli: LoadFailures la salta, la anota y las puertas la cuentan como fallo.
//Los archivos rotos viven en un temporal propio, nunca en local-tests/ ni en Tasks/.

use PiecesPHP\Terminal\CliActions;
use PiecesPHP\Terminal\LoadFailures;

CliActions::make('unit-tests:core/cli-load-isolation', function ($args) {

    echoTerminal("\e[33m[TEST:CliLoadIsolation] Carga aislada de suites y tareas\e[39m");
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

    $base = sys_get_temp_dir() . '/zz-cli-load-isolation-' . bin2hex(random_bytes(4));
    $escribir = function (string $path, string $contenido): void {
        if (file_put_contents($path, $contenido) === false) {
            throw new \RuntimeException("No se puede escribir «{$path}».");
        }
    };

    try {
        //─── a · El arranque real ───────────────────────────────────────────────────────────────────────
        echoTerminal('[a] El arranque de este proceso');
        $check(LoadFailures::loadedCount(LoadFailures::TYPE_SUITE) > 0, 'a1 las suites se cargaron por LoadFailures', (string) LoadFailures::loadedCount(LoadFailures::TYPE_SUITE));
        $check(LoadFailures::loadedCount(LoadFailures::TYPE_TASK) > 0, 'a2 las tareas se cargaron por LoadFailures', (string) LoadFailures::loadedCount(LoadFailures::TYPE_TASK));
        $check(LoadFailures::all() === [], 'a3 ningún archivo sin cargar', (string) json_encode(LoadFailures::all()));
        echoTerminal(' ');

        //─── b · Archivos rotos ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[b] Archivos rotos');
        if (!mkdir($base, 0775, true)) {
            throw new \RuntimeException("No se puede crear «{$base}».");
        }
        $antes = count(LoadFailures::all());
        $suitesRotasAntes = count(LoadFailures::all(LoadFailures::TYPE_SUITE));
        $suitesAntes = LoadFailures::loadedCount(LoadFailures::TYPE_SUITE);

        $escribir("{$base}/sintaxis.php", "<?php\nesto no (\n");
        $cargado = LoadFailures::includeFile("{$base}/sintaxis.php", LoadFailures::TYPE_SUITE);
        $ultimo = LoadFailures::all()[count(LoadFailures::all()) - 1] ?? null;
        $check($cargado === false, 'b1 error de sintaxis → false, sin tumbar el proceso');
        $check(
            $ultimo !== null && $ultimo['type'] === 'suite' && $ultimo['class'] === 'ParseError' && $ultimo['line'] === 2 && str_ends_with($ultimo['file'], '/sintaxis.php'),
            'b2 se anota archivo, tipo, clase y línea',
            (string) json_encode($ultimo)
        );

        $escribir("{$base}/lanza.php", "<?php\nthrow new \\RuntimeException('zz-rota');\n");
        $cargado = LoadFailures::includeFile("{$base}/lanza.php", LoadFailures::TYPE_TASK);
        $tareas = LoadFailures::all(LoadFailures::TYPE_TASK);
        $ultimo = $tareas[count($tareas) - 1] ?? null;
        $check($cargado === false && $ultimo !== null && $ultimo['class'] === 'RuntimeException' && $ultimo['message'] === 'zz-rota', 'b3 una excepción al cargar se anota como tarea', (string) json_encode($ultimo));
        $check(count(LoadFailures::all()) === $antes + 2, 'b4 dos archivos rotos, dos anotaciones');
        $check(count(LoadFailures::all(LoadFailures::TYPE_SUITE)) === $suitesRotasAntes + 1, 'b5 el filtro por tipo separa suites de tareas');
        $check(
            $ultimo !== null && LoadFailures::describe($ultimo) === "{$ultimo['file']}: RuntimeException: zz-rota (línea 2)",
            'b6 describe(): «archivo: clase: mensaje (línea N)»'
        );
        echoTerminal(' ');

        //─── c · Archivo sano ───────────────────────────────────────────────────────────────────────────
        echoTerminal('[c] Archivo sano');
        $escribir("{$base}/sano.php", "<?php\n\$zzCliLoadIsolation = true;\n");
        $check(LoadFailures::includeFile("{$base}/sano.php", LoadFailures::TYPE_SUITE) === true, 'c1 un archivo sano → true');
        $check(LoadFailures::loadedCount(LoadFailures::TYPE_SUITE) === $suitesAntes + 1, 'c2 y cuenta como cargado');
        $check(count(LoadFailures::all()) === $antes + 2, 'c3 sin anotación nueva');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        foreach (['sintaxis.php', 'lanza.php', 'sano.php'] as $name) {
            if (is_file("{$base}/{$name}")) {
                //RETORNO-IGNORADO: limpieza del temporal propio de la prueba.
                @unlink("{$base}/{$name}");
            }
        }
        if (is_dir($base)) {
            //RETORNO-IGNORADO: limpieza del temporal propio de la prueba.
            @rmdir($base);
        }
        $check(!is_dir($base), 'z1 limpieza del temporal propio');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Una suite o una tarea rota no tumba bin/cli: LoadFailures la salta, la anota y las puertas la cuentan.')->setEffects([CliActions::EFFECT_FILES])->register();
