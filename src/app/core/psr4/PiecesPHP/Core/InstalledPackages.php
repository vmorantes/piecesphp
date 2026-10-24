<?php

/**
 * InstalledPackages.php
 */

namespace PiecesPHP\Core;

use Composer\InstalledVersions;

/**
 * InstalledPackages - Los paquetes de PHP que pide src/composer.json, con lo que hay instalado de cada uno.
 *
 * La versión sale de Composer\InstalledVersions y la licencia de src/vendor/composer/installed.json: lo instalado, no
 * lo escrito a mano. La leen «Acerca de» y la Salud de la instalación.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class InstalledPackages
{
    const OWN_VENDOR = 'piecesphp/';

    /**
     * Cada paquete del `require` (sin php ni ext-*), los propios primero y después por nombre.
     *
     * @return array<int,array{name:string,constraint:string,installed:bool,version:string,license:string,own:bool}>
     */
    public static function required(): array
    {
        $licenses = self::licenses();
        $packages = [];
        foreach (self::requireList() as $name => $constraint) {
            if ($name === 'php' || str_starts_with($name, 'ext-')) {
                continue;
            }
            $installed = class_exists(InstalledVersions::class) && InstalledVersions::isInstalled($name);
            $packages[] = [
                'name' => $name,
                'constraint' => $constraint,
                'installed' => $installed,
                'version' => $installed ? (string) InstalledVersions::getPrettyVersion($name) : '',
                'license' => $licenses[$name] ?? '',
                'own' => str_starts_with($name, self::OWN_VENDOR),
            ];
        }
        usort($packages, fn(array $a, array $b): int => [$b['own'], $a['name']] <=> [$a['own'], $b['name']]);
        return $packages;
    }

    /**
     * El `require` de src/composer.json tal cual: también php y ext-*.
     *
     * @return array<string,string>
     */
    public static function requireList(): array
    {
        $composer = json_decode((string) @file_get_contents(self::srcPath() . '/composer.json'), true);
        $require = is_array($composer) && isset($composer['require']) && is_array($composer['require']) ? $composer['require'] : [];
        $list = [];
        foreach ($require as $name => $constraint) {
            if (is_string($name) && is_string($constraint)) {
                $list[$name] = $constraint;
            }
        }
        return $list;
    }

    /**
     * «PiecesPHP database» para un paquete propio; el nombre de Composer para los demás.
     *
     * @param string $name
     * @return string
     */
    public static function displayName(string $name): string
    {
        return str_starts_with($name, self::OWN_VENDOR) ? 'PiecesPHP ' . substr($name, strlen(self::OWN_VENDOR)) : $name;
    }

    /**
     * @return string src/vendor/composer/installed.json, para decir de dónde sale la licencia
     */
    public static function installedJsonPath(): string
    {
        return self::srcPath() . '/vendor/composer/installed.json';
    }

    /**
     * @return array<string,string>
     */
    private static function licenses(): array
    {
        $installed = json_decode((string) @file_get_contents(self::installedJsonPath()), true);
        $packages = is_array($installed) && isset($installed['packages']) && is_array($installed['packages']) ? $installed['packages'] : (is_array($installed) ? $installed : []);
        $licenses = [];
        foreach ($packages as $package) {
            if (is_array($package) && isset($package['name']) && is_string($package['name'])) {
                $license = $package['license'] ?? [];
                $licenses[$package['name']] = is_array($license) ? implode(', ', array_filter($license, 'is_string')) : (is_string($license) ? $license : '');
            }
        }
        return $licenses;
    }

    /**
     * @return string
     */
    private static function srcPath(): string
    {
        return dirname(__DIR__, 5);
    }
}
