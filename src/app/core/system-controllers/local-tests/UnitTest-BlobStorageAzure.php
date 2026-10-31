<?php

//P228 (c): Azure Blob se lee por REST con Guzzle, sin el SDK abandonado de Microsoft. La firma de clave compartida se
//compara con la que calculaba el SDK (los vectores, capturados con él antes de quitarlo) y las lecturas, sin red.
//@areas src/app/classes/API/

use API\Adapters\BlobStorageAzureAdapter;
use API\Adapters\Packages\BlobStorageFileAzurePackage;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/blob-storage-azure', function ($args) {

    echoTerminal("\e[33m[TEST:BlobStorageAzure] La firma de clave compartida y las lecturas de Azure Blob, sin red\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };

    //Sintética: no es la de ninguna cuenta.
    $llaveCruda = 'zz-prueba-llave-sintetica-32-byt';
    $llave = base64_encode($llaveCruda);
    $cuenta = 'zzprueba';

    //Las peticiones de getBlob('contenedor', <nombre>) del SDK 1.5.4, con su Authorization (sin él en las cabeceras).
    $comunes = [
        'Host' => 'zzprueba.blob.core.windows.net',
        'Content-Type' => 'application/x-www-form-urlencoded',
        'x-ms-version' => '2017-11-09',
        'User-Agent' => 'Azure-Storage/1.5.4-1.5.2 (PHP 8.5.12)/Linux',
        'date' => 'Tue, 27 Oct 2026 21:36:24 GMT',
    ];
    $vectores = [
        'archivo.txt' => [
            'uri' => 'https://zzprueba.blob.core.windows.net/contenedor/archivo.txt',
            'headers' => $comunes + ['x-ms-client-request-id' => '6ae119580ba91'],
            'authorization' => 'SharedKey zzprueba:F7aZNFa6TYzz3DQNoI3NA9p3ic17QQVeCvMoXW4kmCc=',
        ],
        'carpeta/archivo.txt' => [
            'uri' => 'https://zzprueba.blob.core.windows.net/contenedor/carpeta/archivo.txt',
            'headers' => $comunes + ['x-ms-client-request-id' => '6ae119580bfa0'],
            'authorization' => 'SharedKey zzprueba:ncMe4RHm+nCh9NWycKSkouoVlOoGXCh8nLY6k5jyroQ=',
        ],
        'carpeta/sub carpeta/año 2026 (v2).pdf' => [
            'uri' => 'https://zzprueba.blob.core.windows.net/contenedor/carpeta/sub%20carpeta/a%C3%B1o%202026%20%28v2%29.pdf',
            'headers' => $comunes + ['x-ms-client-request-id' => '6ae119580c01a'],
            'authorization' => 'SharedKey zzprueba:omi5OodJp920N2wGmOJtNuHitmYjZR36cEiSPbO728I=',
        ],
        'carpeta\\otro.txt' => [
            'uri' => 'https://zzprueba.blob.core.windows.net/contenedor/carpeta/otro.txt',
            'headers' => $comunes + ['x-ms-client-request-id' => '6ae119580c074'],
            'authorization' => 'SharedKey zzprueba:5kkX/G+TY5K+hMEJtYZgNggSIkedY2KkypGEDivUsUA=',
        ],
    ];

    //Un adaptador cuyo cliente responde lo dado y guarda lo que se le pidió.
    $adaptador = function (Response $respuesta, array &$historial) use ($cuenta, $llave): BlobStorageAzureAdapter {
        $pila = HandlerStack::create(new MockHandler([$respuesta]));
        $pila->push(Middleware::history($historial));
        return new BlobStorageAzureAdapter($cuenta, $llave, new Client(['handler' => $pila]));
    };
    $cabecerasDeRespuesta = [
        'Content-Type' => 'text/plain',
        'Content-Length' => '4',
        'Last-Modified' => 'Tue, 27 Oct 2026 10:00:00 GMT',
        'ETag' => '"0x8DCF0000000000"',
        'x-ms-blob-type' => 'BlockBlob',
    ];

    //─── c1 · La firma, contra la del SDK ───────────────────────────────────────────────────────────────
    echoTerminal('[c1] sharedKeyAuthorization() da EXACTAMENTE el Authorization que calculaba el SDK');
    $firmador = new BlobStorageAzureAdapter($cuenta, $llave);
    foreach ($vectores as $nombre => $vector) {
        $firma = $firmador->sharedKeyAuthorization('GET', $vector['uri'], $vector['headers']);
        $check($firma === $vector['authorization'], "c1 «{$nombre}»", "da {$firma}");
    }
    echoTerminal(' ');

    //─── c2 · La lectura ────────────────────────────────────────────────────────────────────────────────
    echoTerminal('[c2] read() pide una sola vez, a la URI del SDK, y firma lo que envía');
    foreach ($vectores as $nombre => $vector) {
        $historial = [];
        $paquete = $adaptador(new Response(200, $cabecerasDeRespuesta, 'hola'), $historial)->read('contenedor', $nombre);
        $check(count($historial) === 1, "c2 «{$nombre}»: una sola petición", 'peticiones: ' . count($historial));
        if (count($historial) !== 1) {
            continue;
        }
        $peticion = $historial[0]['request'];
        $enviadas = [];
        foreach ($peticion->getHeaders() as $cabecera => $valores) {
            if (strtolower($cabecera) !== 'authorization') {
                $enviadas[$cabecera] = implode(', ', $valores);
            }
        }
        $esperada = $firmador->sharedKeyAuthorization($peticion->getMethod(), (string) $peticion->getUri(), $enviadas);
        $check($peticion->getMethod() === 'GET', "c2 «{$nombre}»: GET", $peticion->getMethod());
        $check((string) $peticion->getUri() === $vector['uri'], "c2 «{$nombre}»: la URI del SDK", (string) $peticion->getUri());
        $check($peticion->getHeaderLine('x-ms-version') === '2017-11-09', "c2 «{$nombre}»: x-ms-version 2017-11-09", $peticion->getHeaderLine('x-ms-version'));
        $check($peticion->getHeaderLine('Authorization') === $esperada, "c2 «{$nombre}»: el Authorization es la firma de lo enviado");
        $check(!$peticion->hasHeader('date') && !$peticion->hasHeader('Content-Type'), "c2 «{$nombre}»: sin date ni Content-Type, así que lo firmado es lo enviado");
        $check(
            $paquete instanceof BlobStorageFileAzurePackage
                && $paquete->name() === $nombre
                && $paquete->content() === 'hola'
                && $paquete->contentType() === 'text/plain'
                && $paquete->lastModified()->getTimestamp() === gmmktime(10, 0, 0, 10, 27, 2026),
            "c2 «{$nombre}»: el paquete da nombre, contenido, tipo y fecha",
            $paquete === null ? 'null' : "{$paquete->name()} · {$paquete->content()} · {$paquete->contentType()} · {$paquete->lastModified()->format(DATE_ATOM)}"
        );
    }
    echoTerminal(' ');

    //─── c3 · El archivo que no existe ──────────────────────────────────────────────────────────────────
    echoTerminal('[c3] Un 404 del blob que no existe es null');
    $historial = [];
    $paquete = $adaptador(new Response(404, ['x-ms-error-code' => 'BlobNotFound']), $historial)->read('contenedor', 'no-existe.txt');
    $check($paquete === null, 'c3 404 BlobNotFound → null');
    $historial = [];
    $paquete = $adaptador(new Response(404), $historial)->read('contenedor', 'no-existe.txt');
    $check($paquete === null, 'c3 404 sin código de error → null');
    echoTerminal(' ');

    //─── c4 · Un error de Azure ─────────────────────────────────────────────────────────────────────────
    echoTerminal('[c4] Un 403 lanza, con el código y sin la llave');
    $historial = [];
    $mensaje = null;
    try {
        $adaptador(new Response(403, ['x-ms-error-code' => 'AuthorizationFailure']), $historial)->read('contenedor', 'archivo.txt');
    } catch (\RuntimeException $e) {
        $mensaje = $e->getMessage();
    }
    $check($mensaje !== null, 'c4 403 → \RuntimeException');
    $check($mensaje !== null && str_contains($mensaje, '403'), 'c4 el mensaje lleva el 403', (string) $mensaje);
    $check(
        $mensaje !== null && !str_contains($mensaje, $llave) && !str_contains($mensaje, $llaveCruda) && !str_contains($mensaje, 'SharedKey'),
        'c4 el mensaje no lleva la llave (ni en base64 ni decodificada) ni el Authorization'
    );
    echoTerminal(' ');

    //─── c5 · La respuesta sin fecha ────────────────────────────────────────────────────────────────────
    echoTerminal('[c5] Un 200 sin Last-Modified lanza');
    $historial = [];
    $sinFecha = $cabecerasDeRespuesta;
    unset($sinFecha['Last-Modified']);
    $lanzo = false;
    try {
        $adaptador(new Response(200, $sinFecha, 'hola'), $historial)->read('contenedor', 'archivo.txt');
    } catch (\RuntimeException $e) {
        $lanzo = true;
    }
    $check($lanzo, 'c5 200 sin Last-Modified → \RuntimeException');
    echoTerminal(' ');

    //─── c6 · El contenedor que no existe ───────────────────────────────────────────────────────────────
    echoTerminal('[c6] Un 404 de ContainerNotFound lanza, como con el SDK');
    $historial = [];
    $mensaje = null;
    try {
        $adaptador(new Response(404, ['x-ms-error-code' => 'ContainerNotFound']), $historial)->read('contenedor-mal-escrito', 'archivo.txt');
    } catch (\RuntimeException $e) {
        $mensaje = $e->getMessage();
    }
    $check($mensaje !== null && str_contains($mensaje, '404 (ContainerNotFound)'), 'c6 404 ContainerNotFound → \RuntimeException con «404 (ContainerNotFound)»', (string) $mensaje);

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('P228 (c): la firma de clave compartida de BlobStorageAzureAdapter contra la que calculaba el SDK de Microsoft, y read() con respuestas simuladas (200, 404 del blob y del contenedor, 403 y sin Last-Modified). Sin red ni base.')->setEffects([CliActions::EFFECT_NONE])->register();
