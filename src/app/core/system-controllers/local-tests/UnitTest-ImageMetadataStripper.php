<?php

//ImageMetadataStripper por formato, sobre imágenes de 8 × 8 de GD con metadatos metidos a mano; sin red, base ni disco.
//@areas src/app/classes/PiecesPHP/Settings/

use PiecesPHP\Settings\Uploads\GifStructureValidator;
use PiecesPHP\Settings\Uploads\ImageMetadataStripper;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/image-metadata-stripper', function ($args) {

    echoTerminal("\e[33m[TEST:ImageMetadataStripper] Las pantallas y la animación del cargador, sin metadatos y sin recomprimir\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
        return $condition;
    };
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal(' ');
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];
    };

    if (!$check(function_exists('imagewebp') && function_exists('imagegif') && function_exists('imagejpeg'), 'banco: GD escribe PNG, JPG, WebP y GIF')) {
        return $balance();
    }
    $gd = function (string $formato): string {
        $lienzo = imagecreatetruecolor(8, 8);
        if ($lienzo === false) {
            return '';
        }
        imagefill($lienzo, 0, 0, (int) imagecolorallocate($lienzo, 200, 40, 90));
        ob_start();
        match ($formato) {
            'png' => imagepng($lienzo),
            'jpg' => imagejpeg($lienzo),
            'webp' => imagewebp($lienzo),
            default => imagegif($lienzo),
        };
        return (string) ob_get_clean();
    };
    $medidas = function (string $contenido): array {
        $info = getimagesizefromstring($contenido);
        return is_array($info) ? [$info[0], $info[1]] : [];
    };
    $decodifica = fn (string $contenido): bool => imagecreatefromstring($contenido) !== false;
    $secreto = 'zz-gps-4.6097-74.0817';

    //PNG: tEXt, zTXt, iTXt y eXIf tras IHDR, cada uno con su CRC.
    $chunkPng = fn (string $tipo, string $datos): string => pack('N', strlen($datos)) . $tipo . $datos . pack('N', crc32($tipo . $datos));
    $tiposPng = function (string $contenido): array {
        $tipos = [];
        for ($o = 8; $o + 12 <= strlen($contenido); $o += 12 + (int) hexdec(bin2hex(substr($contenido, $o, 4)))) {
            $tipos[] = substr($contenido, $o + 4, 4);
        }
        return $tipos;
    };
    $png = $gd('png');
    $pngSucio = substr($png, 0, 33) . $chunkPng('tEXt', "Comment\0{$secreto}") . $chunkPng('zTXt', "Author\0\0" . (string) gzcompress($secreto)) . $chunkPng('iTXt', "XML:com.adobe.xmp\0\0\0\0\0{$secreto}") . $chunkPng('eXIf', "MM\0*{$secreto}") . substr($png, 33) . 'zz-cola';
    $pngLimpio = ImageMetadataStripper::strip($pngSucio, 'png');
    $check(is_string($pngLimpio) && $medidas($pngLimpio) === [8, 8] && $decodifica($pngLimpio), 'png1 sigue siendo un PNG de 8 × 8 que se decodifica', var_export($pngLimpio !== null ? $medidas($pngLimpio) : null, true));
    $check(is_string($pngLimpio) && array_intersect($tiposPng($pngLimpio), ['tEXt', 'zTXt', 'iTXt', 'eXIf']) === [] && !str_contains($pngLimpio, $secreto), 'png2 DISCRIMINANTE: sin tEXt, zTXt, iTXt ni eXIf, y el texto no queda en ninguna parte', is_string($pngLimpio) ? implode(',', $tiposPng($pngLimpio)) : 'null');
    $check($pngLimpio === $png, 'png3 los demás chunks, byte a byte (sin recomprimir), y nada tras IEND');
    $check(ImageMetadataStripper::strip(substr($png, 0, -6), 'png') === null, 'png4 un PNG cortado antes de IEND no se puede recorrer: null');
    $check(ImageMetadataStripper::strip(substr($png, 0, 33) . pack('N', 0xFFFFFFFF) . 'zzZz' . substr($png, 33), 'png') === null, 'png5 un chunk que declara 0xFFFFFFFF bytes: null, sin leer más allá');
    //Lista blanca, trozo a trozo: el recorrido no decodifica, así que basta la estructura (IHDR de GD, cada chunk y IEND).
    $conservadosPng = ['PLTE', 'tRNS', 'gAMA', 'cHRM', 'sRGB', 'iCCP', 'pHYs', 'sBIT', 'bKGD', 'acTL', 'fcTL', 'IDAT', 'fdAT'];
    $quitadosPng = ['zzZz', 'caBX', 'tIME', 'dSIG', 'cICP', 'eXIf', 'tEXt'];
    $estructuraPng = substr($png, 0, 33);
    foreach (array_merge($conservadosPng, $quitadosPng) as $tipo) {
        $estructuraPng .= $chunkPng($tipo, "zz-{$tipo}-{$secreto}");
    }
    $estructuraPng .= $chunkPng('IEND', '');
    $tiposEstructura = $tiposPng((string) ImageMetadataStripper::strip($estructuraPng, 'png'));
    foreach ($conservadosPng as $tipo) {
        $check(in_array($tipo, $tiposEstructura, true), "png6 {$tipo} se queda");
    }
    foreach ($quitadosPng as $tipo) {
        $check(!in_array($tipo, $tiposEstructura, true), "png7 DISCRIMINANTE: {$tipo} se va" . ($tipo === 'zzZz' ? ' (un chunk desconocido)' : ''));
    }
    $check($tiposEstructura === array_merge(['IHDR'], $conservadosPng, ['IEND']), 'png8 y en su orden, sin nada más', implode(',', $tiposEstructura));

    //JPG: APP1 Exif, APP2 ICC_PROFILE, APP13 Photoshop, APP14 Adobe y COM tras el SOI, y una cola tras el EOI.
    $segmento = fn (int $marcador, string $datos): string => "\xFF" . chr($marcador & 0xFF) . pack('n', strlen($datos) + 2) . $datos;
    $marcadoresJpg = function (string $contenido): array {
        $vistos = [];
        for ($o = 2; $o + 4 <= strlen($contenido) && $contenido[$o] === "\xFF";) {
            $m = ord($contenido[$o + 1]);
            if ($m === 0xDA || $m === 0xD9) {
                break;
            }
            $datos = substr($contenido, $o + 4, (int) hexdec(bin2hex(substr($contenido, $o + 2, 2))) - 2);
            $vistos[] = sprintf('%02X', $m) . ':' . substr($datos, 0, 5);
            $o += 2 + (int) hexdec(bin2hex(substr($contenido, $o + 2, 2)));
        }
        return $vistos;
    };
    $jpg = $gd('jpg');
    $icc = $segmento(0xE2, "ICC_PROFILE\0\x01\x01zz-perfil");
    $adobe = $segmento(0xEE, "Adobe\x00\x64\x00\x00\x00\x00\x01");
    $jpgSucio = "\xFF\xD8" . $segmento(0xE1, "Exif\0\0MM\0*{$secreto}") . $icc . $segmento(0xE1, "http://ns.adobe.com/xap/1.0/\0{$secreto}") . $segmento(0xED, "Photoshop 3.0\0{$secreto}") . $adobe . $segmento(0xFE, $secreto) . substr($jpg, 2) . "zz-cola{$secreto}";
    $jpgLimpio = ImageMetadataStripper::strip($jpgSucio, 'jpg');
    $marcas = is_string($jpgLimpio) ? $marcadoresJpg($jpgLimpio) : [];
    $check(is_string($jpgLimpio) && $medidas($jpgLimpio) === [8, 8] && $decodifica($jpgLimpio), 'jpg1 sigue siendo un JPG de 8 × 8 que se decodifica');
    $check(is_string($jpgLimpio) && !str_contains($jpgLimpio, $secreto) && preg_grep('/^(E1|ED|FE):/', $marcas) === [], 'jpg2 DISCRIMINANTE: sin Exif, XMP, Photoshop ni comentario, y el texto tampoco queda tras el EOI', implode(' ', $marcas));
    $check(is_string($jpgLimpio) && str_contains($jpgLimpio, $icc) && str_contains($jpgLimpio, $adobe), 'jpg3 se conservan el perfil ICC (APP2) y Adobe APP14: deciden los colores', implode(' ', $marcas));
    $check($jpgLimpio === "\xFF\xD8" . $icc . $adobe . substr((string) ImageMetadataStripper::strip($jpg, 'jpg'), 2), 'jpg4 los datos de la imagen, byte a byte: solo faltan los segmentos quitados', strlen((string) $jpgLimpio) . ' bytes');
    $check(ImageMetadataStripper::strip(substr($jpg, 0, -2), 'jpg') === null, 'jpg5 un JPG sin EOI no se puede recorrer: null');

    //La orientación: un TIFF con un Make que apunta al secreto y Orientation, en los dos órdenes de bytes.
    $tiff = function (bool $intel, int $orientacion) use ($secreto): string {
        $n = fn (int $v): string => pack($intel ? 'v' : 'n', $v);
        $N = fn (int $v): string => pack($intel ? 'V' : 'N', $v);
        $datos = $secreto . "\0";
        return ($intel ? "II*\0" : "MM\0*") . $N(8) . $n(2) . $n(0x010F) . $n(2) . $N(strlen($datos)) . $N(8 + 2 + 24 + 4) . $n(0x0112) . $n(3) . $N(1) . $n($orientacion) . "\0\0" . $N(0) . $datos;
    };
    $tiffMinimo = "MM\0*" . pack('N', 8) . pack('n', 1) . pack('nnNn', 0x0112, 3, 1, 6) . "\0\0" . pack('N', 0);
    $app1Minimo = $segmento(0xE1, "Exif\0\0{$tiffMinimo}");
    foreach ([true => 'II', false => 'MM'] as $intel => $orden) {
        $girado = ImageMetadataStripper::strip("\xFF\xD8" . $segmento(0xE1, "Exif\0\0" . $tiff((bool) $intel, 6)) . substr($jpg, 2), 'jpg');
        $check($girado === "\xFF\xD8" . $app1Minimo . substr((string) ImageMetadataStripper::strip($jpg, 'jpg'), 2), "jpg6 DISCRIMINANTE: Orientation=6 ({$orden}) queda en un APP1 Exif mínimo, solo con ella; el Make y su texto, fuera", is_string($girado) ? bin2hex(substr($girado, 0, 48)) : 'null');
    }
    $recto = ImageMetadataStripper::strip("\xFF\xD8" . $segmento(0xE1, "Exif\0\0" . $tiff(false, 1)) . substr($jpg, 2), 'jpg');
    $check(is_string($recto) && preg_grep('/^E1:/', $marcadoresJpg($recto)) === [] && !str_contains($recto, $secreto), 'jpg7 con Orientation=1 no se escribe ningún APP1');
    $check(ImageMetadataStripper::strip("\xFF\xD8" . $segmento(0xE1, "Exif\0\0" . substr($tiff(false, 6), 0, 20)) . substr($jpg, 2), 'jpg') === ImageMetadataStripper::strip($jpg, 'jpg'), 'jpg8 un IFD cortado antes de la entrada: sin orientación, sin APP1 y sin error');

    //PNG con eXIf: el mismo TIFF; queda un eXIf mínimo en su sitio, con su CRC.
    $pngGirado = ImageMetadataStripper::strip(substr($png, 0, 33) . $chunkPng('eXIf', $tiff(true, 6)) . substr($png, 33), 'png');
    $check($pngGirado === substr($png, 0, 33) . $chunkPng('eXIf', $tiffMinimo) . substr($png, 33) && $decodifica((string) $pngGirado), 'png9 DISCRIMINANTE: un eXIf con Orientation=6 queda mínimo, solo con ella y con su CRC; el PNG se decodifica', is_string($pngGirado) ? implode(',', $tiposPng($pngGirado)) : 'null');
    $check(ImageMetadataStripper::strip(substr($png, 0, 33) . $chunkPng('eXIf', $tiff(true, 1)) . substr($png, 33), 'png') === $png, 'png10 con Orientation=1, el eXIf se va entero');

    //WebP: el VP8 de GD dentro de un contenedor extendido con VP8X (banderas EXIF y XMP), EXIF y XMP (de longitud impar).
    $chunkWebp = fn (string $fourCC, string $datos): string => $fourCC . pack('V', strlen($datos)) . $datos . (strlen($datos) % 2 === 1 ? "\0" : '');
    $chunksWebp = function (string $contenido): array {
        $vistos = [];
        for ($o = 12; $o + 8 <= strlen($contenido);) {
            $largo = (int) hexdec(bin2hex(strrev(substr($contenido, $o + 4, 4))));
            $vistos[] = substr($contenido, $o, 4);
            $o += 8 + $largo + ($largo & 1);
        }
        return $vistos;
    };
    $webp = $gd('webp');
    $imagenWebp = substr($webp, 12);
    $vp8x = $chunkWebp('VP8X', chr(ImageMetadataStripper::WEBP_VP8X_EXIF_FLAG | ImageMetadataStripper::WEBP_VP8X_XMP_FLAG) . "\0\0\0" . substr(pack('V', 7), 0, 3) . substr(pack('V', 7), 0, 3));
    $cuerpo = 'WEBP' . $vp8x . $imagenWebp . $chunkWebp('EXIF', "MM\0*{$secreto}") . $chunkWebp('XMP ', "<x:xmpmeta>{$secreto}</x:xmpmeta>!");
    $webpSucio = 'RIFF' . pack('V', strlen($cuerpo)) . $cuerpo;
    $check($medidas($webpSucio) === [8, 8] && $decodifica($webpSucio), 'banco: el WebP extendido de la prueba es válido antes de quitar nada', implode(',', $chunksWebp($webpSucio)));
    $webpLimpio = ImageMetadataStripper::strip($webpSucio, 'webp');
    $check(is_string($webpLimpio) && $medidas($webpLimpio) === [8, 8] && $decodifica($webpLimpio), 'webp1 sigue siendo un WebP de 8 × 8 que se decodifica');
    $check(is_string($webpLimpio) && $chunksWebp($webpLimpio) === ['VP8X', substr($imagenWebp, 0, 4)] && !str_contains($webpLimpio, $secreto), 'webp2 DISCRIMINANTE: sin EXIF ni XMP; quedan VP8X y la imagen', is_string($webpLimpio) ? implode(',', $chunksWebp($webpLimpio)) : 'null');
    $check(is_string($webpLimpio) && (ord($webpLimpio[20]) & (ImageMetadataStripper::WEBP_VP8X_EXIF_FLAG | ImageMetadataStripper::WEBP_VP8X_XMP_FLAG)) === 0 && (int) hexdec(bin2hex(strrev(substr($webpLimpio, 4, 4)))) === strlen($webpLimpio) - 8 && str_contains($webpLimpio, $imagenWebp), 'webp3 las banderas de VP8X apagadas, el tamaño del RIFF recalculado y la imagen byte a byte');
    $check(ImageMetadataStripper::strip($webp, 'webp') === $webp, 'webp4 un WebP simple sin metadatos sale idéntico');
    //Lista blanca: Chromium no aplica la orientación de un WebP, así que el EXIF se va aunque la lleve.
    $conservadosWebp = ['ICCP', 'ANIM', 'ANMF', 'ALPH', substr($imagenWebp, 0, 4)];
    $quitadosWebp = ['zzZZ', 'EXIF', 'XMP '];
    $vp8xIcc = $chunkWebp('VP8X', chr(0x20 | ImageMetadataStripper::WEBP_VP8X_EXIF_FLAG | ImageMetadataStripper::WEBP_VP8X_XMP_FLAG) . "\0\0\0" . substr(pack('V', 7), 0, 3) . substr(pack('V', 7), 0, 3));
    $cuerpo = 'WEBP' . $vp8xIcc . $chunkWebp('ICCP', "zz-perfil") . $chunkWebp('ANIM', "\0\0\0\0\0\0") . $chunkWebp('ANMF', 'zz-fotograma') . $chunkWebp('ALPH', 'z') . $imagenWebp . $chunkWebp('zzZZ', $secreto) . $chunkWebp('EXIF', $tiff(true, 6)) . $chunkWebp('XMP ', $secreto);
    $estructuraWebp = ImageMetadataStripper::strip('RIFF' . pack('V', strlen($cuerpo)) . $cuerpo, 'webp');
    $vistosWebp = is_string($estructuraWebp) ? $chunksWebp($estructuraWebp) : [];
    foreach ($conservadosWebp as $fourCC) {
        $check(in_array($fourCC, $vistosWebp, true), "webp5 {$fourCC} se queda");
    }
    foreach ($quitadosWebp as $fourCC) {
        $check(!in_array($fourCC, $vistosWebp, true), "webp6 DISCRIMINANTE: {$fourCC} se va" . ($fourCC === 'zzZZ' ? ' (un chunk desconocido)' : ($fourCC === 'EXIF' ? ', también con Orientation=6' : '')));
    }
    $check(is_string($estructuraWebp) && $vistosWebp === array_merge(['VP8X'], $conservadosWebp) && ord($estructuraWebp[20]) === 0x20 && !str_contains($estructuraWebp, $secreto) && (int) hexdec(bin2hex(strrev(substr($estructuraWebp, 4, 4)))) === strlen($estructuraWebp) - 8, 'webp7 en su orden; en VP8X solo queda la bandera del ICC, y el RIFF, recalculado', implode(',', $vistosWebp) . ' · ' . (is_string($estructuraWebp) ? sprintf('0x%02X', ord($estructuraWebp[20])) : 'null'));

    //GIF: NETSCAPE2.0 (el bucle), un comentario y una extensión de aplicación ajena tras la tabla global.
    $gif = $gd('gif');
    $gif = 'GIF89a' . substr($gif, 6);
    $tablaGlobal = (ord($gif[10]) & 0x80) !== 0 ? 3 * (2 ** ((ord($gif[10]) & 0x07) + 1)) : 0;
    $bucle = "\x21\xFF\x0BNETSCAPE2.0\x03\x01\x00\x00\x00";
    $gifSucio = substr($gif, 0, 13 + $tablaGlobal) . $bucle . "\x21\xFE" . chr(strlen($secreto)) . $secreto . "\x00" . "\x21\xFF\x0BXMP DataXMP" . chr(strlen($secreto)) . $secreto . "\x00" . substr($gif, 13 + $tablaGlobal) . 'zz-cola';
    $check(GifStructureValidator::rejectionReason($gifSucio, 4096) === null, 'banco: el GIF de la prueba pasa por GifStructureValidator');
    $gifLimpio = ImageMetadataStripper::strip($gifSucio, 'gif');
    $check(is_string($gifLimpio) && $medidas($gifLimpio) === [8, 8] && $decodifica($gifLimpio) && GifStructureValidator::rejectionReason($gifLimpio, 4096) === null, 'gif1 sigue siendo un GIF de 8 × 8 válido');
    $check(is_string($gifLimpio) && !str_contains($gifLimpio, $secreto) && !str_contains($gifLimpio, 'XMP DataXMP') && !str_contains($gifLimpio, "\x21\xFE"), 'gif2 DISCRIMINANTE: sin el comentario ni la extensión de aplicación ajena');
    $check(is_string($gifLimpio) && str_contains($gifLimpio, $bucle) && $gifLimpio === substr($gif, 0, 13 + $tablaGlobal) . $bucle . substr($gif, 13 + $tablaGlobal), 'gif3 se conserva NETSCAPE2.0 y el resto va byte a byte, sin lo que seguía al trailer');

    //El SVG ya pasa por la lista blanca; un tipo desconocido no se guarda.
    $check(ImageMetadataStripper::strip('<svg/>', 'svg') === '<svg/>' && ImageMetadataStripper::strip($png, 'bmp') === null, 'otros: el SVG sale igual; un tipo desconocido, null');

    return $balance();
})->setDescription('ImageMetadataStripper: las pantallas y la animación del cargador se guardan sin EXIF, XMP ni comentarios y sin recomprimir (PNG, JPG con su ICC y su Adobe APP14, WebP con VP8X al día, GIF con NETSCAPE2.0), sobre imágenes de 8 × 8 hechas con GD. Sin red, sin base, sin disco.')->setEffects([CliActions::EFFECT_NONE])->register();
