<?php

//El CORS da credenciales SOLO al propio origen y a los declarados: la función por casos y las cabeceras por HTTP.
//Sin escribir nada: solo lee, y pide una ruta pública de datos y un preflight.

use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/cors', function ($args) {

    echoTerminal("\e[33m[TEST:Cors] Las credenciales CORS solo para el propio origen y los declarados\e[39m");
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

    //─── 1 · La decisión, por casos ───────────────────────────────────────────────────────────
    echoTerminal('[1] cors_origin_allows_credentials(): comparación exacta de esquema, host y puerto');
    $propio = 'https://app.ejemplo.test';
    $casos = [
        'el propio origen' => ['https://app.ejemplo.test', [], true],
        'uno declarado' => ['https://otra.ejemplo.test:8443', ['https://otra.ejemplo.test:8443'], true],
        'un origen ajeno' => ['https://ajeno.test', [], false],
        'el propio host como prefijo de otro' => ['https://app.ejemplo.test.malo.net', [], false],
        'el propio host por http' => ['http://app.ejemplo.test', [], false],
        'el propio host con otro puerto' => ['https://app.ejemplo.test:8443', [], false],
        'el propio con el puerto por defecto escrito' => ['https://app.ejemplo.test:443', [], false],
        'un Origin con barra final' => ['https://app.ejemplo.test/', [], false],
        'un Origin vacío' => ['', [], false],
        'lista con algo que no es cadena: cuenta como vacía' => ['https://otra.ejemplo.test', ['https://otra.ejemplo.test', 42], false],
        'y con esa lista, el propio sigue valiendo' => ['https://app.ejemplo.test', ['https://otra.ejemplo.test', 42], true],
        'un comodín en la lista no casa' => ['https://x.ejemplo.test', ['https://*.ejemplo.test'], false],
    ];
    foreach ($casos as $nombre => [$origen, $lista, $esperado]) {
        $obtenido = cors_origin_allows_credentials($origen, $lista, $propio);
        $check($obtenido === $esperado, "1 {$nombre} → " . ($esperado ? 'con' : 'sin') . ' credenciales', var_export($obtenido, true));
    }
    $check(url_origin('https://App.Ejemplo.test:443/ruta?x=1') === 'https://app.ejemplo.test' && url_origin('http://h:8080/') === 'http://h:8080' && url_origin('no es una url') === '', '1 url_origin() da el origen como lo manda un navegador');
    echoTerminal(' ');

    //─── 2 · Por HTTP ─────────────────────────────────────────────────────────────────────────
    echoTerminal('[2] Las cabeceras reales');
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    if ($base === '') {
        $matrixPath = dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/files/dev/permissions-matrix.json';
        $matrix = is_file($matrixPath) ? json_decode((string) file_get_contents($matrixPath), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $check($base !== '', 'c1 canario: hay una base con la que pedir');
    $datos = get_route('publications-ajax-all', [], true);
    $datos = is_string($datos) && str_contains($datos, '://') ? (string) parse_url($datos, \PHP_URL_PATH) : (string) $datos;
    $check($datos !== '', 'c2 canario: existe la ruta pública de datos publications-ajax-all');
    if ($base === '' || $datos === '') {
        return ['success' => false, 'message' => "{$passed}/" . ($passed + $failed)];
    }
    $pedir = function (string $metodo, string $origen, array $extra = []) use ($base, $datos): array {
        $cabeceras = [];
        $pedidas = $origen !== '' ? ["Origin: {$origen}"] : [];
        foreach ($extra as $linea) {
            if (is_string($linea)) {
                $pedidas[] = $linea;
            }
        }
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => $base . '/' . ltrim($datos, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $metodo === 'OPTIONS' ? 'OPTIONS' : 'GET',
            CURLOPT_HTTPHEADER => $pedidas,
            CURLOPT_HEADERFUNCTION => function (\CurlHandle $h, string $linea) use (&$cabeceras): int {
                $partes = explode(':', $linea, 2);
                if (count($partes) === 2) {
                    $cabeceras[mb_strtolower(trim($partes[0]))] = trim($partes[1]);
                }
                return strlen($linea);
            },
        ]);
        //RETORNO-IGNORADO: el cuerpo no se mira; lo que se mide son las cabeceras, que recoge HEADERFUNCTION.
        curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => $status, 'h' => $cabeceras];
    };
    $propioHTTP = url_origin($base);
    $ajeno = $pedir('GET', 'https://ajeno.test');
    $check(($ajeno['h']['access-control-allow-origin'] ?? null) === 'https://ajeno.test', '2a origen ajeno: Allow-Origin presente', (string) json_encode($ajeno['h']['access-control-allow-origin'] ?? null));
    $check(!array_key_exists('access-control-allow-credentials', $ajeno['h']), '2b y SIN Allow-Credentials (ni «false»: no se envía)', (string) json_encode($ajeno['h']['access-control-allow-credentials'] ?? null));
    $propia = $pedir('GET', $propioHTTP);
    $check(($propia['h']['access-control-allow-credentials'] ?? null) === 'true', "2c el propio origen ({$propioHTTP}): Allow-Credentials true", (string) json_encode($propia['h']['access-control-allow-credentials'] ?? null));
    $prefijo = $pedir('GET', $propioHTTP . '.malo.net');
    $check(!array_key_exists('access-control-allow-credentials', $prefijo['h']), '2d el propio host como prefijo de otro: sin credenciales');
    $sinOrigen = $pedir('GET', '');
    $check(($sinOrigen['h']['access-control-allow-origin'] ?? null) === '*' && !array_key_exists('access-control-allow-credentials', $sinOrigen['h']), '2e sin Origin: «*» y sin credenciales');
    $cabeceraToken = mb_strtolower(SessionToken::tokenName());
    $preflight = $pedir('OPTIONS', 'https://ajeno.test', ['Access-Control-Request-Method: GET', "Access-Control-Request-Headers: {$cabeceraToken}"]);
    $check($preflight['status'] === 204, '2f preflight de un origen ajeno pidiendo la cabecera del token: 204', 'HTTP ' . $preflight['status']);
    $check(!array_key_exists('access-control-allow-credentials', $preflight['h']) && str_contains(mb_strtolower($preflight['h']['access-control-allow-headers'] ?? ''), $cabeceraToken), '2g sin credenciales y con esa cabecera permitida', (string) json_encode($preflight['h']));
    $check(str_contains($ajeno['h']['vary'] ?? '', 'Origin'), '2h Vary: Origin, igual que antes', (string) ($ajeno['h']['vary'] ?? ''));

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El CORS da credenciales solo al propio origen y a los declarados: la función por casos y las cabeceras por HTTP.')->setEffects([CliActions::EFFECT_NETWORK])->register();
