<?php

/**
 * NewErrorsAlert.php
 */

namespace PiecesPHP\Logs;

use PiecesPHP\Logs\Controllers\LogsController;
use PiecesPHP\SystemStatus\SystemAlert;
use PiecesPHP\SystemStatus\SystemAlertRegistry;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * NewErrorsAlert - El aviso flotante de errores nuevos (ADR 0060 §4): solo root, desde su última visita a Registros ›
 * Errores. La visita se guarda en su `meta`; la versión del aviso es la hora del error más nuevo, así que cerrarlo vale
 * hasta que haya uno más nuevo.
 *
 * @package     PiecesPHP\Logs
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class NewErrorsAlert
{
    const KEY = 'new-errors';
    const META_LAST_VISIT = 'errorsLastVisit';

    /**
     * @var array<int,array{count:int,newest:int}> Por usuario, lo contado en esta petición
     */
    private static $memo = [];

    /**
     * @var ErrorLogReader|null Las pruebas leen de un temporal
     */
    private static $reader = null;

    /**
     * @return void
     */
    public static function register(): void
    {
        SystemAlertRegistry::register(new SystemAlert(
            self::KEY,
            SystemAlert::SEVERITY_WARNING,
            fn(): string => __(LogsController::LANG_GROUP, 'Hay errores nuevos desde tu última visita'),
            fn(): bool => self::stateForCurrent()['count'] > 0,
            true,
            [UsersModel::TYPE_USER_ROOT],
            true,
            'logs-admin-index',
            null,
            ['tab' => LogsController::ERRORS_TAB],
            fn(): string => (string) self::stateForCurrent()['newest']
        ));
    }

    /**
     * La última visita de ese usuario a la pestaña; 0 si nunca.
     *
     * @param int $userID
     * @return int
     */
    public static function lastVisit(int $userID): int
    {
        $value = UsersModel::metaValue($userID, self::META_LAST_VISIT);
        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @param int $userID
     * @param int|null $at Por omisión, ahora
     * @return bool
     */
    public static function markVisited(int $userID, ?int $at = null): bool
    {
        unset(self::$memo[$userID]);
        return UsersModel::setMetaValue($userID, self::META_LAST_VISIT, (string) ($at ?? time()));
    }

    /**
     * Errores nuevos para ese usuario: solo root tiene la pestaña, así que para los demás siempre es cero.
     *
     * @param int $userID
     * @param int $userType
     * @return array{count:int,newest:int}
     */
    public static function state(int $userID, int $userType): array
    {
        if ($userType !== UsersModel::TYPE_USER_ROOT) {
            return ['count' => 0, 'newest' => 0];
        }
        if (!isset(self::$memo[$userID])) {
            self::$memo[$userID] = (self::$reader ?? new ErrorLogReader())->newerThan(self::lastVisit($userID));
        }
        return self::$memo[$userID];
    }

    /**
     * @param ErrorLogReader|null $reader null vuelve al registro de la instalación
     * @return void
     */
    public static function useReaderForTesting(?ErrorLogReader $reader): void
    {
        self::$reader = $reader;
        self::$memo = [];
    }

    /**
     * @return array{count:int,newest:int}
     */
    private static function stateForCurrent(): array
    {
        $user = getLoggedFrameworkUser();
        return $user === null ? ['count' => 0, 'newest' => 0] : self::state((int) $user->id, (int) $user->type);
    }
}
