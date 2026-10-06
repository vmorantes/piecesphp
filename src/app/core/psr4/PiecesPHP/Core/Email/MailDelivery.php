<?php

/**
 * MailDelivery.php
 */
namespace PiecesPHP\Core\Email;

use PiecesPHP\Core\AppEnvironment;

/**
 * MailDelivery - Qué hace este despliegue con el correo: lo retiene o lo envía.
 *
 * Vive como opción PROPIA y no dentro del array `mail` por tres razones medidas (ADR 0043 §1,
 * decidido en pendientes 321.6): ese array se guarda comprimido y cifrado, así que un aviso
 * tendría que descifrarlo entero para leer una palabra; la pantalla de correo lo reescribe
 * completo al guardar, y una declaración del despliegue no puede quedar al alcance de quien
 * edita el SMTP (P52); y esto no es configuración de correo, es del mismo rango que el entorno.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class MailDelivery
{

    /**
     * @var string
     */
    const CONFIG_NAME = 'mail_delivery';

    /**
     * El correo NO sale: va al sumidero local y, si no hay ninguno, al buzón en disco.
     *
     * @var string
     */
    const SINK = 'sink';

    /**
     * El correo sale por el SMTP configurado.
     *
     * @var string
     */
    const REAL = 'real';

    /**
     * Se deduce del entorno. Sin `environment.php`, vale SINK (ADR 0043 §1).
     *
     * @var string
     */
    const AUTO = 'auto';

    /**
     * @var string[]
     */
    const MODES = [self::AUTO, self::SINK, self::REAL];

    /**
     * Carpeta del buzón, relativa a `app/logs`.
     *
     * @var string
     */
    const OUTBOX_DIRECTORY = 'mail-outbox';

    /**
     * Lo que el despliegue DECLARA, sin resolver `auto`. Un valor que no es de los tres cae a
     * `auto`: la configuración puede traer cualquier cosa y no se le cree.
     *
     * @return string
     */
    public static function declared(): string
    {
        $value = get_config(self::CONFIG_NAME);
        return is_string($value) && in_array($value, self::MODES, true) ? $value : self::AUTO;
    }

    /**
     * Qué pasa de verdad con el correo aquí: SINK o REAL, nunca AUTO.
     *
     * **La ausencia de `environment.php` vale SINK, no REAL.** Antes del 2026-10-03 un clon recién
     * hecho enviaba de verdad porque `AppEnvironment::load()` trata la ausencia como `production`:
     * la ausencia de una declaración no puede significar la opción peligrosa (ADR 0043 §1).
     *
     * @return string
     */
    public static function resolved(): string
    {
        $declared = self::declared();
        if ($declared !== self::AUTO) {
            return $declared;
        }
        if (!AppEnvironment::isConfigured()) {
            return self::SINK;
        }
        return AppEnvironment::get() === AppEnvironment::LOCAL ? self::SINK : self::REAL;
    }

    /**
     * @return bool
     */
    public static function goesToSink(): bool
    {
        return self::resolved() === self::SINK;
    }

    /**
     * Carpeta del buzón en disco. El mensaje que no pudo llegar al sumidero se guarda aquí en vez
     * de salir por el correo del sistema (ADR 0045 §1).
     *
     * @param string|null $directory
     * @return string
     */
    public static function outboxDirectory(?string $directory = null): string
    {
        $base = $directory ?? basepath('app/logs');
        return rtrim($base, '/\\') . '/' . self::OUTBOX_DIRECTORY;
    }

    /**
     * El nombre de esta instalación, para marcar cada correo. Sale de `base_url`, no del título:
     * el título cambia con la sección que se esté mirando (`set_title()`), y una etiqueta que
     * cambia no agrupa nada. La URL base es lo único estable y distinto entre dos clones del mismo
     * dominio (ADR 0043 §4).
     *
     * **Con un host que no distingue nada** —`localhost`, `127.0.0.1` o vacío, que es lo que tiene la terminal,
     * donde `base_url` vale `http://localhost`— se usa el nombre de la carpeta de la instalación: si no, dos
     * clones de la misma máquina que mandan desde la terminal se etiquetarían igual en una Mailpit compartida.
     *
     * @param string|null $baseURL Null: la `base_url` de la configuración.
     * @param string|null $installationRoot Null: la carpeta que contiene `src/`.
     * @return string
     */
    public static function installationTag(?string $baseURL = null, ?string $installationRoot = null): string
    {
        $base = $baseURL ?? get_config('base_url');
        $host = is_string($base) ? (string) parse_url($base, PHP_URL_HOST) : '';
        $ruta = is_string($base) ? trim((string) parse_url($base, PHP_URL_PATH), '/') : '';
        $crudo = trim($host . ($ruta !== '' ? '-' . $ruta : ''), '-');
        if (in_array(strtolower($host), ['', 'localhost', '127.0.0.1'], true)) {
            $raiz = $installationRoot ?? dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
            $crudo = basename(rtrim(str_replace('\\', '/', $raiz), '/'));
        }
        //Mailpit lista las etiquetas tal cual: se normaliza para que no haya dos formas del mismo nombre.
        $normalizado = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $crudo));
        return trim($normalizado, '-');
    }

    /**
     * Si un SMTP apunta a ESTA máquina, y por tanto probarlo no sale a la red (ADR 0046 §4).
     *
     * **Se decide por a dónde RESUELVE, no por el nombre**: `mail.example.com` puede apuntar a
     * 127.0.0.1, y un nombre que empiece por «localhost» puede apuntar a cualquier sitio. Resolver
     * un nombre consulta al resolutor del sistema, que **no es conectarse al SMTP**: es justo el
     * dato que la decisión del 0046 §4 necesita.
     *
     * Falla CERRADO en los tres casos en que no se puede afirmar que sea local:
     * - si la resolución falla, `gethostbynamel()` devuelve `false` y se decide que no es local;
     * - si el nombre resuelve a varias direcciones, **todas** tienen que ser de bucle local;
     * - si no resuelve a ninguna dirección, tampoco.
     *
     * @param string $host El host tal y como está configurado, con su prefijo si lo trae.
     * @return bool
     */
    public static function smtpIsLoopback(string $host): bool
    {
        $host = trim($host);
        if ($host === '') {
            return false;
        }
        //PHPMailer admite `ssl://host` y `tls://host`, y los corchetes son la forma de un IPv6.
        $host = (string) preg_replace('#^[a-z0-9+.-]+://#i', '', $host);
        $host = trim($host, '[]');
        if ($host === '') {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return self::isLoopbackAddress($host);
        }

        $resolved = gethostbynamel($host);
        if (!is_array($resolved) || $resolved === []) {
            return false;
        }
        foreach ($resolved as $address) {
            if (!self::isLoopbackAddress((string) $address)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Si una dirección es de bucle local. IPv4 es todo el `127.0.0.0/8`, no solo `127.0.0.1`;
     * IPv6 se compara en binario para que `::1` y `0:0:0:0:0:0:0:1` cuenten lo mismo.
     *
     * @param string $address
     * @return bool
     */
    protected static function isLoopbackAddress(string $address): bool
    {
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return str_starts_with($address, '127.');
        }
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return inet_pton($address) === inet_pton('::1');
        }
        return false;
    }

    /**
     * Por qué la entrega resuelta es la que es, para que un aviso o `mail-doctor` lo expliquen sin
     * repetir la regla.
     *
     * @return string
     */
    public static function reason(): string
    {
        $declared = self::declared();
        if ($declared !== self::AUTO) {
            return 'declarado';
        }
        return AppEnvironment::isConfigured() ? 'entorno' : 'sin-entorno';
    }
}
