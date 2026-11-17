<?php

/**
 * VisitorIP.php
 */

namespace PiecesPHP\Core;

/**
 * VisitorIP. La IP del visitante, también detrás de un proxy (H28, P341).
 *
 * Sin proxies de confianza, `REMOTE_ADDR`, como siempre. Con la lista, solo si la conexión llega de uno de ellos se lee
 * `X-Forwarded-For` de DERECHA a IZQUIERDA saltando los de confianza: la primera que no lo es, es la del visitante.
 * Una cabecera que falta deja `REMOTE_ADDR`; una entrada que no es una IP detiene el recorrido y queda el último salto de
 * confianza visitado, porque lo de su izquierda lo escribe el cliente.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class VisitorIP
{

    /** La lista de proxies de confianza (Configuración › Seguridad, solo root): IP o CIDR, IPv4 o IPv6. */
    const CONFIG_NAME = 'trusted_proxies';

    /** La IP cuando no hay ninguna (el terminal): la de siempre en el framework. */
    const NONE = '0.0.0.0';

    /** Los motivos de entryError(). */
    const ERROR_INVALID = 'invalid';
    const ERROR_ALL = 'all';

    /**
     * La IP del visitante de esta petición.
     *
     * @return string
     */
    public static function get(): string
    {
        return self::resolve($_SERVER, self::trustedProxies());
    }

    /**
     * La IP del visitante con unas cabeceras y una lista dadas. Sin efectos: es lo que prueban las suites.
     *
     * @param array<string,mixed> $server Con la forma de $_SERVER
     * @param string[] $trusted IP o CIDR
     * @return string
     */
    public static function resolve(array $server, array $trusted): string
    {
        $remote = $server['REMOTE_ADDR'] ?? null;
        $remote = is_string($remote) && filter_var($remote, \FILTER_VALIDATE_IP) !== false ? $remote : null;
        if ($remote === null) {
            return is_string($server['REMOTE_ADDR'] ?? null) && $server['REMOTE_ADDR'] !== '' ? $server['REMOTE_ADDR'] : self::NONE;
        }
        if ($trusted === [] || !self::isTrusted($remote, $trusted)) {
            return $remote;
        }
        $header = $server['HTTP_X_FORWARDED_FOR'] ?? null;
        if (!is_string($header) || trim($header) === '') {
            return $remote;
        }
        $chain = array_map('trim', explode(',', $header));
        $last = $remote;
        for ($i = count($chain) - 1; $i >= 0; $i--) {
            if (filter_var($chain[$i], \FILTER_VALIDATE_IP) === false) {
                return $last;
            }
            if (!self::isTrusted($chain[$i], $trusted)) {
                return $chain[$i];
            }
            $last = $chain[$i];
        }
        return $last;
    }

    /**
     * Si es una IP válida privada, local o reservada (una IPv4 mapeada en IPv6, por su IPv4); false si no es una IP.
     *
     * @param string $ip
     * @return bool
     */
    public static function isPrivateOrLocal(string $ip): bool
    {
        $binary = @inet_pton($ip);
        if ($binary === false || filter_var($ip, \FILTER_VALIDATE_IP) === false) {
            return false;
        }
        $ip = (string) inet_ntop(self::unmapped($binary));
        return filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Si la conexión llega de una dirección privada o local con X-Forwarded-For y la lista está vacía.
     *
     * @param array<string,mixed> $server Con la forma de $_SERVER
     * @param string[] $trusted IP o CIDR
     * @return bool
     */
    public static function proxyWithoutList(array $server, array $trusted): bool
    {
        $remote = $server['REMOTE_ADDR'] ?? null;
        $header = $server['HTTP_X_FORWARDED_FOR'] ?? null;
        return $trusted === []
            && is_string($remote) && filter_var($remote, \FILTER_VALIDATE_IP) !== false && self::isPrivateOrLocal($remote)
            && is_string($header) && trim($header) !== '';
    }

    /**
     * La lista guardada, solo con sus entradas válidas. Llega de la base como lista, o como objeto si no lo es (H304).
     *
     * @return string[]
     */
    public static function trustedProxies(): array
    {
        $value = get_config(self::CONFIG_NAME);
        if ($value instanceof \stdClass) {
            $value = get_object_vars($value);
        }
        if (!is_array($value)) {
            return [];
        }
        return array_values(array_filter($value, fn ($entry) => is_string($entry) && self::entryError($entry) === null));
    }

    /**
     * Si una entrada de la lista no vale: null si es una IP o un CIDR válido; ERROR_ALL si abarca todas (prefijo /0, o /96
     * sobre una IPv4 mapeada); ERROR_INVALID si no es ni una cosa ni otra.
     *
     * @param string $entry
     * @return string|null
     */
    public static function entryError(string $entry): ?string
    {
        $entry = trim($entry);
        if (filter_var($entry, \FILTER_VALIDATE_IP) !== false) {
            return null;
        }
        $parts = explode('/', $entry);
        if (count($parts) !== 2 || filter_var($parts[0], \FILTER_VALIDATE_IP) === false || preg_match('/^\d{1,3}$/', $parts[1]) !== 1) {
            return self::ERROR_INVALID;
        }
        $max = str_contains($parts[0], ':') ? 128 : 32;
        $prefix = (int) $parts[1];
        if ($prefix > $max) {
            return self::ERROR_INVALID;
        }
        $base = @inet_pton($parts[0]);
        if ($prefix === 96 && $base !== false && self::unmapped($base) !== $base) {
            return self::ERROR_ALL;
        }
        return $prefix === 0 ? self::ERROR_ALL : null;
    }

    /**
     * Si una IP está en la lista (por igualdad o dentro de un CIDR de su misma familia).
     *
     * @param string $ip
     * @param string[] $trusted
     * @return bool
     */
    public static function isTrusted(string $ip, array $trusted): bool
    {
        $address = @inet_pton($ip);
        if ($address === false) {
            return false;
        }
        $address = self::unmapped($address);
        foreach ($trusted as $entry) {
            $parts = explode('/', $entry, 2);
            $network = $parts[0];
            $prefix = $parts[1] ?? null;
            $base = @inet_pton($network);
            if ($base === false) {
                continue;
            }
            //Una entrada mapeada con un prefijo que no llega a la IPv4 (menos de 96) se compara como IPv6.
            $unmapped = self::unmapped($base);
            if ($unmapped !== $base && ($prefix === null || (int) $prefix - 96 >= 0)) {
                $base = $unmapped;
                $prefix = $prefix === null ? null : (string) ((int) $prefix - 96);
            }
            if (strlen($base) !== strlen($address)) {
                continue;
            }
            $bits = $prefix === null ? strlen($address) * 8 : (int) $prefix;
            $bytes = intdiv($bits, 8);
            if (substr($address, 0, $bytes) !== substr($base, 0, $bytes)) {
                continue;
            }
            $rest = $bits % 8;
            if ($rest === 0) {
                return true;
            }
            $mask = (0xFF << (8 - $rest)) & 0xFF;
            if ((ord($address[$bytes]) & $mask) === (ord($base[$bytes]) & $mask)) {
                return true;
            }
        }
        return false;
    }

    /**
     * El binario de una IPv4 mapeada en IPv6 (::ffff:a.b.c.d), como su IPv4; cualquier otro, tal cual.
     *
     * @param string $binary De inet_pton()
     * @return string
     */
    private static function unmapped(string $binary): string
    {
        return strlen($binary) === 16 && substr($binary, 0, 12) === str_repeat("\0", 10) . "\xff\xff" ? substr($binary, 12) : $binary;
    }
}
