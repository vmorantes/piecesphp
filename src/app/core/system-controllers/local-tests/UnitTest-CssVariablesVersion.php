<?php

//El CSS de variables se versiona por su contenido (G9): cambiar un color cambia su URL, y solo con su propia huella se
//guarda un año; sin marca o con otra (la global, que llevan los de los módulos) se revalida. En proceso, en memoria.
//@areas nucleo

use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\CSSVariables;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/css-variables-version', function ($args) {

    echoTerminal("\e[33m[TEST:CssVariablesVersion] El CSS de variables, versionado por su contenido\e[39m");
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

    //Una instancia propia: la global es la del panel y no se toca.
    $css = CSSVariables::instance('zz-prueba-version-' . bin2hex(random_bytes(3)));
    $css->setVariable('main-brand-color', '#123456');
    $pedir = function (array $query) use ($css): string {
        $request = (new Request('GET', (new UriFactory())->createUri('http://localhost/'), new Headers(), [], [], (new StreamFactory())->createStream('')))->withQueryParams($query);
        return $css->toResponse($request, new Response())->getHeaderLine('Cache-Control');
    };

    echoTerminal('[a] La URL lleva la huella del contenido');
    $url = 'admin/global-variables/variables.css/';
    $antes = $css->versionedURL($url);
    $check($antes === $url . '?cacheStamp=' . $css->fingerprint() && strlen($css->fingerprint()) === 16, 'a1 la URL lleva cacheStamp=<huella de 16>', $antes);
    $check($css->versionedURL($url) === $antes, 'a2 CANARIO: sin cambios, la misma URL');
    $css->setVariable('main-brand-color', '#654321');
    $check($css->versionedURL($url) !== $antes, 'a3 cambiar un color cambia la URL', $css->versionedURL($url));
    $check($css->versionedURL('x.css?a=1') === 'x.css?a=1&cacheStamp=' . $css->fingerprint(), 'a4 con otra consulta, se añade con &');
    echoTerminal(' ');

    echoTerminal('[b] Un año solo con su propia huella');
    $propia = $pedir(['cacheStamp' => $css->fingerprint()]);
    $check($propia === 'public, max-age=31536000, immutable', 'b1 con su huella: un año, inmutable', $propia);
    $otra = $pedir(['cacheStamp' => '948c946e77c94293ae79df7c7493c7e2caac74c4']);
    $check($otra === 'no-cache', 'b2 con otra marca (la global de los módulos): se revalida', $otra);
    $vieja = $pedir(['cacheStamp' => substr(sha1('zz'), 0, 16)]);
    $check($vieja === 'no-cache', 'b3 con una huella vieja: se revalida', $vieja);
    $sin = $pedir([]);
    $check($sin === 'no-cache', 'b4 sin marca: se revalida', $sin);
    $request = (new Request('GET', (new UriFactory())->createUri('http://localhost/'), new Headers(), [], [], (new StreamFactory())->createStream('')))->withQueryParams(['cacheStamp' => $css->fingerprint()]);
    $explicita = $css->toResponse($request, new Response(), false, 'max-age=60')->getHeaderLine('Cache-Control');
    $check($explicita === 'max-age=60', 'b5 un Cache-Control explícito se respeta', $explicita);

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El CSS de variables se versiona por su contenido: un color cambia su URL, y solo con su huella se guarda un año.')->setEffects([CliActions::EFFECT_NONE])->register();
