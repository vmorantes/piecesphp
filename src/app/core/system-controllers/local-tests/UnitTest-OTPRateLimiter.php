<?php

//El límite de intentos del OTP y del segundo factor, con filas y fechas sintéticas: la lógica es una función pura.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/otp-rate-limiter';
$cliTaskDescription = 'Límite de intentos del OTP por usuario y por IP: el tope, la ventana, el desbloqueo y qué filas cuentan';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:OTPRateLimiter] Iniciando suite...', true, "\r\n", '33');
    echoTerminal('');

    $passed = 0;
    $failed = 0;

    $check = function (bool $condition, string $name, ?string $detail = null) use (&$passed, &$failed) {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}");
        }
        if ($detail !== null) {
            echoTerminal("      - {$detail}");
        }
        return $condition;
    };

    $config = [
        'maxFailuresPerUser' => 5,
        'maxFailuresPerIP' => 20,
        'windowMinutes' => 15,
        'lockMinutes' => 15,
        'uniformResponse' => true,
        'oneUseCodeMinutes' => 20,
        'loginMaxFailuresPerIP' => 5,
        'loginAccountDistinctIPs' => 3,
        'loginAccountWindowMinutes' => 1440,
    ];
    $ahora = new \DateTime('2026-01-10 12:00:00');
    $fila = static function (string $usuario, string $ip, int $minutosAtras, array $extra = ['otpVia' => OTPRateLimiter::VIA_CHECK_TOTP], int $exito = 0) use ($ahora): array {
        return [
            'usernameAttempt' => $usuario,
            'ip' => $ip,
            'success' => $exito,
            'date' => (clone $ahora)->modify("-{$minutosAtras} minutes")->format('Y-m-d H:i:s'),
            'extraData' => json_encode($extra),
        ];
    };
    $fallos = static function (array $minutosAtras, string $usuario = 'ana', string $ip = '10.0.0.1', array $extra = ['otpVia' => OTPRateLimiter::VIA_CHECK_TOTP]) use ($fila): array {
        return array_map(fn (int $minutos): array => $fila($usuario, $ip, $minutos, $extra), $minutosAtras);
    };
    $segundos = static function (array $filas, string $usuario = 'ana', string $ip = '10.0.0.1') use ($ahora, $config): int {
        return OTPRateLimiter::secondsToUnlockFromRows($filas, $usuario, $ip, $ahora, $config);
    };

    //──── 1. La configuración ──────────────────────────────────────────────────────────────────
    echoTerminal('[1/6] config(): la de config.php, con las cinco vías que cuentan');

    $check(OTPRateLimiter::config() === $config, 'otp_security de config.php: 5 por usuario, 20 por IP, 15 de ventana, 15 de bloqueo, uniforme, 20 del código y las tres del login (core/login-ip-throttle)',
        json_encode(OTPRateLimiter::config(), JSON_THROW_ON_ERROR));
    $check(OTPRateLimiter::VIAS === ['generate-otp', 'check-totp', 'two-factor-auth-status', 'login-totp', 'recovery-code'], 'las vías: generate-otp, check-totp, two-factor-auth-status, login-totp y recovery-code');
    echoTerminal(' ');

    //──── 2. Por usuario ───────────────────────────────────────────────────────────────────────
    echoTerminal('[2/6] Por usuario: el tope dentro de la ventana, y el bloqueo cuenta desde el fallo que lo completa');

    $check($segundos($fallos([4, 3, 2, 1])) === 0, 'debajo del tope: 4 fallos, sin bloqueo');
    $check($segundos($fallos([4, 3, 2, 1, 0])) === 900, 'DISCRIMINANTE: en el tope, 5 fallos, bloquea 15 minutos desde el quinto');
    $check($segundos($fallos([5, 4, 3, 2, 1, 0])) === 900, 'por encima del tope: sigue bloqueado desde el último que lo completa');
    $check($segundos($fallos([10, 9, 8, 7, 6])) === 540, 'secondsToUnlock: el quinto fue hace 6 minutos, faltan 9 (540 s)');
    $check($segundos($fallos([40, 30, 20, 10, 1])) === 0, 'fuera de la ventana: 5 fallos, pero nunca 5 dentro de 15 minutos');
    $check($segundos($fallos([34, 33, 32, 31, 30])) === 0, 'un bloqueo ya vencido: el quinto fue hace 30 minutos');
    $check($segundos($fallos([4, 3, 2, 1, 0], 'ANA ')) === 900, 'el usuario se compara sin mayúsculas ni espacios');
    $check($segundos($fallos([4, 3, 2, 1, 0], 'otro', '10.0.0.9')) === 0, 'los fallos de otro usuario desde otra IP no bloquean a este');
    echoTerminal(' ');

    //──── 3. Por IP ────────────────────────────────────────────────────────────────────────────
    echoTerminal('[3/6] Por IP: muchos usuarios distintos desde la misma IP');

    $desdeLaIP = static function (int $cuantos) use ($fila): array {
        $filas = [];
        for ($i = 0; $i < $cuantos; $i++) {
            $filas[] = $fila("usuario-{$i}", '10.0.0.1', intdiv($i, 2));
        }
        return $filas;
    };
    $check($segundos($desdeLaIP(19), 'zeta') === 0, 'debajo del tope: 19 fallos de usuarios distintos desde la misma IP');
    $check($segundos($desdeLaIP(20), 'zeta') > 0, 'DISCRIMINANTE: 20 fallos desde la misma IP bloquean a un usuario que no falló nunca');
    $check($segundos($desdeLaIP(20), 'zeta', '10.0.0.2') === 0, 'el mismo usuario desde otra IP no está bloqueado');
    echoTerminal(' ');

    //──── 4. Qué filas cuentan ─────────────────────────────────────────────────────────────────
    echoTerminal('[4/6] Qué filas cuentan: fallos de las vías del OTP, y las bloqueadas no alargan el bloqueo');

    $exitos = array_map(fn (int $m): array => $fila('ana', '10.0.0.1', $m, ['otpVia' => OTPRateLimiter::VIA_CHECK_TOTP], 1), [4, 3, 2, 1, 0]);
    $check($segundos($exitos) === 0, 'los intentos correctos no cuentan');
    $check($segundos($fallos([4, 3, 2, 1, 0], 'ana', '10.0.0.1', ['dimensions' => ['w' => null]])) === 0, 'los fallos del login con contraseña (sin vía) no cuentan: ya los cuenta failedAttempts');
    $check($segundos($fallos([4, 3, 2, 1, 0], 'ana', '10.0.0.1', ['otpVia' => OTPRateLimiter::VIA_LOGIN_PASSWORD])) === 0, 'ni los de la vía login-password: los cuenta el freno del login');
    foreach (OTPRateLimiter::VIAS as $via) {
        $check($segundos($fallos([4, 3, 2, 1, 0], 'ana', '10.0.0.1', ['otpVia' => $via])) === 900, "los fallos de la vía {$via} cuentan");
    }
    $check($segundos($fallos([4, 3, 2, 1, 0], 'ana', '10.0.0.1', ['otpVia' => OTPRateLimiter::VIA_CHECK_TOTP, 'otpLocked' => true])) === 0,
        'DISCRIMINANTE: las respuestas bloqueadas se registran, pero no cuentan: el bloqueo no se alarga solo');
    $sinColumna = array_map(function (array $f): array {
        unset($f['extraData']);
        return $f;
    }, $fallos([4, 3, 2, 1, 0]));
    $check($segundos($sinColumna) === 900, 'sin la columna extraData no se sabe la vía y cuenta: falla cerrado');
    $comoArreglo = array_map(fn (array $f): array => ['extraData' => ['otpVia' => OTPRateLimiter::VIA_GENERATE_OTP]] + $f, $sinColumna);
    $check($segundos($comoArreglo) === 900, 'extraData ya decodificado también se entiende');
    $check($segundos([['basura' => true], 'no es una fila', $fila('ana', '10.0.0.1', 0)]) === 0, 'una fila ilegible se ignora, y un fallo solo no bloquea');
    echoTerminal(' ');

    //──── 5. La IP como grupo ────────────────────────────────────────────────────────────────────
    echoTerminal('[5/6] La IP se compara por grupo: una IPv6 por su /64; una IPv4, tal cual');

    $grupos = [
        '203.0.113.7' => '203.0.113.7',
        '2001:0db8:0000:0001:0000:0000:0000:0042' => '2001:db8:0:1::/64',
        '2001:db8:0:1::99' => '2001:db8:0:1::/64',
        '2001:DB8:0:1:ABCD::1' => '2001:db8:0:1::/64',
        '::ffff:198.51.100.4' => '198.51.100.4',
        'no-es-una-ip' => 'no-es-una-ip',
        '' => '',
    ];
    $malos = [];
    foreach ($grupos as $entrada => $esperado) {
        if (OTPRateLimiter::ipGroup((string) $entrada) !== $esperado) {
            $malos[] = "«{$entrada}» → «" . OTPRateLimiter::ipGroup((string) $entrada) . "»";
        }
    }
    $check($malos === [], 'i1 ipGroup: IPv4 tal cual; IPv6 completa, abreviada y en mayúsculas, a su /64; ::ffff:IPv4, la IPv4; lo demás, tal cual', implode(' · ', $malos) ?: null);

    $login = ['otpVia' => OTPRateLimiter::VIA_LOGIN_PASSWORD];
    $loginDesde = static function (array $ips, string $usuario = 'ana') use ($fila, $login): array {
        return array_map(fn (int $i): array => $fila($usuario, $ips[$i], $i, $login), array_keys($ips));
    };
    $frenoLogin = static fn (array $filas, string $ip): int => OTPRateLimiter::loginSecondsToUnlockFromRows($filas, $ip, $ahora, $config);
    $misma64 = array_map(fn (int $i): string => '2001:db8:0:1::' . dechex($i + 1), range(0, 4));
    $check($frenoLogin($loginDesde($misma64), '2001:db8:0:1::ffff') > 0,
        'i2 DISCRIMINANTE: 5 fallos desde 5 direcciones del mismo /64 frenan el login como si fueran una, también a otra dirección de ese /64');
    $otpDesde64 = array_map(fn (int $i): array => $fila("usuario-{$i}", '2001:db8:0:1::' . dechex($i + 1), intdiv($i, 2)), range(0, 19));
    $check($segundos($otpDesde64, 'zeta', '2001:db8:0:1::abc') > 0, 'i2 y lo mismo en el tope del OTP por IP: 20 direcciones del mismo /64');
    $dosRedes = array_merge(array_slice($misma64, 0, 3), ['2001:db8:0:2::1', '2001:db8:0:2::2']);
    $check($frenoLogin($loginDesde($dosRedes), '2001:db8:0:1::1') === 0 && $frenoLogin($loginDesde($dosRedes), '2001:db8:0:2::1') === 0,
        'i3 dos /64 distintos siguen siendo dos: 3 y 2 fallos no completan el tope de 5 en ninguno');
    $distintas = static fn (array $filas): int => OTPRateLimiter::loginDistinctIPsFromRows($filas, 'ana', $ahora, $config);
    $check($distintas($loginDesde(array_slice($misma64, 0, 3))) === 1,
        'i4 DISCRIMINANTE: tres direcciones del mismo /64 cuentan como UNA para el bloqueo de la cuenta', (string) $distintas($loginDesde(array_slice($misma64, 0, 3))));
    $check($distintas($loginDesde(['2001:db8:0:1::1', '2001:db8:0:2::1', '2001:db8:0:3::1'])) === 3, 'i4 y tres /64 distintos, tres');
    $vecinas = ['10.0.0.1', '10.0.0.2', '10.0.0.3', '10.0.0.4', '10.0.0.5'];
    $check($frenoLogin($loginDesde($vecinas), '10.0.0.1') === 0 && $frenoLogin($loginDesde(array_fill(0, 5, '10.0.0.1')), '10.0.0.1') > 0 && $distintas($loginDesde(array_slice($vecinas, 0, 3))) === 3,
        'i5 CANARIO: IPv4 como antes: cinco vecinas no se agrupan, la misma cinco veces frena, y tres son tres');
    echoTerminal(' ');

    //──── 6. El envío del código de un uso ──────────────────────────────────────────────────────
    echoTerminal('[6/6] El envío del código: una espera mínima de 45 s y un tope de 5 por hora, por nombre o correo');

    $nombres = ['ana', 'ana@ejemplo.com'];
    $envio = static function (string $usuario, int $segundosAtras, array $extra = ['otpVia' => OTPRateLimiter::VIA_GENERATE_OTP], int $exito = 1) use ($ahora): array {
        return [
            'usernameAttempt' => $usuario,
            'ip' => '10.0.0.1',
            'success' => $exito,
            'date' => (clone $ahora)->modify("-{$segundosAtras} seconds")->format('Y-m-d H:i:s'),
            'extraData' => json_encode($extra),
        ];
    };
    $limite = static fn (array $filas, array $de = ['ana', 'ana@ejemplo.com']): bool => OTPRateLimiter::sendLimitReachedFromRows($filas, $de, $ahora);
    $hora = [60, 600, 1200, 1800, 2400];

    $loginJS = (string) @file_get_contents(basepath('app/classes/PiecesPHP/UserSystem/Statics/js/login.js'));
    $boton = preg_match('/\bRESEND_SECONDS\s*=\s*(\d+)/', $loginJS, $m) === 1 ? (int) $m[1] : -1;
    $check(OTPRateLimiter::SEND_MIN_SECONDS_BETWEEN === $boton && OTPRateLimiter::SEND_MAX_PER_HOUR === 5, 'u0 la espera del servidor es la del botón (RESEND_SECONDS de login.js) y el tope, 5 por hora', "servidor " . OTPRateLimiter::SEND_MIN_SECONDS_BETWEEN . " s, botón {$boton} s");
    $check(!$limite([]), 'u1 sin envíos previos, no hay límite');
    $check($limite([$envio('ana', 10)]), 'u2 DISCRIMINANTE: un envío con éxito hace 10 s, límite');
    $check(!$limite([$envio('ana', 50)]), 'u3 uno hace 50 s, no');
    $check($limite(array_map(fn (int $s): array => $envio('ana', $s), $hora)), 'u4 DISCRIMINANTE: cinco en la última hora, límite (aunque el último tenga más de 45 s)');
    $check(!$limite(array_map(fn (int $s): array => $envio('ana', $s), array_slice($hora, 0, 4))), 'u4 CANARIO: cuatro, no');
    $check(!$limite(array_map(fn (int $s): array => $envio('ana', $s + 3600), $hora)), 'u5 cinco de hace más de una hora, no');
    $fallos6 = array_map(fn (int $s): array => $envio('ana', $s, ['otpVia' => OTPRateLimiter::VIA_GENERATE_OTP], 0), [10, 60, 600, 1200, 1800]);
    $otraVia = array_map(fn (int $s): array => $envio('ana', $s, ['otpVia' => OTPRateLimiter::VIA_CHECK_TOTP]), [10, 60, 600, 1200, 1800]);
    $otroUsuario = array_map(fn (int $s): array => $envio('beto', $s), [10, 60, 600, 1200, 1800]);
    $check(!$limite($fallos6) && !$limite($otraVia) && !$limite($otroUsuario) && !$limite(array_merge($fallos6, $otraVia, $otroUsuario)),
        'u6 los fallos, otra vía y otro usuario no cuentan (ni sueltos ni juntos)');
    $alterna = array_merge([$envio('ana', 600), $envio('ana', 1200)], [$envio('ana@ejemplo.com', 1800), $envio('ana@ejemplo.com', 2400), $envio('ANA@ejemplo.com', 3000)]);
    $check($limite($alterna), 'u9 DISCRIMINANTE: alternar nombre y correo (y mayúsculas) comparte un solo contador: 2 por nombre + 3 por correo, límite');
    $check(!$limite($alterna, ['ana']) && !$limite($alterna, ['ana@ejemplo.com']), 'u9 CANARIO: contados por separado serían 2 y 3, sin límite: el agujero que cierra contar los dos');
    $check($limite([$envio('Ana', 5)], ['ana']), 'u9 sin distinguir mayúsculas: «Ana» cuenta para «ana»');
    $check($limite([$envio('ana', -30)]), 'u10 un envío con fecha del futuro cuenta como reciente: falla cerrado');
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:OTPRateLimiter] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "El límite de intentos del OTP cuenta y bloquea como debe ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_NONE])->register();
