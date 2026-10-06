<?php

/**
 * ProtectFileMiddleware.php
 */

namespace PiecesPHP\Core\Statics;

use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\SessionToken;

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
    const POLICY_SESSION = 'session';
    const POLICY_VALIDATOR = 'validator';
    const POLICY_PUBLIC = 'public';

    /**
     * @var array<string, callable> Directorios protegidos y sus validadores
     */
    private static array $protectedDirectories = [];

    /**
     * @var array<string, string> Directorios protegidos y su política (POLICY_*)
     */
    private static array $policies = [];

    /**
     * Registra un directorio protegido y su validador. Lo privado de la carpeta lleva el sufijo en disco (ProtectedUploads).
     *
     * @param string $directory Ruta absoluta del directorio a proteger
     * @param callable|null $validator Función de validación. Recibe (Request $request, string $filePath) y debe devolver bool. Sin ella, nadie entra.
     * @param string|null $indexFile Sin uso: se conserva la firma. El .htaccess de la vuelta atrás lo da rewriteHtaccessContent()
     * @param string $policy POLICY_SESSION o POLICY_VALIDATOR: la política que ve quien lee la configuración
     * @return void
     */
    public static function protect(string $directory, ?callable $validator = null, ?string $indexFile = null, string $policy = self::POLICY_VALIDATOR): void
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

        //Aquí NO se escribe .htaccess: protege el sufijo, y el siguiente arranque repondría los que retira statics-protect-migrate.
        //SIN VALIDADOR, FALLA CERRADO: una carpeta registrada sin decir quién entra no se sirve a nadie.
        self::$protectedDirectories[$realDirectory] = $validator ?: static function (Request $request, string $filePath): bool {
            return false;
        };
        self::$policies[$realDirectory] = $policy;
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

    /**
     * El .htaccess de «reescribir todo» que protect() escribía en cada carpeta. Solo lo usa la vuelta atrás de
     * statics-protect-migrate, para reponerlo tal cual era.
     *
     * @param string $realDirectory
     * @param string|null $indexFile Ruta absoluta de index.php; por defecto, la del framework
     * @return string
     */
    public static function rewriteHtaccessContent(string $realDirectory, ?string $indexFile = null): string
    {
        $indexFileRelative = getRelativePath($realDirectory, $indexFile ?: basepath('index.php'));
        return "RewriteEngine On\nRewriteRule ^(.*)$ {$indexFileRelative} [L]\n";
    }

    /**
     * Protege un directorio con la política de sesión: se sirve a quien tenga una sesión activa.
     *
     * @param string $directory Ruta absoluta del directorio a proteger
     * @return void
     */
    public static function protectWithSession(string $directory): void
    {
        self::protect($directory, static function (Request $request, string $filePath): bool {
            return SessionToken::isActiveSession((string) SessionToken::getJWTReceived());
        }, null, self::POLICY_SESSION);
    }

    /**
     * La política de la carpeta protegida que contiene la ruta, o POLICY_PUBLIC si ninguna la contiene.
     *
     * @param string $filePath
     * @return string
     */
    public static function policyFor(string $filePath): string
    {
        $path = realpath($filePath);
        $path = $path !== false ? $path : str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, $filePath);
        foreach (self::$policies as $directory => $policy) {
            if (self::isInside($path, $directory)) {
                return $policy;
            }
        }
        return self::POLICY_PUBLIC;
    }

    /**
     * @return array<string, string> Directorios protegidos y su política
     */
    public static function getPolicies(): array
    {
        return self::$policies;
    }
}
