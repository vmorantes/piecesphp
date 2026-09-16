<?php

//El límite de intentos del OTP y del segundo factor, con filas y fechas sintéticas: la lógica es una función pura.

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
    ];
    $ahora = new \DateTime('2026-01-10 12:00:00');
    $fila = static function (string $usuario, string $ip, int $minutosAtras, array $extra = ['otpVia' => OTPRateLimiter::VIA_CHECK_TOTP], int $exito = 0) use ($ahora): array {
        return [
            'username_attempt' => $usuario,
            'ip' => $ip,
            'success' => $exito,
            'date' => (clone $ahora)->modify("-{$minutosAtras} minutes")->format('Y-m-d H:i:s'),
            'extra_data' => json_encode($extra),
        ];
    };
    $fallos = static function (array $minutosAtras, string $usuario = 'ana', string $ip = '10.0.0.1', array $extra = ['otpVia' => OTPRateLimiter::VIA_CHECK_TOTP]) use ($fila): array {
        return array_map(fn (int $minutos): array => $fila($usuario, $ip, $minutos, $extra), $minutosAtras);
    };
    $segundos = static function (array $filas, string $usuario = 'ana', string $ip = '10.0.0.1') use ($ahora, $config): int {
        return OTPRateLimiter::secondsToUnlockFromRows($filas, $usuario, $ip, $ahora, $config);
    };

    //──── 1. La configuración ──────────────────────────────────────────────────────────────────
    echoTerminal('[1/4] config(): la de config.php, con las cinco vías que cuentan');

    $check(OTPRateLimiter::config() === $config, 'otp_security de config.php: 5 por usuario, 20 por IP, 15 de ventana, 15 de bloqueo, uniforme y 20 del código',
        json_encode(OTPRateLimiter::config(), JSON_THROW_ON_ERROR));
    $check(OTPRateLimiter::VIAS === ['generate-otp', 'check-totp', 'two-factor-auth-status', 'login-totp', 'recovery-code'], 'las vías: generate-otp, check-totp, two-factor-auth-status, login-totp y recovery-code');
    echoTerminal(' ');

    //──── 2. Por usuario ───────────────────────────────────────────────────────────────────────
    echoTerminal('[2/4] Por usuario: el tope dentro de la ventana, y el bloqueo cuenta desde el fallo que lo completa');

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
    echoTerminal('[3/4] Por IP: muchos usuarios distintos desde la misma IP');

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
    echoTerminal('[4/4] Qué filas cuentan: fallos de las vías del OTP, y las bloqueadas no alargan el bloqueo');

    $exitos = array_map(fn (int $m): array => $fila('ana', '10.0.0.1', $m, ['otpVia' => OTPRateLimiter::VIA_CHECK_TOTP], 1), [4, 3, 2, 1, 0]);
    $check($segundos($exitos) === 0, 'los intentos correctos no cuentan');
    $check($segundos($fallos([4, 3, 2, 1, 0], 'ana', '10.0.0.1', ['dimensions' => ['w' => null]])) === 0, 'los fallos del login con contraseña (sin vía) no cuentan: ya los cuenta failed_attempts');
    foreach (OTPRateLimiter::VIAS as $via) {
        $check($segundos($fallos([4, 3, 2, 1, 0], 'ana', '10.0.0.1', ['otpVia' => $via])) === 900, "los fallos de la vía {$via} cuentan");
    }
    $check($segundos($fallos([4, 3, 2, 1, 0], 'ana', '10.0.0.1', ['otpVia' => OTPRateLimiter::VIA_CHECK_TOTP, 'otpLocked' => true])) === 0,
        'DISCRIMINANTE: las respuestas bloqueadas se registran, pero no cuentan: el bloqueo no se alarga solo');
    $sinColumna = array_map(function (array $f): array {
        unset($f['extra_data']);
        return $f;
    }, $fallos([4, 3, 2, 1, 0]));
    $check($segundos($sinColumna) === 900, 'sin la columna extra_data no se sabe la vía y cuenta: falla cerrado');
    $comoArreglo = array_map(fn (array $f): array => ['extra_data' => ['otpVia' => OTPRateLimiter::VIA_GENERATE_OTP]] + $f, $sinColumna);
    $check($segundos($comoArreglo) === 900, 'extra_data ya decodificado también se entiende');
    $check($segundos([['basura' => true], 'no es una fila', $fila('ana', '10.0.0.1', 0)]) === 0, 'una fila ilegible se ignora, y un fallo solo no bloquea');
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
