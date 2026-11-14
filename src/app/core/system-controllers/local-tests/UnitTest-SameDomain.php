<?php

//H273: la comprobación de mismo dominio compara el origen EXACTO (no «empieza por») y decide «local» por la
//configuración, no por el host. Función pura: no toca la instalación ni $_SERVER.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/same-domain', function ($args) {

    echoTerminal("\e[33m[TEST:SameDomain] El mismo dominio, por el origen exacto\e[39m");
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
        $own = 'https://sitio.test';

        //─── a · Por la cabecera Origin ─────────────────────────────────────────────────────────────────
        echoTerminal('[a] Origin');
        foreach ([
            'a1 origen igual → sí' => ['https://sitio.test', true],
            'a2 con mayúsculas en el host → sí' => ['https://SITIO.test', true],
            'a3 con el puerto 443 explícito → sí' => ['https://sitio.test:443', true],
            'a4 con otro puerto → no' => ['https://sitio.test:8443', false],
            'a5 https://sitio.test.otro.test (empieza igual) → no' => ['https://sitio.test.otro.test', false],
            'a6 https://otrositio.test → no' => ['https://otrositio.test', false],
            'a7 http://sitio.test (otro esquema) → no' => ['http://sitio.test', false],
        ] as $name => [$origin, $expected]) {
            $check(same_domain_request($own, $origin, '', false) === $expected, $name);
        }
        echoTerminal(' ');

        //─── b · Por la cabecera Referer ────────────────────────────────────────────────────────────────
        echoTerminal('[b] Referer');
        $check(same_domain_request($own, '', 'https://sitio.test/admin/x?y=1', false) === true, 'b1 referer https://sitio.test/admin/x?y=1 → sí');
        $check(same_domain_request($own, '', 'https://sitio.test.otro.test/admin', false) === false, 'b2 referer https://sitio.test.otro.test/admin → no');
        echoTerminal(' ');

        //─── c · Sin pruebas de otro dominio ────────────────────────────────────────────────────────────
        echoTerminal('[c] Sin Origin ni Referer');
        $check(same_domain_request($own, '', '', true) === true, 'c1 en local → sí');
        $check(same_domain_request($own, '', '', false) === false, 'c2 en producción → no');
        $check(same_domain_request($own, 'https://otrositio.test', '', true) === false, 'c3 en local con un origen ajeno → no');
        $check(same_domain_request('', 'https://sitio.test', '', false) === false, 'c4 sin origen propio y con origen → no');
        echoTerminal(' ');

        //─── d · La terminal, como hasta ahora ──────────────────────────────────────────────────────────
        echoTerminal('[d] requestIsSameDomain() en la terminal');
        $check(requestIsSameDomain() === true, 'd1 en la terminal → sí');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('H273: same_domain_request() compara el origen exacto (Origin y Referer) y el caso local sale de is_local().')->setEffects([CliActions::EFFECT_NONE])->register();
