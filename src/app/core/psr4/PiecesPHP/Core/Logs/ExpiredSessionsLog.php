<?php

/**
 * ExpiredSessionsLog.php
 */

namespace PiecesPHP\Core\Logs;

use DateTimeImmutable;
use DateTimeZone;

/**
 * ExpiredSessionsLog.
 *
 * El rastro de las peticiones que llegan con la sesión caducada: UNA LÍNEA por petición, en un
 * registro con tope, y solo si la instalación lo enciende. Ver ADR 0039.
 *
 * NO recibe el token ni sabe leerlo: solo los datos ya extraídos, así que no puede escribirlo.
 *
 * @package     PiecesPHP\Core\Logs
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class ExpiredSessionsLog
{

    /**
     * La opción que lo enciende. Apagada por omisión: lo normal es no necesitarlo.
     */
    const CONFIG_NAME = 'log_expired_sessions';

    /**
     * Tope del registro. Al pasarlo rota, así que el disco no crece sin fin.
     */
    const MAX_BYTES = 1048576;

    const FILE_NAME = 'expired-sessions.log';
    const ROTATED_FILE_NAME = 'expired-sessions.log.1';

    /**
     * La carpeta del formato viejo, con un `.json` por caducidad y el token dentro.
     *
     * NO se borra: `clean-logs` solo la retira si está vacía (ADR 0039 §4).
     */
    const LEGACY_FOLDER_NAME = 'expired-sessions';

    /**
     * Si la instalación quiere este rastro.
     *
     * @return bool
     */
    public static function enabled(): bool
    {
        return get_config(self::CONFIG_NAME) === true;
    }

    /**
     * La carpeta de registros, o la que se le pase (para las pruebas).
     *
     * @param string|null $directory
     * @return string
     */
    public static function directory(?string $directory = null): string
    {
        return $directory ?? basepath('app/logs');
    }

    /**
     * La ruta del registro.
     *
     * @param string|null $directory
     * @return string
     */
    public static function path(?string $directory = null): string
    {
        return rtrim(self::directory($directory), '/\\') . '/' . self::FILE_NAME;
    }

    /**
     * La ruta del registro rotado.
     *
     * @param string|null $directory
     * @return string
     */
    public static function rotatedPath(?string $directory = null): string
    {
        return rtrim(self::directory($directory), '/\\') . '/' . self::ROTATED_FILE_NAME;
    }

    /**
     * La carpeta del formato viejo.
     *
     * @param string|null $directory
     * @return string
     */
    public static function legacyFolder(?string $directory = null): string
    {
        return rtrim(self::directory($directory), '/\\') . '/' . self::LEGACY_FOLDER_NAME;
    }

    /**
     * Anota una caducidad. Con la opción apagada no escribe NI MIRA el disco.
     *
     * Ningún argumento es el token ni se saca de él en esta clase: `iat` y `exp` son fechas y
     * entran ya resueltas.
     *
     * @param int|null $userId Id del usuario que traía el token, o null si no lo traía.
     * @param string|null $routeName Nombre de la ruta pedida.
     * @param string $requestUrl Lo que se pidió.
     * @param string $ip
     * @param int|null $issuedAt `iat` del token, en segundos.
     * @param int|null $expiresAt `exp` del token, en segundos.
     * @param bool $renewalCandidate Si la ruta estaba en la lista de renovación automática.
     * @param string|null $directory Para las pruebas; por omisión `app/logs`.
     * @return bool Si se escribió la línea.
     */
    public static function record(
        ?int $userId,
        ?string $routeName,
        string $requestUrl,
        string $ip,
        ?int $issuedAt,
        ?int $expiresAt,
        bool $renewalCandidate,
        ?string $directory = null
    ): bool {
        if (!self::enabled()) {
            return false;
        }

        $path = self::path($directory);
        self::rotateIfNeeded($path, $directory);

        $zone = new DateTimeZone(date_default_timezone_get());
        $moment = new DateTimeImmutable('now', $zone);
        $asDate = static function (?int $stamp) use ($zone): string {
            if ($stamp === null) {
                return '-';
            }
            return (new DateTimeImmutable('@' . $stamp))->setTimezone($zone)->format('Y-m-d H:i:s');
        };

        //El formato de los registros de la casa: la fecha con microsegundos y los campos entre
        //corchetes, como `error.plain.log`.
        $line = sprintf(
            "[%s] [usuario %s] [ruta %s] [ip %s] [iat %s] [exp %s] [renovable %s] %s\n",
            $moment->format('Y-m-d H:i:s.u'),
            $userId !== null ? (string) $userId : 'anónimo',
            $routeName !== null && $routeName !== '' ? $routeName : '-',
            $ip !== '' ? $ip : '-',
            $asDate($issuedAt),
            $asDate($expiresAt),
            $renewalCandidate ? 'sí' : 'no',
            self::withoutTokens(str_replace(["\n", "\r"], ' ', $requestUrl))
        );

        return @file_put_contents($path, $line, \FILE_APPEND) !== false;
    }

    /**
     * Lo que se pidió, con cualquier cosa con FORMA de JWT sustituida.
     *
     * El framework lee el token de la cabecera o de la cookie, nunca de la consulta (medido en
     * `SessionToken::getJWTReceived()`), pero un enlace ajeno puede llevarlo en la URL y el ADR 0039
     * §1 dice que el token no se escribe NUNCA. Se ataca la forma, no el nombre del parámetro.
     *
     * @param string $url
     * @return string
     */
    protected static function withoutTokens(string $url): string
    {
        $limpia = preg_replace('/[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}/', '«token-omitido»', $url);
        return is_string($limpia) ? $limpia : '«url-omitida»';
    }

    /**
     * Mueve el registro a `.log.1` si pasó el tope. El rotado anterior se sobrescribe.
     *
     * @param string $path
     * @param string|null $directory
     * @return void
     */
    protected static function rotateIfNeeded(string $path, ?string $directory = null): void
    {
        if (!is_file($path)) {
            return;
        }
        $size = @filesize($path);
        if (!is_int($size) || $size < self::MAX_BYTES) {
            return;
        }
        //RETORNO-IGNORADO: si el traslado falla, la línea se añade al archivo que ya había; se
        //pierde el tope de esa pasada, no la anotación.
        @rename($path, self::rotatedPath($directory));
    }

}
