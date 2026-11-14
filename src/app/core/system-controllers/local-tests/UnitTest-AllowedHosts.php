<?php

//P310 c: la lista de dominios permitidos. Vacía, cualquiera; con ella, el Host y X-Forwarded-Host tienen que estar dentro.
//Funciones puras: no toca la instalación ni $_SERVER.
//@areas nucleo

use PiecesPHP\Core\AllowedHosts;
use PiecesPHP\SystemStatus\SystemStatusRoutes;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/allowed-hosts', function ($args) {

    echoTerminal("\e[33m[TEST:AllowedHosts] La lista de dominios permitidos\e[39m");
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
        //─── a · normalize ──────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] normalize()');
        foreach ([
            'a1 mayúsculas' => ['Ejemplo.COM', 'ejemplo.com'],
            'a2 puerto' => ['ejemplo.com:8080', 'ejemplo.com'],
            'a3 IPv6 con puerto' => ['[::1]:8080', '[::1]'],
            'a4 IPv6 sin puerto' => ['[::1]', '[::1]'],
            'a5 punto final y puerto' => ['Ejemplo.COM.:443', 'ejemplo.com'],
            'a6 espacios' => ['  ejemplo.com  ', 'ejemplo.com'],
        ] as $name => [$input, $expected]) {
            $got = AllowedHosts::normalize($input);
            $check($got === $expected, "{$name}: «{$input}» → «{$expected}»", $got);
        }
        echoTerminal(' ');

        //─── b · allows ─────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[b] allows()');
        $sub = ['.ejemplo.com'];
        foreach ([
            'b1 lista vacía → cualquiera' => ['otro.test', [], true],
            'b2 lista solo con «» y no cadenas → cualquiera' => ['otro.test', ['', '  ', 7, null, ['x']], true],
            'b3 exacta' => ['ejemplo.com', ['ejemplo.com'], true],
            'b4 exacta, otro dominio → no' => ['otro.test', ['ejemplo.com'], false],
            'b5 mayúsculas y puerto en el Host' => ['EJEMPLO.com:8443', ['ejemplo.com'], true],
            'b6 «.ejemplo.com» con ejemplo.com' => ['ejemplo.com', $sub, true],
            'b7 «.ejemplo.com» con a.ejemplo.com' => ['a.ejemplo.com', $sub, true],
            'b8 «.ejemplo.com» con a.b.ejemplo.com' => ['a.b.ejemplo.com', $sub, true],
            'b9 «.ejemplo.com» con otroejemplo.com → no' => ['otroejemplo.com', $sub, false],
            'b10 «.ejemplo.com» con ejemplo.com.evil.test → no' => ['ejemplo.com.evil.test', $sub, false],
            'b11 Host vacío con lista → no' => ['', ['ejemplo.com'], false],
            'b12 IPv6 en la lista' => ['[::1]:8080', ['[::1]'], true],
        ] as $name => [$host, $allowed, $expected]) {
            $check(AllowedHosts::allows($host, $allowed) === $expected, $name);
        }
        echoTerminal(' ');

        //─── c · hostForSameDomain ──────────────────────────────────────────────────────────────────────
        echoTerminal('[c] hostForSameDomain()');
        $server = ['HTTP_X_FORWARDED_HOST' => 'proxy.ejemplo.com', 'HTTP_HOST' => 'app.ejemplo.com', 'SERVER_NAME' => 'srv.ejemplo.com'];
        $got = AllowedHosts::hostForSameDomain($server, []);
        $check($got === 'proxy.ejemplo.com', 'c1 lista vacía con X-Forwarded-Host → él (como hasta ahora)', $got);
        $got = AllowedHosts::hostForSameDomain($server, ['app.ejemplo.com']);
        $check($got === 'app.ejemplo.com', 'c2 con lista y X-Forwarded-Host fuera → HTTP_HOST', $got);
        $got = AllowedHosts::hostForSameDomain($server, ['.ejemplo.com']);
        $check($got === 'proxy.ejemplo.com', 'c3 con lista y X-Forwarded-Host dentro → él', $got);
        $got = AllowedHosts::hostForSameDomain(['SERVER_NAME' => 'srv.ejemplo.com'], ['app.ejemplo.com']);
        $check($got === 'srv.ejemplo.com', 'c4 sin HTTP_HOST → SERVER_NAME', $got);
        echoTerminal(' ');

        //─── d · El aviso ───────────────────────────────────────────────────────────────────────────────
        echoTerminal('[d] SystemStatusRoutes::allowedHostsMissing()');
        $check(SystemStatusRoutes::allowedHostsMissing([], true) === false, 'd1 en local → sin aviso');
        $check(SystemStatusRoutes::allowedHostsMissing([], false) === true, 'd2 [] fuera de local → aviso');
        $check(SystemStatusRoutes::allowedHostsMissing(null, false) === true, 'd3 null fuera de local → aviso');
        $check(SystemStatusRoutes::allowedHostsMissing([''], false) === true, 'd4 [\'\'] fuera de local → aviso');
        $check(SystemStatusRoutes::allowedHostsMissing(['ejemplo.com'], false) === false, 'd5 [\'ejemplo.com\'] → sin aviso');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('P310 c: la lista de dominios permitidos (normalize, allows, hostForSameDomain) y su aviso.')->setEffects([CliActions::EFFECT_NONE])->register();
