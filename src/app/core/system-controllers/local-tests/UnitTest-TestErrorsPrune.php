<?php

//ADR 0063: lo que escribe una prueba lleva su marca y se poda; un error real nunca. Todo en un temporal propio.
//@areas nucleo

use PiecesPHP\Core\CustomErrorsHandlers\GenericHandler;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/test-errors-prune', function ($args) {

    echoTerminal("\e[33m[TEST:TestErrorsPrune] Lo marcado por una prueba se poda; lo real, nunca\e[39m");
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

    $entornoPrevio = getenv(GenericHandler::TEST_RUN_ENV);
    $conCorrida = function (?string $run): void {
        //RETORNO-IGNORADO: si no cambia, a1 y c6 lo cazan: las marcas no salen como se esperan.
        putenv($run === null ? GenericHandler::TEST_RUN_ENV : GenericHandler::TEST_RUN_ENV . '=' . $run);
    };
    //Un proceso que no vive: el primero libre por encima del pid máximo habitual.
    $muerto = 4194000;
    while (posix_kill($muerto, 0) || posix_get_last_error() === 1) {
        $muerto++;
    }
    $mia = 't' . getmypid() . '-' . bin2hex(random_bytes(6));
    $otraViva = 't' . getmypid() . '-' . bin2hex(random_bytes(6));
    $deMuerta = 't' . $muerto . '-' . bin2hex(random_bytes(6));
    $directorio = sys_get_temp_dir() . '/zz-test-errors-prune-' . bin2hex(random_bytes(4));
    $archivo = $directorio . '/' . GenericHandler::FILE_ENTRIES;
    $lineas = function () use ($archivo): array {
        return array_values(array_filter(explode("\n", (string) @file_get_contents($archivo)), fn(string $l): bool => $l !== ''));
    };
    $grave = function (string $mensaje) use ($directorio): GenericHandler {
        $handler = new GenericHandler(new \RuntimeException($mensaje), $directorio);
        $handler->logging(false);
        return $handler;
    };
    $suave = fn(string $mensaje): string => GenericHandler::logSoft(new \ErrorException($mensaje), 'E_WARNING', [], $directorio);

    try {
        //─── a · La marca ────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] Lo que se escribe dentro de una corrida lleva su marca');
        $conCorrida($mia);
        $grave('zz grave de la prueba');
        $refSuaveMarcado = $suave('zz suave');
        $conCorrida(null);
        $real = $grave('zz grave REAL');
        $refSuaveReal = $suave('zz suave');
        $conCorrida($otraViva);
        $grave('zz grave de otra corrida viva');
        $conCorrida($deMuerta);
        $grave('zz grave de una corrida muerta');
        $conCorrida(null);

        $antes = $lineas();
        $marcas = array_map(fn(string $l) => json_decode($l, true)['context']['test'] ?? null, $antes);
        $check($marcas === [$mia, $mia, null, null, $otraViva, $deMuerta], 'a1 cada entrada lleva la marca de su corrida, y la real ninguna', json_encode($marcas) ?: '');
        $check($refSuaveMarcado !== $refSuaveReal, 'a2 el mismo suave, marcado y real, NO se agrupa: dos referencias (§4)');
        $lineaReal = '';
        foreach ($antes as $linea) {
            if (str_contains($linea, $real->reference())) {
                $lineaReal = $linea;
            }
        }
        $trazaMarcada = (string) (json_decode($antes[0], true)['traceFingerprint'] ?? '');
        $contadorMarcado = (string) (json_decode($antes[1], true)['softKey'] ?? '');
        $contadorReal = (string) (json_decode($antes[3], true)['softKey'] ?? '');
        $check(
            is_file("{$directorio}/" . GenericHandler::DIRECTORY_TRACES . "/{$trazaMarcada}.json") && is_file("{$directorio}/" . GenericHandler::DIRECTORY_SOFT . "/{$contadorMarcado}"),
            'a3 la traza y el contador del suave marcados existen antes de podar'
        );

        //─── b · La poda ─────────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] La poda quita lo de su corrida y lo de las muertas; nada más');
        $podado = GenericHandler::pruneTestRun($mia, $directorio);
        $despues = $lineas();
        $check($podado['entries'] === 3 && $podado['error'] === null, 'b1 poda 3: las dos de su corrida y la de la corrida muerta', json_encode($podado) ?: '');
        $check(in_array($lineaReal, $despues, true), 'b2 la entrada REAL sigue, byte a byte');
        $check(count($despues) === 3 && str_contains(implode("\n", $despues), $otraViva), 'b3 quedan la real, el suave real y la de la otra corrida VIVA');
        $check(!is_file("{$directorio}/" . GenericHandler::DIRECTORY_TRACES . "/{$trazaMarcada}.json"), 'b4 la traza que ya nadie cita se retira');
        $check(
            !is_file("{$directorio}/" . GenericHandler::DIRECTORY_SOFT . "/{$contadorMarcado}") && is_file("{$directorio}/" . GenericHandler::DIRECTORY_SOFT . "/{$contadorReal}"),
            'b5 el contador del suave marcado se retira y el del real se queda'
        );
        $otraVez = GenericHandler::pruneTestRun($mia, $directorio);
        $check($otraVez['entries'] === 0 && $lineas() === $despues, 'b6 podar otra vez no toca nada');

        //─── c · La cabecera solo vale en local y por web ───────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] La cabecera: solo en una petición web de una instalación local');
        $local = fn(): bool => true;
        $noLocal = fn(): bool => false;
        $check(GenericHandler::testRunFrom(false, $mia, false, $local) === $mia, 'c1 por web y en local, la cabecera marca');
        $check(GenericHandler::testRunFrom(false, $mia, false, $noLocal) === null, 'c2 por web y FUERA de local, se ignora: un error real no se puede esconder');
        $check(GenericHandler::testRunFrom(false, $mia, true, $local) === null, 'c3 en la terminal la cabecera no cuenta: solo el entorno');
        $check(GenericHandler::testRunFrom(false, 'zz-no-es-un-id', false, $local) === null, 'c4 un valor sin la forma de un id se ignora');
        $check(GenericHandler::testRunFrom($otraViva, $mia, false, $noLocal) === $otraViva, 'c5 el entorno manda sobre la cabecera');
        $conCorrida($mia);
        $check(\Terminal\TestRun::withHeader(['A: b']) === ['A: b', GenericHandler::TEST_RUN_HEADER . ': ' . $mia], 'c6 las peticiones de las suites llevan la cabecera de la corrida en curso');
        $conCorrida(null);
        $check(\Terminal\TestRun::withHeader(['A: b']) === ['A: b'], 'c7 y sin corrida, ninguna');

        //─── d · Los dos registros planos ───────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[d] error.plain.log y error.unique.message.log: lo marcado se poda; lo real, nunca');
        $plano = $directorio . '/' . GenericHandler::FILE_PLAIN;
        $unico = $directorio . '/' . GenericHandler::FILE_UNIQUE;
        //Lo que ya había, real (el suave real de [a]): tiene que quedar igual.
        $planoPrevio = (string) @file_get_contents($plano);
        $conCorrida($mia);
        //Con un salto en el mensaje: la poda quita el bloque entero, no solo su cabecera.
        $planoMarcado = new GenericHandler(new \RuntimeException("zz plano de la prueba\nsu segunda línea"), $directorio);
        $planoMarcado->logging(true);
        $conCorrida(null);
        $planoReal = new GenericHandler(new \RuntimeException('zz plano REAL'), $directorio);
        $planoReal->logging(true);
        $conCorrida($mia);
        (new GenericHandler(new \RuntimeException('zz plano de la prueba, después'), $directorio))->logging(true);
        //La misma firma, primero marcada y después real: nacen en la misma línea.
        foreach ([$mia, null] as $corrida) {
            $conCorrida($corrida);
            (new GenericHandler(new \RuntimeException('zz único'), $directorio))->loggingUniqueMessage();
        }
        $conCorrida(null);
        $planoAntes = (string) @file_get_contents($plano);
        $unicoAntes = (string) @file_get_contents($unico);
        $lineaPlanaReal = '';
        foreach (explode("\n", $planoAntes) as $linea) {
            if (str_contains($linea, '[ref ' . $planoReal->reference() . ']')) {
                $lineaPlanaReal = $linea . "\n";
            }
        }
        $bloqueUnicoReal = implode("\n", array_slice(explode("\n", $unicoAntes), 5));
        $check(
            str_contains($planoAntes, '[ref ' . $planoMarcado->reference() . '] [test ' . $mia . '] ') && !str_contains($lineaPlanaReal, '[test '),
            'd1 la línea plana de la prueba lleva [test <corrida>] tras [ref X]; la real, no'
        );
        $cabecerasUnico = preg_match_all('/^\[[0-9]{4}-/m', $unicoAntes);
        $check(
            $cabecerasUnico === 2 && str_contains($unicoAntes, "zz único in ") && str_contains(explode("\n", $unicoAntes)[0], " [test {$mia}]") && !str_contains($bloqueUnicoReal, '[test '),
            'd2 un error REAL con la misma firma que uno de prueba sí se escribe en el único: la marca va dentro de la firma',
            "cabeceras {$cabecerasUnico}"
        );
        $podaPlana = GenericHandler::pruneTestRun($mia, $directorio);
        $check(
            $podaPlana['plain'] === 2 && $podaPlana['unique'] === 1 && $podaPlana['error'] === null,
            'd3 poda las 2 líneas planas y el bloque único de la corrida',
            json_encode($podaPlana) ?: ''
        );
        $check(
            $lineaPlanaReal !== '' && (string) @file_get_contents($plano) === $planoPrevio . $lineaPlanaReal && (string) @file_get_contents($unico) === $bloqueUnicoReal,
            'd4 lo REAL queda byte a byte, y del mensaje con salto no queda nada'
        );
    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        $conCorrida(is_string($entornoPrevio) ? $entornoPrevio : null);
        foreach (array_merge(glob($directorio . '/*/*/*') ?: [], glob($directorio . '/*/*') ?: [], glob($directorio . '/*') ?: []) as $ruta) {
            //RETORNO-IGNORADO: un temporal propio; lo que no se borre se queda en sys_get_temp_dir().
            is_dir($ruta) ? @rmdir($ruta) : @unlink($ruta);
        }
        //RETORNO-IGNORADO: un temporal propio; lo que no se borre se queda en sys_get_temp_dir().
        @rmdir($directorio);
    }

    return $balance();

})->setDescription('Lo que escribe una prueba en el registro de errores se poda al terminar; lo real, nunca (ADR 0063)')->setEffects([CliActions::EFFECT_FILES])->register();
