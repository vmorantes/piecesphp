<?php

/**
 * ServerDelegatedLinks.php
 */

namespace PiecesPHP\SystemStatus;

/**
 * ServerDelegatedLinks - Los enlaces simbólicos de src/statics/server-delegated: cuántos hay, cuáles están rotos y su borrado.
 *
 * Nunca sigue un enlace: recorre el árbol sin entrar en los enlaces a carpetas, y borrar un enlace roto borra el
 * enlace, jamás su destino. Solo borra dentro de la raíz, y la raíz no se borra nunca.
 *
 * @package     PiecesPHP\SystemStatus
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ServerDelegatedLinks
{
    const RELATIVE_ROOT = 'statics/server-delegated';

    /**
     * @var string|null Raíz fijada por una prueba
     */
    private static $testRoot = null;

    /**
     * @return string
     */
    public static function root(): string
    {
        return self::$testRoot ?? basepath(self::RELATIVE_ROOT);
    }

    /**
     * SOLO PARA PRUEBAS: recorre otra raíz; null vuelve a la real.
     *
     * @internal
     * @param string|null $root
     * @return void
     */
    public static function useRootForTesting(?string $root): void
    {
        self::$testRoot = $root;
    }

    /**
     * Todos los enlaces bajo la raíz, con si están rotos. Rutas relativas a la raíz.
     *
     * @return array{total:int,broken:string[]}
     */
    public static function scan(): array
    {
        $total = 0;
        $broken = [];
        foreach (self::links() as $path) {
            $total++;
            //file_exists() mira el destino sin tocarlo: false es que apunta a algo que no existe.
            if (!file_exists($path)) {
                $broken[] = self::relative($path);
            }
        }
        sort($broken);
        return ['total' => $total, 'broken' => $broken];
    }

    /**
     * Borra los enlaces rotos y luego las carpetas que quedan vacías dentro de la raíz (nunca la raíz).
     *
     * @return array{deleted:string[],emptiedDirectories:string[],failed:int}
     */
    public static function deleteBroken(): array
    {
        $root = rtrim(self::root(), '/');
        $deleted = [];
        $failed = 0;
        foreach (self::links() as $path) {
            if (!file_exists($path) && is_link($path) && self::isInside($path, $root)) {
                if (self::removeLink($path)) {
                    $deleted[] = self::relative($path);
                } else {
                    $failed++;
                }
            }
        }

        //De dentro hacia fuera, para que una carpeta cuyo único contenido era otra vacía también se vaya.
        $emptied = [];
        if (is_dir($root) && !is_link($root)) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($it as $item) {
                $path = (string) $item->getPathname();
                if ($item->isDir() && !is_link($path) && $path !== $root && self::isInside($path, $root) && count(scandir($path) ?: []) === 2 && is_writable(dirname($path)) && rmdir($path)) {
                    $emptied[] = self::relative($path);
                }
            }
        }
        sort($deleted);
        sort($emptied);
        return ['deleted' => $deleted, 'emptiedDirectories' => $emptied, 'failed' => $failed];
    }

    /**
     * Borra TODOS los enlaces bajo la raíz, rotos o no, y luego las carpetas que quedan vacías
     * (nunca la raíz). Se regeneran al pedirse el estático.
     *
     * Cuerpo duplicado de deleteBroken() a propósito: unificarlos toca el camino de borrado que ya
     * está probado, y eso pide su propia ronda con su prueba.
     *
     * @return array{deleted:string[],emptiedDirectories:string[],failed:int}
     */
    public static function deleteAll(): array
    {
        $root = rtrim(self::root(), '/');
        $deleted = [];
        $failed = 0;
        foreach (self::links() as $path) {
            //Se borra el ENLACE, jamás su destino, y solo dentro de la raíz.
            if (is_link($path) && self::isInside($path, $root)) {
                if (self::removeLink($path)) {
                    $deleted[] = self::relative($path);
                } else {
                    $failed++;
                }
            }
        }

        //De dentro hacia fuera, para que una carpeta cuyo único contenido era otra vacía también se vaya.
        $emptied = [];
        if (is_dir($root) && !is_link($root)) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($it as $item) {
                $path = (string) $item->getPathname();
                if ($item->isDir() && !is_link($path) && $path !== $root && self::isInside($path, $root) && count(scandir($path) ?: []) === 2 && is_writable(dirname($path)) && rmdir($path)) {
                    $emptied[] = self::relative($path);
                }
            }
        }
        sort($deleted);
        sort($emptied);
        return ['deleted' => $deleted, 'emptiedDirectories' => $emptied, 'failed' => $failed];
    }

    /**
     * Borra un enlace si el proceso puede: sin permiso sobre su carpeta, un unlink da un aviso, y aquí un aviso aborta la
     * petición entera. Lo que no puede, no lo intenta y lo dice.
     *
     * @param string $path
     * @return bool
     */
    private static function removeLink(string $path): bool
    {
        return is_writable(dirname($path)) && unlink($path);
    }

    /**
     * Bytes de una carpeta, sin seguir enlaces; 0 si no existe.
     *
     * @param string $directory
     * @return int
     */
    public static function directorySize(string $directory): int
    {
        if (!is_dir($directory)) {
            return 0;
        }
        $size = 0;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $item) {
            if ($item->isFile() && !$item->isLink()) {
                $size += (int) $item->getSize();
            }
        }
        return $size;
    }

    /**
     * Las rutas de los enlaces bajo la raíz. RecursiveDirectoryIterator no entra en los enlaces a carpetas.
     *
     * @return string[]
     */
    private static function links(): array
    {
        $root = self::root();
        if (!is_dir($root) || is_link($root)) {
            return [];
        }
        $links = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($it as $item) {
            $path = (string) $item->getPathname();
            if (is_link($path)) {
                $links[] = $path;
            }
        }
        return $links;
    }

    /**
     * Por la ruta escrita, no por realpath: un enlace se juzga por dónde está, no por adónde apunta.
     *
     * @param string $path
     * @param string $root
     * @return bool
     */
    private static function isInside(string $path, string $root): bool
    {
        return str_starts_with($path, $root . '/') && !str_contains($path, '/../');
    }

    /**
     * @param string $path
     * @return string
     */
    private static function relative(string $path): string
    {
        return ltrim(mb_substr($path, mb_strlen(rtrim(self::root(), '/'))), '/');
    }
}
