<?php

/**
 * ProtectFileMiddleware.php
 */

namespace PiecesPHP\Core\Helpers\Directories;

use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;

/**
 * ProtectFileMiddleware - Middleware/Helper para proteger directorios de archivos estáticos.
 *
 * Permite crear archivos .htaccess para redirigir peticiones a index.php y
 * gestionar la entrega de archivos protegidos mediante PHP.
 *
 * @package     PiecesPHP\Core\Helpers\Directories
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class ProtectFileMiddleware
{
    /**
     * @var array<string, callable> Directorios protegidos y sus validadores
     */
    private static array $protectedDirectories = [];

    /**
     * Protege un directorio creando un .htaccess y registrándolo en el middleware.
     *
     * @param string $directory Ruta absoluta del directorio a proteger
     * @param callable|null $validator Función de validación (opcional). Recibe (Request $request, string $filePath) y debe devolver bool.
     * @param string|null $indexFile Ruta absoluta de index.php (opcional)
     * @return void
     */
    public static function protect(string $directory, ?callable $validator = null, ?string $indexFile = null): void
    {
        $realDirectory = realpath($directory);
        if ($realDirectory === false || !is_dir($realDirectory)) {
            return;
        }

        $indexFile = $indexFile ?: basepath('index.php');
        $htaccess = rtrim($realDirectory, DIRECTORY_SEPARATOR) . '/.htaccess';

        if (!file_exists($htaccess)) {
            $indexFileRelative = getRelativePath($realDirectory, $indexFile);
            $htaccessContent = "RewriteEngine On\n";
            $htaccessContent .= "RewriteRule ^(.*)$ {$indexFileRelative} [L]\n";
            file_put_contents($htaccess, $htaccessContent);
        }

        self::$protectedDirectories[$realDirectory] = $validator ?: function (Request $request, string $filePath) {
            return true;
        };
    }

    /**
     * Verifica si un archivo está en un directorio protegido y ejecuta el validador.
     *
     * @param string $filePath Ruta absoluta del archivo
     * @param Request $request Objeto de solicitud
     * @return bool|null True si es válido, False si es protegido pero inválido, Null si no es protegido.
     */
    public static function validateAccess(string $filePath, Request $request): ?bool
    {
        $realFilePath = realpath($filePath);
        if ($realFilePath === false) {
            return null;
        }

        foreach (self::$protectedDirectories as $dir => $validator) {
            if (mb_strpos($realFilePath, $dir) === 0) {
                return (bool) call_user_func($validator, $request, $realFilePath);
            }
        }

        return null;
    }

    /**
     * Verifica si una ruta absoluta está bajo algún directorio protegido.
     *
     * @param string $filePath Ruta del archivo
     * @return bool
     */
    public static function isProtected(string $filePath): bool
    {
        $realFilePath = realpath($filePath);
        if ($realFilePath === false) {
            //Si el archivo no existe, pero queremos saber si su RUTA teórica sería protegida
            //Usamos la ruta tal cual, normalizando separadores
            $path = str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, $filePath);
            foreach (self::$protectedDirectories as $dir => $validator) {
                if (mb_strpos($path, $dir) === 0) {
                    return true;
                }
            }
            return false;
        }

        foreach (self::$protectedDirectories as $dir => $validator) {
            if (mb_strpos($realFilePath, $dir) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtiene los directorios protegidos.
     *
     * @return array<string, callable>
     */
    public static function getProtectedDirectories(): array
    {
        return self::$protectedDirectories;
    }
}
