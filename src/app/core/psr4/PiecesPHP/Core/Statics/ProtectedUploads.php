<?php

/**
 * ProtectedUploads.php
 */

namespace PiecesPHP\Core\Statics;

/**
 * ProtectedUploads - Lo privado de uploads lo decide el NOMBRE en disco: foto.jpg.protected.
 *
 * La URL es siempre la del nombre público (foto.jpg); el sufijo solo existe en disco.
 *  - Lo privado lleva el sufijo. Nginx no lo reconoce como estático y pasa a Apache, el .htaccess de uploads niega el
 *    sufijo, y ServerStatics sirve el nombre público tras validar con el validador de la carpeta.
 *  - Lo visible lleva su nombre real, y Nginx y Apache lo sirven directamente, sin PHP.
 * El sufijo sale de la configuración `protected_uploads_suffix` (por defecto «.protected»).
 *
 * @package     PiecesPHP\Core\Statics
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class ProtectedUploads
{
    const DEFAULT_SUFFIX = '.protected';
    const CONFIG_SUFFIX = 'protected_uploads_suffix';

    const RESOLVED_PUBLIC = 'public';
    const RESOLVED_PRIVATE = 'private';
    const RESOLVED_SUFFIX_REQUESTED = 'suffix-requested';
    const RESOLVED_MISSING = 'missing';

    const VISIBILITY_RENAMED = 'renamed';
    const VISIBILITY_UNCHANGED = 'unchanged';
    const VISIBILITY_MISSING = 'missing';
    const VISIBILITY_CONFLICT = 'conflict';
    const VISIBILITY_FAILED = 'failed';

    /**
     * La primera línea del .htaccess de uploads: sin ella, el archivo no es del subsistema y no se reescribe.
     */
    const DENY_HTACCESS_MARK = '#PiecesPHP\\Core\\Statics\\ProtectedUploads: lo reescribe el subsistema; no se edita a mano.';

    /**
     * El sufijo de lo privado: el de la configuración si es válido; si no, «.protected».
     *
     * @return string
     */
    public static function suffix(): string
    {
        $suffix = get_config(self::CONFIG_SUFFIX);
        return is_string($suffix) && preg_match('/^\.[A-Za-z0-9_-]{2,32}$/', $suffix) === 1 ? $suffix : self::DEFAULT_SUFFIX;
    }

    /**
     * Dónde está en disco lo que se pide por su nombre público.
     *
     * @param string $requestedPath La ruta del nombre público
     * @param string|null $suffix
     * @return array{0: string|null, 1: string} [ruta en disco o null, RESOLVED_*]. Pedir el nombre de disco
     *                                           (RESOLVED_SUFFIX_REQUESTED) no se sirve nunca.
     */
    public static function resolve(string $requestedPath, ?string $suffix = null): array
    {
        $suffix ??= self::suffix();
        if (str_ends_with($requestedPath, $suffix)) {
            return [null, self::RESOLVED_SUFFIX_REQUESTED];
        }
        if (is_file($requestedPath)) {
            return [$requestedPath, self::RESOLVED_PUBLIC];
        }
        if (is_file($requestedPath . $suffix)) {
            return [$requestedPath . $suffix, self::RESOLVED_PRIVATE];
        }
        return [null, self::RESOLVED_MISSING];
    }

    /**
     * La ruta en disco de un archivo privado, a partir de su nombre público.
     *
     * @param string $publicPath
     * @param string|null $suffix
     * @return string
     */
    public static function privatePath(string $publicPath, ?string $suffix = null): string
    {
        $suffix ??= self::suffix();
        return str_ends_with($publicPath, $suffix) ? $publicPath : $publicPath . $suffix;
    }

    /**
     * Pone un archivo, dado por su nombre público, en la visibilidad pedida. Un solo rename(), que es atómico.
     *
     * @param string $publicPath
     * @param bool $public
     * @param string|null $suffix
     * @return string VISIBILITY_*: no pisa nada; si existen los dos nombres, es un conflicto y no se toca
     */
    public static function setFileVisibility(string $publicPath, bool $public, ?string $suffix = null): string
    {
        $suffix ??= self::suffix();
        $privatePath = self::privatePath($publicPath, $suffix);
        $from = $public ? $privatePath : $publicPath;
        $to = $public ? $publicPath : $privatePath;
        $fromExists = is_file($from);
        $toExists = is_file($to);
        if ($fromExists && $toExists) {
            return self::VISIBILITY_CONFLICT;
        }
        if (!$fromExists) {
            return $toExists ? self::VISIBILITY_UNCHANGED : self::VISIBILITY_MISSING;
        }
        //@: un rename fallido es un aviso, y en local un aviso aborta; el fallo lo dice el retorno.
        return @rename($from, $to) ? self::VISIBILITY_RENAMED : self::VISIBILITY_FAILED;
    }

    /**
     * Pone todos los archivos de una carpeta (y sus subcarpetas) en la visibilidad pedida, uno a uno.
     * Los archivos ocultos (.htaccess y compañía) no se tocan.
     *
     * @param string $directory
     * @param bool $public
     * @param string|null $suffix
     * @param bool $recursive Con sus subcarpetas (por defecto) o solo los archivos de la carpeta
     * @return array{renamed: int, unchanged: int, conflicts: string[], failed: string[]}
     */
    public static function setFolderVisibility(string $directory, bool $public, ?string $suffix = null, bool $recursive = true): array
    {
        $suffix ??= self::suffix();
        $report = ['renamed' => 0, 'unchanged' => 0, 'conflicts' => [], 'failed' => []];
        if (!is_dir($directory)) {
            return $report;
        }
        $publicNames = [];
        foreach (self::listFiles($directory, $recursive) as $path) {
            $publicNames[str_ends_with($path, $suffix) ? mb_substr($path, 0, -mb_strlen($suffix)) : $path] = true;
        }
        foreach (array_keys($publicNames) as $publicPath) {
            $result = self::setFileVisibility($publicPath, $public, $suffix);
            if ($result === self::VISIBILITY_RENAMED) {
                $report['renamed']++;
            } elseif ($result === self::VISIBILITY_UNCHANGED) {
                $report['unchanged']++;
            } elseif ($result === self::VISIBILITY_CONFLICT) {
                $report['conflicts'][] = $publicPath;
            } else {
                $report['failed'][] = $publicPath;
            }
        }
        return $report;
    }

    /**
     * Los archivos de una carpeta, sin los ocultos (.htaccess y compañía), en orden.
     *
     * @param string $directory
     * @param bool $recursive Con sus subcarpetas o solo los archivos de la carpeta
     * @return string[]
     */
    public static function listFiles(string $directory, bool $recursive = true): array
    {
        if (!is_dir($directory)) {
            return [];
        }
        $iterator = $recursive
            ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS))
            : new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);
        $files = [];
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && !str_starts_with($file->getFilename(), '.')) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);
        return $files;
    }

    /**
     * Mueve un archivo subido DIRECTAMENTE a su nombre privado: el nombre público no existe en disco ni un instante.
     * El nombre sigue la regla de moveFileTo() de FileUpload: «nombre.extensión», con la extensión cortada a 8 caracteres.
     *
     * @param string $temporaryFile El tmp_name de la subida
     * @param string $directory
     * @param string|null $basename Sin extensión; null, uno aleatorio
     * @param string|null $extension Null, la del temporal
     * @param bool $overwrite Si el nombre ya existe, público o privado, se retiran los dos; si no, no se mueve
     * @param (callable(string, string): bool)|null $mover Por defecto move_uploaded_file(); las pruebas pasan rename()
     * @param string|null $suffix
     * @return string La ruta del nombre PÚBLICO, que es la que se guarda; vacía si no se movió
     */
    public static function moveUploadedToPrivate(string $temporaryFile, string $directory, ?string $basename = null, ?string $extension = null, bool $overwrite = true, ?callable $mover = null, ?string $suffix = null): string
    {
        $suffix ??= self::suffix();
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            return '';
        }
        $extension ??= pathinfo($temporaryFile, \PATHINFO_EXTENSION);
        $basename ??= bin2hex(random_bytes(8));
        $publicPath = str_replace(['//', '\\\\'], ['/', '\\'], $directory . \DIRECTORY_SEPARATOR . sprintf('%s.%0.8s', $basename, $extension));
        $privatePath = self::privatePath($publicPath, $suffix);
        foreach ([$publicPath, $privatePath] as $existing) {
            if (is_file($existing) && (!$overwrite || !@unlink($existing))) {
                return '';
            }
        }
        $mover ??= static fn (string $from, string $to): bool => move_uploaded_file($from, $to);
        return $mover($temporaryFile, $privatePath) ? $publicPath : '';
    }

    /**
     * Pone archivos sueltos, dados por su nombre público, en la visibilidad pedida, pero solo los que están dentro de
     * $rootDirectory. Lo de fuera (una imagen por defecto, una ruta con «..») no se toca y se cuenta en `outside`.
     *
     * @param string[] $publicPaths
     * @param string $rootDirectory
     * @param bool $public
     * @param string|null $suffix
     * @return array{renamed: int, unchanged: int, missing: int, outside: int, conflicts: string[], failed: string[]}
     */
    public static function setFilesVisibility(array $publicPaths, string $rootDirectory, bool $public, ?string $suffix = null): array
    {
        $suffix ??= self::suffix();
        $report = ['renamed' => 0, 'unchanged' => 0, 'missing' => 0, 'outside' => 0, 'conflicts' => [], 'failed' => []];
        $root = realpath($rootDirectory);
        foreach (array_unique($publicPaths) as $publicPath) {
            $directory = realpath(dirname($publicPath));
            //SOLO DENTRO DE LA RAÍZ: realpath() resuelve «..» y los enlaces antes de comparar.
            if ($root === false || $directory === false || str_ends_with($publicPath, $suffix)
                || !str_starts_with($directory . \DIRECTORY_SEPARATOR, $root . \DIRECTORY_SEPARATOR)) {
                $report['outside']++;
                continue;
            }
            $path = $directory . \DIRECTORY_SEPARATOR . basename($publicPath);
            $result = self::setFileVisibility($path, $public, $suffix);
            if ($result === self::VISIBILITY_RENAMED) {
                $report['renamed']++;
            } elseif ($result === self::VISIBILITY_UNCHANGED) {
                $report['unchanged']++;
            } elseif ($result === self::VISIBILITY_MISSING) {
                $report['missing']++;
            } elseif ($result === self::VISIBILITY_CONFLICT) {
                $report['conflicts'][] = $path;
            } else {
                $report['failed'][] = $path;
            }
        }
        return $report;
    }

    /**
     * El .htaccess de uploads que niega pedir un archivo por su nombre de disco. Idempotente y atómico; un
     * .htaccess que no lleve la marca del subsistema no se pisa.
     *
     * @param string $uploadsDirectory
     * @param string|null $suffix
     * @return bool
     */
    public static function writeDenyHtaccess(string $uploadsDirectory, ?string $suffix = null): bool
    {
        $suffix ??= self::suffix();
        $content = self::DENY_HTACCESS_MARK . "\n<FilesMatch \"" . preg_quote($suffix, '"') . "$\">\n    Require all denied\n</FilesMatch>\n";
        $htaccess = rtrim($uploadsDirectory, '/\\') . \DIRECTORY_SEPARATOR . '.htaccess';
        if (is_file($htaccess)) {
            $current = file_get_contents($htaccess);
            if ($current === $content) {
                return true;
            }
            if (!is_string($current) || !str_starts_with($current, self::DENY_HTACCESS_MARK)) {
                return false;
            }
        }
        $temporary = $htaccess . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (file_put_contents($temporary, $content) === false) {
            return false;
        }
        if (!@rename($temporary, $htaccess)) {
            //RETORNO-IGNORADO: el temporal que no se pudo renombrar se retira si se puede; el fallo ya lo dice el rename.
            @unlink($temporary);
            return false;
        }
        return true;
    }
}
