<?php

/**
 * AccountChangeNotice.php
 */

namespace PiecesPHP\UserSystem;

use MySpace\Controllers\MySpaceController;
use PiecesPHP\Notifications\NotificationMessage;
use PiecesPHP\Notifications\Notifications;
use PiecesPHP\UserSystem\Authentication\Impersonation;

/**
 * AccountChangeNotice - Avisa a una persona de que un administrador cambió su cuenta (ADR 0073): su contraseña, una de
 * sus sesiones o todas. No avisa a quien actúa sobre sí mismo (con suplantación, el que actúa es root) y nunca lanza.
 *
 * @package     PiecesPHP\UserSystem
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class AccountChangeNotice
{
    const KIND = 'account-changed-by-admin';

    const PASSWORD = 'password';
    const ONE_SESSION = 'one-session';
    const ALL_SESSIONS = 'all-sessions';

    /**
     * @param int $affectedUserID
     * @param string $case Uno de PASSWORD, ONE_SESSION o ALL_SESSIONS
     * @return int Cuántas creó: 0 si actúa sobre sí mismo o si algo falla
     */
    public static function notify(int $affectedUserID, string $case): int
    {
        try {
            $actor = getLoggedFrameworkUser();
            $message = self::message($case);
            if ($actor === null || $message === null) {
                return 0;
            }
            //Suplantando, el usuario actual es el suplantado: quien actúa de verdad es root.
            $realActorID = Impersonation::rootOriginalID() ?? (int) $actor->id;
            if ($realActorID === $affectedUserID) {
                return 0;
            }
            return Notifications::notify($affectedUserID, $message);
        } catch (\Throwable $e) {
            //RETORNO-IGNORADO: la referencia no la necesita nadie; quien llama sigue igual.
            log_exception($e);
            return 0;
        }
    }

    /**
     * Con argumentos con nombre y claves literales: así las ve bin/censo-claves-huerfanas.
     *
     * @param string $case
     * @return NotificationMessage|null null si el caso no existe
     */
    private static function message(string $case): ?NotificationMessage
    {
        return match ($case) {
            self::PASSWORD => new NotificationMessage(
                kind: self::KIND,
                level: NotificationMessage::LEVEL_WARNING,
                langGroup: UserSystemFeaturesLang::LANG_GROUP,
                titleKey: 'Un administrador cambió tu contraseña',
                bodyKey: 'Si no lo esperabas, contacta con soporte.',
                routeName: MySpaceController::securityRouteName(),
            ),
            self::ONE_SESSION => new NotificationMessage(
                kind: self::KIND,
                level: NotificationMessage::LEVEL_WARNING,
                langGroup: UserSystemFeaturesLang::LANG_GROUP,
                titleKey: 'Un administrador cerró una de tus sesiones',
                bodyKey: 'Si no lo esperabas, contacta con soporte.',
                routeName: MySpaceController::securityRouteName(),
            ),
            self::ALL_SESSIONS => new NotificationMessage(
                kind: self::KIND,
                level: NotificationMessage::LEVEL_WARNING,
                langGroup: UserSystemFeaturesLang::LANG_GROUP,
                titleKey: 'Un administrador cerró todas tus sesiones',
                bodyKey: 'Si no lo esperabas, contacta con soporte.',
                routeName: MySpaceController::securityRouteName(),
            ),
            default => null,
        };
    }
}
