<?php

//Lo que sirve PHP: rangos, caché privada para lo validado, Vary y ETag. Solo funciones puras, sin HTTP.

use PiecesPHP\Core\ServerStatics;
use PiecesPHP\Terminal\CliActions;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/server-statics';
$cliTaskDescription = 'ServerStatics: rangos, Cache-Control privado en lo validado, Vary y ETag por fecha, tamaño y tipo';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:ServerStatics] Iniciando suite...', true, "\r\n", '33');
    echoTerminal('');

    $passed = 0;
    $failed = 0;

    $check = function (bool $condition, string $name, ?string $detail = null) use (&$passed, &$failed) {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}");
        }
        if ($detail !== null) {
            echoTerminal("      - {$detail}");
        }
        return $condition;
    };
    $texto = static fn ($valor): string => var_export($valor, true);

    //──── 1. Rangos ────────────────────────────────────────────────────────────────────────────
    echoTerminal('[1/5] resolveRange(): un tramo → [inicio, fin]; inválido o fuera → false (416); varios o ninguno → null (200)');

    $casos = [
        ['', 1000, null, 'sin cabecera → archivo entero'],
        ['bytes=0-99', 1000, [0, 99], 'bytes=0-99 → los 100 primeros'],
        ['bytes=-100', 1000, [900, 999], 'bytes=-100 → los 100 últimos'],
        ['bytes=500-', 1000, [500, 999], 'bytes=500- → hasta el final'],
        ['bytes=990-2000', 1000, [990, 999], 'un fin más allá del tamaño se recorta'],
        ['bytes=-2000', 1000, [0, 999], 'un sufijo mayor que el archivo es el archivo entero'],
        ['bytes=999-', 1000, [999, 999], 'DISCRIMINANTE: el último byte sí'],
        ['bytes=1000-', 1000, false, 'el primer byte fuera del archivo → 416'],
        ['bytes=99999999-', 1000, false, 'bytes=99999999- → 416'],
        ['bytes=-0', 1000, false, 'un sufijo de cero bytes → 416'],
        ['bytes=5-3', 1000, false, 'inicio mayor que fin → 416'],
        ['bytes=abc', 1000, false, 'mal formado → 416'],
        ['bytes=0-0', 0, false, 'un archivo vacío no tiene tramos → 416'],
        ['bytes=0-1,5-6', 1000, null, 'varios tramos → archivo entero (200)'],
        ['items=0-5', 1000, null, 'otra unidad → se ignora (200)'],
    ];
    foreach ($casos as [$cabecera, $tamano, $esperado, $nombre]) {
        $obtenido = ServerStatics::resolveRange($cabecera, $tamano);
        $check($obtenido === $esperado, $nombre, "«{$cabecera}» sobre {$tamano} → " . $texto($obtenido));
    }
    echoTerminal(' ');

    //──── 2. Cache-Control ─────────────────────────────────────────────────────────────────────
    echoTerminal('[2/5] cacheControlValue(): lo validado es privado; lo público, como antes');

    $privado = ServerStatics::cacheControlValue(true, false);
    $check($privado === 'private, max-age=5256000, must-revalidate', 'validado → private, max-age=5256000, must-revalidate', $privado);
    $check(mb_strpos($privado, 'public') === false, 'y sin «public»: una caché compartida no lo guarda');
    $check(ServerStatics::cacheControlValue(false, false) === 'max-age=5256000, public', 'DISCRIMINANTE: lo público, sin cambios');
    $check(ServerStatics::cacheControlValue(false, true) === 'max-age=5256000, public, must-revalidate', 'lo público con must-revalidate, sin cambios');
    echoTerminal(' ');

    //──── 3. Vary ──────────────────────────────────────────────────────────────────────────────
    echoTerminal('[3/5] varyValues(): de qué depende la respuesta');

    $check(ServerStatics::varyValues(true, false, false) === ['Cookie', 'Authorization'], 'validado → Vary: Cookie, Authorization');
    $check(ServerStatics::varyValues(true, true, true) === ['Cookie', 'Authorization', 'Accept', 'Accept-Encoding'], 'validado, convertido y comprimido → los cuatro');
    $check(ServerStatics::varyValues(false, true, false) === ['Accept'], 'público convertido a WebP → Vary: Accept');
    $check(ServerStatics::varyValues(false, false, true) === ['Accept-Encoding'], 'público comprimido → Vary: Accept-Encoding');
    $check(ServerStatics::varyValues(false, false, false) === [], 'DISCRIMINANTE: público sin procesar → sin Vary propio');
    echoTerminal(' ');

    //──── 4. ETag ──────────────────────────────────────────────────────────────────────────────
    echoTerminal('[4/5] eTagFor(): fecha, tamaño y tipo de salida');

    $check(ServerStatics::eTagFor(1757966939, 193) === ServerStatics::eTagFor(1757966939, 193), 'DISCRIMINANTE: lo mismo da el mismo ETag');
    $check(ServerStatics::eTagFor(1757966939, 193) !== ServerStatics::eTagFor(1757966939, 6291456), 'misma fecha y distinto tamaño → distinto ETag (antes coincidían)');
    $check(ServerStatics::eTagFor(1757966939, 193) !== ServerStatics::eTagFor(1757966939, 193, ServerStatics::TYPE_WEBP), 'el original y su WebP → distinto ETag');
    $check(ServerStatics::eTagFor(1757966939, 193) !== ServerStatics::eTagFor(1757966940, 193), 'distinta fecha → distinto ETag');
    echoTerminal(' ');

    //──── 5. Compresión ────────────────────────────────────────────────────────────────────────
    echoTerminal('[5/5] allowCompression(): solo el texto');

    //Si esto cae, un PDF o un vídeo vuelven a leerse enteros para comprimirlos y pierden el streaming y el Range.
    $comprime = new \ReflectionMethod(ServerStatics::class, 'allowCompression');
    foreach (['pdf', 'png', 'jpg', 'webp', 'mp4', 'woff2', 'zip', 'xyz'] as $binario) {
        $check($comprime->invoke(null, $binario) === false, "{$binario} → sin comprimir");
    }
    foreach (['css', 'js', 'json', 'csv', 'svg', 'txt', 'map', 'html'] as $extensionTexto) {
        $check($comprime->invoke(null, $extensionTexto) === true, ($extensionTexto === 'css' ? 'DISCRIMINANTE: ' : '') . "{$extensionTexto} → se comprime");
    }
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:ServerStatics] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "Rangos, Cache-Control, Vary y ETag como deben ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_NONE])->register();
