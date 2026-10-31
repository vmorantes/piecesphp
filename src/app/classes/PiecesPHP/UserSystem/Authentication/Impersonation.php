<?php

/**
 * Impersonation.php
 */

namespace PiecesPHP\UserSystem\Authentication;

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;

/**
 * Impersonation - Root actuando como otro usuario (P204 a): la regla, sin HTTP.
 *
 * El estado vive en la cookie CONNECT_AS_ANOTHER_USER_ID_COOKIE_NAME con el valor `<targetID>.<startedAt>.<firma>`, que
 * solo escribe el servidor (ImpersonationController). La firma ata el valor a la sesión de root (`sid` de su token), a
 * root y al objetivo: otra sesión, o cerrar la de root, la invalida. Caduca a los MINUTES minutos de empezar.
 *
 * @package     PiecesPHP\UserSystem\Authentication
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class Impersonation
{
    const LANG_GROUP = UserSystemFeaturesLang::LANG_GROUP;

    /**
     * Cuánto dura una suplantación desde que empieza.
     */
    const MINUTES = 60;

    /**
     * El uso de la clave derivada de la llave de la app (Config::app_key_derived()).
     */
    const KEY_PURPOSE = 'impersonation';

    /**
     * Los estados de organización con los que un usuario puede tener sesión. El arranque (index.php) aplica la MISMA
     * lista a todo usuario con organización: es el único sitio donde vive.
     */
    const ORGANIZATION_STATUSES_WITH_SESSION = [
        OrganizationMapper::ACTIVE,
        OrganizationMapper::PENDING_APPROVAL,
    ];

    /**
     * @return int Segundos
     */
    public static function lifetime(): int
    {
        return self::MINUTES * 60;
    }

    /**
     * El valor de la cookie para una suplantación.
     *
     * @param string $sessionID El `sid` del token de root
     * @param int $rootID
     * @param int $targetID
     * @param int|null $startedAt Segundos Unix; null, ahora
     * @return string
     */
    public static function cookieValue(string $sessionID, int $rootID, int $targetID, ?int $startedAt = null): string
    {
        $startedAt ??= time();
        return "{$targetID}.{$startedAt}." . self::signature($sessionID, $rootID, $targetID, $startedAt);
    }

    /**
     * Lee un valor de la cookie y comprueba su firma. No mira la caducidad ni si el objetivo es elegible.
     *
     * @param mixed $value
     * @param string $sessionID El `sid` del token de root
     * @param int $rootID
     * @return array{targetID:int,startedAt:int}|null Null si no tiene la forma o la firma no casa
     */
    public static function read($value, string $sessionID, int $rootID): ?array
    {
        if (!is_string($value) || preg_match('/^([0-9]{1,18})\.([0-9]{1,18})\.([0-9a-f]{64})$/', $value, $parts) !== 1) {
            return null;
        }
        $targetID = (int) $parts[1];
        $startedAt = (int) $parts[2];
        if (!hash_equals(self::signature($sessionID, $rootID, $targetID, $startedAt), $parts[3])) {
            return null;
        }
        return ['targetID' => $targetID, 'startedAt' => $startedAt];
    }

    /**
     * @param int $startedAt
     * @param int|null $now Null, ahora
     * @return bool
     */
    public static function isExpired(int $startedAt, ?int $now = null): bool
    {
        return ($now ?? time()) >= $startedAt + self::lifetime();
    }

    /**
     * Por qué no se puede actuar como ese usuario.
     *
     * @param mixed $targetID
     * @return string|null Null si se puede; si no, el motivo, ya traducido
     */
    public static function refusalReason($targetID): ?string
    {
        if (!Validator::isInteger($targetID) || (int) $targetID <= 0 || UsersModel::isSystemUser((int) $targetID)) {
            return __(self::LANG_GROUP, 'No se puede actuar como ese usuario.');
        }
        $target = UsersModel::getBy((int) $targetID, 'id');
        if ($target === null) {
            return __(self::LANG_GROUP, 'El usuario no existe');
        }
        if ((int) $target->type === UsersModel::TYPE_USER_ROOT) {
            return __(self::LANG_GROUP, 'No se puede actuar como otro usuario root.');
        }
        if (in_array($target->status, UsersModel::STATUSES_INACTIVE_EQUIVALENT)) {
            return __(self::LANG_GROUP, 'El usuario no está activo.');
        }
        if (!self::organizationAllowsSession($target->organization ?? null)) {
            return __(self::LANG_GROUP, 'La organización del usuario no está activa.');
        }
        return null;
    }

    /**
     * Si la organización de un usuario le deja tener sesión: sin organización, sí.
     *
     * @param mixed $organizationID
     * @return bool
     */
    public static function organizationAllowsSession($organizationID): bool
    {
        if ($organizationID === null) {
            return true;
        }
        $organization = OrganizationMapper::getBy($organizationID, 'id');
        return $organization === null || in_array($organization->status, self::ORGANIZATION_STATUSES_WITH_SESSION);
    }

    /**
     * @param string $sessionID
     * @param int $rootID
     * @param int $targetID
     * @param int $startedAt
     * @return string
     */
    private static function signature(string $sessionID, int $rootID, int $targetID, int $startedAt): string
    {
        return hash_hmac('sha256', "{$sessionID}|{$rootID}|{$targetID}|{$startedAt}", Config::app_key_derived(self::KEY_PURPOSE));
    }
}
