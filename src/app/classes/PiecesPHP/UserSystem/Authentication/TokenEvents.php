<?php

/**
 * TokenEvents.php
 */

namespace PiecesPHP\UserSystem\Authentication;

use EventsLog\LogsRoutes;
use EventsLog\Mappers\LogsMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;

/**
 * TokenEvents - Las clases de código que ve Registros › Tokens y códigos, y la escritura de su historia.
 *
 * Escribir un evento NUNCA tumba un acceso: va en try/catch y, si falla, queda la excepción en el registro de errores.
 * Y nunca lleva el valor del código: quien lea la historia no puede usarla para entrar.
 *
 * @package     PiecesPHP\UserSystem\Authentication
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class TokenEvents
{
    const LANG_GROUP = UserSystemFeaturesLang::LANG_GROUP;

    const KIND_ONE_USE_CODE = 'one-use-code';
    const KIND_TWO_FACTOR = 'two-factor';
    const KIND_RECOVERY = 'recovery';
    const KIND_FORGOTTEN_USER = 'forgotten-user';
    const KIND_BLOCKED_USER = 'blocked-user';
    const KIND_TOKEN = 'token';

    /**
     * El nombre de cada clase, sin traducir: así va en las variables del evento, como el resto del registro.
     */
    const KINDS = [
        self::KIND_ONE_USE_CODE => 'Código de acceso de un solo uso',
        self::KIND_TWO_FACTOR => 'Doble factor (TOTP)',
        self::KIND_RECOVERY => 'Recuperación de contraseña',
        self::KIND_FORGOTTEN_USER => 'Usuario olvidado',
        self::KIND_BLOCKED_USER => 'Usuario bloqueado',
        self::KIND_TOKEN => 'Token',
    ];

    /**
     * Las clases cuyo valor root puede ver. El secreto TOTP no (se restablece) y un token tampoco (su dato va sellado).
     */
    const REVEALABLE = [self::KIND_ONE_USE_CODE, self::KIND_RECOVERY, self::KIND_FORGOTTEN_USER, self::KIND_BLOCKED_USER];

    //Los motivos de rechazo. «Ya usado» no se distingue de «inexistente»: usar un código lo borra.
    const REASON_NOT_FOUND = 'inexistente o ya usado';
    const REASON_EXPIRED = 'caducado';

    /**
     * @param string $kind
     * @return string Traducido
     */
    public static function label(string $kind): string
    {
        //Literales: el censo de traducciones solo ve __() con la cadena escrita.
        return match ($kind) {
            self::KIND_ONE_USE_CODE => __(self::LANG_GROUP, 'Código de acceso de un solo uso'),
            self::KIND_TWO_FACTOR => __(self::LANG_GROUP, 'Doble factor (TOTP)'),
            self::KIND_RECOVERY => __(self::LANG_GROUP, 'Recuperación de contraseña'),
            self::KIND_FORGOTTEN_USER => __(self::LANG_GROUP, 'Usuario olvidado'),
            self::KIND_BLOCKED_USER => __(self::LANG_GROUP, 'Usuario bloqueado'),
            self::KIND_TOKEN => __(self::LANG_GROUP, 'Token'),
            default => $kind,
        };
    }

    /**
     * @param string $type Un MSG_* de LogsMapper
     * @param string $kind Un KIND_*
     * @param int|null $targetUserID null: no se sabe de quién era (un código que no existe)
     * @param string $target Cómo se nombra al destinatario en el mensaje
     * @param array<string,string> $extra Más variables del mensaje (%username%, %reason%)
     * @return void
     */
    public static function log(string $type, string $kind, ?int $targetUserID, string $target, array $extra = []): void
    {
        if (!LogsRoutes::ENABLE) {
            return;
        }
        try {
            $variables = array_merge([
                '%kind%' => self::KINDS[$kind] ?? $kind,
                '%target%' => $target !== '' ? $target : '—',
            ], $extra);
            if ($targetUserID !== null) {
                LogsMapper::addLog($type, $variables, 'id', (string) $targetUserID, UsersModel::TABLE);
            } else {
                LogsMapper::addLog($type, $variables);
            }
        } catch (\Throwable $e) {
            log_exception($e);
        }
    }
}
