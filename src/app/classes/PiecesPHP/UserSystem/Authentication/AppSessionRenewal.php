<?php

/**
 * AppSessionRenewal.php
 */

namespace PiecesPHP\UserSystem\Authentication;

use EventsLog\Mappers\LogsMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Crypto\Sealed;
use PiecesPHP\Core\MaintenanceMode;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;
use PiecesPHP\UserSystem\UserTypes;

/**
 * AppSessionRenewal - Renovar la sesión de una app (ADR 0092 §4): la llave de un solo uso `selector.verificador`, firmada
 * por la clave del dispositivo. La fila manda sobre todo: nunca se mira el usuario de la petición ni la sesión actual.
 *
 * Cada renovación que entrega un token fabrica un identificador nuevo y lo pone en sessionHash en la misma sentencia
 * condicional (enmienda del ADR 0092): el token de acceso anterior deja de valer.
 *
 * @package     PiecesPHP\UserSystem\Authentication
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class AppSessionRenewal
{
    const SIGNED_PREFIX = 'pcsphp-renew-session-v1|';
    const CLOCK_SKEW_SECONDS = 300;

    const ERROR_MISSING_OR_UNEXPECTED_PARAMS = 'MISSING_OR_UNEXPECTED_PARAMS';
    const ERROR_CLOCK_SKEW = 'CLOCK_SKEW';
    const ERROR_UNAVAILABLE = 'UNAVAILABLE';
    const ERROR_REAUTH_REQUIRED = 'REAUTH_REQUIRED';
    const ERROR_RETRY = 'RETRY';
    const ERROR_MAINTENANCE = 'MAINTENANCE';

    const LANG_GROUP = UserSystemFeaturesLang::LANG_GROUP;

    /**
     * La acción renew-session: los pasos del ADR 0092 §4, en orden. Toda respuesta lleva Cache-Control: no-store.
     *
     * @param RequestRoute $request
     * @param ResponseRoute $response
     * @return ResponseRoute
     */
    public static function handle(RequestRoute $request, ResponseRoute $response): ResponseRoute
    {
        //Paso 1: la forma, solo del cuerpo.
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];
        $refreshToken = $body['refreshToken'] ?? null;
        $timestamp = $body['timestamp'] ?? null;
        $signature = $body['signature'] ?? null;
        $rawSignature = is_string($signature) ? base64_decode($signature, true) : false;
        //Con /D: sin él, «$» admite un salto de línea final, que acabaría en el verificador.
        if (!is_string($refreshToken) || preg_match('/^[A-Za-z0-9_-]{22}\.[A-Za-z0-9_-]{43}$/D', $refreshToken) !== 1
            || !is_string($timestamp) || preg_match('/^[1-9][0-9]{9}$/D', $timestamp) !== 1
            || !is_string($rawSignature) || strlen($rawSignature) !== 64) {
            return self::fail($response, 400, self::ERROR_MISSING_OR_UNEXPECTED_PARAMS);
        }

        //Paso 2: la hora, antes de cualquier consulta (no revela nada).
        $now = time();
        if (abs((int) $timestamp - $now) > self::CLOCK_SKEW_SECONDS) {
            return self::fail($response, 409, self::ERROR_CLOCK_SKEW, ['serverTime' => $now]);
        }

        //Paso 3: sin el llavero no se puede sellar ni firmar.
        if (Config::app_keyring_unreadable()) {
            return self::fail($response, 503, self::ERROR_UNAVAILABLE);
        }

        //Sin la migración no hay sesiones de app que renovar: se responde sin consultar la fila.
        if (!UserSessions::hasAppColumns()) {
            return self::fail($response, 401, self::ERROR_REAUTH_REQUIRED);
        }

        //Paso 4: la fila de app con ese selector.
        [$selector, $verifier] = explode('.', $refreshToken, 2);
        $row = self::rowBySelector($selector);
        if ($row === null) {
            return self::fail($response, 401, self::ERROR_REAUTH_REQUIRED);
        }

        //Paso 5: la firma del dispositivo. Sin ella no se toca la fila: un tercero sin la clave no cierra nada.
        if (!self::signatureIsValid(self::SIGNED_PREFIX . $refreshToken . '|' . $timestamp, $rawSignature, (string) ($row['deviceKey'] ?? ''))) {
            return self::fail($response, 401, self::ERROR_REAUTH_REQUIRED);
        }

        //Paso 6: el timestamp, estrictamente creciente.
        if ((int) $timestamp <= (int) ($row['refreshLastTimestamp'] ?? 0)) {
            return self::fail($response, 409, self::ERROR_RETRY);
        }

        //Paso 7: la renueva solo si index.php aceptaría el token resultante.
        if (!self::rowMayRenew($row)) {
            return self::fail($response, 401, self::ERROR_REAUTH_REQUIRED);
        }

        //Paso 7b: el mantenimiento que para su tipo, sin tocar la fila.
        $user = self::userRow((int) $row['userID']);
        if ($user !== null && MaintenanceMode::stopsRole((int) $user['type'])) {
            return self::fail($response, 503, self::ERROR_MAINTENANCE);
        }

        //Paso 8: según el verificador. Si la sentencia no casa, se relee la fila UNA vez.
        $hash = hash('sha256', $verifier);
        for ($attempt = 0; $attempt < 2; $attempt++) {
            if ($attempt > 0) {
                $row = self::rowBySelector($selector);
                $refusal = self::rereadRefusal($row, (int) $timestamp);
                if ($refusal !== null) {
                    return self::fail($response, $refusal[0], $refusal[1]);
                }
                if ($row === null) {
                    return self::fail($response, 401, self::ERROR_REAUTH_REQUIRED);
                }
            }
            $currentHash = (string) ($row['refreshHash'] ?? '');
            $previousHash = $row['refreshPrevHash'] ?? null;
            if ($currentHash !== '' && hash_equals($currentHash, $hash)) {
                $result = self::rotate($row, $selector, (int) $timestamp);
                if ($result !== null) {
                    return self::success($response, $result);
                }
                continue;
            }
            if (is_string($previousHash) && hash_equals($previousHash, $hash)) {
                $result = self::resend($row, $selector, (int) $timestamp);
                if ($result === false) {
                    return self::fail($response, 401, self::ERROR_REAUTH_REQUIRED);
                }
                return $result !== null ? self::success($response, $result) : self::fail($response, 409, self::ERROR_RETRY);
            }
            //Otro verificador con la firma buena: alguien con la clave repite una llave vieja. Se cierra solo esta fila.
            UserSessions::close((int) $row['id'], null, UserSessions::REASON_REFRESH_REUSE);
            LogsMapper::addLogSafe(LogsMapper::MSG_SESSION_REFRESH_REUSE, [
                '%username%' => $user !== null ? (string) $user['username'] : (string) $row['userID'],
                '%sessionID%' => (string) $row['id'],
            ], 'id', (string) $row['userID'], UsersModel::TABLE);
            return self::fail($response, 401, self::ERROR_REAUTH_REQUIRED);
        }
        return self::fail($response, 409, self::ERROR_RETRY);
    }

    /**
     * Si index.php aceptaría el token de esa fila: abierta y sin caducar, nacida (la FILA, no el token) después de las dos
     * marcas, usuario activo y no de sistema, tipo con rol y con acceso externo, y su organización con sesión.
     *
     * @param array<string,mixed> $row
     * @return bool
     */
    public static function rowMayRenew(array $row): bool
    {
        if (UserSessions::kindOf($row) !== UserSessions::KIND_APP || !UserSessions::isOpen($row)) {
            return false;
        }
        $user = self::userRow((int) ($row['userID'] ?? 0));
        if ($user === null || UsersModel::isSystemUser((int) $user['id']) || in_array($user['status'], UsersModel::STATUSES_INACTIVE_EQUIVALENT)) {
            return false;
        }
        $createdAt = strtotime((string) ($row['createdAt'] ?? ''));
        $userMark = is_string($user['sessionsValidFrom'] ?? null) ? $user['sessionsValidFrom'] : null;
        if ($createdAt === false || !SessionToken::isCreatedAfterMarks($createdAt, $userMark)) {
            return false;
        }
        $type = (int) $user['type'];
        if (!Roles::roleExists($type) || !UserTypes::hasExternalLogin($type)) {
            return false;
        }
        $organizationID = $user['organization'] ?? null;
        $found = $organizationID !== null ? OrganizationMapper::getBy($organizationID, 'id') : null;
        $organization = $found instanceof \stdClass ? OrganizationMapper::objectToMapper($found) : null;
        return OrganizationMapper::statusAllowsSession($organization);
    }

    /**
     * Si la fila releída en el paso 8 ya no se puede renovar: vuelve a pasar el paso 7 (pudo cerrarse, caducar o perder su
     * usuario) y el 6 (si otra petición posterior ya renovó, la de ahora es vieja: RETRY, nunca la rama de reutilización).
     *
     * @param array<string,mixed>|null $row
     * @param int $timestamp
     * @return array{0: int, 1: string}|null [estado, error], o null si puede seguir
     */
    private static function rereadRefusal(?array $row, int $timestamp): ?array
    {
        if ($row === null || !self::rowMayRenew($row)) {
            return [401, self::ERROR_REAUTH_REQUIRED];
        }
        if ($timestamp <= (int) ($row['refreshLastTimestamp'] ?? 0)) {
            return [409, self::ERROR_RETRY];
        }
        return null;
    }

    /**
     * Una firma IEEE P1363 (r||s, 64 bytes) en DER mínimo: cada entero sin ceros a la izquierda y con 0x00 delante si su
     * bit alto está puesto. Null si no son 64 bytes.
     *
     * @param string $raw
     * @return string|null
     */
    public static function derFromRaw(string $raw): ?string
    {
        if (strlen($raw) !== 64) {
            return null;
        }
        $integer = static function (string $bytes): string {
            $bytes = ltrim($bytes, "\0");
            if ($bytes === '' || (ord($bytes[0]) & 0x80) !== 0) {
                $bytes = "\0" . $bytes;
            }
            return "\x02" . chr(strlen($bytes) & 0xFF) . $bytes;
        };
        $sequence = $integer(substr($raw, 0, 32)) . $integer(substr($raw, 32, 32));
        return "\x30" . chr(strlen($sequence) & 0xFF) . $sequence;
    }

    /**
     * Si la firma r||s del mensaje es de esa clave pública (PEM). Solo openssl_verify() === 1 vale; nunca una excepción.
     *
     * @param string $message
     * @param string $raw
     * @param string $publicKeyPem
     * @return bool
     */
    public static function signatureIsValid(string $message, string $raw, string $publicKeyPem): bool
    {
        $der = self::derFromRaw($raw);
        if ($der === null || $publicKeyPem === '') {
            return false;
        }
        //La cola de errores de openssl se vacía antes y después: ni se lee lo de otra operación ni se le deja lo de esta.
        while (openssl_error_string() !== false) {
        }
        try {
            $verified = openssl_verify($message, $der, $publicKeyPem, \OPENSSL_ALGO_SHA256) === 1;
        } catch (\Throwable $e) {
            $verified = false;
        }
        while (openssl_error_string() !== false) {
        }
        return $verified;
    }

    /**
     * El camino de la llave actual: rota el verificador y el identificador en una sola sentencia condicional.
     *
     * @param array<string,mixed> $row
     * @param string $selector
     * @param int $timestamp
     * @return array{token: string, refreshToken: string, accessTokenExpiresAt: int, accessTokenExpiresIn: int}|null null si la sentencia no casó
     */
    private static function rotate(array $row, string $selector, int $timestamp): ?array
    {
        //TODO LO QUE PUEDE FALLAR VA ANTES DE LA SENTENCIA: el azar, el sellado y la firma del token.
        $verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $pending = Sealed::seal($verifier, Sealed::PURPOSE_SESSION_REFRESH_PENDING);
        [$identifier, $token, $now, $lifetime] = self::newAccess((int) $row['userID']);
        $statement = self::database()->prepare('UPDATE `' . UserSessions::TABLE . '` SET refreshPrevHash = refreshHash, refreshHash = ?, refreshPending = ?, refreshLastTimestamp = ?, lastActivityAt = ?, expiresAt = ?, sessionHash = ?'
            . ' WHERE id = ? AND kind = ? AND refreshHash = ? AND closedAt IS NULL AND expiresAt > ? AND (refreshLastTimestamp IS NULL OR refreshLastTimestamp < ?)');
        $statement->execute([
            hash('sha256', $verifier),
            $pending,
            $timestamp,
            date('Y-m-d H:i:s', $now),
            date('Y-m-d H:i:s', self::expiresAt($row, $now)),
            UserSessions::hash($identifier),
            (int) $row['id'],
            UserSessions::KIND_APP,
            (string) $row['refreshHash'],
            date('Y-m-d H:i:s', $now),
            $timestamp,
        ]);
        if ($statement->rowCount() !== 1) {
            return null;
        }
        return ['token' => $token, 'refreshToken' => $selector . '.' . $verifier, 'accessTokenExpiresAt' => $now + $lifetime, 'accessTokenExpiresIn' => $lifetime];
    }

    /**
     * El camino de la llave anterior: la app no recibió la actual. Se le reenvía, sin rotar la llave, con un token nuevo.
     *
     * @param array<string,mixed> $row
     * @param string $selector
     * @param int $timestamp
     * @return array{token: string, refreshToken: string, accessTokenExpiresAt: int, accessTokenExpiresIn: int}|false|null false si no se puede reenviar nunca (lo pendiente no se abre o no casa: volver a entrar); null si la sentencia no casó (reintentar)
     */
    private static function resend(array $row, string $selector, int $timestamp): array|false|null
    {
        $opened = Sealed::open((string) ($row['refreshPending'] ?? ''), Sealed::PURPOSE_SESSION_REFRESH_PENDING);
        if ($opened === null || !hash_equals((string) ($row['refreshHash'] ?? ''), hash('sha256', $opened))) {
            return false;
        }
        [$identifier, $token, $now, $lifetime] = self::newAccess((int) $row['userID']);
        //Con la misma llave actual que se leyó: si otra petición la rotó en medio, lo pendiente leído ya no vale.
        $statement = self::database()->prepare('UPDATE `' . UserSessions::TABLE . '` SET refreshLastTimestamp = ?, lastActivityAt = ?, expiresAt = ?, sessionHash = ?'
            . ' WHERE id = ? AND kind = ? AND refreshHash = ? AND closedAt IS NULL AND expiresAt > ? AND (refreshLastTimestamp IS NULL OR refreshLastTimestamp < ?)');
        $statement->execute([
            $timestamp,
            date('Y-m-d H:i:s', $now),
            date('Y-m-d H:i:s', self::expiresAt($row, $now)),
            UserSessions::hash($identifier),
            (int) $row['id'],
            UserSessions::KIND_APP,
            (string) $row['refreshHash'],
            date('Y-m-d H:i:s', $now),
            $timestamp,
        ]);
        if ($statement->rowCount() !== 1) {
            return null;
        }
        return ['token' => $token, 'refreshToken' => $selector . '.' . $opened, 'accessTokenExpiresAt' => $now + $lifetime, 'accessTokenExpiresIn' => $lifetime];
    }

    /**
     * Un identificador nuevo y su token de acceso de app (sin aud, vida corta), firmado antes de escribir.
     *
     * @param int $userID
     * @return array{0: string, 1: string, 2: int, 3: int} [identificador, token, ahora, vida]
     */
    private static function newAccess(int $userID): array
    {
        $identifier = bin2hex(random_bytes(32));
        $lifetime = UserSessions::tokenLifetime(UserSessions::KIND_APP);
        $now = time();
        $token = SessionToken::generateToken(['id' => $userID, 'sid' => $identifier], null, $lifetime, false);
        return [$identifier, $token, $now, $lifetime];
    }

    /**
     * La caducidad tras renovar: la de la vida de app desde ahora, sin acortar la que ya tenía (una extensión se respeta).
     *
     * @param array<string,mixed> $row
     * @param int $now
     * @return int
     */
    private static function expiresAt(array $row, int $now): int
    {
        $created = strtotime((string) ($row['createdAt'] ?? ''));
        $current = strtotime((string) ($row['expiresAt'] ?? ''));
        $expires = UserSessions::expiryAt($now, $created !== false ? $created : $now, UserSessions::KIND_APP);
        return $current !== false ? max($current, $expires) : $expires;
    }

    /**
     * @param string $selector
     * @return array<string,mixed>|null
     */
    private static function rowBySelector(string $selector): ?array
    {
        $statement = self::database()->prepare('SELECT * FROM `' . UserSessions::TABLE . '` WHERE refreshSelector = ? AND kind = ? LIMIT 1');
        $statement->execute([$selector, UserSessions::KIND_APP]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /**
     * @param int $userID
     * @return array<string,mixed>|null
     */
    private static function userRow(int $userID): ?array
    {
        $statement = self::database()->prepare('SELECT id, username, type, status, organization, sessionsValidFrom FROM `' . UsersModel::TABLE . '` WHERE id = ? LIMIT 1');
        $statement->execute([$userID]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /**
     * @param ResponseRoute $response
     * @param array{token: string, refreshToken: string, accessTokenExpiresAt: int, accessTokenExpiresIn: int} $result
     * @return ResponseRoute
     */
    private static function success(ResponseRoute $response, array $result): ResponseRoute
    {
        return $response->withHeader('Cache-Control', 'no-store')->withJson(['success' => true] + $result);
    }

    /**
     * @param ResponseRoute $response
     * @param int $status
     * @param string $error
     * @param array<string,mixed> $extra
     * @return ResponseRoute
     */
    private static function fail(ResponseRoute $response, int $status, string $error, array $extra = []): ResponseRoute
    {
        return $response->withHeader('Cache-Control', 'no-store')->withJson(['success' => false, 'error' => $error, 'message' => self::message($error)] + $extra, $status);
    }

    /**
     * @param string $error
     * @return string
     */
    private static function message(string $error): string
    {
        return match ($error) {
            self::ERROR_CLOCK_SKEW => __(self::LANG_GROUP, 'La hora del dispositivo no coincide con la del servidor.'),
            self::ERROR_UNAVAILABLE => __(self::LANG_GROUP, 'El servicio no está disponible ahora. Inténtelo más tarde.'),
            self::ERROR_REAUTH_REQUIRED => __(self::LANG_GROUP, 'Vuelva a iniciar sesión.'),
            self::ERROR_RETRY => __(self::LANG_GROUP, 'Repita la petición.'),
            self::ERROR_MAINTENANCE => __('page503', 'El sitio está en mantenimiento y tu tipo de usuario no puede entrar ahora.'),
            default => __(self::LANG_GROUP, 'Faltan datos o no tienen la forma esperada.'),
        };
    }

    /**
     * @return \PDO
     * @throws \RuntimeException
     */
    private static function database(): \PDO
    {
        $database = (new BaseModel())->getDatabase();
        if (!$database instanceof \PDO) {
            throw new \RuntimeException('Sin conexión a la base: no se puede renovar la sesión.');
        }
        return $database;
    }
}
