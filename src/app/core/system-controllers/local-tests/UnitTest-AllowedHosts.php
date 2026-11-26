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
        echoTerminal('[d] SystemStatusRoutes::allowedHostsMissing(): también en local (P415)');
        $check(SystemStatusRoutes::allowedHostsMissing([]) === true, 'd1 [] → aviso');
        $check(SystemStatusRoutes::allowedHostsMissing(null) === true, 'd2 null → aviso');
        $check(SystemStatusRoutes::allowedHostsMissing(['']) === true, 'd3 [\'\'] → aviso');
        $check(SystemStatusRoutes::allowedHostsMissing(['ejemplo.com']) === false, 'd4 [\'ejemplo.com\'] → sin aviso');
        $check(SystemStatusRoutes::allowedHostsMissing(['.', ':80']) === true && AllowedHosts::allows('otro.test', ['.', ':80']) === true, 'd4b entradas que se normalizan a vacío: la lista admite cualquiera, y el aviso lo dice');
        //El aviso registrado, en esta instalación local: activo con la lista vacía y apagado con la lista llena.
        SystemStatusRoutes::registerCoreAlerts();
        $alert = \PiecesPHP\SystemStatus\SystemAlertRegistry::get('allowed-hosts-empty');
        $previous = get_config('allowed_hosts');
        set_config('allowed_hosts', []);
        $activeEmpty = $alert !== null && $alert->evaluateActive();
        set_config('allowed_hosts', ['localhost']);
        $activeFull = $alert !== null && $alert->evaluateActive();
        set_config('allowed_hosts', $previous);
        $check(is_local() && $activeEmpty && !$activeFull && $alert->showAsNag() && $alert->audience() === [\PiecesPHP\UserSystem\ORM\UsersModel::TYPE_USER_ROOT], 'd5 en local: con la lista vacía el aviso flotante sale (para root); con la lista llena, no', var_export([is_local(), $activeEmpty, $activeFull], true));
        $texto = $alert !== null ? $alert->message() : '';
        $check(str_contains($texto, 'www.example.org') && str_contains($texto, 'confía en el dominio que llega en cada petición') && !str_contains($texto, 'recuperación'), 'd6 el texto dice que example.org admite también www.example.org, y no enseña a qué ataca (regla 6bis)');
        echoTerminal(' ');

        //─── e · El www inferido (P415) ─────────────────────────────────────────────────────────────────
        echoTerminal('[e] «example.org» admite también «www.example.org»; nada más se infiere');
        foreach ([
            'e1 example.org admite www.example.org' => ['www.example.org', ['example.org'], true, true],
            'e2 y no api.example.org' => ['api.example.org', ['example.org'], true, false],
            'e3 con la bandera en false, no admite el www' => ['www.example.org', ['example.org'], false, false],
            'e4 con la bandera en false, example.org sigue' => ['example.org', ['example.org'], false, true],
            'e5 «.example.org» igual que hoy (www y api)' => ['api.example.org', ['.example.org'], true, true],
            'e6 www.example.org en la lista no infiere example.org' => ['example.org', ['www.example.org'], true, false],
            'e7 ni www.www.example.org' => ['www.www.example.org', ['www.example.org'], true, false],
            'e8 a localhost no se le antepone www' => ['www.localhost', ['localhost'], true, false],
            'e9 ni a una IP' => ['www.127.0.0.1', ['127.0.0.1'], true, false],
            'e10 sin el argumento, infiere (el config.php viejo)' => ['www.example.org', ['example.org'], null, true],
        ] as $name => [$host, $allowed, $infer, $expected]) {
            $got = $infer === null ? AllowedHosts::allows($host, $allowed) : AllowedHosts::allows($host, $allowed, $infer);
            $check($got === $expected, $name);
        }
        echoTerminal(' ');
        echoTerminal('[f] Con lista, un Host que no tiene forma de nombre no se compara (la URL base apuntaría a otro dominio)');
        foreach (['example.org:@evil.com', 'example.org:80@evil.com', 'www.example.org:@evil.com', 'evil.com?.example.org', 'evil.com#.example.org', 'example.org/evil.com', 'exa mple.org'] as $hostile) {
            $check(AllowedHosts::allows($hostile, ['example.org', '.example.org']) === false, "f «{$hostile}» → no");
        }
        $check(AllowedHosts::allows('EXAMPLE.org.:8443', ['example.org']) && AllowedHosts::allows('[::1]:8080', ['[::1]']) && AllowedHosts::allows('evil.com?x', []), 'f8 lo válido sigue (mayúsculas, punto final, puerto, IPv6) y con la lista vacía no se mira la forma');
        $server = ['HTTP_X_FORWARDED_HOST' => 'www.example.org', 'HTTP_HOST' => 'example.org'];
        $check(AllowedHosts::hostForSameDomain($server, ['example.org']) === 'www.example.org' && AllowedHosts::hostForSameDomain($server, ['example.org'], false) === 'example.org', 'e11 X-Forwarded-Host infiere igual: www.example.org cuenta; con la bandera en false, no');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('P310 c y P415: la lista de dominios permitidos (normalize, allows con el www inferido, hostForSameDomain) y su aviso, también en local.')->setEffects([CliActions::EFFECT_NONE])->register();
