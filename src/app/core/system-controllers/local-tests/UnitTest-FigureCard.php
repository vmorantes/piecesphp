<?php

//La tarjeta de cifra (`statistic-card.php` con `figure`) y los botones de filtros, pintados de verdad y leídos con un
//analizador: `render()` reformatea el HTML. No escribe en ningún sitio.
//@areas nucleo

use PiecesPHP\Core\BaseController;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/figure-card', function ($args) {

    echoTerminal('[TEST:FigureCard] Iniciando suite...', true, "\r\n", '33');
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
            if ($detail !== null) {
                echoTerminal("      - {$detail}");
            }
        }
        return $condition;
    };
    $leer = function (string $vista, array $datos): \DOMDocument {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . (string) (new BaseController())->render($vista, $datos, false));
        return $dom;
    };
    $clases = fn (\DOMDocument $dom, string $tag): string => (string) $dom->getElementsByTagName($tag)->item(0)?->attributes?->getNamedItem('class')?->nodeValue;
    $marca = '<script>zzMarca(1)</script>';

    echoTerminal('[0/2] La tarjeta de cifra');
    $tarjeta = $leer('panel/built-in/statistics/statistic-card', [
        'title' => "Título {$marca}",
        'help' => "Ayuda {$marca}",
        'figure' => ['value' => "1{$marca}", 'label' => "Rótulo {$marca}", 'meta' => "Meta {$marca}", 'description' => "Detalle {$marca}"],
    ]);
    $check($tarjeta->getElementsByTagName('script')->length === 0, 'c1 DISCRIMINANTE: ningún texto crea una etiqueta');
    $check(str_contains((string) $tarjeta->textContent, "Título {$marca}") && str_contains((string) $tarjeta->textContent, "Detalle {$marca}"), 'c2 CANARIO: los textos llegan enteros');
    $check($clases($tarjeta, 'div') === 'statistic card', 'c3 sin variante, `statistic card`', $clases($tarjeta, 'div'));
    $boton = $tarjeta->getElementsByTagName('button')->item(0);
    $check($boton instanceof \DOMElement && $boton->getAttribute('aria-label') === "Ayuda {$marca}", 'c4 la ayuda, entera en su atributo');
    $alta = $leer('panel/built-in/statistics/statistic-card', ['figure' => ['value' => '1', 'variant' => 'tall', 'size' => 'huge marked']]);
    $check($clases($alta, 'div') === 'tall statistic card', 'c5 `tall`', $clases($alta, 'div'));
    $check(str_contains(implode(' ', array_map(fn ($n) => (string) $n->attributes?->getNamedItem('class')?->nodeValue, iterator_to_array($alta->getElementsByTagName('div')))), 'ui huge marked statistic'), 'c6 `huge marked`');
    $rara = $leer('panel/built-in/statistics/statistic-card', ['figure' => ['value' => '1', 'variant' => 'zz" onclick="x', 'size' => 'zz']]);
    $check($clases($rara, 'div') === 'statistic card', 'c7 DISCRIMINANTE: una variante desconocida no llega al atributo', $clases($rara, 'div'));
    $clasica = $leer('panel/built-in/statistics/statistic-card', ['title' => 'x']);
    $check(str_contains($clases($clasica, 'div'), 'card-statistic'), 'c8 sin `figure`, sigue la pieza de siempre', $clases($clasica, 'div'));
    echoTerminal(' ');

    echoTerminal('[1/2] Los botones de filtros');
    $reinicio = $leer('panel/built-in/content/filter-actions', []);
    $botones = $reinicio->getElementsByTagName('button');
    $check($botones->length === 2 && $botones->item(0)?->attributes?->getNamedItem('type')?->nodeValue === 'submit' && $botones->item(1)?->attributes?->getNamedItem('type')?->nodeValue === 'reset', 'f1 sin `clearUrl`: enviar y reiniciar');
    $url = 'http://localhost/zz?a=1&b="><img src=x onerror=zzMarca(1)>';
    $enlace = $leer('panel/built-in/content/filter-actions', ['clearUrl' => $url]);
    $a = $enlace->getElementsByTagName('a')->item(0);
    $check($a instanceof \DOMElement && $a->getAttribute('href') === $url, 'f2 DISCRIMINANTE: con `clearUrl`, un enlace con la URL entera en su atributo');
    $check($enlace->getElementsByTagName('img')->length === 0, 'f3 ninguna etiqueta inyectada');
    echoTerminal(' ');

    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:FigureCard] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? 'Suite ejecutada correctamente.'
            : "Suite completada con {$failed} prueba(s) fallida(s).",
    ];

})->setDescription('La tarjeta de cifra y los botones de filtros: escapan lo que reciben y sus variantes son una lista cerrada. No escribe.')->setEffects([CliActions::EFFECT_NONE])->register();
