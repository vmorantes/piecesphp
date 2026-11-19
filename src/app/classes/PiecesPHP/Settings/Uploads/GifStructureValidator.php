<?php

/**
 * GifStructureValidator.php
 */

namespace PiecesPHP\Settings\Uploads;

/**
 * Un GIF subido (la animación de carga, H345) se recorre bloque a bloque antes de aceptarlo: un archivo pequeño puede
 * declarar un lienzo o un fotograma enormes, y el navegador reserva su memoria al pintarlo. Rechaza el lienzo o un
 * fotograma que pase del tope, un fotograma que se salga del lienzo y cualquier estructura truncada o mal formada.
 *
 * @package     PiecesPHP\Settings\Uploads
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class GifStructureValidator
{

    const REASON_SIGNATURE = 'signature';
    const REASON_TRUNCATED = 'truncated';
    const REASON_MALFORMED = 'malformed';
    const REASON_TOO_LARGE = 'too-large';
    const REASON_FRAME_OUTSIDE = 'frame-outside';

    /**
     * @param string $content El archivo entero
     * @param int $maxSide El lado máximo, en píxeles, del lienzo y de cada fotograma
     * @return string|null null si se acepta; si no, el motivo (una de las constantes REASON_*)
     */
    public static function rejectionReason(string $content, int $maxSide): ?string
    {
        if (!str_starts_with($content, 'GIF87a') && !str_starts_with($content, 'GIF89a')) {
            return self::REASON_SIGNATURE;
        }
        $length = strlen($content);
        if ($length < 13) {
            return self::REASON_TRUNCATED;
        }
        //Logical Screen Descriptor: ancho, alto (little-endian), campo empaquetado, fondo y proporción.
        $width = self::word($content, 6);
        $height = self::word($content, 8);
        if ($width > $maxSide || $height > $maxSide) {
            return self::REASON_TOO_LARGE;
        }
        $offset = 13 + self::colorTableSize(ord($content[10]));
        $frames = 0;

        while ($offset < $length) {
            $block = ord($content[$offset]);
            if ($block === 0x3B) {
                //El trailer: sin al menos un fotograma no es una imagen.
                return $frames > 0 ? null : self::REASON_MALFORMED;
            }
            if ($block === 0x21) {
                //Extensión: etiqueta y sub-bloques hasta el de tamaño 0.
                $offset = self::skipSubBlocks($content, $offset + 2);
            } elseif ($block === 0x2C) {
                //Image Descriptor: izquierda, arriba, ancho, alto y campo empaquetado (tabla local de color).
                if ($offset + 10 > $length) {
                    return self::REASON_TRUNCATED;
                }
                $left = self::word($content, $offset + 1);
                $top = self::word($content, $offset + 3);
                $frameWidth = self::word($content, $offset + 5);
                $frameHeight = self::word($content, $offset + 7);
                if ($frameWidth > $maxSide || $frameHeight > $maxSide) {
                    return self::REASON_TOO_LARGE;
                }
                if ($left + $frameWidth > $width || $top + $frameHeight > $height) {
                    return self::REASON_FRAME_OUTSIDE;
                }
                //Tras la tabla local, el tamaño mínimo de código LZW (1 byte) y los sub-bloques de datos.
                $offset = self::skipSubBlocks($content, $offset + 10 + self::colorTableSize(ord($content[$offset + 9])) + 1);
                $frames++;
            } else {
                return self::REASON_MALFORMED;
            }
            if ($offset < 0) {
                return self::REASON_TRUNCATED;
            }
        }
        return self::REASON_TRUNCATED;
    }

    /**
     * El tamaño, en bytes, de la tabla de color que anuncia un campo empaquetado (bit alto y tres bits bajos).
     *
     * @param int $packed
     * @return int
     */
    private static function colorTableSize(int $packed): int
    {
        return ($packed & 0x80) !== 0 ? 3 * (2 ** (($packed & 0x07) + 1)) : 0;
    }

    /**
     * Salta los sub-bloques que empiezan en $offset (cada uno, su tamaño y sus datos) hasta el terminador de tamaño 0.
     *
     * @param string $content
     * @param int $offset
     * @return int La posición tras el terminador, o -1 si el archivo se acaba antes
     */
    private static function skipSubBlocks(string $content, int $offset): int
    {
        $length = strlen($content);
        while ($offset < $length) {
            $size = ord($content[$offset]);
            $offset++;
            if ($size === 0) {
                return $offset;
            }
            $offset += $size;
        }
        return -1;
    }

    /**
     * @param string $content
     * @param int $offset
     * @return int Dos bytes little-endian
     */
    private static function word(string $content, int $offset): int
    {
        return ord($content[$offset]) | (ord($content[$offset + 1]) << 8);
    }

}
