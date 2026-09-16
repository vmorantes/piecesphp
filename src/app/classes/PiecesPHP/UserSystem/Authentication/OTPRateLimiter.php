<?php

/**
 * OTPRateLimiter.php
 */

namespace PiecesPHP\UserSystem\Authentication;

use App\Model\LoginAttemptsModel;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;

/**
 * OTPRateLimiter - Límite de intentos de las vías del OTP y del segundo factor, por usuario y por IP.
 *
 * Cuenta los fallos que login_attempts guarda con su vía en extra_data (clave `otpVia`). La configuración es
 * `otp_security` (config.php).
 *  - Un bloqueo empieza en el fallo que completa el tope dentro de la ventana y dura `lockMinutes` desde él.
 *  - Las respuestas bloqueadas se registran marcadas (`otpLocked`) y no cuentan: el bloqueo no se alarga solo.
 *  - Sin la columna extra_data, que no está en el esquema declarado (el modelo la detecta al vuelo), no se sabe la vía
 *    de un fallo y cuenta: falla cerrado.
 *
 * @package     PiecesPHP\UserSystem\Authentication
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class OTPRateLimiter
{
    const VIA_GENERATE_OTP = 'generate-otp';
    const VIA_CHECK_TOTP = 'check-totp';
    const VIA_TWO_FACTOR_STATUS = 'two-factor-auth-status';
    const VIA_LOGIN_TOTP = 'login-totp';
    const VIA_RECOVERY_CODE = 'recovery-code';
    const VIAS = [
        self::VIA_GENERATE_OTP,
        self::VIA_CHECK_TOTP,
        self::VIA_TWO_FACTOR_STATUS,
        self::VIA_LOGIN_TOTP,
        self::VIA_RECOVERY_CODE,
    ];

    const EXTRA_DATA_VIA = 'otpVia';
    const EXTRA_DATA_LOCKED = 'otpLocked';
    const CONFIG_KEY = 'otp_security';
    const ERROR_TOO_MANY_ATTEMPTS = 'TOO_MANY_ATTEMPTS';

    const LANG_GROUP = UserSystemFeaturesLang::LANG_GROUP;

    /**
     * La configuración, con el valor por defecto donde falte o no sea válido.
     *
     * @return array{maxFailuresPerUser: int, maxFailuresPerIP: int, windowMinutes: int, lockMinutes: int, uniformResponse: bool, oneUseCodeMinutes: int}
     */
    public static function config(): array
    {
        $configured = get_config(self::CONFIG_KEY);
        $configured = is_array($configured) ? $configured : [];
        $uniformResponse = $configured['uniformResponse'] ?? null;
        return [
            'maxFailuresPerUser' => self::positiveInt($configured['maxFailuresPerUser'] ?? null, 5),
            'maxFailuresPerIP' => self::positiveInt($configured['maxFailuresPerIP'] ?? null, 20),
            'windowMinutes' => self::positiveInt($configured['windowMinutes'] ?? null, 15),
            'lockMinutes' => self::positiveInt($configured['lockMinutes'] ?? null, 15),
            'uniformResponse' => is_bool($uniformResponse) ? $uniformResponse : true,
            'oneUseCodeMinutes' => self::positiveInt($configured['oneUseCodeMinutes'] ?? null, 20),
        ];
    }

    /**
     * La IP de la petición, como la guarda login_attempts.
     *
     * @return string
     */
    public static function clientIP(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        return is_string($ip) && $ip !== '' ? $ip : '0.0.0.0';
    }

    /**
     * @param string $username
     * @param string $ip
     * @param \DateTimeInterface|null $now Null: la hora de la base, la misma con la que se guardan los intentos
     * @return bool
     */
    public static function isLocked(string $username, string $ip, ?\DateTimeInterface $now = null): bool
    {
        return self::secondsToUnlock($username, $ip, $now) > 0;
    }

    /**
     * Segundos hasta que se levanta el bloqueo del usuario o de la IP; 0 si no hay bloqueo.
     *
     * @param string $username
     * @param string $ip
     * @param \DateTimeInterface|null $now Null: la hora de la base, la misma con la que se guardan los intentos
     * @return int
     */
    public static function secondsToUnlock(string $username, string $ip, ?\DateTimeInterface $now = null): int
    {
        $config = self::config();
        $database = (new BaseModel())->getDatabase();
        //SIN BASE NO SE PUEDE CONTAR: falla cerrado, ninguna vía del OTP sigue sin comprobar el límite.
        if ($database === null) {
            throw new \RuntimeException('OTPRateLimiter: sin conexión a la base no se puede comprobar el límite de intentos.');
        }
        if ($now === null) {
            $statement = $database->prepare('SELECT NOW()');
            $statement->execute();
            $databaseNow = $statement->fetchColumn();
            $parsed = is_string($databaseNow) ? \DateTime::createFromFormat('Y-m-d H:i:s', $databaseNow) : false;
            $now = $parsed !== false ? $parsed : new \DateTime();
        }
        $since = date('Y-m-d H:i:s', $now->getTimestamp() - ($config['windowMinutes'] + $config['lockMinutes']) * 60);
        $statement = $database->prepare('SELECT * FROM ' . LoginAttemptsModel::TABLE . ' WHERE success = ? AND date >= ? AND (username_attempt = ? OR ip = ?)');
        $statement->execute([LoginAttemptsModel::FAIL_ATTEMPT, $since, $username, $ip]);
        return self::secondsToUnlockFromRows($statement->fetchAll(\PDO::FETCH_ASSOC), $username, $ip, $now, $config);
    }

    /**
     * La lógica del límite, sin base: los segundos hasta que se levanta el bloqueo, a partir de las filas de login_attempts.
     *
     * @param array<mixed> $rows Filas con username_attempt, ip, success, date (Y-m-d H:i:s) y, si la columna existe, extra_data
     * @param string $username
     * @param string $ip
     * @param \DateTimeInterface $now
     * @param array{maxFailuresPerUser: int, maxFailuresPerIP: int, windowMinutes: int, lockMinutes: int, uniformResponse: bool, oneUseCodeMinutes: int} $config
     * @return int
     */
    public static function secondsToUnlockFromRows(array $rows, string $username, string $ip, \DateTimeInterface $now, array $config): int
    {
        $username = mb_strtolower(trim($username));
        $byUser = [];
        $byIP = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !self::countsAsFailure($row)) {
                continue;
            }
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', (string) ($row['date'] ?? ''));
            if ($date === false) {
                continue;
            }
            if ($username !== '' && mb_strtolower(trim((string) ($row['username_attempt'] ?? ''))) === $username) {
                $byUser[] = $date->getTimestamp();
            }
            if ($ip !== '' && (string) ($row['ip'] ?? '') === $ip) {
                $byIP[] = $date->getTimestamp();
            }
        }
        $window = $config['windowMinutes'] * 60;
        $lock = $config['lockMinutes'] * 60;
        $until = max(self::lockedUntil($byUser, $config['maxFailuresPerUser'], $window, $lock), self::lockedUntil($byIP, $config['maxFailuresPerIP'], $window, $lock));
        return max(0, $until - $now->getTimestamp());
    }

    /**
     * Registra un intento de una de las vías, con su vía en extra_data.
     *
     * @param string $via Una de VIAS
     * @param int|null $userID
     * @param string $username
     * @param bool $success
     * @param string $message
     * @param bool $locked Si fue una respuesta bloqueada: se registra, pero no cuenta para el límite
     * @return void
     */
    public static function record(string $via, ?int $userID, string $username, bool $success, string $message, bool $locked = false): void
    {
        $extraData = [self::EXTRA_DATA_VIA => $via];
        if ($locked) {
            $extraData[self::EXTRA_DATA_LOCKED] = true;
        }
        LoginAttemptsModel::addLogin($userID, $username, $success, $message, $extraData);
    }

    /**
     * La respuesta de un intento bloqueado: 429 con Retry-After y un mensaje genérico. Se registra marcada, y no cuenta.
     *
     * @param ResponseRoute $response
     * @param string $via Una de VIAS
     * @param string $username
     * @param int $seconds
     * @return ResponseRoute
     */
    public static function lockedResponse(ResponseRoute $response, string $via, string $username, int $seconds): ResponseRoute
    {
        $seconds = max(1, $seconds);
        self::record($via, null, $username, false, self::lockedMessage(), true);
        return $response->withHeader('Retry-After', (string) $seconds)->withJson([
            'success' => false,
            'error' => self::ERROR_TOO_MANY_ATTEMPTS,
            'message' => self::lockedMessage(),
            'retryAfter' => $seconds,
        ], 429);
    }

    /**
     * @return string
     */
    public static function lockedMessage(): string
    {
        return __(self::LANG_GROUP, 'Demasiados intentos. Inténtelo de nuevo más tarde.');
    }

    /**
     * La respuesta de generate-otp con uniformResponse: la misma exista o no el usuario.
     *
     * @return string
     */
    public static function uniformOTPMessage(): string
    {
        return __(self::LANG_GROUP, 'Si el usuario existe, recibirá un código en su correo.');
    }

    /**
     * @param array<mixed> $row
     * @return bool
     */
    protected static function countsAsFailure(array $row): bool
    {
        if ((int) ($row['success'] ?? LoginAttemptsModel::SUCCESS_ATTEMPT) !== LoginAttemptsModel::FAIL_ATTEMPT) {
            return false;
        }
        //SIN LA COLUMNA extra_data no se sabe la vía: cuenta. Falla cerrado.
        if (!array_key_exists('extra_data', $row)) {
            return true;
        }
        $extraData = is_string($row['extra_data']) ? json_decode($row['extra_data'], true) : $row['extra_data'];
        if (!is_array($extraData)) {
            return false;
        }
        return in_array($extraData[self::EXTRA_DATA_VIA] ?? null, self::VIAS, true) && empty($extraData[self::EXTRA_DATA_LOCKED]);
    }

    /**
     * Hasta cuándo bloquean unos fallos: el que completa $max dentro de la ventana bloquea $lock segundos desde él.
     *
     * @param int[] $times Marcas de tiempo de los fallos
     * @param int $max
     * @param int $window Segundos
     * @param int $lock Segundos
     * @return int Marca de tiempo; 0 si nunca se completó el tope
     */
    protected static function lockedUntil(array $times, int $max, int $window, int $lock): int
    {
        sort($times);
        $until = 0;
        $count = count($times);
        for ($index = $max - 1; $index < $count; $index++) {
            if ($times[$index] - $times[$index - $max + 1] <= $window) {
                $until = max($until, $times[$index] + $lock);
            }
        }
        return $until;
    }

    /**
     * @param mixed $value
     * @param int $default
     * @return int
     */
    protected static function positiveInt($value, int $default): int
    {
        return is_int($value) && $value > 0 ? $value : $default;
    }
}
