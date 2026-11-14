<?php

/**
 * AllowedHosts.php
 */

namespace PiecesPHP\Core;

/**
 * AllowedHosts - La lista de dominios que sirven esta instalación ($config['allowed_hosts']).
 *
 * Se aplica en bootstrap.php, justo después de cargar config.php: pasada esa puerta, la cabecera Host es una de la lista.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class AllowedHosts
{

    /**
     * Sin espacios a los lados, en minúsculas, sin el puerto y sin el punto final («[::1]:8080» → «[::1]»).
     *
     * @param string $host
     * @return string
     */
    public static function normalize(string $host): string
    {
        $host = mb_strtolower(trim($host));
        if (str_starts_with($host, '[')) {
            $end = strpos($host, ']');
            return $end === false ? $host : substr($host, 0, $end + 1);
        }
        if (substr_count($host, ':') === 1) {
            $host = substr($host, 0, (int) strpos($host, ':'));
        }
        return rtrim($host, '.');
    }

    /**
     * Si el Host está en la lista. Lista vacía: cualquiera. Una entrada «.dominio» casa con él y con sus subdominios.
     *
     * @param string $host
     * @param array<mixed> $allowed
     * @return bool
     */
    public static function allows(string $host, array $allowed): bool
    {
        $entries = [];
        foreach ($allowed as $entry) {
            if (is_string($entry)) {
                $entry = self::normalize($entry);
                if ($entry !== '') {
                    $entries[] = $entry;
                }
            }
        }
        if ($entries === []) {
            return true;
        }
        $host = self::normalize($host);
        if ($host === '') {
            return false;
        }
        foreach ($entries as $entry) {
            if ($host === $entry) {
                return true;
            }
            if (str_starts_with($entry, '.') && ($host === substr($entry, 1) || str_ends_with($host, $entry))) {
                return true;
            }
        }
        return false;
    }

    /**
     * El host con el que requestIsSameDomain() compara. Con lista, X-Forwarded-Host solo cuenta si la lista lo admite.
     *
     * @param array<mixed> $server
     * @param array<mixed> $allowed
     * @return string
     */
    public static function hostForSameDomain(array $server, array $allowed): string
    {
        $pick = static fn(string $key): ?string => isset($server[$key]) && is_string($server[$key]) ? $server[$key] : null;
        $forwarded = $pick('HTTP_X_FORWARDED_HOST');
        if ($forwarded !== null && self::allows($forwarded, $allowed)) {
            return $forwarded;
        }
        return $pick('HTTP_HOST') ?? $pick('SERVER_NAME') ?? '';
    }
}
