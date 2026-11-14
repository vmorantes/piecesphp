<?php

/**
 * SessionToken.php
 */
namespace PiecesPHP\Core;

use PiecesPHP\Core\BaseToken;

/**
 * SessionToken - Autenticación
 *
 * Manejar autenticaciones
 * @category    Autenticación
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class SessionToken
{
    /** Valor POR DEFECTO del nombre de la sesión. La fuente es `tokenName()`, no esta constante. */
    const TOKEN_NAME = 'JWTAuth';

    /** Clave de configuración con la que se cambia el nombre de la sesión. */
    const TOKEN_NAME_CONFIG = 'session_token_name';

    /** La vida por omisión de un token que no pide la suya (el del terminal), en segundos: 31 días. */
    const DURATION = 31 * 24 * 3600;

    /**
     * RETIRADA en la 9 (P142): la sesión la gobiernan `session_inactivity` y `session_ceiling`. Solo se mira para avisar,
     * una vez, a quien aún la tenga configurada y crea que manda.
     */
    const DURATION_CONFIG = 'session_duration';

    /** Cota superior de lo configurable, en segundos: 365 días. Más allá, la caducidad no acota nada. */
    const DURATION_MAX = 365 * 24 * 3600;

    /** Segundos sin actividad que cierran una sesión (0 = no caduca por inactividad). Por omisión, 31 días. */
    const INACTIVITY_CONFIG = 'session_inactivity';
    const INACTIVITY = 31 * 24 * 3600;

    /** Segundos desde que se abrió que la cierran pase lo que pase (0 = sin tope). Por omisión, 365 días. */
    const CEILING_CONFIG = 'session_ceiling';
    const CEILING = 365 * 24 * 3600;

    /**
     * @var bool Si ya se anotó un nombre configurado inválido: se dice UNA vez por proceso, no una por lectura.
     */
    private static bool $invalidNameLogged = false;

    private static bool $retiredDurationLogged = false;

    /** @var array<string,bool> Las opciones de sesión no válidas ya anotadas: una vez por proceso cada una. */
    private static array $invalidLifetimeLogged = [];

    //El centinela histórico de «sin marca». NO se toca: `core/session-isolated` congela su valor para documentar que
    //la sesión aislada tiene el suyo propio.
    const DEFAULT_MINIMUM_DATE_CREATED = '1990-01-01';

    //LA MARCA GLOBAL: ningún token nacido antes de esta fecha vale, para NADIE. Era un literal en
    //`config/roles.php`; este valor es el que tenía ahí, para que el cambio no eche fuera a nadie.
    const MINIMUM_DATE_DEFAULT = '2026-03-02 00:00:00';

    const MINIMUM_DATE_CONFIG = 'session_minimum_date';

    private static bool $invalidMinimumDateLogged = false;

    /**
     * @var \DateTime|null
     */
    private static $minimumDateCreated = null;

    /**
     * El nombre de la sesión: el de la configuración si es válido, y si no el de por defecto.
     *
     * ÚNICA PUERTA. El literal estaba escrito en cuatro sitios —esta clase y tres `static` del JavaScript— y nada los
     * ataba; `verify-integrity` vigila ahora que el valor por defecto de los dos lados no se separe.
     *
     * @return string
     */
    public static function tokenName(): string
    {
        $configured = get_config(self::TOKEN_NAME_CONFIG);

        if (is_string($configured) && self::tokenNameIsValid($configured)) {
            return $configured;
        }

        if (is_string($configured) && trim($configured) !== '' && !self::$invalidNameLogged) {
            self::$invalidNameLogged = true;
            log_exception(new \UnexpectedValueException(
                'El nombre de sesión configurado en «' . self::TOKEN_NAME_CONFIG . '» no es válido y se usa el de por'
                    . ' defecto. Solo se admiten letras, dígitos y el guion bajo, hasta 64 caracteres.'
            ));
        }

        return self::TOKEN_NAME;
    }

    /**
     * El nombre de la sesión como CABECERA: el de tokenName() con «_» cambiado por «-». Apache descarta las cabeceras con
     * «_», y PHP pasa el «-» a «_» en $_SERVER, así que esta forma llega a lo que leen getJWTReceived() y receivedByHeader().
     *
     * @return string
     */
    public static function headerName(): string
    {
        return str_replace('_', '-', self::tokenName());
    }

    /**
     * La vida por omisión de un token que no pide la suya (el del terminal, `initSession()`). Ya no se configura: la sesión
     * de un usuario la gobiernan `inactivity()` y `ceiling()`, y la fila manda (ADR 0026).
     *
     * @return int
     */
    public static function duration(): int
    {
        return self::DURATION;
    }

    /**
     * Los segundos sin actividad que cierran una sesión; 0, que no caduca por inactividad. Un valor no válido usa el de por
     * omisión y se anota una vez.
     *
     * @return int
     */
    public static function inactivity(): int
    {
        return self::lifetime(self::INACTIVITY_CONFIG, self::INACTIVITY);
    }

    /**
     * Los segundos desde que se abrió que cierran una sesión pase lo que pase; 0, sin tope. Un valor no válido usa el de
     * por omisión y se anota una vez.
     *
     * @return int
     */
    public static function ceiling(): int
    {
        return self::lifetime(self::CEILING_CONFIG, self::CEILING);
    }

    /**
     * Un entero de 0 a `DURATION_MAX` segundos: el 0 es «sin ese límite».
     *
     * @param mixed $seconds
     * @return bool
     */
    public static function lifetimeIsValid($seconds): bool
    {
        if (is_bool($seconds) || !is_scalar($seconds)) {
            return false;
        }
        if (!is_int($seconds) && (!is_string($seconds) || preg_match('/^\d+$/', $seconds) !== 1)) {
            return false;
        }
        return (int) $seconds >= 0 && (int) $seconds <= self::DURATION_MAX;
    }

    /**
     * @param string $config
     * @param int $default
     * @return int
     */
    private static function lifetime(string $config, int $default): int
    {
        self::warnRetiredDuration();
        $configured = get_config($config);
        if (self::lifetimeIsValid($configured)) {
            return (int) $configured;
        }
        if ($configured !== false && $configured !== null && !isset(self::$invalidLifetimeLogged[$config])) {
            self::$invalidLifetimeLogged[$config] = true;
            log_exception(new \UnexpectedValueException(
                'La opción de sesión «' . $config . '» no es válida y se usa la de por omisión. Se admite un entero de 0'
                    . ' (sin ese límite) a ' . self::DURATION_MAX . ' segundos.'
            ));
        }
        return $default;
    }

    /**
     * `session_duration` ya no manda: si una instalación la tiene, se dice una vez para que nadie crea que sí.
     *
     * @return void
     */
    private static function warnRetiredDuration(): void
    {
        $configured = get_config(self::DURATION_CONFIG);
        if ($configured !== false && $configured !== null && !self::$retiredDurationLogged) {
            self::$retiredDurationLogged = true;
            log_exception(new \UnexpectedValueException(
                'La opción «' . self::DURATION_CONFIG . '» ya no se usa (P142): la sesión la gobiernan «' . self::INACTIVITY_CONFIG
                    . '» y «' . self::CEILING_CONFIG . '».'
            ));
        }
    }

    /**
     * Un entero de 1 a `DURATION_MAX` segundos.
     *
     * CERO Y NEGATIVO NO: emitirían un token ya caducado y la sesión no arrancaría nunca. Y por encima de la cota, una
     * caducidad tan lejana no acota nada, que es justo para lo que existe.
     *
     * @param mixed $duration
     * @return bool
     */
    public static function durationIsValid($duration): bool
    {
        if (is_bool($duration) || !is_scalar($duration)) {
            return false;
        }

        if (!is_int($duration) && (!is_string($duration) || preg_match('/^\d+$/', $duration) !== 1)) {
            return false;
        }

        $seconds = (int) $duration;

        return $seconds >= 1 && $seconds <= self::DURATION_MAX;
    }

    /**
     * Letras, dígitos y guion bajo, hasta 64 caracteres.
     *
     * NO SE ADMITE MÁS: el mismo nombre viaja como cookie y como cabecera, y PHP convierte el guion en guion bajo al
     * pasar una cabecera a `$_SERVER`. Un nombre con guion se recibiría por un lado y no por el otro, en silencio.
     *
     * @param string $name
     * @return bool
     */
    public static function tokenNameIsValid(string $name): bool
    {
        return preg_match('/^[A-Za-z0-9_]{1,64}$/', $name) === 1;
    }

    /**
     * @param mixed $data Información que se almacenará en el token
     * @param string $key Llave
     * @param int $expire_time Duración en segundos
     * @param bool $aud Validar token con ip
     * @return string El token generado
     */
    public static function generateToken($data, ?string $key = null, ?int $expire_time = null, bool $aud = true)
    {
        $time = time();

        if (is_null($expire_time)) {
            $expire_time = $time + self::duration();
        } else {
            $expire_time += $time;
        }

        $token = BaseToken::setToken($data, $key, $time, $expire_time, $aud);

        return $token;
    }

    /**
     * Verifica si el JWT actual recibido tiene una sesión activa
     * @return bool
     */
    public static function currentReceivedJWTHasActiveSession()
    {
        $JWT = self::getJWTReceived();
        return self::isActiveSession($JWT);
    }

    /**
     * Si un token nacido en `$tokenCreated` sigue valiendo frente a las dos marcas de revocación (ADR 0026).
     *
     * ÚNICA REGLA para las dos: vale si nació DESPUÉS de la marca. El mismo segundo NO vale. Sin fecha entera no
     * hay nada que comparar y se niega; y una marca de usuario que no se entiende como fecha también niega: falla
     * cerrado. La usa `index.php` en sus dos sitios, y con la fecha del token VIEJO cuando va a renovar uno caducado:
     * el token recién fabricado nace después de cualquier marca, y preguntar por él sería no preguntar.
     *
     * @param int|null $tokenCreated El `iat` del token.
     * @param string|null $userSessionsValidFrom La marca del usuario; nula o vacía es «sin revocaciones».
     * @return bool
     */
    public static function isCreatedAfterMarks(?int $tokenCreated, ?string $userSessionsValidFrom): bool
    {
        if ($tokenCreated === null) {
            return false;
        }

        $globalMark = self::$minimumDateCreated ?? self::minimumDateCreated();
        if ($tokenCreated <= $globalMark->getTimestamp()) {
            return false;
        }

        $userMark = $userSessionsValidFrom !== null ? trim($userSessionsValidFrom) : '';
        if ($userMark === '') {
            return true;
        }

        $userMarkTime = strtotime($userMark);

        return $userMarkTime !== false && $tokenCreated > $userMarkTime;
    }

    /**
     * isCreatedAfterMarks(), salvo para la sesión que la terminal fabrica para root, a la que no se le aplican las marcas:
     * nace en el mismo segundo que una revocación recién lanzada desde la terminal y caería con ella (H289).
     *
     * @param int|null $tokenCreated El `iat` del token.
     * @param string|null $userSessionsValidFrom La marca del usuario; nula o vacía es «sin revocaciones».
     * @param bool $terminalSession Si el token lo fabricó la terminal en esta misma petición.
     * @return bool
     */
    public static function revocationAllows(?int $tokenCreated, ?string $userSessionsValidFrom, bool $terminalSession): bool
    {
        return $terminalSession || self::isCreatedAfterMarks($tokenCreated, $userSessionsValidFrom);
    }

    /**
     * @param string $token
     * @param string $key
     * @param bool $ignoreMarks Sin la marca global: solo para la sesión que la terminal fabrica para root (H289).
     * @return bool
     */
    public static function isActiveSession(string $token, ?string $key = null, bool $ignoreMarks = false)
    {
        $logged = BaseToken::check($token, $key);

        if (self::$minimumDateCreated === null) {
            self::$minimumDateCreated = self::minimumDateCreated();
        }

        if ($logged !== true) {

            return false;

        } else if ($logged === true) {

            //Firma buena y datos que no se abren (un token de antes del cifrado de la 9, ADR 0061): no es una sesión. Sin
            //esto, index.php pasaba el null de getData() a quien exige un objeto, y era un 500 en vez de «inicie sesión».
            if (!(BaseToken::getData($token, $key) instanceof \stdClass)) {
                return false;
            }

            $dateCreatedToken = BaseToken::getCreated($token, $key);

            //`getCreated()` puede devolver un código o null: con el código, `date()` lanzaba; con null tomaba LA HORA
            //ACTUAL y el token pasaba por recién creado. Sin fecha no hay nada que comparar.
            if (!is_int($dateCreatedToken)) {
                return false;
            }

            if ($ignoreMarks) {
                return true;
            }

            $dateCreatedToken = new \DateTime(date('Y-m-d H:i:s', $dateCreatedToken));

            //DESPUÉS de la marca, no «desde»: la marca por usuario ya rechazaba el mismo segundo, y las dos dicen lo mismo.
            if ($dateCreatedToken > self::$minimumDateCreated) {
                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

    /**
     * @return string
     */
    public static function getJWTReceived()
    {

        $JWT = '';

        $name = self::tokenName();
        $name_on_server = 'HTTP_' . strtoupper($name);

        //Una cabecera VACÍA cuenta como ausente: el JavaScript ya no tiene el token (H188), y un código que aún la mande con
        //getJWT() —que da ''— se quedaría sin sesión teniendo la cookie. Una cabecera con valor manda, como siempre.
        if (self::receivedByHeader()) {

            $JWT = $_SERVER[$name_on_server];

        } elseif (isset($_COOKIE[$name]) && is_string($_COOKIE[$name])) {

            $JWT = $_COOKIE[$name];

        }

        return $JWT;
    }

    /**
     * Si el token de esta petición llega por la cabecera (con valor): entonces manda sobre la cookie.
     *
     * @return bool
     */
    public static function receivedByHeader(): bool
    {
        $header = $_SERVER['HTTP_' . strtoupper(self::tokenName())] ?? null;
        return is_string($header) && trim($header) !== '';
    }

    /**
     * La marca global: la de la configuración si es válida, y si no la de por defecto.
     *
     * ÚNICA PUERTA, como `tokenName()` y `duration()`. Un valor inválido NO puede dejar el sistema sin marca, así que
     * cae al de por defecto y avisa una vez.
     *
     * @return \DateTime
     */
    public static function minimumDateCreated(): \DateTime
    {
        $configured = get_config(self::MINIMUM_DATE_CONFIG);

        if (is_string($configured) && self::minimumDateIsValid($configured)) {
            return new \DateTime($configured);
        }

        if ($configured !== false && $configured !== null && !self::$invalidMinimumDateLogged) {
            self::$invalidMinimumDateLogged = true;
            log_exception(new \UnexpectedValueException(
                'La marca global de sesión configurada en «' . self::MINIMUM_DATE_CONFIG . '» no es una fecha válida y'
                    . ' se usa la de por defecto. Se admite una fecha como «2026-03-02 00:00:00».'
            ));
        }

        return new \DateTime(self::MINIMUM_DATE_DEFAULT);
    }

    /**
     * Una fecha que el propio motor de fechas entienda.
     *
     * UN NÚMERO NO VALE, aunque `DateTime` acepte algunos: «0» y «2026» se leerían como algo que nadie quiso escribir.
     *
     * @param string $date
     * @return bool
     */
    public static function minimumDateIsValid(string $date): bool
    {
        $date = trim($date);

        if ($date === '' || preg_match('/^[+-]?\d+$/', $date) === 1) {
            return false;
        }

        try {
            new \DateTime($date);
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * @param \DateTime $minimumDateCreated
     * @return void
     */
    public static function setMinimumDateCreated(\DateTime $minimumDateCreated)
    {
        self::$minimumDateCreated = $minimumDateCreated;
    }

    //---- Preservación de compatibilidad
    public static function initSession($data, ?string $key = null, ?int $expire_time = null)
    {
        return self::generateToken($data, $key, $expire_time);
    }

}
