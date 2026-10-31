<?php

//Un error cuya traza se purgó lo dice (G8): el lector lo marca (tracePurged) y la vista lo pinta; sin huella, como siempre.
//Sobre un registro de pruebas propio en un temporal (el lector admite su directorio), nunca el real.
//@areas src/app/classes/PiecesPHP/Logs/

use PiecesPHP\Core\CustomErrorsHandlers\GenericHandler;
use PiecesPHP\Logs\Controllers\HelperController;
use PiecesPHP\Logs\Controllers\LogsController;
use PiecesPHP\Logs\ErrorLogReader;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/logs-trace-purged', function ($args) {

    echoTerminal("\e[33m[TEST:LogsTracePurged] Un error cuya traza se purgó lo dice\e[39m");
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

    $directorio = sys_get_temp_dir() . '/zz-logs-trace-purged-' . bin2hex(random_bytes(4));
    $trazas = $directorio . '/' . GenericHandler::DIRECTORY_TRACES;
    $huellaViva = str_repeat('a', 64);
    $huellaPurgada = str_repeat('b', 64);
    $huellaPreviaPurgada = str_repeat('c', 64);
    $dia = date('d-m-Y');
    $entrada = fn (string $referencia, string $huella, array $previas = []): string => (string) json_encode([
        'day' => $dia, 'time' => '10:00:00', 'reference' => $referencia, 'level' => 'grave', 'type' => 'RuntimeException',
        'message' => "zz {$referencia}", 'code' => '0', 'file' => '/zz.php', 'line' => '1', 'traceFingerprint' => $huella, 'previous' => $previas,
    ]);
    $previa = fn (string $huella): array => ['type' => 'LogicException', 'message' => 'zz previa', 'code' => '0', 'file' => '/zz.php', 'line' => '2', 'traceFingerprint' => $huella];

    try {
        $preparado = mkdir($trazas, 0700, true)
            && file_put_contents("{$trazas}/{$huellaViva}.json", (string) json_encode([['file' => '/zz-traza-viva.php', 'line' => 7, 'function' => 'zzFuncion']])) !== false
            && file_put_contents($directorio . '/' . GenericHandler::FILE_ENTRIES, implode("\n", [
            $entrada('ERR-20261023-AAAAAA', $huellaViva),
            $entrada('ERR-20261023-BBBBBB', $huellaPurgada, [$previa($huellaPreviaPurgada), $previa($huellaViva), $previa('')]),
            $entrada('ERR-20261023-CCCCCC', ''),
        ]) . "\n") !== false;
        if (!$check($preparado, 'p0 el registro de pruebas se crea en un temporal propio', $directorio)) {
            return $balance();
        }
        $lector = new ErrorLogReader($directorio, $directorio . '/olds');
        $viva = $lector->findByReference('ERR-20261023-AAAAAA');
        $purgada = $lector->findByReference('ERR-20261023-BBBBBB');
        $sinHuella = $lector->findByReference('ERR-20261023-CCCCCC');

        echoTerminal('[a] El lector');
        $check(($viva['entry']['trace'][0]['function'] ?? null) === 'zzFuncion' && ($viva['entry']['tracePurged'] ?? null) === false, 'a1 CANARIO: con su archivo, la traza de siempre y tracePurged false');
        $check(($purgada['entry']['trace'] ?? null) === [] && ($purgada['entry']['tracePurged'] ?? null) === true, 'a2 DISCRIMINANTE: con huella y sin archivo, tracePurged true');
        $check(($sinHuella['entry']['tracePurged'] ?? null) === false && ($sinHuella['entry']['trace'] ?? null) === [], 'a3 sin huella, tracePurged false: no hubo traza que purgar');
        $previas = $purgada['entry']['previous'] ?? [];
        $check(($previas[0]['tracePurged'] ?? null) === true, 'a4 una excepción previa con huella y sin archivo: tracePurged true');
        $check(($previas[1]['tracePurged'] ?? null) === false && ($previas[1]['trace'][0]['function'] ?? null) === 'zzFuncion', 'a5 una previa con su archivo: su traza y tracePurged false');
        $check(($previas[2]['tracePurged'] ?? null) === false, 'a6 una previa sin huella: tracePurged false');

        echoTerminal(' ');
        echoTerminal('[b] La vista de una entrada abierta');
        $pintar = function (?array $encontrada): string {
            return (string) (new HelperController())->setInstanceViewDir(basepath('app/classes/PiecesPHP/Logs/Views/'))->render('errors', [
                'langGroup' => LogsController::LANG_GROUP, 'tabKey' => 'errors', 'tabURL' => 'https://zz.test/?tab=errors',
                'filters' => ['text' => '', 'type' => '', 'day' => '', 'level' => ''], 'reference' => 'zz', 'file' => null,
                'plainFiles' => array_keys(ErrorLogReader::PLAIN_FILES), 'plainLines' => ErrorLogReader::PLAIN_LINES, 'types' => [],
                'levels' => ErrorLogReader::LEVELS, 'pageSize' => ErrorLogReader::PAGE_SIZE, 'found' => $encontrada,
            ], false);
        };
        $aviso = __(LogsController::LANG_GROUP, 'La traza de este error ya se purgó.');
        $htmlPurgada = $pintar($purgada);
        $check(substr_count($htmlPurgada, 'data-error-trace-purged') === 2 && str_contains($htmlPurgada, escape_html($aviso)),
            'b1 DISCRIMINANTE: la purgada pinta el aviso, en la entrada y en su previa purgada (2)', (string) substr_count($htmlPurgada, 'data-error-trace-purged'));
        $htmlViva = $pintar($viva);
        $check(!str_contains($htmlViva, 'data-error-trace-purged') && str_contains($htmlViva, 'zzFuncion'), 'b2 la viva pinta su traza y ningún aviso');
        $check(!str_contains($pintar($sinHuella), 'data-error-trace-purged'), 'b3 sin huella, ni bloque ni aviso');
    } catch (\Throwable $e) {
        $check(false, 'e* el recorrido corre entero', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        //RETORNO-IGNORADO: es un temporal propio; z1 comprueba que no queda.
        remove_directory($directorio);
        $check(!file_exists($directorio), 'z1 el registro de pruebas no queda');
    }

    return $balance();

})->setDescription('Un error (o una excepción previa) con huella de traza y sin el archivo de la traza lo dice: el lector lo marca con tracePurged y la vista pinta «La traza de este error ya se purgó.»; sin huella, nada.')->setEffects([CliActions::EFFECT_NONE])->register();
