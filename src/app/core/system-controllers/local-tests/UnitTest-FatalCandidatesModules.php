<?php

//Los posibles 500 de fatal-candidates fuera del núcleo: adaptadores de IA sin cliente, glob() que da false y los scripts sin $argv.
//Sin red ni gasto: el cliente se anula en proceso y nada llega a OpenAI, Mistral ni Groq.
//@areas nucleo
//@areas src/app/classes/API/

use API\Adapters\MistralHandlerAdapter;
use API\Adapters\OpenAIHandlerAdapter;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\FixWebmDurationTask;
use Terminal\Tasks\ScanMissingLangTask;

CliActions::make('unit-tests:core/fatal-candidates-modules', function ($args) {

    echoTerminal("\e[33m[TEST:FatalCandidatesModules] Los posibles 500 fuera del núcleo no revientan\e[39m");
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

    //Lo que lanza la llamada: la clase y el mensaje. Un \Error (llamar un método sobre null) es el 500 que se busca evitar.
    $lanza = function (callable $llamada): array {
        try {
            $llamada();
            return ['', ''];
        } catch (\Throwable $e) {
            return [get_class($e), $e->getMessage()];
        }
    };
    $anula = function (object $objeto, string $propiedad): void {
        $reflexion = new \ReflectionProperty($objeto, $propiedad);
        $reflexion->setValue($objeto, null);
    };
    $lee = function (object $objeto, string $propiedad): mixed {
        $reflexion = new \ReflectionProperty($objeto, $propiedad);
        return $reflexion->getValue($objeto);
    };

    //a. Adaptadores de IA: el constructor deja el cliente sin pedir nada a la red; sin él, una excepción controlada.
    $openAI = new OpenAIHandlerAdapter('zz-sin-clave', '-');
    $check($lee($openAI, 'openAIClient') instanceof \OpenAI\Client, 'a1 CANARIO: OpenAI nace con su cliente, sin llamar a la red');
    $anula($openAI, 'openAIClient');
    [$clase, $mensaje] = $lanza(fn() => $openAI->askToChat('zz'));
    $check($clase === \RuntimeException::class, 'a2 OpenAI askToChat sin cliente lanza una RuntimeException, no un \Error', "{$clase}: {$mensaje}");
    [$clase, $mensaje] = $lanza(fn() => $openAI->askToAssistent('zz'));
    $check($clase === \RuntimeException::class, 'a3 OpenAI askToAssistent sin cliente lanza una RuntimeException, no un \Error', "{$clase}: {$mensaje}");

    $mistral = new MistralHandlerAdapter('zz-sin-clave');
    $check($lee($mistral, 'httpClient') instanceof \PiecesPHP\Core\Http\HttpClient, 'a4 CANARIO: Mistral nace con su cliente, sin llamar a la red');
    $anula($mistral, 'httpClient');
    [$clase, $mensaje] = $lanza(fn() => $mistral->translate(['k' => 'zz']));
    $check($clase === \RuntimeException::class, 'a5 Mistral translate sin cliente lanza una RuntimeException, no un \Error', "{$clase}: {$mensaje}");

    //b. glob() da false con un patrón de más de 4096 caracteres; se calla solo su Warning para que el false llegue al foreach.
    //Se cuentan los avisos de foreach(): FixWebmDuration captura la excepción por dentro y no se vería de otro modo.
    $largo = str_repeat('a', 5000);
    $avisosForeach = 0;
    $conGlobCallado = function (callable $llamada) use ($lanza, &$avisosForeach): array {
        $avisosForeach = 0;
        $anterior = null;
        $anterior = set_error_handler(function (int $type, string $message, string $file = '', int $line = 0) use (&$anterior, &$avisosForeach): bool {
            if (str_contains($message, 'glob()')) {
                return true;
            }
            if (str_contains($message, 'foreach()')) {
                $avisosForeach++;
            }
            return is_callable($anterior) ? (bool) $anterior($type, $message, $file, $line) : false;
        });
        try {
            return $lanza($llamada);
        } finally {
            restore_error_handler();
        }
    };

    $resultado = null;
    [$clase, $mensaje] = $conGlobCallado(function () use (&$resultado, $largo) {
        $resultado = ScanMissingLangTask::collectMissing($largo, ['es'], [], []);
    });
    $check($clase === '' && $resultado === [] && $avisosForeach === 0, 'b1 ScanMissingLang: con glob() en false no hay nada que leer, sin foreach sobre false', "{$clase}: {$mensaje}; avisos de foreach: {$avisosForeach}");

    $temporal = rtrim(sys_get_temp_dir(), '/') . '/zz-fatal-candidates-' . bin2hex(random_bytes(4));
    //RETORNO-IGNORADO: si no se crea, FixWebmDuration dice que el directorio no existe y z1 lo delata.
    mkdir($temporal);
    $terminal = TerminalData::getInstance();
    $argumentosAntes = $terminal->arguments();
    try {
        $terminal->arguments(['--updir' => $temporal, '--absolute' => true, '--glob' => $largo]);
        [$clase, $mensaje] = $conGlobCallado(fn() => FixWebmDurationTask::main());
    } finally {
        $terminal->arguments(is_array($argumentosAntes) ? $argumentosAntes : []);
    }
    $check($clase === '' && $avisosForeach === 0, 'b2 FixWebmDuration: con glob() en false no hay foreach sobre false', "{$clase}: {$mensaje}; avisos de foreach: {$avisosForeach}");

    //c. Los scripts sueltos, en su propio proceso: sin $argv paran con su mensaje; el webm con glob() en false no recorre.
    $raiz = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    $ejecuta = function (string $comando): array {
        $tuberias = [];
        $proceso = proc_open($comando, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias);
        if (!is_resource($proceso)) {
            return [-1, ''];
        }
        $texto = stream_get_contents($tuberias[1]) . stream_get_contents($tuberias[2]);
        //RETORNO-IGNORADO: el proceso ya terminó de escribir; proc_close() da su código igual.
        fclose($tuberias[1]);
        //RETORNO-IGNORADO: el proceso ya terminó de escribir; proc_close() da su código igual.
        fclose($tuberias[2]);
        return [proc_close($proceso), (string) $texto];
    };
    $php = escapeshellarg(PHP_BINARY);

    [$codigo, $texto] = $ejecuta("{$php} " . escapeshellarg("{$raiz}/files/CliScripts/CorregirTiempoDuraciónWebm.php") . ' ' . escapeshellarg("--updir={$temporal}") . ' --absolute ' . escapeshellarg("--glob={$largo}"));
    $check(!str_contains($texto, 'foreach()'), 'c1 CorregirTiempoDuraciónWebm: con glob() en false no hay foreach sobre false', "código {$codigo}: " . mb_substr($texto, 0, 300));

    [$codigo, $texto] = $ejecuta("{$php} -r " . escapeshellarg('unset($argv, $argc); include ' . var_export("{$raiz}/files/CliScripts/GeneradorIntegralTipoUsuario.php", true) . ';'));
    $check($codigo === 1 && str_contains($texto, 'Solo se ejecuta por terminal.'), 'c2 GeneradorIntegralTipoUsuario sin $argv para con su mensaje y código 1', "código {$codigo}: " . mb_substr($texto, 0, 300));

    //RETORNO-IGNORADO: z1 comprueba justo después que el directorio no quede.
    rmdir($temporal);
    $check(!file_exists($temporal), 'z1 el directorio temporal no queda');

    return $balance();

})->setDescription('Los posibles 500 de fatal-candidates fuera del núcleo: adaptadores de IA sin cliente, glob() en false y scripts sin $argv. Sin red.')->setEffects([CliActions::EFFECT_FILES])->register();
