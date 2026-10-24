<?php

/**
 * GeoThumbnail.php
 */

namespace PiecesPHP\Geo;

use PiecesPHP\Geo\Controllers\GeoController;

/**
 * GeoThumbnail - Las miniaturas de los puntos: se generan una vez desde el original que dice la fuente
 * (GeoSource::thumbnailSource()) y se guardan en app/cache/, que no se sirve: solo sale por la ruta del geovisor, con
 * el permiso de la fuente. La caché va por contenido: su nombre lleva la huella del original, así que otro original
 * (aunque su fecha sea más vieja) da otra miniatura.
 *
 * @package     PiecesPHP\Geo
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class GeoThumbnail
{
    /**
     * Relativa a basepath(); app/cache/ lleva su .htaccess que la deniega y git la ignora.
     */
    const DIRECTORY = 'app/cache/geo-thumbnails';

    /**
     * Los únicos anchos que se generan: cualquier otro es un 404, para que nadie llene el disco pidiendo tamaños.
     */
    const SIZES = [64, 128];

    /**
     * El que lleva cada punto.
     */
    const DEFAULT_SIZE = 64;

    /**
     * La URL de la miniatura de un punto, con la fecha del original como sello de versión; o null si no hay original
     * o el usuario no puede pedir la ruta.
     *
     * @param string $sourceKey
     * @param int $id
     * @param string|null $original El archivo en disco
     * @return string|null
     */
    public static function url(string $sourceKey, int $id, ?string $original): ?string
    {
        $modified = $original !== null && is_file($original) ? filemtime($original) : false;
        if ($modified === false) {
            return null;
        }
        $route = GeoController::routeName('thumbnail', ['source' => $sourceKey, 'id' => $id, 'size' => self::DEFAULT_SIZE]);
        return $route !== '' ? $route . '?' . http_build_query(['v' => $modified]) : null;
    }

    /**
     * El archivo de la miniatura, generado si no está la de la huella del original de hoy; null si la fuente no da
     * original o el original no es una imagen.
     *
     * @param GeoSource $source
     * @param int $id
     * @param int $size Uno de SIZES
     * @return string|null
     */
    public static function file(GeoSource $source, int $id, int $size): ?string
    {
        if (!in_array($size, self::SIZES, true) || preg_match('/^[A-Za-z0-9_-]+$/', $source->key()) !== 1) {
            return null;
        }
        $original = $source->thumbnailSource($id);
        $originalModified = $original !== null && is_file($original) ? filemtime($original) : false;
        $originalSize = $original !== null && is_file($original) ? filesize($original) : false;
        $originalPath = $original !== null ? realpath($original) : false;
        if ($original === null || $originalModified === false || $originalSize === false || $originalPath === false
            || @getimagesize($original) === false) {
            return null;
        }

        //Por contenido, no por fecha: un original sustituido por otro con una fecha más vieja seguía sirviendo la vieja.
        $fingerprint = substr(sha1($originalPath . '|' . $originalModified . '|' . $originalSize), 0, 12);
        $directory = basepath(self::DIRECTORY . '/' . $source->key());
        $cached = "{$directory}/{$id}-{$size}-{$fingerprint}.png";
        if (is_file($cached)) {
            return $cached;
        }
        if (!self::generate($original, $directory, $cached, $size)) {
            return null;
        }
        $stale = glob("{$directory}/{$id}-{$size}-*.png");
        foreach (array_merge($stale !== false ? $stale : [], ["{$directory}/{$id}-{$size}.png"]) as $old) {
            if ($old !== $cached && is_file($old)) {
                //RETORNO-IGNORADO: una miniatura vieja que no se pudo borrar no se sirve (su huella ya no es la del original).
                @unlink($old);
            }
        }
        return $cached;
    }

    /**
     * Con un temporal y rename(): quien lee ve la miniatura entera o ninguna. PNG, para no perder la transparencia de
     * un logo.
     *
     * @param string $original
     * @param string $directory
     * @param string $cached
     * @param int $size
     * @return bool
     */
    private static function generate(string $original, string $directory, string $cached, int $size): bool
    {
        //umask 0002, como la caché de WebP de ServerStatics: la carpeta la puede crear Apache y vaciarla la terminal.
        $oldUmask = umask(0002);
        try {
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                return false;
            }
            $temporary = $cached . '.' . bin2hex(random_bytes(6)) . '.tmp';
            imageToThumbnail($original, $size, $size, 80, $temporary, false, true);
            if (!is_file($temporary)) {
                return false;
            }
            if (!rename($temporary, $cached)) {
                //RETORNO-IGNORADO: el temporal que no se pudo renombrar se retira si se puede; el fallo ya lo dice el rename.
                @unlink($temporary);
                return false;
            }
            return true;
        } finally {
            umask($oldUmask);
        }
    }
}
