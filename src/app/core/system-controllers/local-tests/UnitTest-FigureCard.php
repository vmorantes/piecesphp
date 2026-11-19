<?php

//La tarjeta de cifra (`statistic-card.php` con `figure`, también con varias cifras, pie y acceso directo) y los botones
//de filtros, pintados de verdad y leídos con un analizador: `render()` reformatea el HTML. No escribe en ningún sitio.
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
    $porClase = function (\DOMDocument $dom, string $clase): array {
        return array_values(array_filter(iterator_to_array($dom->getElementsByTagName('*')), fn ($n) => $n instanceof \DOMElement && in_array($clase, explode(' ', $n->getAttribute('class')), true)));
    };
    $dos = $leer('panel/built-in/statistics/statistic-card', ['title' => 'x', 'figure' => ['statistics' => [
        ['value' => '1', 'dataType' => 'zz-0', 'label' => "Uno {$marca}"],
        ['value' => '2', 'dataType' => 'zz-1" onclick="x', 'label' => 'Dos'],
    ]]]);
    $estadisticas = array_filter($porClase($dos, 'statistic'), fn ($n) => str_starts_with($n->getAttribute('class'), 'ui '));
    $valores = array_map(fn ($n) => $n->getAttribute('data-type'), $porClase($dos, 'value'));
    $check(count($estadisticas) === 2 && $valores === ['zz-0', 'zz-1" onclick="x'] && (($padre = $estadisticas[array_key_first($estadisticas)]->parentNode) instanceof \DOMElement ? $padre->getAttribute('class') : '') === 'ui wide flex', 'c9 `statistics`: dos `ui statistic` en la misma tarjeta, dentro de un `ui wide flex`, con su `data-type` entero', implode(',', $valores));
    $check($dos->getElementsByTagName('script')->length === 0 && str_contains((string) $dos->textContent, "Uno {$marca}"), 'c10 DISCRIMINANTE: el rótulo de una cifra no crea una etiqueta');
    $url = 'http://localhost/zz?a=1&b="><img src=x onerror=zzMarca(1)>';
    $pie = $leer('panel/built-in/statistics/statistic-card', ['figure' => ['value' => '1'], 'footer' => ['caption' => "Ver {$marca}", 'url' => $url]]);
    $extra = $porClase($pie, 'extra')[0] ?? null;
    $enlacePie = $extra?->getElementsByTagName('a')->item(0);
    $check($enlacePie instanceof \DOMElement && $enlacePie->getAttribute('href') === $url && trim((string) $enlacePie->textContent) === "Ver {$marca}" && $pie->getElementsByTagName('img')->length === 0 && $pie->getElementsByTagName('script')->length === 0, 'c11 DISCRIMINANTE: el pie, un enlace de texto en `extra content` con la URL y el texto enteros, sin etiquetas inyectadas');
    $check($porClase($pie, 'button') === [] && $pie->getElementsByTagName('i')->length === 0, 'c12 el pie no lleva botón ni flecha');
    $sinPie = $leer('panel/built-in/statistics/statistic-card', ['figure' => ['value' => '1'], 'footer' => ['url' => 'zz']]);
    $check($porClase($sinPie, 'extra') === [], 'c13 un pie sin texto no se pinta');
    $acceso = $leer('panel/built-in/statistics/statistic-card', ['title' => 'Informe', 'help' => 'zz-ayuda', 'toolbarExtra' => '<button type="button">zz</button>', 'figure' => ['url' => $url, 'description' => 'Las cifras'], 'footer' => ['caption' => 'Ver el informe', 'url' => 'zz-otra']]);
    $raiz = $acceso->getElementsByTagName('a')->item(0);
    $check($raiz instanceof \DOMElement && $raiz->getAttribute('class') === 'link statistic card' && $raiz->getAttribute('href') === $url && $raiz->getAttribute('aria-label') === 'Informe: Las cifras' && $acceso->getElementsByTagName('a')->length === 1, 'c14 `url`: la tarjeta entera es el enlace, con su `aria-label`, y el pie no lleva otro dentro');
    $check($acceso->getElementsByTagName('button')->length === 0 && $acceso->getElementsByTagName('i')->length === 0 && array_filter($porClase($acceso, 'statistic'), fn ($n) => str_starts_with($n->getAttribute('class'), 'ui ')) === [], 'c15 el acceso directo, sin botones ni iconos ni cifra');
    $plegable = $leer('panel/built-in/statistics/statistic-card', ['title' => 'x', 'help' => 'zz-ayuda', 'toolbarExtra' => '<button type="button" class="ui right floated mini tertiary icon button" data-zz="1">zz</button>', 'figure' => ['statistics' => [], 'description' => 'Plegada']]);
    $contenido = $porClase($plegable, 'content')[0] ?? null;
    $esquina = $contenido instanceof \DOMElement ? array_filter(iterator_to_array($contenido->childNodes), fn ($n) => $n instanceof \DOMElement && $n->tagName === 'button' && array_diff(['right', 'floated', 'icon', 'button'], explode(' ', $n->getAttribute('class'))) === []) : [];
    $check(count($esquina) === 2 && $porClase($plegable, 'floated') !== [] && count($porClase($plegable, 'floated')) === 2, 'c16 ayuda y botón extra, hermanos e hijos directos de `.content` como `.right.floated.icon.button` (el estilo del tema)', (string) count($esquina));
    $hoja = basepath('statics/core/css/graphs-and-statistics.css');
    $css = is_file($hoja) ? (string) preg_replace('/\s+/', '', (string) file_get_contents($hoja)) : '';
    $scss = (string) preg_replace('/\s+/', '', (string) file_get_contents(basepath('statics/core/sass/includes/_graphs-and-statistics.scss')));
    $check(str_contains($scss, '.ui.cards>.statistic.card>.content>.right.floated.icon.button+.right.floated.icon.button,') && str_contains($css, '.ui.cards>.statistic.card>.content>.right.floated.icon.button+.right.floated.icon.button,') && str_contains($css, '.right.floated.icon.button+.right.floated.icon.button~.header'), 'c18 la hoja compilada corre el segundo botón de esquina y deja hueco al título (sin ella: gulp sass-compile-general)');
    $check($porClase($plegable, 'statistic') !== [] && array_filter($porClase($plegable, 'statistic'), fn ($n) => str_starts_with($n->getAttribute('class'), 'ui ')) === [] && str_contains((string) $plegable->textContent, 'Plegada'), 'c17 `statistics` vacío: la tarjeta del tema, sin ninguna cifra');
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
