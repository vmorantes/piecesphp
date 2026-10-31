<?php

//Lo que sirve PHP: rangos, caché privada para lo validado, Vary, ETag y el rechazo de «..». Sin escribir en disco; su única
//petición HTTP pide una hoja para que el servidor cree el acceso directo que mira el canario (H72).
//@areas nucleo

use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\RequestRouteFactory;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\Terminal\CliActions;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/server-statics';
$cliTaskDescription = 'ServerStatics: rangos, Cache-Control privado en lo validado, Vary, ETag por fecha, tamaño y tipo, y «..» rechazado antes de delegar';

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
    echoTerminal('[1/6] resolveRange(): un tramo → [inicio, fin]; inválido o fuera → false (416); varios o ninguno → null (200)');

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
    echoTerminal('[2/6] cacheControlValue(): con versión, un año; sin ella, no-cache; lo validado, nunca public (ADR 0034)');

    $casos = [
        [false, false, false, 'no-cache', 'público sin versión → no-cache'],
        [false, true, false, 'no-cache', 'público sin versión, aunque pida must-revalidate → no-cache'],
        [false, false, true, 'public, max-age=31536000, immutable', 'DISCRIMINANTE: público con versión → un año e immutable'],
        [true, false, false, 'private, no-cache', 'validado sin versión → private, no-cache'],
        [true, false, true, 'private, max-age=31536000, immutable', 'validado con versión → un año, pero private'],
    ];
    foreach ($casos as [$privado, $revalidar, $versionado, $esperado, $nombre]) {
        $obtenido = ServerStatics::cacheControlValue($privado, $revalidar, $versionado);
        $check($obtenido === $esperado, $nombre, $obtenido);
    }
    $check(mb_strpos(ServerStatics::cacheControlValue(true, false, true), 'public') === false && mb_strpos(ServerStatics::cacheControlValue(true, false, false), 'public') === false, 'lo validado nunca dice «public»: una caché compartida no lo guarda');
    //X-Served-By: is_local() lee, en el terminal, el `local` de PCSPHP_TERMINAL_DATA; se fabrica y se devuelve.
    $terminalPrevio = $_SERVER['PCSPHP_TERMINAL_DATA'] ?? null;
    try {
        $_SERVER['PCSPHP_TERMINAL_DATA'] = array_merge(is_array($terminalPrevio) ? $terminalPrevio : [], ['isTerminal' => true, 'local' => true]);
        $check(ServerStatics::servedByHeader() === 'Web-Server-Delegated', 'X-Served-By en local');
        $_SERVER['PCSPHP_TERMINAL_DATA']['local'] = false;
        $check(ServerStatics::servedByHeader() === null, 'DISCRIMINANTE: fuera de local, sin X-Served-By (no anuncia el framework)');
    } finally {
        $_SERVER['PCSPHP_TERMINAL_DATA'] = $terminalPrevio;
    }
    echoTerminal(' ');

    //──── 3. Vary ──────────────────────────────────────────────────────────────────────────────
    echoTerminal('[3/6] varyValues(): de qué depende la respuesta');

    $check(ServerStatics::varyValues(true, false, false) === ['Cookie', 'Authorization'], 'validado → Vary: Cookie, Authorization');
    $check(ServerStatics::varyValues(true, true, true) === ['Cookie', 'Authorization', 'Accept', 'Accept-Encoding'], 'validado, convertido y comprimido → los cuatro');
    $check(ServerStatics::varyValues(false, true, false) === ['Accept'], 'público convertido a WebP → Vary: Accept');
    $check(ServerStatics::varyValues(false, false, true) === ['Accept-Encoding'], 'público comprimido → Vary: Accept-Encoding');
    $check(ServerStatics::varyValues(false, false, false) === [], 'DISCRIMINANTE: público sin procesar → sin Vary propio');
    echoTerminal(' ');

    //──── 4. ETag ──────────────────────────────────────────────────────────────────────────────
    echoTerminal('[4/6] eTagFor(): fecha, tamaño y tipo de salida');

    $check(ServerStatics::eTagFor(1757966939, 193) === ServerStatics::eTagFor(1757966939, 193), 'DISCRIMINANTE: lo mismo da el mismo ETag');
    $check(ServerStatics::eTagFor(1757966939, 193) !== ServerStatics::eTagFor(1757966939, 6291456), 'misma fecha y distinto tamaño → distinto ETag (antes coincidían)');
    $check(ServerStatics::eTagFor(1757966939, 193) !== ServerStatics::eTagFor(1757966939, 193, ServerStatics::TYPE_WEBP), 'el original y su WebP → distinto ETag');
    $check(ServerStatics::eTagFor(1757966939, 193) !== ServerStatics::eTagFor(1757966940, 193), 'distinta fecha → distinto ETag');
    echoTerminal(' ');

    //──── 5. Compresión ────────────────────────────────────────────────────────────────────────
    echoTerminal('[5/6] allowCompression(): solo el texto');

    //Si esto cae, un PDF o un vídeo vuelven a leerse enteros para comprimirlos y pierden el streaming y el Range.
    $comprime = new \ReflectionMethod(ServerStatics::class, 'allowCompression');
    foreach (['pdf', 'png', 'jpg', 'webp', 'mp4', 'woff2', 'zip', 'xyz'] as $binario) {
        $check($comprime->invoke(null, $binario) === false, "{$binario} → sin comprimir");
    }
    foreach (['css', 'js', 'json', 'csv', 'svg', 'txt', 'map', 'html'] as $extensionTexto) {
        $check($comprime->invoke(null, $extensionTexto) === true, ($extensionTexto === 'css' ? 'DISCRIMINANTE: ' : '') . "{$extensionTexto} → se comprime");
    }
    echoTerminal(' ');

    //──── 6. Salir de la carpeta ───────────────────────────────────────────────────────────────
    echoTerminal('[6/6] pathHasTraversal(): «..» o el byte nulo se rechazan ANTES de delegar (P210)');

    foreach (['../x.css', 'a/../b.png', 'a/..', "x\0.css", '....//x.js'] as $conSalida) {
        $check(ServerStatics::pathHasTraversal($conSalida) === true, $texto($conSalida) . ' → se rechaza');
    }
    foreach (['core/css/ui-pcs.css', 'images/favicon.png', 'uploads/a b.jpg'] as $sinSalida) {
        $check(ServerStatics::pathHasTraversal($sinSalida) === false, $texto($sinSalida) . ' → se acepta');
    }

    //Existe dentro de basepath() pero fuera de statics/, y su extensión se delega: sin la guarda, 302 hacia un enlace nuevo.
    $fuera = '../app/classes/Publications/Statics/css/publications.css';
    $peticion = (new RequestRouteFactory())->createServerRequest('GET', baseurl('statics/' . $fuera));
    if ($check($peticion instanceof RequestRoute, 'la petición es una RequestRoute')) {
        /** @var RequestRoute $peticion */
        $respuesta = (new ServerStatics())->serve($peticion, new ResponseRoute(), ['params' => $fuera], basepath('statics'));
        $check($respuesta->getStatusCode() === 404 && $respuesta->getHeaderLine('Location') === '', 'DISCRIMINANTE: serve() con «..» → 404 y sin Location', $respuesta->getStatusCode() . ' · Location ' . $texto($respuesta->getHeaderLine('Location')));
    }
    $check(ServerStatics::getSymbolicLink(['params' => $fuera], basepath('statics')) === null, 'getSymbolicLink() con «..» → null');

    //Control del camino bueno: el enlace lo crea el servidor web al servir la hoja por su ruta PHP, así que se pide antes
    //(tras «Borrar todos los accesos directos» no queda ninguno hasta que alguien la pide; H72). Aquí solo se lee.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    if ($base === '') {
        $matrixPath = dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/files/dev/permissions-matrix.json';
        $matrix = is_file($matrixPath) ? json_decode((string) file_get_contents($matrixPath), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    if ($base !== '') {
        $fabrica = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($fabrica, [
            CURLOPT_URL => rtrim($base, '/') . '/admin/publications/statics/css/publications.css',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
        ]);
        //RETORNO-IGNORADO: lo que importa es el enlace que deja en disco, y lo comprueba el canario.
        curl_exec($fabrica);
    }
    //basepath() pasa por realpath(): sobre el enlace entero devolvería su destino.
    $enlace = basepath('statics/server-delegated') . '/app/classes/Publications/Statics/css/publications.css';
    if ($check(is_link($enlace), 'CANARIO: existe el enlace delegado de publications.css', $enlace)) {
        $url = ServerStatics::getSymbolicLink(['params' => 'css/publications.css'], basepath('app/classes/Publications/Statics'));
        $check(is_string($url) && str_contains($url, 'server-delegated'), 'getSymbolicLink() sin «..» → la URL delegada', $texto($url));
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

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_FILES])->register();
