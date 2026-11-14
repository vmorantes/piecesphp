<?php

//H287: con open_basedir (HestiaCP), la búsqueda de la carpeta de claves no toca lo que queda fuera. Sobre
//path_within_open_basedir(), pura, y un subproceso con open_basedir que carga solo lo necesario. No escribe nada.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/open-basedir', function ($args) {

    echoTerminal("\e[33m[TEST:OpenBasedir] open_basedir: la carpeta de claves se busca sin avisos\e[39m");
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

    try {
        //─── a · La función, pura ───────────────────────────────────────────────────────────────────────
        echoTerminal('[a] path_within_open_basedir()');
        $lista = '/var/www/a' . \PATH_SEPARATOR . '/srv/zz-otra/';
        $check(path_within_open_basedir('/cualquier/sitio', '') === true, 'a1 sin open_basedir, todo está permitido');
        $check(path_within_open_basedir('/var/www/a/b/c', $lista) === true, 'a2 dentro de la primera entrada');
        $check(path_within_open_basedir('/srv/zz-otra/x', $lista) === true, 'a3 dentro de la segunda');
        $check(path_within_open_basedir('/var/www/otra', $lista) === false, 'a4 fuera de las dos');
        $check(path_within_open_basedir('/var/www/ab', $lista) === false, 'a5 un prefijo engañoso: «/var/www/a» no deja «/var/www/ab»');
        $check(path_within_open_basedir('/var/www/a', $lista) === true && path_within_open_basedir('/srv/zz-otra', $lista) === true, 'a6 la propia carpeta, con o sin barra final en la entrada');
        $check(path_within_open_basedir('/var/www/a/b/../c/./d', $lista) === true, 'a7 «.» y «..» que se quedan dentro');
        $check(path_within_open_basedir('/var/www/a/../b', $lista) === false && path_within_open_basedir('/var/www/a/..', $lista) === false, 'a8 «..» que sale de la entrada: fuera');
        echoTerminal(' ');

        //─── b · AppKeyring::directory(), con open_basedir de verdad ────────────────────────────────────
        echoTerminal('[b] AppKeyring::directory() en un subproceso con open_basedir: sin avisos');
        $src = rtrim(str_replace('\\', '/', basepath('')), '/');
        //Como HestiaCP: solo la carpeta de la aplicación (public_html) y el temporal; la raíz del repositorio queda fuera.
        $openBasedir = $src . \PATH_SEPARATOR . sys_get_temp_dir();
        $codigo = 'error_reporting(E_ALL);'
            . 'require ' . var_export($src . '/app/core/Utilities.php', true) . ';'
            . 'require ' . var_export($src . '/app/core/psr4/PiecesPHP/Core/Crypto/AppKeyring.php', true) . ';'
            . 'echo "DIRECTORIO=" . var_export(\PiecesPHP\Core\Crypto\AppKeyring::directory(), true) . "\n";';
        $proceso = proc_open([\PHP_BINARY, '-n', '-d', 'open_basedir=' . $openBasedir, '-d', 'display_errors=stderr', '-d', 'log_errors=0', '-r', $codigo], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tubos);
        $salida = '';
        $errores = '';
        $estado = -1;
        if (is_resource($proceso)) {
            //RETORNO-IGNORADO: el subproceso no lee nada; cerrar su entrada solo evita que espere.
            fclose($tubos[0]);
            $salida = (string) stream_get_contents($tubos[1]);
            $errores = (string) stream_get_contents($tubos[2]);
            //RETORNO-IGNORADO: tubos propios de la prueba, ya leídos.
            fclose($tubos[1]);
            //RETORNO-IGNORADO: tubos propios de la prueba, ya leídos.
            fclose($tubos[2]);
            $estado = proc_close($proceso);
        }
        $check($estado === 0 && str_contains($salida, 'DIRECTORIO=NULL'), 'b1 el subproceso corre y no encuentra carpeta (las dos quedan fuera)', "salida {$estado}: " . mb_substr(trim($salida), 0, 200));
        $check(!str_contains($salida . $errores, 'open_basedir') && trim($errores) === '', 'b2 y ningún aviso de open_basedir', mb_substr(trim($errores . ' ' . $salida), 0, 300));

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('open_basedir (H287): path_within_open_basedir() pura (vacía, dentro, fuera, prefijo engañoso, barra final, «..»); y AppKeyring::directory() en un subproceso con open_basedir, sin avisos.')->setEffects([CliActions::EFFECT_NONE])->register();
