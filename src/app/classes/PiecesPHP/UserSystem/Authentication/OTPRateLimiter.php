<?php

/**
 * OTPRateLimiter.php
 */

namespace PiecesPHP\UserSystem\Authentication;

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;

/**
 * OTPRateLimiter - Límite de intentos de las vías del OTP y del segundo factor, por usuario y por IP.
 *
 * Cuenta los fallos que login_attempts guarda con su vía en extraData (clave `otpVia`). La configuración es
 * `otp_security` (config.php).
 *  - Un bloqueo empieza en el fallo que completa el tope dentro de la ventana y dura `lockMinutes` desde él.
 *  - Las respuestas bloqueadas se registran marcadas (`otpLocked`) y no cuentan: el bloqueo no se alarga solo.
 *  - Sin la columna extraData, que no está en el esquema declarado (el modelo la detecta al vuelo), no se sabe la vía
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
    //FUERA DE VIAS a propósito: los topes del OTP no cuentan las contraseñas, y el freno del login no cuenta el OTP.
    const VIA_LOGIN_PASSWORD = 'login-password';

    const EXTRA_DATA_VIA = 'otpVia';
    const EXTRA_DATA_LOCKED = 'otpLocked';
    const CONFIG_KEY = 'otp_security';
    const ERROR_TOO_MANY_ATTEMPTS = 'TOO_MANY_ATTEMPTS';

    const LANG_GROUP = UserSystemFeaturesLang::LANG_GROUP;

    /**
     * La configuración, con el valor por defecto donde falte o no sea válido.
     *
     * @return array{maxFailuresPerUser: int, maxFailuresPerIP: int, windowMinutes: int, lockMinutes: int, uniformResponse: bool, oneUseCodeMinutes: int, loginMaxFailuresPerIP: int, loginAccountDistinctIPs: int, loginAccountWindowMinutes: int}
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
            'loginMaxFailuresPerIP' => self::positiveInt($configured['loginMaxFailuresPerIP'] ?? null, 5),
            'loginAccountDistinctIPs' => self::positiveInt($configured['loginAccountDistinctIPs'] ?? null, 3),
            'loginAccountWindowMinutes' => self::positiveInt($configured['loginAccountWindowMinutes'] ?? null, 1440),
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
        $statement = $database->prepare('SELECT * FROM ' . LoginAttemptsModel::TABLE . ' WHERE success = ? AND date >= ? AND (usernameAttempt = ? OR ip = ?)');
        $statement->execute([LoginAttemptsModel::FAIL_ATTEMPT, $since, $username, $ip]);
        return self::secondsToUnlockFromRows($statement->fetchAll(\PDO::FETCH_ASSOC), $username, $ip, $now, $config);
    }

    /**
     * La lógica del límite, sin base: los segundos hasta que se levanta el bloqueo, a partir de las filas de login_attempts.
     *
     * @param array<mixed> $rows Filas con usernameAttempt, ip, success, date (Y-m-d H:i:s) y, si la columna existe, extraData
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
            if ($username !== '' && mb_strtolower(trim((string) ($row['usernameAttempt'] ?? ''))) === $username) {
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
     * Segundos hasta que se levanta el freno del login con contraseña para una IP; 0 si no hay freno.
     *
     * @param string $ip
     * @param \DateTimeInterface|null $now Null: la hora de la base, la misma con la que se guardan los intentos
     * @return int
     */
    public static function loginSecondsToUnlock(string $ip, ?\DateTimeInterface $now = null): int
    {
        $config = self::config();
        $database = self::databaseOrFail();
        $now = $now ?? self::databaseNow($database);
        $since = date('Y-m-d H:i:s', $now->getTimestamp() - ($config['windowMinutes'] + $config['lockMinutes']) * 60);
        $statement = $database->prepare('SELECT * FROM ' . LoginAttemptsModel::TABLE . ' WHERE success = ? AND date >= ? AND ip = ?');
        $statement->execute([LoginAttemptsModel::FAIL_ATTEMPT, $since, $ip]);
        return self::loginSecondsToUnlockFromRows($statement->fetchAll(\PDO::FETCH_ASSOC), $ip, $now, $config);
    }

    /**
     * La lógica del freno del login por IP, sin base: el fallo que completa loginMaxFailuresPerIP dentro de windowMinutes
     * frena esa IP lockMinutes desde él.
     *
     * @param array<mixed> $rows Filas con ip, success, date (Y-m-d H:i:s) y, si la columna existe, extraData
     * @param string $ip
     * @param \DateTimeInterface $now
     * @param array{windowMinutes: int, lockMinutes: int, loginMaxFailuresPerIP: int} $config
     * @return int
     */
    public static function loginSecondsToUnlockFromRows(array $rows, string $ip, \DateTimeInterface $now, array $config): int
    {
        if ($ip === '') {
            return 0;
        }
        $byIP = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !self::countsAsLoginFailure($row) || (string) ($row['ip'] ?? '') !== $ip) {
                continue;
            }
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', (string) ($row['date'] ?? ''));
            if ($date !== false) {
                $byIP[] = $date->getTimestamp();
            }
        }
        $until = self::lockedUntil($byIP, $config['loginMaxFailuresPerIP'], $config['windowMinutes'] * 60, $config['lockMinutes'] * 60);
        return max(0, $until - $now->getTimestamp());
    }

    /**
     * Si los fallos de contraseña de un usuario vienen de bastantes IP distintas como para bloquear la cuenta.
     *
     * @param string $username
     * @param \DateTimeInterface|null $now Null: la hora de la base, la misma con la que se guardan los intentos
     * @return bool
     */
    public static function loginShouldBlockAccount(string $username, ?\DateTimeInterface $now = null): bool
    {
        $config = self::config();
        $database = self::databaseOrFail();
        $now = $now ?? self::databaseNow($database);
        $since = date('Y-m-d H:i:s', $now->getTimestamp() - $config['loginAccountWindowMinutes'] * 60);
        $statement = $database->prepare('SELECT * FROM ' . LoginAttemptsModel::TABLE . ' WHERE success = ? AND date >= ? AND usernameAttempt = ?');
        $statement->execute([LoginAttemptsModel::FAIL_ATTEMPT, $since, $username]);
        return self::loginDistinctIPsFromRows($statement->fetchAll(\PDO::FETCH_ASSOC), $username, $now, $config) >= $config['loginAccountDistinctIPs'];
    }

    /**
     * Si una cuenta está BAJO ATAQUE: la misma condición con la que se bloquea una activa (P223 a), al menos $minFailures
     * fallos de contraseña en loginAccountWindowMinutes y desde loginAccountDistinctIPs IP distintas.
     *
     * @param string $username
     * @param int $minFailures UsersController::MAX_ATTEMPTS
     * @param \DateTimeInterface|null $now Null: la hora de la base, la misma con la que se guardan los intentos
     * @return bool
     */
    public static function loginAccountUnderAttack(string $username, int $minFailures, ?\DateTimeInterface $now = null): bool
    {
        $config = self::config();
        $database = self::databaseOrFail();
        $now = $now ?? self::databaseNow($database);
        $since = date('Y-m-d H:i:s', $now->getTimestamp() - $config['loginAccountWindowMinutes'] * 60);
        $statement = $database->prepare('SELECT * FROM ' . LoginAttemptsModel::TABLE . ' WHERE success = ? AND date >= ? AND usernameAttempt = ?');
        $statement->execute([LoginAttemptsModel::FAIL_ATTEMPT, $since, $username]);
        return self::loginAccountUnderAttackFromRows($statement->fetchAll(\PDO::FETCH_ASSOC), $username, $minFailures, $now, $config);
    }

    /**
     * La decisión de loginAccountUnderAttack(), sin base.
     *
     * @param array<mixed> $rows Filas con usernameAttempt, ip, success, date (Y-m-d H:i:s) y, si la columna existe, extraData
     * @param string $username
     * @param int $minFailures
     * @param \DateTimeInterface $now
     * @param array{loginAccountWindowMinutes: int, loginAccountDistinctIPs: int} $config
     * @return bool
     */
    public static function loginAccountUnderAttackFromRows(array $rows, string $username, int $minFailures, \DateTimeInterface $now, array $config): bool
    {
        $username = mb_strtolower(trim($username));
        if ($username === '') {
            return false;
        }
        $since = $now->getTimestamp() - $config['loginAccountWindowMinutes'] * 60;
        $failures = 0;
        foreach ($rows as $row) {
            if (!is_array($row) || !self::countsAsLoginFailure($row) || mb_strtolower(trim((string) ($row['usernameAttempt'] ?? ''))) !== $username) {
                continue;
            }
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', (string) ($row['date'] ?? ''));
            if ($date !== false && $date->getTimestamp() >= $since && $date->getTimestamp() <= $now->getTimestamp()) {
                $failures++;
            }
        }
        return $failures >= $minFailures && self::loginDistinctIPsFromRows($rows, $username, $now, $config) >= $config['loginAccountDistinctIPs'];
    }

    /**
     * Cuántas IP distintas tienen fallos de contraseña de un usuario en las últimas loginAccountWindowMinutes, sin base.
     *
     * @param array<mixed> $rows Filas con usernameAttempt, ip, success, date (Y-m-d H:i:s) y, si la columna existe, extraData
     * @param string $username
     * @param \DateTimeInterface $now
     * @param array{loginAccountWindowMinutes: int} $config
     * @return int
     */
    public static function loginDistinctIPsFromRows(array $rows, string $username, \DateTimeInterface $now, array $config): int
    {
        $username = mb_strtolower(trim($username));
        if ($username === '') {
            return 0;
        }
        $since = $now->getTimestamp() - $config['loginAccountWindowMinutes'] * 60;
        $ips = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !self::countsAsLoginFailure($row) || mb_strtolower(trim((string) ($row['usernameAttempt'] ?? ''))) !== $username) {
                continue;
            }
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', (string) ($row['date'] ?? ''));
            if ($date !== false && $date->getTimestamp() >= $since && $date->getTimestamp() <= $now->getTimestamp()) {
                $ips[(string) ($row['ip'] ?? '')] = true;
            }
        }
        return count($ips);
    }

    /**
     * Registra un intento de una de las vías, con su vía en extraData.
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
     * La respuesta bloqueada del formulario de acceso: la de lockedResponse(), con los campos que el cliente del login
     * espera (sin `auth` lo trata como un error inesperado).
     *
     * @param ResponseRoute $response
     * @param string $via VIA_LOGIN_PASSWORD o VIA_LOGIN_TOTP
     * @param string $username
     * @param int $seconds
     * @return ResponseRoute
     */
    public static function lockedLoginResponse(ResponseRoute $response, string $via, string $username, int $seconds): ResponseRoute
    {
        $seconds = max(1, $seconds);
        self::record($via, null, $username, false, self::lockedMessage(), true);
        return $response->withHeader('Retry-After', (string) $seconds)->withJson([
            'success' => false,
            'auth' => false,
            'isAuth' => false,
            'token' => '',
            'error' => self::ERROR_TOO_MANY_ATTEMPTS,
            'user' => $username,
            'message' => self::lockedMessage(),
            'userData' => [],
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
        //SIN LA COLUMNA extraData no se sabe la vía: cuenta. Falla cerrado.
        if (!array_key_exists('extraData', $row)) {
            return true;
        }
        $extraData = is_string($row['extraData']) ? json_decode($row['extraData'], true) : $row['extraData'];
        if (!is_array($extraData)) {
            return false;
        }
        return in_array($extraData[self::EXTRA_DATA_VIA] ?? null, self::VIAS, true) && empty($extraData[self::EXTRA_DATA_LOCKED]);
    }

    /**
     * Si una fila es un fallo del login con contraseña que cuenta para su freno.
     *
     * @param array<mixed> $row
     * @return bool
     */
    protected static function countsAsLoginFailure(array $row): bool
    {
        if ((int) ($row['success'] ?? LoginAttemptsModel::SUCCESS_ATTEMPT) !== LoginAttemptsModel::FAIL_ATTEMPT) {
            return false;
        }
        //SIN LA COLUMNA extraData no se sabe la vía: cuenta. Falla cerrado.
        if (!array_key_exists('extraData', $row)) {
            return true;
        }
        $extraData = is_string($row['extraData']) ? json_decode($row['extraData'], true) : $row['extraData'];
        if (!is_array($extraData)) {
            return false;
        }
        return ($extraData[self::EXTRA_DATA_VIA] ?? null) === self::VIA_LOGIN_PASSWORD && empty($extraData[self::EXTRA_DATA_LOCKED]);
    }

    /**
     * @return \PDO
     */
    protected static function databaseOrFail(): \PDO
    {
        $database = (new BaseModel())->getDatabase();
        //SIN BASE NO SE PUEDE CONTAR: falla cerrado, el login no sigue sin comprobar su freno.
        if ($database === null) {
            throw new \RuntimeException('OTPRateLimiter: sin conexión a la base no se puede comprobar el límite de intentos.');
        }
        return $database;
    }

    /**
     * @param \PDO $database
     * @return \DateTimeInterface La hora de la base, la misma con la que se guardan los intentos
     */
    protected static function databaseNow(\PDO $database): \DateTimeInterface
    {
        $statement = $database->prepare('SELECT NOW()');
        $statement->execute();
        $databaseNow = $statement->fetchColumn();
        $parsed = is_string($databaseNow) ? \DateTime::createFromFormat('Y-m-d H:i:s', $databaseNow) : false;
        return $parsed !== false ? $parsed : new \DateTime();
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
