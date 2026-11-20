<?php

/**
 * ImageMetadataStripper.php
 */

namespace PiecesPHP\Settings\Uploads;

/**
 * Las imágenes del cargador se guardan tal cual y su URL va en las cabeceras públicas: antes de guardarlas se les quitan
 * los metadatos recorriendo su estructura, sin decodificar ni recomprimir los píxeles. PNG y WebP van por lista blanca:
 * solo pasan los trozos que intervienen en cómo se pinta (imagen, transparencia, color, perfil ICC, animación); todo lo
 * demás, conocido o no, se quita. En el JPG se quitan APP1-APP15 y COM salvo el perfil ICC y el Adobe APP14; en el GIF,
 * los comentarios y las extensiones de aplicación salvo el bucle NETSCAPE2.0.
 * La orientación de una foto (la etiqueta Exif 0x0112) se conserva en el JPG y en el PNG, en un Exif mínimo que solo la
 * lleva a ella: el navegador gira esas imágenes por ella. En el WebP se quita con el resto, porque Chromium no la aplica.
 *
 * @package     PiecesPHP\Settings\Uploads
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class ImageMetadataStripper
{

    const PNG_SIGNATURE = "\x89PNG\r\n\x1A\n";
    const PNG_KEPT_CHUNKS = ['IHDR', 'PLTE', 'IDAT', 'IEND', 'tRNS', 'gAMA', 'cHRM', 'sRGB', 'iCCP', 'pHYs', 'sBIT', 'bKGD', 'acTL', 'fcTL', 'fdAT'];
    const WEBP_KEPT_CHUNKS = ['VP8 ', 'VP8L', 'VP8X', 'ALPH', 'ANIM', 'ANMF', 'ICCP'];
    const EXIF_ORIENTATION_TAG = 0x0112;
    const WEBP_VP8X_EXIF_FLAG = 0x08;
    const WEBP_VP8X_XMP_FLAG = 0x04;

    /**
     * @param string $content El archivo entero, ya validado
     * @param string $extension png, jpg, webp, gif o svg
     * @return string|null El archivo sin metadatos; null si su estructura no se puede recorrer
     */
    public static function strip(string $content, string $extension): ?string
    {
        return match ($extension) {
            'png' => self::stripPng($content),
            'jpg' => self::stripJpeg($content),
            'webp' => self::stripWebp($content),
            'gif' => self::stripGif($content),
            //El SVG ya pasa por SvgWhitelistValidator: no se toca.
            'svg' => $content,
            default => null,
        };
    }

    /**
     * Quita APP1-APP15 (salvo APP2 ICC_PROFILE y APP14 Adobe) y COM; lo que siga al EOI (las imágenes de un MPF) se descarta.
     * El primer APP1 Exif con una orientación distinta de 1 se sustituye por uno mínimo que solo la lleva a ella.
     *
     * @param string $content
     * @return string|null
     */
    public static function stripJpeg(string $content): ?string
    {
        if (!str_starts_with($content, "\xFF\xD8")) {
            return null;
        }
        $length = strlen($content);
        $output = "\xFF\xD8";
        $offset = 2;
        $orientationKept = false;
        while ($offset < $length) {
            if ($content[$offset] !== "\xFF") {
                return null;
            }
            //Bytes de relleno 0xFF antes del marcador.
            while ($offset < $length && $content[$offset] === "\xFF") {
                $offset++;
            }
            if ($offset >= $length) {
                return null;
            }
            $marker = ord($content[$offset]);
            $offset++;
            if ($marker === 0xD9) {
                return $output . "\xFF\xD9";
            }
            if ($marker === 0x01 || ($marker >= 0xD0 && $marker <= 0xD7)) {
                $output .= "\xFF" . chr($marker);
                continue;
            }
            if ($offset + 2 > $length) {
                return null;
            }
            $segmentLength = (ord($content[$offset]) << 8) | ord($content[$offset + 1]);
            if ($segmentLength < 2 || $offset + $segmentLength > $length) {
                return null;
            }
            $segment = substr($content, $offset, $segmentLength);
            $offset += $segmentLength;
            if ($marker === 0xDA) {
                //Tras el SOS, los datos comprimidos llegan hasta el primer marcador que no sea 0xFF00 ni un RSTn.
                $scanStart = $offset;
                while (true) {
                    $position = strpos($content, "\xFF", $offset);
                    if ($position === false || $position + 1 >= $length) {
                        return null;
                    }
                    $next = ord($content[$position + 1]);
                    if ($next === 0x00 || ($next >= 0xD0 && $next <= 0xD7)) {
                        $offset = $position + 2;
                    } elseif ($next === 0xFF) {
                        $offset = $position + 1;
                    } else {
                        break;
                    }
                }
                $output .= "\xFF\xDA" . $segment . substr($content, $scanStart, $position - $scanStart);
                $offset = $position;
                continue;
            }
            //El ICC da los colores, y Adobe APP14 dice si un JPG de 3 o 4 componentes es YCbCr/YCCK o RGB/CMYK: sin ellos se pinta con otros colores.
            $keep = ($marker === 0xE2 && substr($segment, 2, 12) === "ICC_PROFILE\0") || ($marker === 0xEE && substr($segment, 2, 5) === 'Adobe');
            if ($marker === 0xE1 && !$orientationKept && substr($segment, 2, 6) === "Exif\0\0") {
                $orientation = self::tiffOrientation(substr($segment, 8));
                if ($orientation !== 1) {
                    $exif = "Exif\0\0" . self::minimalOrientationTiff($orientation);
                    $output .= "\xFF\xE1" . pack('n', 2 + strlen($exif)) . $exif;
                    $orientationKept = true;
                }
            }
            if (($marker >= 0xE1 && $marker <= 0xEF && !$keep) || $marker === 0xFE) {
                continue;
            }
            $output .= "\xFF" . chr($marker) . $segment;
        }
        return null;
    }

    /**
     * Solo pasan los chunks de PNG_KEPT_CHUNKS, con su CRC tal cual; lo que siga a IEND se descarta. El primer eXIf con una
     * orientación distinta de 1 se sustituye, en su sitio, por uno mínimo que solo la lleva a ella.
     *
     * @param string $content
     * @return string|null
     */
    public static function stripPng(string $content): ?string
    {
        if (!str_starts_with($content, self::PNG_SIGNATURE)) {
            return null;
        }
        $length = strlen($content);
        $output = self::PNG_SIGNATURE;
        $offset = strlen(self::PNG_SIGNATURE);
        $orientationKept = false;
        while ($offset + 12 <= $length) {
            $dataLength = self::uint32($content, $offset, true);
            if ($dataLength === null || $dataLength > 0x7FFFFFFF) {
                return null;
            }
            $type = substr($content, $offset + 4, 4);
            //Longitud, tipo, datos y CRC.
            $chunkLength = 12 + $dataLength;
            if ($offset + $chunkLength > $length) {
                return null;
            }
            if (in_array($type, self::PNG_KEPT_CHUNKS, true)) {
                $output .= substr($content, $offset, $chunkLength);
            } elseif ($type === 'eXIf' && !$orientationKept) {
                $orientation = self::tiffOrientation(substr($content, $offset + 8, $dataLength));
                if ($orientation !== 1) {
                    $tiff = self::minimalOrientationTiff($orientation);
                    $output .= pack('N', strlen($tiff)) . 'eXIf' . $tiff . pack('N', crc32('eXIf' . $tiff));
                    $orientationKept = true;
                }
            }
            $offset += $chunkLength;
            if ($type === 'IEND') {
                return $output;
            }
        }
        return null;
    }

    /**
     * Solo pasan los chunks de WEBP_KEPT_CHUNKS (EXIF entero fuera: Chromium no aplica su orientación en un WebP), las
     * banderas EXIF y XMP de VP8X se apagan y el tamaño del RIFF se recalcula.
     *
     * @param string $content
     * @return string|null
     */
    public static function stripWebp(string $content): ?string
    {
        if (strlen($content) < 12 || !str_starts_with($content, 'RIFF') || substr($content, 8, 4) !== 'WEBP') {
            return null;
        }
        //Solo cuenta lo que declara el RIFF: lo que venga detrás no es de la imagen.
        $riffLength = self::uint32($content, 4, false);
        if ($riffLength === null || 8 + $riffLength > strlen($content)) {
            return null;
        }
        $end = 8 + $riffLength;
        if ($end < 12) {
            return null;
        }
        $chunks = '';
        $offset = 12;
        while ($offset < $end) {
            if ($offset + 8 > $end) {
                return null;
            }
            $fourCC = substr($content, $offset, 4);
            $dataLength = self::uint32($content, $offset + 4, false);
            if ($dataLength === null) {
                return null;
            }
            //Los datos se rellenan hasta un número par de bytes.
            $chunkLength = 8 + $dataLength + ($dataLength & 1);
            if ($offset + $chunkLength > $end) {
                return null;
            }
            $chunk = substr($content, $offset, $chunkLength);
            $offset += $chunkLength;
            if (!in_array($fourCC, self::WEBP_KEPT_CHUNKS, true)) {
                continue;
            }
            if ($fourCC === 'VP8X' && $dataLength >= 1) {
                $chunk[8] = chr(ord($chunk[8]) & ~(self::WEBP_VP8X_EXIF_FLAG | self::WEBP_VP8X_XMP_FLAG) & 0xFF);
            }
            $chunks .= $chunk;
        }
        return 'RIFF' . pack('V', 4 + strlen($chunks)) . 'WEBP' . $chunks;
    }

    /**
     * Quita las extensiones de comentario y las de aplicación salvo NETSCAPE2.0 (el bucle); lo que siga al trailer se descarta.
     *
     * @param string $content
     * @return string|null
     */
    public static function stripGif(string $content): ?string
    {
        $length = strlen($content);
        if ($length < 13 || (!str_starts_with($content, 'GIF87a') && !str_starts_with($content, 'GIF89a'))) {
            return null;
        }
        $offset = 13 + self::gifColorTableSize(ord($content[10]));
        if ($offset > $length) {
            return null;
        }
        $output = substr($content, 0, $offset);
        while ($offset < $length) {
            $block = ord($content[$offset]);
            if ($block === 0x3B) {
                return $output . "\x3B";
            }
            if ($block === 0x21) {
                if ($offset + 2 > $length) {
                    return null;
                }
                $label = ord($content[$offset + 1]);
                $end = self::gifSkipSubBlocks($content, $offset + 2);
                if ($end < 0) {
                    return null;
                }
                //El primer sub-bloque de una extensión de aplicación lleva su identificador (8 bytes) y su código (3).
                $isLoop = $label === 0xFF && substr($content, $offset + 2, 12) === "\x0BNETSCAPE2.0";
                if ($label !== 0xFE && ($label !== 0xFF || $isLoop)) {
                    $output .= substr($content, $offset, $end - $offset);
                }
                $offset = $end;
            } elseif ($block === 0x2C) {
                if ($offset + 10 > $length) {
                    return null;
                }
                //Image Descriptor, tabla local, tamaño mínimo de código LZW y los sub-bloques de datos.
                $end = self::gifSkipSubBlocks($content, $offset + 10 + self::gifColorTableSize(ord($content[$offset + 9])) + 1);
                if ($end < 0) {
                    return null;
                }
                $output .= substr($content, $offset, $end - $offset);
                $offset = $end;
            } else {
                return null;
            }
        }
        return null;
    }

    /**
     * @param string $content
     * @param int $offset Donde empiezan los cuatro bytes (el llamador ya comprobó que caben)
     * @param bool $bigEndian PNG lo escribe big-endian; RIFF, little-endian
     * @return int|null Null si no se puede leer o si no cabe sin signo en el int de esta plataforma
     */
    private static function uint32(string $content, int $offset, bool $bigEndian): ?int
    {
        //unpack() y no desplazamientos: `<< 24` da negativos en un PHP de 32 bits.
        $value = unpack($bigEndian ? 'N' : 'V', $content, $offset);
        return is_array($value) && is_int($value[1] ?? null) && $value[1] >= 0 ? $value[1] : null;
    }

    /**
     * @param string $content
     * @param int $offset
     * @param bool $bigEndian
     * @return int|null Null si los dos bytes no caben
     */
    private static function uint16(string $content, int $offset, bool $bigEndian): ?int
    {
        if ($offset < 0 || $offset + 2 > strlen($content)) {
            return null;
        }
        $value = unpack($bigEndian ? 'n' : 'v', $content, $offset);
        return is_array($value) && is_int($value[1] ?? null) ? $value[1] : null;
    }

    /**
     * La orientación (0x0112) del IFD0 de un bloque TIFF de Exif; 1 si no la lleva o el bloque no se puede recorrer.
     *
     * @param string $tiff Desde la cabecera «II*\0» o «MM\0*»
     * @return int De 1 a 8
     */
    private static function tiffOrientation(string $tiff): int
    {
        $order = substr($tiff, 0, 4);
        if ($order !== "II*\0" && $order !== "MM\0*") {
            return 1;
        }
        $bigEndian = $order === "MM\0*";
        $ifd = strlen($tiff) >= 8 ? self::uint32($tiff, 4, $bigEndian) : null;
        $count = $ifd !== null ? self::uint16($tiff, $ifd, $bigEndian) : null;
        if ($ifd === null || $count === null) {
            return 1;
        }
        for ($i = 0; $i < $count; $i++) {
            $entry = $ifd + 2 + 12 * $i;
            if ($entry + 12 > strlen($tiff)) {
                return 1;
            }
            if (self::uint16($tiff, $entry, $bigEndian) !== self::EXIF_ORIENTATION_TAG) {
                continue;
            }
            //Tipo SHORT (3), una sola cuenta y el valor en los dos primeros bytes del campo.
            $value = self::uint16($tiff, $entry + 8, $bigEndian);
            $valid = self::uint16($tiff, $entry + 2, $bigEndian) === 3 && self::uint32($tiff, $entry + 4, $bigEndian) === 1;
            return $valid && $value !== null && $value >= 1 && $value <= 8 ? $value : 1;
        }
        return 1;
    }

    /**
     * Un TIFF big-endian con un único IFD de una sola entrada: la orientación. Nada más del Exif original viaja.
     *
     * @param int $orientation De 2 a 8
     * @return string
     */
    private static function minimalOrientationTiff(int $orientation): string
    {
        return "MM\0*" . pack('N', 8) . pack('n', 1) . pack('nnNn', self::EXIF_ORIENTATION_TAG, 3, 1, $orientation) . "\0\0" . pack('N', 0);
    }

    /**
     * @param int $packed El campo empaquetado del descriptor
     * @return int El tamaño, en bytes, de la tabla de color que anuncia
     */
    private static function gifColorTableSize(int $packed): int
    {
        return ($packed & 0x80) !== 0 ? 3 * (2 ** (($packed & 0x07) + 1)) : 0;
    }

    /**
     * @param string $content
     * @param int $offset Donde empieza el primer sub-bloque
     * @return int La posición tras el terminador de tamaño 0, o -1 si el archivo se acaba antes
     */
    private static function gifSkipSubBlocks(string $content, int $offset): int
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

}
