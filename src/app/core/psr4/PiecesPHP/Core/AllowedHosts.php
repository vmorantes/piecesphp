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
     * Si el Host está en la lista. Lista vacía: cualquiera. Una entrada «.dominio» casa con él y con sus subdominios. Una
     * entrada «example.org» admite también «www.example.org» salvo que $inferWww sea false (`allowed_hosts_infer_www`,
     * P415); nada más se infiere.
     *
     * @param string $host
     * @param array<mixed> $allowed
     * @param bool $inferWww
     * @return bool
     */
    public static function allows(string $host, array $allowed, bool $inferWww = true): bool
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
        //Con lista, un Host con caracteres que no son de un nombre (o un «puerto» que no es un número) no se compara:
        //«example.org:@evil.com» pasaba la lista y la URL base apuntaba a evil.com.
        if (!self::validSyntax($host)) {
            return false;
        }
        $host = self::normalize($host);
        if ($host === '') {
            return false;
        }
        foreach ($entries as $entry) {
            if ($host === $entry) {
                return true;
            }
            if ($inferWww && $host === 'www.' . $entry && self::infersWww($entry)) {
                return true;
            }
            if (str_starts_with($entry, '.') && ($host === substr($entry, 1) || str_ends_with($host, $entry))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Si un Host tiene la forma de un nombre (letras, cifras, guiones y puntos), o una IPv6 entre corchetes, con un puerto
     * numérico opcional.
     *
     * @param string $host
     * @return bool
     */
    public static function validSyntax(string $host): bool
    {
        $host = trim($host);
        return preg_match('/^(?:[a-z0-9-]+(?:\.[a-z0-9-]+)*\.?|\[[0-9a-f:.]+\])(?::\d{1,5})?$/i', $host) === 1;
    }

    /**
     * Si una entrada (ya normalizada) es un nombre de dominio al que se le infiere el www: con un punto, sin punto delante,
     * sin empezar ya por «www.» y que no sea una IP (a «localhost» o «127.0.0.1» no se les antepone nada).
     *
     * @param string $entry
     * @return bool
     */
    public static function infersWww(string $entry): bool
    {
        return str_contains($entry, '.') && !str_starts_with($entry, '.') && !str_starts_with($entry, 'www.')
            && !str_starts_with($entry, '[') && filter_var($entry, FILTER_VALIDATE_IP) === false;
    }

    /**
     * El host con el que requestIsSameDomain() compara. Con lista, X-Forwarded-Host solo cuenta si la lista lo admite.
     *
     * @param array<mixed> $server
     * @param array<mixed> $allowed
     * @param bool $inferWww
     * @return string
     */
    public static function hostForSameDomain(array $server, array $allowed, bool $inferWww = true): string
    {
        $pick = static fn(string $key): ?string => isset($server[$key]) && is_string($server[$key]) ? $server[$key] : null;
        $forwarded = $pick('HTTP_X_FORWARDED_HOST');
        if ($forwarded !== null && self::allows($forwarded, $allowed, $inferWww)) {
            return $forwarded;
        }
        return $pick('HTTP_HOST') ?? $pick('SERVER_NAME') ?? '';
    }
}
