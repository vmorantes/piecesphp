<?php

/**
 * AppVersion.php
 */

namespace PiecesPHP\Core;

/**
 * AppVersion - La versión de la instalación y el commit del que salió.
 *
 * El commit sale, en este orden, del sello de despliegue (src/app/version-stamp.json) o del repositorio. Del
 * repositorio solo se leen .git/HEAD, .git/refs/** y .git/packed-refs: nunca .git/config, que lleva las
 * credenciales del remoto, y nunca se ejecuta git.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class AppVersion
{
    const SOURCE_STAMP = 'stamp';
    const SOURCE_GIT = 'git';

    const STAMP_RELATIVE_PATH = 'app/version-stamp.json';

    /**
     * @var string|null Raíz del repositorio fijada por una prueba
     */
    private static $testRoot = null;

    /**
     * @var string|null Sello fijado por una prueba
     */
    private static $testStamp = null;

    /**
     * @return string
     */
    public static function version(): string
    {
        return APP_VERSION;
    }

    /**
     * @return string
     */
    public static function date(): string
    {
        return APP_VERSION_DATE;
    }

    /**
     * El hash completo (40 hex) del commit, o null si no se sabe.
     *
     * @return string|null
     */
    public static function commit(): ?string
    {
        return self::resolve()[0];
    }

    /**
     * De dónde salió commit(): 'stamp', 'git' o null.
     *
     * @return string|null
     */
    public static function commitSource(): ?string
    {
        return self::resolve()[1];
    }

    /**
     * El commit según el repositorio, sin mirar el sello: lo que version-stamp sella por defecto.
     *
     * @return string|null
     */
    public static function gitCommit(): ?string
    {
        try {
            return self::readGit(self::root());
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return string
     */
    public static function stampPath(): string
    {
        return self::$testStamp ?? basepath(self::STAMP_RELATIVE_PATH);
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isCommitHash(string $value): bool
    {
        return preg_match('/^[0-9a-f]{40}$/', $value) === 1;
    }

    /**
     * SOLO PARA PRUEBAS: lee otra raíz de repositorio y otro sello; null vuelve a los reales.
     *
     * @internal
     * @param string|null $root
     * @param string|null $stampPath
     * @return void
     */
    public static function useForTesting(?string $root, ?string $stampPath): void
    {
        self::$testRoot = $root;
        self::$testStamp = $stampPath;
    }

    /**
     * @return array{0:string|null,1:string|null}
     */
    private static function resolve(): array
    {
        try {
            $stamped = self::readStamp(self::stampPath());
            if ($stamped !== null) {
                return [$stamped, self::SOURCE_STAMP];
            }
            $git = self::readGit(self::root());
            return $git !== null ? [$git, self::SOURCE_GIT] : [null, null];
        } catch (\Throwable $e) {
            return [null, null];
        }
    }

    /**
     * La raíz del repositorio es el padre de src/.
     *
     * @return string
     */
    private static function root(): string
    {
        return self::$testRoot ?? dirname(rtrim(basepath(), '/'));
    }

    /**
     * @param string $path
     * @return string|null
     */
    private static function readStamp(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($path), true);
        $commit = is_array($data) && is_string($data['commit'] ?? null) ? $data['commit'] : '';
        return self::isCommitHash($commit) ? $commit : null;
    }

    /**
     * @param string $root
     * @return string|null
     */
    private static function readGit(string $root): ?string
    {
        $gitDir = self::gitDirectory($root);
        if ($gitDir === null || !is_file($gitDir . '/HEAD')) {
            return null;
        }
        $head = trim((string) file_get_contents($gitDir . '/HEAD'));
        //HEAD separado: el hash va directo.
        if (self::isCommitHash($head)) {
            return $head;
        }
        if (preg_match('#^ref: (refs/[A-Za-z0-9._/-]+)$#', $head, $match) !== 1 || str_contains($match[1], '..')) {
            return null;
        }
        $ref = $match[1];
        if (is_file($gitDir . '/' . $ref)) {
            $hash = trim((string) file_get_contents($gitDir . '/' . $ref));
            return self::isCommitHash($hash) ? $hash : null;
        }
        //Las ramas empaquetadas viven en packed-refs: «<hash> <ref>» por línea.
        if (is_file($gitDir . '/packed-refs')) {
            foreach (preg_split('/\R/', (string) file_get_contents($gitDir . '/packed-refs')) ?: [] as $line) {
                $parts = explode(' ', trim($line), 2);
                if (count($parts) === 2 && $parts[1] === $ref) {
                    return self::isCommitHash($parts[0]) ? $parts[0] : null;
                }
            }
        }
        return null;
    }

    /**
     * .git como carpeta, o como archivo «gitdir: …» (worktree o submódulo) si apunta dentro del repositorio.
     *
     * @param string $root
     * @return string|null
     */
    private static function gitDirectory(string $root): ?string
    {
        $git = $root . '/.git';
        if (is_dir($git)) {
            return $git;
        }
        if (!is_file($git)) {
            return null;
        }
        if (preg_match('/^gitdir: (.+)$/', trim((string) file_get_contents($git)), $match) !== 1) {
            return null;
        }
        $target = str_starts_with($match[1], '/') ? $match[1] : $root . '/' . $match[1];
        $real = realpath($target);
        $realRoot = realpath($root);
        if ($real === false || $realRoot === false || !is_dir($real) || !str_starts_with($real, rtrim($realRoot, '/') . '/')) {
            return null;
        }
        return $real;
    }
}
