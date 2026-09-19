<?php

/**
 * UploadedFilesStructureMapper.php
 */
namespace PiecesPHP\Core\Forms;

/**
 * UploadedFilesStructureMapper
 *
 * Clase para normalizar, mapear y reconstruir la estructura entrelazada de $_FILES en PHP.
 * Permite tratar cada archivo de forma independiente sin importar la complejidad del formulario.
 *
 * @package     PiecesPHP\Core\Forms
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class UploadedFilesStructureMapper
{
    /**
     * Mapea cada archivo de un array $_FILES mediante un callback y devuelve el array reconstruido
     *
     * @param array $files El array $_FILES original (o similar)
     * @param callable $callback Función que recibe ($fileData, $associativePath) y devuelve el $fileData modificado
     * @param bool $rebuild
     * @return array Estructura entrelazada reconstruida
     */
    public static function map(array $files, callable $callback, bool $rebuild = true): array
    {
        $normalized = self::normalize($files);

        foreach ($normalized as &$item) {
            $item['data'] = $callback($item['data'], $item['path']);
        }
        unset($item);

        return $rebuild ? self::rebuild($normalized) : $normalized;
    }

    /**
     * Normaliza $_FILES a una lista plana de archivos con sus rutas asociativas
     *
     * @param array $files
     * @param array $currentPath
     * @return array [ ['data' => [...], 'path' => [...]], ... ]
     */
    public static function normalize(array $files, array $currentPath = []): array
    {
        $normalized = [];
        foreach ($files as $key => $value) {
            $newPath = array_merge($currentPath, [$key]);

            if (isset($value['tmp_name'])) {
                if (is_array($value['tmp_name'])) {
                    $keys = array_keys($value);
                    $indices = array_keys($value['tmp_name']);

                    foreach ($indices as $idx) {
                        $item = [];
                        foreach ($keys as $k) {
                            $item[$k] = $value[$k][$idx];
                        }
                        $normalized = array_merge($normalized, self::normalize([$idx => $item], $newPath));
                    }
                } else {
                    $normalized[] = [
                        'data' => $value,
                        'path' => $newPath,
                    ];
                }
            } elseif (is_array($value)) {
                $normalized = array_merge($normalized, self::normalize($value, $newPath));
            }
        }
        return $normalized;
    }

    /**
     * Reconstruye la estructura entrelazada de PHP a partir de la lista normalizada por ::normalize
     *
     * @param array $normalizedList
     * @return array
     */
    public static function rebuild(array $normalizedList): array
    {
        $rebuilt = [];
        foreach ($normalizedList as $item) {
            $path = $item['path'];
            $data = $item['data'];

            foreach ($data as $property => $value) {
                $current = &$rebuilt;
                $root = $path[0];
                $subPath = array_slice($path, 1);

                if (!isset($current[$root])) {
                    $current[$root] = [];
                }
                $current = &$current[$root];

                if (!isset($current[$property])) {
                    $current[$property] = [];
                }
                $current = &$current[$property];

                foreach ($subPath as $step) {
                    if (!isset($current[$step])) {
                        $current[$step] = [];
                    }
                    $current = &$current[$step];
                }

                $current = $value;
            }
        }
        return $rebuilt;
    }
}