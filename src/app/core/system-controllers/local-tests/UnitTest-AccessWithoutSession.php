<?php

//Sin sesión, una ruta con `require_login` NIEGA por CUALQUIER vía. Sale a la red porque las dos
//capas viven en `src/index.php` y solo corren al servir. Ver `.agents/docs/pendientes.md` 115.

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/access-without-session', function ($args) {

    echoTerminal("\e[33m[TEST:AccessWithoutSession] Sin sesión, una ruta protegida niega por cualquier vía\e[39m");
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

    //`base_url` en el terminal es `http://localhost` (bootstrap.php fuerza HTTP_HOST): no sirve.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    if ($base === '') {
        //`basepath('')` es `src/`, y `files/dev/` vive un nivel arriba. Igual que RouteInventoryTask.
        $repoRoot = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $matrixPath = $repoRoot . '/files/dev/permissions-matrix.json';
        if (is_file($matrixPath)) {
            $matrix = json_decode((string) file_get_contents($matrixPath), true);
            $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
        }
    }
    $base = rtrim($base, '/');

    //El canario de la base va ANTES de definir esto: sin base no se pide nada.
    echoTerminal('[canario] Lo que esta prueba da por hecho');
    $check($base !== '', 'c1 hay una base con la que pedir',
        'ni PCSPHP_WALK_BASE ni files/dev/permissions-matrix.json');
    if ($base === '') {
        echoTerminal("\e[31m Sin base no hay nada que medir: lo que saliera no significaría nada. \e[39m");
        return ['success' => false, 'message' => "{$passed}/" . ($passed + $failed)];
    }

    /**
     * @param list<string> $headers
     * @return array{status:int,body:string,location:string}
     */
    $pedir = function (string $path, array $headers) use ($base): array {
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => $base . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            //Sin esto una redirección al login se seguiría sola y un 302 se leería como 200.
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $location = (string) curl_getinfo($handle, CURLINFO_REDIRECT_URL);
        //Sin curl_close(): deprecado desde PHP 8.5 y sin efecto desde la 8.0, y aquí una deprecación aborta.
        return ['status' => $status, 'body' => is_string($body) ? $body : '', 'location' => $location];
    };

    $protegida = 'admin-about-framework';
    $abierta = 'users-form-login';
    $rutaProtegida = '/admin/about/';
    $rutaAbierta = '/users/login/';

    //─── Canario ────────────────────────────────────────────────────────────────────────────────
    //Sin esto pasaría con el servidor apagado o el día que la protegida deje de exigir sesión.
    $infoProtegida = get_route_info($protegida);
    $infoAbierta = get_route_info($abierta);
    $check(is_array($infoProtegida) && ($infoProtegida['require_login'] ?? false) === true,
        "c2 {$protegida} sigue exigiendo sesión", is_array($infoProtegida) ? 'require_login en false' : 'la ruta no existe');
    $check(is_array($infoAbierta) && ($infoAbierta['require_login'] ?? false) === false,
        "c3 {$abierta} sigue sin exigirla", is_array($infoAbierta) ? 'require_login en true' : 'la ruta no existe');
    if ($failed > 0) {
        echoTerminal("\e[31m Lo que la prueba da por hecho no se cumple: lo que midiera no significaría nada. \e[39m");
        return ['success' => false, 'message' => "{$passed}/" . ($passed + $failed)];
    }
    echoTerminal(' ');

    //─── 1 · Petición de datos, con las cabeceras que pone el cliente ───────────────────────────
    echoTerminal('[1] Una petición de datos sin sesión recibe 403, con las cabeceras que quiera');
    $cabeceras = [
        'X-Requested-With: XMLHttpRequest',
        'Referer: ' . $base . $rutaAbierta,
    ];
    $r1 = $pedir($rutaProtegida, $cabeceras);
    $json1 = json_decode($r1['body'], true);
    $check($r1['status'] === 403, '1a responde 403', 'HTTP ' . $r1['status']);
    $check(is_array($json1) && ($json1['error'] ?? null) === 'RESTRICTED_AREA',
        '1b y el cuerpo dice RESTRICTED_AREA', mb_substr($r1['body'], 0, 60));
    echoTerminal(' ');

    //─── 2 · Petición normal ────────────────────────────────────────────────────────────────────
    echoTerminal('[2] Una petición normal sin sesión va al formulario de acceso');
    $r2 = $pedir($rutaProtegida, []);
    $check($r2['status'] === 302, '2a responde 302', 'HTTP ' . $r2['status']);
    $check(str_contains($r2['location'], $rutaAbierta), '2b y redirige al acceso',
        $r2['location'] !== '' ? $r2['location'] : 'sin cabecera Location');
    echoTerminal(' ');

    //─── 3 · Lo que NO cambia ───────────────────────────────────────────────────────────────────
    echoTerminal('[3] Una ruta que no exige sesión sigue abierta');
    $r3 = $pedir($rutaAbierta, $cabeceras);
    $check($r3['status'] === 200, '3a responde 200', 'HTTP ' . $r3['status']);
    $check(mb_strlen($r3['body']) > 0, '3b con cuerpo', mb_strlen($r3['body']) . ' bytes');

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Sin sesión, una ruta con require_login niega por HTTP: 403 a una petición de datos y redirección a una normal.')->setEffects([CliActions::EFFECT_NETWORK])->register();
