<?php

/**
 * ProtectFileMiddleware.php
 */

namespace PiecesPHP\Core\Statics;

use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;

/**
 * ProtectFileMiddleware - Middleware/Helper para proteger directorios de archivos estáticos.
 *
 * Permite crear archivos .htaccess para redirigir peticiones a index.php y
 * gestionar la entrega de archivos protegidos mediante PHP.
 *
 * @package     PiecesPHP\Core\Statics
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
        //UNA CARPETA QUE NO SE PUEDE PROTEGER NO SE DEJA SERVIBLE: si falta se crea, y si no se puede
        //crear o resolver, se lanza en vez de salir callado.
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException("No se pudo crear la carpeta protegida {$directory}.");
        }
        $realDirectory = realpath($directory);
        if ($realDirectory === false || !is_dir($realDirectory)) {
            throw new \RuntimeException("No se pudo resolver la carpeta protegida {$directory}.");
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
            if (self::isInside($realFilePath, $dir)) {
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
                if (self::isInside($path, $dir)) {
                    return true;
                }
            }
            return false;
        }

        foreach (self::$protectedDirectories as $dir => $validator) {
            if (self::isInside($realFilePath, $dir)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Si la ruta es la carpeta o está dentro de ella.
     *
     * @param string $path
     * @param string $directory
     * @return bool
     */
    private static function isInside(string $path, string $directory): bool
    {
        //EL SEPARADOR ES OBLIGATORIO: sin él, `…/uno` cubriría también `…/uno-dos`.
        $directory = rtrim($directory, \DIRECTORY_SEPARATOR);
        return $path === $directory || mb_strpos($path, $directory . \DIRECTORY_SEPARATOR) === 0;
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
