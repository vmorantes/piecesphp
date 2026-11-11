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
        echoTerminal(' ');

        echoTerminal('[c] La vista del prototipo (vistas-referencia.md §3.24): una fila por error y su «Detalle» en un acordeón');
        $pintarLista = function (array $filtros, ?array $resultado) use ($lector): string {
            return (string) (new HelperController())->setInstanceViewDir(basepath('app/classes/PiecesPHP/Logs/Views/'))->render('errors', [
                'langGroup' => LogsController::LANG_GROUP, 'tabKey' => 'errors', 'tabURL' => 'https://zz.test/?tab=errors',
                'filters' => $filtros + ['text' => '', 'type' => '', 'day' => '', 'level' => ''], 'reference' => '', 'file' => null,
                'plainFiles' => array_keys(ErrorLogReader::PLAIN_FILES), 'plainLines' => ErrorLogReader::PLAIN_LINES, 'types' => [],
                'levels' => ErrorLogReader::LEVELS, 'pageSize' => ErrorLogReader::PAGE_SIZE, 'result' => $resultado ?? $lector->page($filtros, 1),
            ], false);
        };
        $dom = function (string $html): \DOMXPath {
            $documento = new \DOMDocument();
            @$documento->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            return new \DOMXPath($documento);
        };
        $grupo = LogsController::LANG_GROUP;
        $x = $dom($htmlViva);
        $fila = '//*[@data-logs-errors][contains(@class,"ui divided relaxed list")]/*[contains(concat(" ",@class," ")," item ")][@data-error-reference="ERR-20261023-AAAAAA"]';
        $check($x->query($fila)?->length === 1, 'c1 DISCRIMINANTE: el error es una fila (item) de una lista, no el título de un acordeón');
        $titulo = $x->query("{$fila}//*[@data-logs-error-detail][contains(@class,\"ui styled fluid accordion\")]/*[contains(@class,\"title\")]")?->item(0);
        $contenido = $x->query("{$fila}//*[@data-logs-error-detail]/*[contains(@class,\"content\")]")?->item(0);
        $check($titulo instanceof \DOMElement && trim($titulo->textContent) === __($grupo, 'Detalle') && str_contains($titulo->getAttribute('class'), 'active') && $contenido instanceof \DOMElement && str_contains($contenido->getAttribute('class'), 'active') && str_contains($contenido->textContent, 'zzFuncion') && str_contains($contenido->textContent, __($grupo, 'Mensaje completo y ruta')), 'c2 DISCRIMINANTE: debajo, el acordeón «Detalle», abierto al buscar por referencia y con todo lo de antes (traza y mensaje completo)');
        $hora = $x->query("{$fila}//time[@datetime]")?->item(0);
        $esperada = \DateTime::createFromFormat('!d-m-Y H:i:s', $dia . ' 10:00:00');
        $check($hora instanceof \DOMElement && $esperada !== false && $hora->getAttribute('datetime') === $esperada->format('Y-m-d\TH:i:s') && trim($hora->textContent) === '10:00:00', 'c3 la hora, en <time datetime>');
        $copiar = $x->query("{$fila}//button[@data-logs-copy=\"ERR-20261023-AAAAAA\"]")?->item(0);
        $check($copiar instanceof \DOMElement && str_contains($copiar->getAttribute('class'), 'ui tertiary icon button') && $copiar->getAttribute('aria-label') === __($grupo, 'Copiar referencia') && $copiar->getAttribute('data-tooltip') === __($grupo, 'Copiar referencia') && $copiar->getAttribute('data-copied-tooltip') === __($grupo, 'Copiado') && $x->query("{$fila}//button[@data-logs-copy]/i[contains(concat(\" \",@class,\" \"),\" copy \")]")?->length === 1, 'c4 DISCRIMINANTE: copiar, botón solo icono sin borde (tertiary), con aria-label, tooltip y «Copiado»');
        $check(!str_contains($htmlViva, 'outline icon'), 'c5 ningún icono outline en la entrada');
        $htmlLista = $pintarLista([], null);
        $xl = $dom($htmlLista);
        $dias = $xl->query('//h2[@data-logs-errors-day]');
        $primerDia = $dias?->item(0);
        $check($dias?->length === 1 && $primerDia instanceof \DOMElement && trim($primerDia->textContent) === $dia && $xl->query('//*[@data-logs-error-detail]/*[contains(@class,"title")][contains(@class,"active")]')?->length === 0 && $xl->query('//*[@data-logs-errors]/*[@data-error-reference]')?->length === 3, 'c6 en la lista, el día como h2 y los «Detalle» cerrados (3 filas)');
        $vacio = ['entries' => [], 'page' => 1, 'hasMore' => false];
        $sinFiltros = $dom($pintarLista([], $vacio));
        $estado = $sinFiltros->query('//*[contains(@class,"ui placeholder segment")]//*[contains(@class,"icon header")]')?->item(0);
        $check($estado instanceof \DOMElement && str_contains($estado->textContent, __($grupo, 'No hay errores anotados.')) && $sinFiltros->query('//*[contains(@class,"ui placeholder segment")]//i[contains(@class,"check circle icon")]')?->length === 1, 'c7 DISCRIMINANTE: sin filtros y sin errores, el estado vacío «No hay errores anotados.» con check circle');
        $conFiltros = $pintarLista(['text' => 'zz-nada'], $vacio);
        $check(!str_contains($conFiltros, 'ui placeholder segment') && str_contains($conFiltros, escape_html(__($grupo, 'No hay errores que mostrar con estos filtros.'))), 'c8 con filtros y sin resultados, el mensaje de siempre');
    } catch (\Throwable $e) {
        $check(false, 'e* el recorrido corre entero', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        //RETORNO-IGNORADO: es un temporal propio; z1 comprueba que no queda.
        remove_directory($directorio);
        $check(!file_exists($directorio), 'z1 el registro de pruebas no queda');
    }

    return $balance();

})->setDescription('Un error (o una excepción previa) con huella de traza y sin el archivo de la traza lo dice: el lector lo marca con tracePurged y la vista pinta «La traza de este error ya se purgó.»; sin huella, nada.')->setEffects([CliActions::EFFECT_NONE])->register();
