<?php

/**
 * ProtectedUploadsMigration.php
 */

namespace PiecesPHP\Core\Statics;

/**
 * ProtectedUploadsMigration - Pasa uploads a la protección por sufijo, y de vuelta.
 *
 * El ORDEN es la garantía: primero se renombra a privado todo lo que debe serlo, y solo al final se retira el .htaccess
 * de «reescribir todo» de cada carpeta protegida. Al revés, lo privado quedaría servido directamente entre los dos pasos.
 * La vuelta atrás invierte el orden: primero repone los .htaccess y después quita los sufijos.
 *
 * @package     PiecesPHP\Core\Statics
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class ProtectedUploadsMigration
{
    const STEP_PRIVATE = 'private';
    const STEP_PUBLIC = 'public';
    const STEP_REMOVE_HTACCESS = 'remove-htaccess';
    const STEP_RESTORE_HTACCESS = 'restore-htaccess';

    /**
     * Los pasos, en orden.
     *
     * @param array<int, array{module: string, directory: string, public: bool, recursive?: bool}> $folders Carpetas y la visibilidad que les toca
     * @param array<int, array{module: string, directory: string}> $protectedRoots Carpetas protegidas con su .htaccess de «reescribir todo»
     * @param bool $revert
     * @return array<int, array{step: string, module: string, directory: string, recursive: bool}>
     */
    public static function plan(array $folders, array $protectedRoots, bool $revert): array
    {
        $renames = [];
        foreach ($folders as $folder) {
            $renames[] = [
                'step' => $revert || $folder['public'] ? self::STEP_PUBLIC : self::STEP_PRIVATE,
                'module' => $folder['module'],
                'directory' => $folder['directory'],
                'recursive' => $folder['recursive'] ?? true,
            ];
        }
        $htaccess = [];
        foreach ($protectedRoots as $root) {
            $htaccess[] = [
                'step' => $revert ? self::STEP_RESTORE_HTACCESS : self::STEP_REMOVE_HTACCESS,
                'module' => $root['module'],
                'directory' => $root['directory'],
                'recursive' => false,
            ];
        }
        //PRIMERO LO PRIVADO; el .htaccess, solo al final. La vuelta atrás, al revés.
        return $revert ? array_merge($htaccess, $renames) : array_merge($renames, $htaccess);
    }

    /**
     * Ejecuta los pasos, o los simula sin tocar nada.
     *
     * @param array<int, array{step: string, module: string, directory: string, recursive: bool}> $steps
     * @param bool $dryRun Solo cuenta lo que haría
     * @param callable|null $afterStep Recibe cada paso ya hecho: sirve para comprobar entre pasos qué queda servible
     * @param string|null $suffix
     * @return array<string, array{toPrivate: int, toPublic: int, unchanged: int, htaccess: int, conflicts: string[], failed: string[]}> Informe por módulo
     */
    public static function execute(array $steps, bool $dryRun, ?callable $afterStep = null, ?string $suffix = null): array
    {
        $suffix ??= ProtectedUploads::suffix();
        $report = [];
        foreach ($steps as $step) {
            $module = $step['module'];
            $report[$module] ??= ['toPrivate' => 0, 'toPublic' => 0, 'unchanged' => 0, 'htaccess' => 0, 'conflicts' => [], 'failed' => []];
            if ($step['step'] === self::STEP_PRIVATE || $step['step'] === self::STEP_PUBLIC) {
                $public = $step['step'] === self::STEP_PUBLIC;
                $key = $public ? 'toPublic' : 'toPrivate';
                if ($dryRun) {
                    foreach (ProtectedUploads::listFiles($step['directory'], $step['recursive']) as $path) {
                        if (str_ends_with($path, $suffix) === $public) {
                            $report[$module][$key]++;
                        } else {
                            $report[$module]['unchanged']++;
                        }
                    }
                } else {
                    $result = ProtectedUploads::setFolderVisibility($step['directory'], $public, $suffix, $step['recursive']);
                    $report[$module][$key] += $result['renamed'];
                    $report[$module]['unchanged'] += $result['unchanged'];
                    $report[$module]['conflicts'] = array_merge($report[$module]['conflicts'], $result['conflicts']);
                    $report[$module]['failed'] = array_merge($report[$module]['failed'], $result['failed']);
                }
            } else {
                $htaccess = rtrim($step['directory'], '/\\') . \DIRECTORY_SEPARATOR . '.htaccess';
                $expected = ProtectFileMiddleware::rewriteHtaccessContent($step['directory']);
                if ($step['step'] === self::STEP_REMOVE_HTACCESS) {
                    if (is_file($htaccess)) {
                        //Solo se retira el de protect(): uno distinto lo puso alguien a propósito y se informa.
                        if (file_get_contents($htaccess) !== $expected) {
                            $report[$module]['failed'][] = $htaccess;
                        } elseif ($dryRun || @unlink($htaccess)) {
                            $report[$module]['htaccess']++;
                        } else {
                            $report[$module]['failed'][] = $htaccess;
                        }
                    }
                } elseif (!is_file($htaccess)) {
                    if ($dryRun || self::writeFile($htaccess, $expected)) {
                        $report[$module]['htaccess']++;
                    } else {
                        $report[$module]['failed'][] = $htaccess;
                    }
                }
            }
            if ($afterStep !== null && !$dryRun) {
                $afterStep($step);
            }
        }
        return $report;
    }

    /**
     * Lo que ahora mismo se serviría directamente sin deberlo: un archivo que debe ser privado, sin el sufijo, en una
     * carpeta protegida que ya no tiene su .htaccess de «reescribir todo».
     *
     * @param array<int, array{module: string, directory: string, public: bool, recursive?: bool}> $folders
     * @param array<int, array{module: string, directory: string}> $protectedRoots
     * @param string|null $suffix
     * @return string[]
     */
    public static function servablePrivates(array $folders, array $protectedRoots, ?string $suffix = null): array
    {
        $suffix ??= ProtectedUploads::suffix();
        $unguarded = [];
        foreach ($protectedRoots as $root) {
            $directory = rtrim($root['directory'], '/\\') . \DIRECTORY_SEPARATOR;
            if (!is_file($directory . '.htaccess')) {
                $unguarded[] = $directory;
            }
        }
        $servable = [];
        foreach ($folders as $folder) {
            if ($folder['public']) {
                continue;
            }
            $directory = rtrim($folder['directory'], '/\\') . \DIRECTORY_SEPARATOR;
            $inside = false;
            foreach ($unguarded as $root) {
                $inside = $inside || str_starts_with($directory, $root);
            }
            if (!$inside) {
                continue;
            }
            foreach (ProtectedUploads::listFiles($folder['directory'], $folder['recursive'] ?? true) as $path) {
                if (!str_ends_with($path, $suffix)) {
                    $servable[] = $path;
                }
            }
        }
        return $servable;
    }

    /**
     * Escribe con un temporal y rename().
     *
     * @param string $path
     * @param string $content
     * @return bool
     */
    private static function writeFile(string $path, string $content): bool
    {
        $temporary = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (file_put_contents($temporary, $content) === false) {
            return false;
        }
        if (!@rename($temporary, $path)) {
            //RETORNO-IGNORADO: el temporal que no se pudo renombrar se retira si se puede; el fallo ya lo dice el rename.
            @unlink($temporary);
            return false;
        }
        return true;
    }
}
