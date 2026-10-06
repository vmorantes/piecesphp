<?php

//Las cookies del servidor salen con SameSite: la función que calcula las opciones, por casos, y la configuración de
//cookies.php. Pura: no pide ni escribe nada.

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/cookie-options', function ($args) {

    echoTerminal("\e[33m[TEST:CookieOptions] Las opciones de las cookies del servidor llevan SameSite\e[39m");
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

    //─── 1 · La función, por casos ────────────────────────────────────────────────────────────
    echoTerminal('[1] cookie_options_by_config()');
    $base = ['lifetime' => 0, 'path' => '/', 'domain' => 'app.ejemplo.test', 'secure' => false, 'httponly' => false];
    $casos = [
        'Lax' => [['samesite' => 'Lax'], 'Lax'],
        'Strict' => [['samesite' => 'Strict'], 'Strict'],
        'en minúsculas' => [['samesite' => 'strict'], 'Strict'],
        'None con secure' => [['samesite' => 'None', 'secure' => true], 'None'],
        'None sin secure: Lax' => [['samesite' => 'None'], 'Lax'],
        'un valor inventado: Lax' => [['samesite' => 'Nada'], 'Lax'],
        'no una cadena: Lax' => [['samesite' => true], 'Lax'],
        'sin la clave: Lax' => [[], 'Lax'],
    ];
    foreach ($casos as $nombre => [$extra, $esperado]) {
        $opciones = cookie_options_by_config(array_merge($base, $extra));
        $check($opciones['samesite'] === $esperado, "1 {$nombre}", (string) json_encode($opciones));
    }
    $opciones = cookie_options_by_config(array_merge($base, ['lifetime' => 3600, 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']));
    $check($opciones === ['expires' => 3600, 'path' => '/', 'domain' => 'app.ejemplo.test', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax'], '1 el resto de opciones pasa tal cual', (string) json_encode($opciones));
    $opciones = cookie_options_by_config(['lifetime' => 'mucho', 'secure' => 'sí', 'httponly' => 1]);
    $check($opciones['expires'] === 0 && $opciones['secure'] === false && $opciones['httponly'] === false && $opciones['path'] === '', '1 un tipo inválido cae a su valor por defecto, sin volverse seguro por accidente', (string) json_encode($opciones));
    echoTerminal(' ');

    //─── 2 · La configuración ─────────────────────────────────────────────────────────────────
    echoTerminal('[2] cookies.php');
    $config = get_config('cookies');
    $check(is_array($config) && ($config['samesite'] ?? null) === 'Lax', "2a \$config['cookies']['samesite'] es 'Lax'", (string) json_encode(is_array($config) ? ($config['samesite'] ?? null) : null));
    $check(cookie_options_by_config(is_array($config) ? $config : [])['samesite'] === 'Lax', '2b y setCookieByConfig() la emite con SameSite=Lax');

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Las cookies del servidor salen con SameSite: la función de opciones por casos y cookies.php.')->setEffects([CliActions::EFFECT_NONE])->register();
