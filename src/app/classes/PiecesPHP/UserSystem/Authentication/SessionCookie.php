<?php

/**
 * SessionCookie.php
 */

namespace PiecesPHP\UserSystem\Authentication;

use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\SessionToken;

/**
 * SessionCookie - La cookie de la sesión la pone y la borra el SERVIDOR (H188; P291 y P292): HttpOnly, para que ningún
 * JavaScript de la página la lea; con la caducidad del token, para que la sesión sobreviva al cierre del navegador; y
 * Secure en https. Solo para el host que la pone, SIN Domain aunque cookies.php lo traiga: con Domain viajaría a todos los
 * subdominios. El path y SameSite salen de `$config['cookies']` (cookies.php, del proyecto), salvo SameSite=None, que pasa
 * a Lax: sin él, la cookie de sesión iría en las peticiones de otros sitios.
 *
 * El JavaScript ya no la escribe. Hasta la 10.5 sí lo hacía (sin dominio y sin HttpOnly; PiecesPHPGenericHandlerSession
 * además con el host y con .host), y la 10.6 previa la puso con Domain: al ponerla y al borrarla se caducan también esas
 * formas, que si no conviven con la de ahora bajo el mismo nombre.
 *
 * La API sigue por cabecera (SessionToken::getJWTReceived()): esto es solo para el navegador.
 *
 * @package     PiecesPHP\UserSystem\Authentication
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SessionCookie
{

    /**
     * Pone la cookie con este token, hasta que el token caduque.
     *
     * @param string $token
     * @return void
     */
    public static function put(string $token): void
    {
        $expires = BaseToken::getExpired($token);
        //Sin caducidad legible el token no vale como sesión: no se pone una cookie que no caduque nunca.
        if (!is_int($expires) || $expires <= time()) {
            return;
        }
        $options = self::options($expires);
        //El ORDEN importa: en un host IP o de una etiqueta el navegador guarda Domain=host como de solo host, y un borrado
        //posterior se llevaría la nueva. Primero lo viejo, al final la buena.
        self::forgetLegacy($options['path']);
        setcookie(SessionToken::tokenName(), $token, $options);
    }

    /**
     * Borra la cookie: la que pone el servidor y las formas que escribía el JavaScript.
     *
     * @return void
     */
    public static function forget(): void
    {
        $options = self::options(1);
        setcookie(SessionToken::tokenName(), '', $options);
        self::forgetLegacy($options['path']);
    }

    /**
     * Las opciones de setcookie(): las de cookies.php con HttpOnly siempre, sin Domain, Secure también en https (o tras un
     * proxy que diga https) y SameSite Lax o Strict, nunca None.
     *
     * @param int $expires
     * @param array<mixed>|null $cookiesConfig La de cookies.php si no se pasa (las pruebas pasan la suya).
     * @return array{expires:int,path:string,domain:string,secure:bool,httponly:bool,samesite:'Lax'|'Strict'|'None'}
     */
    public static function options(int $expires, ?array $cookiesConfig = null): array
    {
        $cookiesConfig ??= get_config('cookies');
        $options = cookie_options_by_config(is_array($cookiesConfig) ? $cookiesConfig : []);
        $options['expires'] = $expires;
        $options['httponly'] = true;
        //Detrás de un proxy que termina el TLS, PHP ve http: la cabecera del proxy solo puede AÑADIR Secure.
        $proxied = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))) === 'https';
        $options['secure'] = $options['secure'] || get_config('domain_protocol') === 'https://' || $proxied;
        $options['domain'] = '';
        if ($options['samesite'] === 'None') {
            $options['samesite'] = 'Lax';
        }
        if ($options['path'] === '') {
            $options['path'] = '/';
        }
        return $options;
    }

    /**
     * Caduca las formas viejas, todas con path /: con Domain (el host y .host: las del JavaScript viejo y la de la 10.6
     * previa) y, si la de ahora va en otro path, también la de solo host (con path / es la misma: la sustituye o la borra
     * quien llama).
     *
     * @param string $currentPath El path con que se pone ahora.
     * @return void
     */
    private static function forgetLegacy(string $currentPath): void
    {
        $legacy = ['expires' => 1, 'path' => '/', 'secure' => get_config('domain_protocol') === 'https://', 'httponly' => false, 'samesite' => 'Lax'];
        if ($currentPath !== '/') {
            setcookie(SessionToken::tokenName(), '', $legacy);
        }
        $host = isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        //Sin el puerto; un host IPv6 ([::1]:8080) no admite Domain y no tiene formas viejas que borrar.
        $host = str_starts_with($host, '[') ? '' : explode(':', $host)[0];
        if ($host === '') {
            return;
        }
        foreach ([$host, ".{$host}"] as $domain) {
            setcookie(SessionToken::tokenName(), '', $legacy + ['domain' => $domain]);
        }
    }

}
