<?php

/**
 * UserSessions.php
 */

namespace PiecesPHP\UserSystem\Authentication;

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\TerminalData;
use PiecesPHP\UserSystem\ORM\UserSessionsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserHierarchy;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;

/**
 * UserSessions - Cada sesión con su fila (ADR 0026, fases B y C; P130 a).
 *
 * El token lleva dentro de `data` el id del usuario y el IDENTIFICADOR de su sesión; la base guarda solo su sha256. Una
 * sesión vale si su fila existe, es del mismo usuario, no está cerrada y no ha caducado: la fila manda sobre la caducidad
 * del token, que se emite con el tope (session_ceiling; sin tope, SESSION_TOKEN_NO_CEILING) para que extender sea mover
 * la fila. La fila caduca por inactividad (session_inactivity) y cada uso, como mucho cada ACTIVITY_WRITE_SECONDS, la corre.
 *
 * Todo por SQL preparado: se consulta en cada petición y no puede pagar un mapper.
 *
 * @package     PiecesPHP\UserSystem\Authentication
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class UserSessions
{
    const TABLE = UserSessionsModel::TABLE;

    const REASON_LOGOUT = 'logout';
    const REASON_CLOSED_BY_USER = 'closed-by-user';
    const REASON_CLOSED_BY_ADMIN = 'closed-by-admin';
    const REASON_REVOKE_ALL_USER = 'revoke-all-user';
    const REASON_REVOKE_ALL_GLOBAL = 'revoke-all-global';

    /**
     * La marca en `data` del token que el terminal se fabrica a sí mismo: sin fila, y solo vale en el terminal.
     */
    const TERMINAL_FLAG = 'terminal';

    /**
     * Cada cuánto, como mucho, se escribe la última actividad de una sesión.
     */
    const ACTIVITY_WRITE_SECONDS = 300;

    /**
     * La vida del token y de la fila cuando una opción vale 0 («sin ese límite»): 10 años. El token lleva siempre `exp`
     * (BaseToken), así que «no caduca» es «caduca muy lejos»; lo que de verdad la cierra es la fila (revocable).
     */
    const SESSION_TOKEN_NO_CEILING = 10 * 365 * 24 * 3600;

    /**
     * Cuánto se guardan las filas cerradas o caducadas antes de borrarlas.
     */
    const RETENTION_DAYS = 30;

    /**
     * El hash de la sesión de esta petición, fijado por index.php al validarla.
     *
     * @var string|null
     */
    private static $currentHash = null;

    /**
     * Abre una sesión y devuelve su token: la fila caduca con la duración configurada; el token, con el tope.
     *
     * @param int $userID
     * @param bool $aud La comprobación de origen del token (la del login: check_aud_on_auth)
     * @return string
     * @throws \RuntimeException si no se puede guardar la fila
     */
    public static function issueToken(int $userID, bool $aud = false): string
    {
        $identifier = self::open($userID);
        //El token vive el tope; la sesión, lo que diga su fila (que la actividad y extender mueven hasta ese tope).
        return SessionToken::generateToken(['id' => $userID, 'sid' => $identifier], null, self::tokenLifetime(), $aud);
    }

    /**
     * El token que el terminal se fabrica sin sesión de nadie: sin fila, y marcado para que fuera del terminal no entre.
     *
     * @param int $userID
     * @return string
     */
    public static function terminalToken(int $userID): string
    {
        return SessionToken::generateToken(['id' => $userID, self::TERMINAL_FLAG => true]);
    }

    /**
     * Crea la fila de una sesión nueva y devuelve su identificador en claro (que solo debe ir dentro de un token).
     *
     * @param int $userID
     * @return string
     * @throws \RuntimeException
     */
    public static function open(int $userID): string
    {
        $database = self::database();
        self::purge($database);
        $identifier = bin2hex(random_bytes(32));
        $now = time();
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) && is_string($_SERVER['HTTP_USER_AGENT']) ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
        $ip = isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR']) ? mb_substr($_SERVER['REMOTE_ADDR'], 0, 45) : null;
        $statement = $database->prepare('INSERT INTO `' . self::TABLE . '` (userID, sessionHash, createdAt, lastActivityAt, expiresAt, ip, userAgent) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([
            $userID,
            self::hash($identifier),
            date('Y-m-d H:i:s', $now),
            date('Y-m-d H:i:s', $now),
            date('Y-m-d H:i:s', self::expiryAt($now, $now)),
            $ip,
            $userAgent,
        ]);
        return $identifier;
    }

    /**
     * Si los datos de un token (los de `data`) son de una sesión viva de ese usuario. Un token sin identificador no
     * entra (la ruptura de P130 a); el del terminal, solo en el terminal. Fija la sesión actual y, como mucho cada
     * ACTIVITY_WRITE_SECONDS, su última actividad.
     *
     * @param mixed $tokenData Lo que BaseToken::getData() devuelve
     * @param int $userID El usuario del token (ANTES de una suplantación)
     * @return bool
     */
    public static function tokenIsValid($tokenData, int $userID): bool
    {
        self::$currentHash = null;
        if (!$tokenData instanceof \stdClass) {
            return false;
        }
        if (($tokenData->{self::TERMINAL_FLAG} ?? false) === true) {
            return TerminalData::getInstance()->isTerminal() === true;
        }
        $identifier = $tokenData->sid ?? null;
        if (!self::identifierIsWellFormed($identifier)) {
            return false;
        }
        $row = self::rowByIdentifier((string) $identifier);
        if ($row === null || !self::isOpen($row) || (int) $row['userID'] !== $userID) {
            return false;
        }
        self::$currentHash = (string) $row['sessionHash'];
        self::recordActivity($row);
        return true;
    }

    /**
     * Si un token entero vale: su firma y vigencia (SessionToken::isActiveSession()) y su fila. Para quien decide fuera de
     * index.php si hay sesión (el login, «¿estoy dentro?»): con solo la firma, un token viejo sin fila hacía un bucle
     * entre el acceso y el panel.
     *
     * @param string $jwt
     * @return bool
     */
    public static function jwtIsValid(string $jwt): bool
    {
        if (!SessionToken::isActiveSession($jwt)) {
            return false;
        }
        $data = BaseToken::getData($jwt);
        $id = $data instanceof \stdClass ? ($data->id ?? null) : null;
        return (is_int($id) || (is_string($id) && ctype_digit($id))) && self::tokenIsValid($data, (int) $id);
    }

    /**
     * Si el identificador de un token es de una sesión abierta de ese usuario (la renovación por lista blanca).
     *
     * @param mixed $identifier
     * @param int $userID
     * @return bool
     */
    public static function identifierIsOpen($identifier, int $userID): bool
    {
        if (!self::identifierIsWellFormed($identifier)) {
            return false;
        }
        $row = self::rowByIdentifier((string) $identifier);
        return $row !== null && self::isOpen($row) && (int) $row['userID'] === $userID;
    }

    /**
     * El hash de la sesión de esta petición (null fuera de una sesión con fila).
     *
     * @return string|null
     */
    public static function currentHash(): ?string
    {
        return self::$currentHash;
    }

    /**
     * La fila de la sesión de esta petición.
     *
     * @return array<string,mixed>|null
     */
    public static function current(): ?array
    {
        return self::$currentHash !== null ? self::rowBy('sessionHash', self::$currentHash) : null;
    }

    /**
     * @param int $id
     * @return array<string,mixed>|null
     */
    public static function byID(int $id): ?array
    {
        return self::rowBy('id', $id);
    }

    /**
     * Cierra una fila abierta.
     *
     * @param int $id
     * @param int|null $closedBy
     * @param string $reason
     * @return bool false si ya estaba cerrada o no existe
     */
    public static function close(int $id, ?int $closedBy, string $reason): bool
    {
        $statement = self::database()->prepare('UPDATE `' . self::TABLE . '` SET closedAt = ?, closedBy = ?, closeReason = ? WHERE id = ? AND closedAt IS NULL');
        $statement->execute([date('Y-m-d H:i:s'), $closedBy, $reason, $id]);
        return $statement->rowCount() > 0;
    }

    /**
     * Cierra las filas abiertas de un usuario, menos la que se diga.
     *
     * @param int $userID
     * @param int|null $closedBy
     * @param string $reason
     * @param string|null $exceptHash
     * @return int Cuántas se cerraron
     */
    public static function closeAllOfUser(int $userID, ?int $closedBy, string $reason, ?string $exceptHash = null): int
    {
        $sql = 'UPDATE `' . self::TABLE . '` SET closedAt = ?, closedBy = ?, closeReason = ? WHERE userID = ? AND closedAt IS NULL';
        $values = [date('Y-m-d H:i:s'), $closedBy, $reason, $userID];
        if ($exceptHash !== null) {
            $sql .= ' AND sessionHash <> ?';
            $values[] = $exceptHash;
        }
        $statement = self::database()->prepare($sql);
        $statement->execute($values);
        return $statement->rowCount();
    }

    /**
     * Cierra todas las filas abiertas de todos.
     *
     * @param string $reason
     * @return int
     */
    public static function closeAll(string $reason): int
    {
        $statement = self::database()->prepare('UPDATE `' . self::TABLE . '` SET closedAt = ?, closeReason = ? WHERE closedAt IS NULL');
        $statement->execute([date('Y-m-d H:i:s'), $reason]);
        return $statement->rowCount();
    }

    /**
     * Lo que vive el token de una sesión: el tope configurado o, sin tope (0), SESSION_TOKEN_NO_CEILING.
     *
     * @return int Segundos
     */
    public static function tokenLifetime(): int
    {
        $ceiling = SessionToken::ceiling();
        return $ceiling > 0 ? $ceiling : self::SESSION_TOKEN_NO_CEILING;
    }

    /**
     * La caducidad de una sesión abierta en `$createdAt` si su última actividad es `$activityAt`: la inactividad desde
     * esa actividad, sin pasar del tope desde su inicio. Una opción a 0 no limita; con las dos a 0, SESSION_TOKEN_NO_CEILING.
     *
     * @param int $activityAt
     * @param int $createdAt
     * @return int Marca de tiempo
     */
    public static function expiryAt(int $activityAt, int $createdAt): int
    {
        $inactivity = SessionToken::inactivity();
        $ceiling = SessionToken::ceiling();
        $limits = [];
        if ($inactivity > 0) {
            $limits[] = $activityAt + $inactivity;
        }
        if ($ceiling > 0) {
            $limits[] = $createdAt + $ceiling;
        }
        return $limits !== [] ? min($limits) : $createdAt + self::SESSION_TOKEN_NO_CEILING;
    }

    /**
     * El techo de una extensión: su inicio más el tope configurado (sin tope, SESSION_TOKEN_NO_CEILING), que es también
     * la caducidad de su token.
     *
     * @param array<string,mixed> $row
     * @return int Marca de tiempo
     */
    public static function ceiling(array $row): int
    {
        return (int) strtotime((string) $row['createdAt']) + self::tokenLifetime();
    }

    /**
     * Mueve la caducidad de una fila abierta `$days` días desde ahora, sin pasar del techo.
     *
     * @param int $id
     * @param int $days
     * @return \DateTimeImmutable|null La caducidad nueva; null si la fila no está abierta o se pasa del techo
     */
    public static function extend(int $id, int $days): ?\DateTimeImmutable
    {
        $row = self::byID($id);
        if ($row === null || !self::isOpen($row) || $days < 1) {
            return null;
        }
        $until = time() + $days * 86400;
        if ($until > self::ceiling($row)) {
            return null;
        }
        $statement = self::database()->prepare('UPDATE `' . self::TABLE . '` SET expiresAt = ? WHERE id = ? AND closedAt IS NULL');
        $statement->execute([date('Y-m-d H:i:s', $until), $id]);
        return (new \DateTimeImmutable())->setTimestamp($until);
    }

    /**
     * Las sesiones abiertas de un usuario, de la más reciente a la más vieja.
     *
     * @param int $userID
     * @return array<int,array<string,mixed>>
     */
    public static function openOfUser(int $userID): array
    {
        $statement = self::database()->prepare('SELECT * FROM `' . self::TABLE . '` WHERE userID = ? AND closedAt IS NULL AND expiresAt > ? ORDER BY lastActivityAt DESC, id DESC');
        $statement->execute([$userID, date('Y-m-d H:i:s')]);
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }

    /**
     * @param array<string,mixed> $row
     * @return bool
     */
    public static function isOpen(array $row): bool
    {
        $expires = strtotime((string) ($row['expiresAt'] ?? ''));
        return ($row['closedAt'] ?? null) === null && $expires !== false && $expires > time();
    }

    /**
     * El rango de un tipo de usuario (P133). La regla vive en UserHierarchy, compartida con los registros.
     *
     * @param int $userType
     * @return int
     */
    public static function rank(int $userType): int
    {
        return UserHierarchy::rank($userType);
    }

    /**
     * Si quien actúa puede ver y cerrar las sesiones de otro usuario (P133): UserHierarchy::canActOn(). Las propias van
     * por «Mis sesiones», no por aquí.
     *
     * @param int $actorType
     * @param int|null $actorOrganization
     * @param int $targetType
     * @param int|null $targetOrganization
     * @return bool
     */
    public static function canActOn(int $actorType, ?int $actorOrganization, int $targetType, ?int $targetOrganization): bool
    {
        return UserHierarchy::canActOn($actorType, $actorOrganization, $targetType, $targetOrganization);
    }

    /**
     * Si quien actúa puede extender las sesiones de ese usuario: root y el administrador general, sobre lo que pueden
     * cerrar.
     *
     * @param int $actorType
     * @param int|null $actorOrganization
     * @param int $targetType
     * @param int|null $targetOrganization
     * @return bool
     */
    public static function canExtendOn(int $actorType, ?int $actorOrganization, int $targetType, ?int $targetOrganization): bool
    {
        return in_array($actorType, [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL], true)
            && self::canActOn($actorType, $actorOrganization, $targetType, $targetOrganization);
    }

    /**
     * El navegador y el sistema, legibles («Firefox en Linux»), a partir de la cadena del agente de usuario.
     *
     * @param string|null $userAgent
     * @return string
     */
    public static function describeUserAgent(?string $userAgent): string
    {
        $userAgent = (string) $userAgent;
        if (trim($userAgent) === '') {
            return __(UserSystemFeaturesLang::LANG_GROUP, 'Navegador desconocido');
        }
        $browsers = [
            'Edg/' => 'Edge',
            'OPR/' => 'Opera',
            'Firefox/' => 'Firefox',
            'Chrome/' => 'Chrome',
            'Chromium/' => 'Chromium',
            'Safari/' => 'Safari',
            'curl/' => 'curl',
        ];
        $browser = __(UserSystemFeaturesLang::LANG_GROUP, 'Otro navegador');
        foreach ($browsers as $needle => $name) {
            if (str_contains($userAgent, $needle)) {
                $browser = $name;
                break;
            }
        }
        $systems = [
            'Android' => 'Android',
            'iPhone' => 'iOS',
            'iPad' => 'iPadOS',
            'Windows' => 'Windows',
            'Mac OS X' => 'macOS',
            'Linux' => 'Linux',
        ];
        $system = null;
        foreach ($systems as $needle => $name) {
            if (str_contains($userAgent, $needle)) {
                $system = $name;
                break;
            }
        }
        return $system !== null ? sprintf(__(UserSystemFeaturesLang::LANG_GROUP, '%s en %s'), $browser, $system) : $browser;
    }

    /**
     * @param string $identifier
     * @return string
     */
    public static function hash(string $identifier): string
    {
        return hash('sha256', $identifier);
    }

    /**
     * @param mixed $identifier
     * @return bool
     */
    private static function identifierIsWellFormed($identifier): bool
    {
        return is_string($identifier) && preg_match('/^[0-9a-f]{64}$/', $identifier) === 1;
    }

    /**
     * @param string $identifier
     * @return array<string,mixed>|null
     */
    private static function rowByIdentifier(string $identifier): ?array
    {
        return self::rowBy('sessionHash', self::hash($identifier));
    }

    /**
     * @param string $column id o sessionHash
     * @param int|string $value
     * @return array<string,mixed>|null
     */
    private static function rowBy(string $column, $value): ?array
    {
        $column = $column === 'id' ? 'id' : 'sessionHash';
        $statement = self::database()->prepare('SELECT * FROM `' . self::TABLE . "` WHERE `{$column}` = ? LIMIT 1");
        $statement->execute([$value]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /**
     * La última actividad, como mucho una vez cada ACTIVITY_WRITE_SECONDS por sesión (la condición va también en el
     * UPDATE), y con ella la caducidad corre: expiryAt(). Nunca la ACORTA: una extensión de un administrador se respeta.
     *
     * @param array<string,mixed> $row
     * @return void
     */
    private static function recordActivity(array $row): void
    {
        $now = time();
        $last = strtotime((string) $row['lastActivityAt']);
        if ($last !== false && $now - $last < self::ACTIVITY_WRITE_SECONDS) {
            return;
        }
        $created = strtotime((string) $row['createdAt']);
        $current = strtotime((string) $row['expiresAt']);
        $expires = self::expiryAt($now, $created !== false ? $created : $now);
        $expires = $current !== false ? max($current, $expires) : $expires;
        $statement = self::database()->prepare('UPDATE `' . self::TABLE . '` SET lastActivityAt = ?, expiresAt = ? WHERE id = ? AND lastActivityAt < ? AND closedAt IS NULL');
        $statement->execute([date('Y-m-d H:i:s', $now), date('Y-m-d H:i:s', $expires), (int) $row['id'], date('Y-m-d H:i:s', $now - self::ACTIVITY_WRITE_SECONDS)]);
    }

    /**
     * La retención: las filas cerradas o caducadas hace más de RETENTION_DAYS, y las de usuarios que ya no existen.
     *
     * @param \PDO $database
     * @return void
     */
    private static function purge(\PDO $database): void
    {
        $limit = date('Y-m-d H:i:s', time() - self::RETENTION_DAYS * 86400);
        $database->prepare('DELETE FROM `' . self::TABLE . '` WHERE (closedAt IS NOT NULL AND closedAt < ?) OR expiresAt < ?')->execute([$limit, $limit]);
        $database->exec('DELETE s FROM `' . self::TABLE . '` s LEFT JOIN `pcsphp_users` u ON u.id = s.userID WHERE u.id IS NULL');
    }

    /**
     * @return \PDO
     * @throws \RuntimeException
     */
    private static function database(): \PDO
    {
        $database = (new BaseModel())->getDatabase();
        if (!$database instanceof \PDO) {
            throw new \RuntimeException('Sin conexión a la base: no se puede gestionar la sesión.');
        }
        return $database;
    }
}
