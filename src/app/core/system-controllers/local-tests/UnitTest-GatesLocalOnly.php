<?php

//`gates` solo corre en una instalación `local`: las suites escriben en la base de donde se corren.
//Se prueba con archivos de entorno temporales; el `environment.php` real no se toca.

use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\GatesTask;

CliActions::make('unit-tests:core/gates-local-only', function ($args) {

    echoTerminal("\e[33m[TEST:GatesLocalOnly] gates se niega a correr fuera del entorno local\e[39m");
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

    $entornoReal = AppEnvironment::get();
    $temporal = sys_get_temp_dir() . '/zz-gates-entorno-' . bin2hex(random_bytes(4));
    $archivo = fn(string $nombre): string => "{$temporal}-{$nombre}.php";

    try {
        //RETORNO-IGNORADO: sin este archivo, a1 y a2 fallarían y lo dirían.
        @file_put_contents($archivo('production'), "<?php\nreturn 'production';\n");
        //RETORNO-IGNORADO: sin este archivo, a5 fallaría y lo diría.
        @file_put_contents($archivo('local'), "<?php\nreturn 'local';\n");
        //RETORNO-IGNORADO: sin este archivo, a4 fallaría y lo diría.
        @file_put_contents($archivo('raro'), "<?php\nreturn 'loc al';\n");

        //─── a · La decisión ────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] Qué entorno deja correr las suites');
        AppEnvironment::useForTesting($archivo('production'));
        $produccion = GatesTask::localOnly();
        $check($produccion['ok'] === false, 'a1 «production»: NO corre');
        $check(str_contains($produccion['line'], 'NO local') && str_contains($produccion['line'], "return 'local';"), 'a2 y la línea dice por qué y cómo se arregla', mb_substr($produccion['line'], 0, 120));

        AppEnvironment::useForTesting($archivo('ausente-nunca-creado'));
        $sinArchivo = GatesTask::localOnly();
        $check($sinArchivo['ok'] === false && str_contains($sinArchivo['line'], 'producción'), 'a3 sin environment.php: NO corre, y dice que cuenta como producción');

        AppEnvironment::useForTesting($archivo('raro'));
        $check(GatesTask::localOnly()['ok'] === false, 'a4 un valor que no es exactamente «local»: NO corre');

        //CANARIO: la otra cara. Sin ella, un «nunca corre» pasaría todo lo de arriba.
        AppEnvironment::useForTesting($archivo('local'));
        $local = GatesTask::localOnly();
        $check($local['ok'] === true && str_contains($local['line'], 'entorno: local'), 'a5 CANARIO: «local» SÍ corre, y también lo dice');

        //─── b · Que main() la use, y antes de todo ─────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] main() la consulta lo primero y sin interruptor');
        $fuente = (string) file_get_contents((string) (new \ReflectionClass(GatesTask::class))->getFileName());
        $inicio = (int) strpos($fuente, 'public static function main(');
        $fin = (int) strpos($fuente, 'protected static function runSuite(');
        $main = $inicio > 0 && $fin > $inicio ? substr($fuente, $inicio, $fin - $inicio) : '';
        $guarda = strpos($main, 'self::localOnly()');
        $primerArgumento = strpos($main, 'getArgument(');
        $primeraSuite = strpos($main, 'self::runSuite(');
        $check($main !== '' && $guarda !== false, 'b1 main() llama a localOnly()');
        $check($guarda !== false && $primerArgumento !== false && $guarda < $primerArgumento, 'b2 ANTES de leer ningún argumento: no hay argumento que la salte');
        $check($guarda !== false && $primeraSuite !== false && $guarda < $primeraSuite, 'b3 y antes de la primera suite');
        $tras = $guarda !== false ? substr($main, (int) $guarda, 400) : '';
        $check(preg_match('/if\s*\(!\$entorno\[.ok.\]\)\s*\{.{0,300}?exit\(\s*[1-9]/s', $tras) === 1, 'b4 y si no es local, sale con código distinto de cero');
    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        AppEnvironment::useForTesting(null);
        foreach (['production', 'local', 'raro'] as $nombre) {
            //RETORNO-IGNORADO: lo comprueba z1.
            @unlink($archivo($nombre));
        }
        echoTerminal(' ');
        echoTerminal('[z] Limpieza: el entorno real de vuelta y los temporales retirados');
        $check(!is_file($archivo('production')) && !is_file($archivo('local')) && !is_file($archivo('raro')), 'z1 no queda ningún archivo temporal');
        $check(AppEnvironment::get() === $entornoReal, 'z2 y el entorno vuelve a ser el real de esta máquina', $entornoReal);
    }

    return $balance();

})->setDescription('gates se niega a correr fuera del entorno local, sin interruptor para saltárselo: «production», sin environment.php o con un valor raro no corre; «local» sí, y lo dice. Y main() lo consulta antes de leer ningún argumento y antes de la primera suite. Usa archivos de entorno temporales: el real no se toca.')->setEffects([CliActions::EFFECT_FILES])->register();
