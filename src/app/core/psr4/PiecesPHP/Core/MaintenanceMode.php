<?php

/**
 * MaintenanceMode.php
 */

namespace PiecesPHP\Core;

use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * MaintenanceMode. El modo mantenimiento del sitio.
 *
 * Encendido, toda petición responde **503** con la vista `pages/503`, salvo los roles configurados
 * y las rutas de `ALWAYS_ALLOWED`.
 *
 * POR QUÉ 503 Y NO 200: un 200 le dice a un buscador que esa pantalla es la página definitiva del
 * sitio, y la indexa. El 503 con `Retry-After` dice «vuelve luego», que es lo que pasa.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class MaintenanceMode
{

    /** La vista que se sirve. Existe desde 2022 y hasta hoy no tuvo quién la sirviera. */
    const VIEW = 'pages/503';

    /** Segundos del `Retry-After` por omisión. Una hora: honesto sin prometer un minuto exacto. */
    const RETRY_AFTER = 3600;

    const RETRY_AFTER_CONFIG = 'maintenance_retry_after';

    /** Cota superior, en segundos: 7 días. Más allá, un buscador puede dejar de volver. */
    const RETRY_AFTER_MAX = 7 * 24 * 3600;

    const ENABLED_CONFIG = 'maintenance_mode';

    const ALLOWED_ROLES_CONFIG = 'maintenance_allowed_roles';

    /**
     * Quién sigue pasando si no se configura nada: SOLO root. Y root pasa igual aunque no esté.
     *
     * @var int[]
     */
    const ALLOWED_ROLES_DEFAULT = [
        UsersModel::TYPE_USER_ROOT,
    ];

    /**
     * Las rutas que pasan SIEMPRE, con el modo encendido y sin sesión.
     *
     * NO son un permiso: saltan la puerta del mantenimiento y nada más. El control de acceso de
     * `src/index.php` §8 sigue corriendo después, así que una de estas rutas que pida sesión sigue
     * pidiéndola. Cada una está aquí por un motivo que se puede leer:
     *
     * - `users-form-login` y `users-login-request`: sin ellas, un root que no tenga ya la sesión
     *   abierta NO PUEDE ENTRAR A APAGARLO, y el sitio queda muerto hasta que alguien edite la
     *   configuración en el servidor. Es el requisito, no una mejora.
     * - `users-verify-login-request`: el segundo paso del acceso cuando el usuario tiene el segundo
     *   factor activo. Sin ella, un root con 2FA se queda a mitad de camino, que es el mismo muro.
     * - `statics-files` y `admin-global-variables-css`: los estáticos y el CSS de variables que
     *   pide la propia vista 503. Sin ellos la pantalla sale sin estilos y sin su imagen.
     * - `system-status-site-maintenance` y `system-status-site-maintenance-save`: la pantalla del
     *   modo y su guardado, que son el camino de vuelta. **Las dos son de root y root pasa siempre**,
     *   así que hoy son redundantes; se dejan porque la garantía de root vive en código y esta lista
     *   es lo que hace que el traslado de los mandos no rompa la vuelta.
     *
     * @var string[]
     */
    const ALWAYS_ALLOWED = [
        'users-form-login',
        'users-login-request',
        'users-verify-login-request',
        'statics-files',
        'admin-global-variables-css',
        'system-status-site-maintenance',
        'system-status-site-maintenance-save',
    ];

    /**
     * @var bool Si ya se anotó una configuración inválida: se dice UNA vez por proceso.
     */
    private static bool $invalidEnabledLogged = false;

    private static bool $invalidRolesLogged = false;

    private static bool $invalidRetryAfterLogged = false;

    /**
     * Si el modo está encendido. Por omisión NO.
     *
     * Falla CERRADA hacia el sitio en pie: un valor que no se entiende deja el sitio funcionando,
     * porque encender el mantenimiento por un error de configuración apaga el sitio entero.
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        $configured = get_config(self::ENABLED_CONFIG);

        if (self::enabledIsValid($configured)) {
            return in_array($configured, [true, 1, '1', 'true'], true);
        }

        if ($configured !== false && $configured !== null && !self::$invalidEnabledLogged) {
            self::$invalidEnabledLogged = true;
            log_exception(new \UnexpectedValueException(
                'El valor de «' . self::ENABLED_CONFIG . '» no es válido y el modo mantenimiento se deja APAGADO.'
                . ' Se admite un booleano, o 0/1, o «true»/«false».'
            ));
        }

        return false;
    }

    /**
     * Un booleano, o lo que un formulario manda por un booleano: 0/1 y «true»/«false».
     *
     * @param mixed $value
     * @return bool
     */
    public static function enabledIsValid($value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        return in_array($value, [0, 1, '0', '1', 'true', 'false'], true);
    }

    /**
     * Los códigos de rol que siguen pasando.
     *
     * QUÉ HACE CADA CASO INVÁLIDO, y ninguno acaba en silencio:
     * - una LISTA VACÍA es válida y significa «solo root». Es un caso real: «que no trabaje nadie
     *   más que yo». NO significa «no pasa nadie»: root pasa siempre, lo diga la lista o no;
     * - un valor que no es lista, un texto, un nulo o una lista con algo que no es un código de rol
     *   existente NO se corrige a medias: se descarta ENTERA y se usa la de por omisión, con aviso
     *   en el registro. Aceptar los códigos buenos y tirar los malos daría un reparto que nadie
     *   escribió, y en una puerta de acceso eso es peor que ignorar la configuración.
     *
     * @return int[]
     */
    public static function allowedRoles(): array
    {
        $configured = get_config(self::ALLOWED_ROLES_CONFIG);

        if (self::allowedRolesAreValid($configured)) {
            return array_values(array_unique(array_map('intval', (array) $configured)));
        }

        if ($configured !== false && $configured !== null && !self::$invalidRolesLogged) {
            self::$invalidRolesLogged = true;
            log_exception(new \UnexpectedValueException(
                'La lista de «' . self::ALLOWED_ROLES_CONFIG . '» no es válida y se usa la de por defecto.'
                . ' Se admite una lista de códigos de rol existentes, y la lista vacía significa que no pasa nadie.'
            ));
        }

        return self::ALLOWED_ROLES_DEFAULT;
    }

    /**
     * Una lista —puede estar vacía— de códigos de rol que existen.
     *
     * @param mixed $roles
     * @return bool
     */
    public static function allowedRolesAreValid($roles): bool
    {
        if ($roles instanceof \stdClass) {
            $roles = (array) $roles;
        }

        if (!is_array($roles)) {
            return false;
        }

        $existentes = array_keys(UsersModel::TYPES_USERS);

        foreach ($roles as $role) {
            if (is_bool($role) || !is_scalar($role)) {
                return false;
            }
            if (!is_int($role) && (!is_string($role) || preg_match('/^\d+$/', $role) !== 1)) {
                return false;
            }
            if (!in_array((int) $role, $existentes, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Los segundos del `Retry-After`.
     *
     * CERO Y NEGATIVO NO: un `Retry-After: 0` le dice al buscador «vuelve ya», que es lo contrario
     * de lo que significa un mantenimiento. Y por encima de la cota puede dejar de volver.
     *
     * @return int
     */
    public static function retryAfter(): int
    {
        $configured = get_config(self::RETRY_AFTER_CONFIG);

        if (self::retryAfterIsValid($configured)) {
            return (int) $configured;
        }

        if ($configured !== false && $configured !== null && !self::$invalidRetryAfterLogged) {
            self::$invalidRetryAfterLogged = true;
            log_exception(new \UnexpectedValueException(
                'El valor de «' . self::RETRY_AFTER_CONFIG . '» no es válido y se usa el de por defecto.'
                . ' Se admite un entero de 1 a ' . self::RETRY_AFTER_MAX . ' segundos.'
            ));
        }

        return self::RETRY_AFTER;
    }

    /**
     * Un entero de 1 a `RETRY_AFTER_MAX` segundos.
     *
     * @param mixed $seconds
     * @return bool
     */
    public static function retryAfterIsValid($seconds): bool
    {
        if (is_bool($seconds) || !is_scalar($seconds)) {
            return false;
        }

        if (!is_int($seconds) && (!is_string($seconds) || preg_match('/^\d+$/', $seconds) !== 1)) {
            return false;
        }

        $value = (int) $seconds;

        return $value >= 1 && $value <= self::RETRY_AFTER_MAX;
    }

    /**
     * Si esa petición se detiene.
     *
     * ROOT PASA SIEMPRE, y la garantía está AQUÍ, en código, no en la configuración. Antes la lista
     * vacía se documentó como «no pasa nadie, ni root» y se llamó caso válido: no lo es. Dejar a
     * root fuera del sitio no tiene ninguna ganancia a cambio, y la configuración que lo provoca se
     * puede escribir sin querer.
     *
     * UNA RUTA SIN NOMBRE SE DETIENE. `getName()` devuelve `null` cuando la ruta no declara nombre, y
     * un `null` no puede estar en la lista de lo permitido: dejarla pasar sería abrir por descuido
     * justo lo que no se puede nombrar.
     *
     * @param string|null $routeName El nombre de la ruta pedida, o `null` si no lo declara.
     * @param int|null $roleCode El código del rol del usuario, o `null` si no hay sesión.
     * @return bool
     */
    public static function blocks(?string $routeName, ?int $roleCode): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        if ($roleCode === UsersModel::TYPE_USER_ROOT) {
            return false;
        }

        if ($routeName !== null && in_array($routeName, self::ALWAYS_ALLOWED, true)) {
            return false;
        }

        //Sin rol no hay excepción que mirar: sin sesión, el modo se aplica.
        return $roleCode === null || !in_array($roleCode, self::allowedRoles(), true);
    }

}
