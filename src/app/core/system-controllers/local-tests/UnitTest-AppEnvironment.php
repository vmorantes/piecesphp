<?php

//P58: el entorno sale de environment.php y la cabecera Host ya no decide si la instalación es local.
//Los archivos de entorno de prueba viven en un temporal propio; el real no se toca.

use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Core\CustomErrorsHandlers\CustomSlimErrorHandler;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/app-environment', function ($args) {

    echoTerminal("\e[33m[TEST:AppEnvironment] El entorno sale de la configuración, no de la cabecera Host\e[39m");
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

    $base = sys_get_temp_dir() . '/zz-app-environment-' . bin2hex(random_bytes(4));
    $write = function (string $name, string $content) use ($base): string {
        $path = "{$base}/{$name}";
        if (file_put_contents($path, $content) === false) {
            throw new \RuntimeException("No se puede escribir «{$path}».");
        }
        return $path;
    };
    $previousHost = $_SERVER['HTTP_HOST'] ?? null;
    $previousTerminal = $_SERVER['PCSPHP_TERMINAL_DATA'];
    //Fuera de la terminal: así es como decide una petición web.
    $asWeb = function (?string $host): void {
        $_SERVER['PCSPHP_TERMINAL_DATA']['isTerminal'] = false;
        if ($host === null) {
            unset($_SERVER['HTTP_HOST']);
        } else {
            $_SERVER['HTTP_HOST'] = $host;
        }
    };

    try {
        if (!mkdir($base, 0775, true)) {
            throw new \RuntimeException("No se puede crear «{$base}».");
        }
        $production = $write('production.php', "<?php\nreturn 'production';\n");
        $local = $write('local.php', "<?php\nreturn 'local';\n");
        $garbage = $write('garbage.php', "<?php\nreturn 'dev';\n");
        $notString = $write('not-string.php', "<?php\nreturn 1;\n");
        $throws = $write('throws.php', "<?php\nthrow new \\RuntimeException('zz');\n");

        //─── a · La cabecera Host no decide ─────────────────────────────────────────────────────────────
        echoTerminal('[a] La cabecera Host no decide');
        AppEnvironment::useForTesting($production);
        $asWeb('x.localhost');
        $check(is_local() === false, 'a1 entorno production + Host «x.localhost» → no es local');
        $asWeb('localhost');
        $check(is_local() === false, 'a2 entorno production + Host «localhost» → no es local');
        AppEnvironment::useForTesting($local);
        $asWeb('example.com');
        $check(is_local() === true, 'a3 entorno local + Host cualquiera → local');
        $asWeb(null);
        $check(is_local() === true, 'a4 entorno local sin Host → local');
        echoTerminal(' ');

        //─── b · Sin archivo o con basura: producción ───────────────────────────────────────────────────
        echoTerminal('[b] Sin archivo, o con un valor que no vale: producción');
        AppEnvironment::useForTesting("{$base}/no-existe.php");
        $check(app_environment() === 'production' && AppEnvironment::isConfigured() === false, 'b1 archivo ausente → production y «sin configurar»');
        foreach (['b2 «dev»' => $garbage, 'b3 un entero' => $notString, 'b4 un archivo que lanza' => $throws] as $name => $path) {
            AppEnvironment::useForTesting($path);
            $check(app_environment() === 'production' && AppEnvironment::isConfigured() === true, "{$name} → production");
        }
        echoTerminal(' ');

        //─── c · La terminal, como antes ────────────────────────────────────────────────────────────────
        echoTerminal('[c] En la terminal manda --local, como antes');
        AppEnvironment::useForTesting($production);
        $_SERVER['PCSPHP_TERMINAL_DATA'] = $previousTerminal;
        $_SERVER['PCSPHP_TERMINAL_DATA']['isTerminal'] = true;
        $_SERVER['PCSPHP_TERMINAL_DATA']['local'] = true;
        $check(is_local() === true, 'c1 terminal con --local → local aunque el entorno sea production');
        $_SERVER['PCSPHP_TERMINAL_DATA']['local'] = false;
        AppEnvironment::useForTesting($local);
        $check(is_local() === false, 'c2 terminal sin --local → no es local aunque el entorno sea local');
        echoTerminal(' ');

        //─── d · El manejador de errores no diverge ─────────────────────────────────────────────────────
        echoTerminal('[d] CustomSlimErrorHandler::isLocal() dice lo mismo que is_local()');
        $same = true;
        foreach ([[$production, 'x.localhost'], [$local, 'example.com'], ["{$base}/no-existe.php", 'localhost']] as [$path, $host]) {
            AppEnvironment::useForTesting($path);
            $asWeb($host);
            $same = $same && CustomSlimErrorHandler::isLocal() === is_local();
        }
        $check($same, 'd1 las dos respuestas coinciden en los tres casos');
        echoTerminal(' ');

        //─── e · Esta instalación ───────────────────────────────────────────────────────────────────────
        echoTerminal('[e] Esta instalación');
        AppEnvironment::useForTesting(null);
        $check(app_environment() === 'local' && AppEnvironment::isConfigured(), 'e1 environment.php de esta máquina dice local', app_environment());
        $check(get_config('environment') === app_environment(), 'e2 el arranque lo dejó en la configuración (environment)', var_export(get_config('environment'), true));

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        AppEnvironment::useForTesting(null);
        $_SERVER['PCSPHP_TERMINAL_DATA'] = $previousTerminal;
        if ($previousHost === null) {
            unset($_SERVER['HTTP_HOST']);
        } else {
            $_SERVER['HTTP_HOST'] = $previousHost;
        }
        foreach (['production.php', 'local.php', 'garbage.php', 'not-string.php', 'throws.php'] as $name) {
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

})->setDescription('P58: el entorno sale de environment.php; la cabecera Host ya no decide si la instalación es local.')->setEffects([CliActions::EFFECT_FILES])->register();
