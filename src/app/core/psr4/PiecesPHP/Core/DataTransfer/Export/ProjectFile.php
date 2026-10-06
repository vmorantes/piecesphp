<?php

/**
 * ProjectFile.php
 */

namespace PiecesPHP\Core\DataTransfer\Export;

/**
 * ProjectFile - Resuelve un archivo que la exportación incluye o incrusta, solo si está dentro del proyecto.
 *
 * @package     PiecesPHP\Core\DataTransfer\Export
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ProjectFile
{
    /**
     * @param string $path
     * @param string[] $extensions en minúsculas, sin punto
     * @return string La ruta real
     * @throws \InvalidArgumentException si no existe, está fuera del proyecto o tiene otra extensión
     */
    public static function resolve(string $path, array $extensions): string
    {
        $real = realpath($path);
        $root = realpath(basepath());
        if ($real === false || !is_file($real)) {
            throw new \InvalidArgumentException("El archivo «{$path}» no existe.");
        }
        //Solo archivos del proyecto: una definición no puede incluir ni incrustar lo que haya en el servidor.
        if ($root === false || !str_starts_with($real, rtrim($root, '/') . '/')) {
            throw new \InvalidArgumentException("El archivo «{$path}» está fuera del proyecto.");
        }
        if (!in_array(strtolower(pathinfo($real, PATHINFO_EXTENSION)), $extensions, true)) {
            throw new \InvalidArgumentException("El archivo «{$path}» debe ser " . implode(', ', array_map(fn($e) => ".{$e}", $extensions)) . '.');
        }
        return $real;
    }
}
