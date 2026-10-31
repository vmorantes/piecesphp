<?php

//Leer un mensaje flash no abre una sesión: sin su cookie, una página pública no manda Set-Cookie. Escribir y leer en
//la petición siguiente lo prueba core/impersonation c6, el único camino del framework que lo hace.
//@areas nucleo

use PiecesPHP\Core\FlashMessages;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Controllers\UsersController;

CliActions::make('unit-tests:core/session-on-demand', function ($args) {

    echoTerminal("\e[33m[TEST:SessionOnDemand] Leer un mensaje flash no abre una sesión\e[39m");
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

    //`base_url` en el terminal es `http://localhost` (bootstrap.php fuerza HTTP_HOST): no sirve.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    if ($base === '') {
        $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    if (!$check($base !== '', 'c1 hay una base con la que pedir', 'ni PCSPHP_WALK_BASE ni files/dev/permissions-matrix.json')) {
        return $balance();
    }

    /**
     * Sin cookies: es la primera visita de alguien.
     * @return array{status:int,setCookies:list<string>}
     */
    $pedir = function (string $path) use ($base): array {
        $handle = \Terminal\TestRun::curlInit();
        $setCookies = [];
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HEADERFUNCTION => function (\CurlHandle $h, string $linea) use (&$setCookies): int {
                if (stripos($linea, 'Set-Cookie:') === 0) {
                    $setCookies[] = trim(substr($linea, 11));
                }
                return strlen($linea);
            },
        ]);
        curl_exec($handle);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'setCookies' => $setCookies];
    };
    //El nombre de la cookie de sesión lo da el php.ini de quien sirve; el del terminal puede no ser el mismo.
    $nombres = fn(array $r): string => implode(',', array_map(fn(string $l): string => (string) strtok($l, '='), $r['setCookies']));
    $conSesion = fn(array $r): bool => count(array_filter($r['setCookies'], fn(string $l): bool => str_starts_with($l, session_name() . '='))) > 0;

    echoTerminal('[1/2] Una primera visita, sin cookies');
    $portada = $pedir('/');
    $check($portada['status'] === 200 && !$conSesion($portada), 's1 la portada pública no abre una sesión', "HTTP {$portada['status']}, Set-Cookie: " . $nombres($portada));
    $acceso = UsersController::routeName('form-login', [], true);
    $acceso = str_contains($acceso, '://') ? (string) parse_url($acceso, \PHP_URL_PATH) : $acceso;
    $prefijo = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $acceso = $prefijo !== '' && str_starts_with($acceso, $prefijo) ? substr($acceso, strlen($prefijo)) : $acceso;
    $login = $pedir($acceso);
    $check($login['status'] === 200 && !$conSesion($login), 's2 ni la página de acceso', "{$acceso}: HTTP {$login['status']}, Set-Cookie: " . $nombres($login));
    echoTerminal(' ');

    echoTerminal('[2/2] En el proceso: leer sin cookie no arranca la sesión');
    $antes = session_status();
    $hay = FlashMessages::hasMessages();
    $mensajes = FlashMessages::getMessages();
    $check($antes === PHP_SESSION_NONE && !isset($_COOKIE[session_name()]) && $hay === false && $mensajes === [] && session_status() === PHP_SESSION_NONE,
        's3 hasMessages() y getMessages() responden «no hay» y la sesión sigue sin arrancar', 'estado ' . session_status());

    return $balance();

})->setDescription('Leer un mensaje flash no abre una sesión: una primera visita a la portada o al acceso no recibe la cookie de sesión.')->setEffects([CliActions::EFFECT_FILES])->register();
