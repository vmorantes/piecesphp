<?php

//La tarjeta de imagen, pintada de verdad: escapa lo que recibe, la variante vacía no lleva Descargar y el JavaScript
//encuentra sus `data-*`. No escribe en ningún sitio.
//@areas nucleo
//@areas src/app/classes/PiecesPHP/Settings/

use PiecesPHP\Core\BaseController;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/image-card', function ($args) {

    echoTerminal('[TEST:ImageCard] Iniciando suite...', true, "\r\n", '33');
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

    $pintar = function (array $datos): string {
        return (string) (new BaseController())->render('panel/built-in/content/image-card', $datos, false);
    };

    $marca = '<script>zzMarca(1)</script>';
    $comillas = '"><img src=x onerror=zzMarca(1)>';
    $html = $pintar([
        'id' => 'zz-tarjeta',
        'title' => "Título {$marca}",
        'description' => "Descripción {$marca}",
        'hint' => "Ayuda {$marca}",
        'image' => "statics/images/zz.png{$comillas}",
        'imageAlt' => "Alternativo {$comillas}",
        'accept' => "image/*{$comillas}",
        'field' => "campo{$comillas}",
        'action' => "http://localhost/zz{$comillas}",
        'fillColor' => "white{$comillas}",
        'width' => 400,
        'height' => 300,
    ]);

    echoTerminal('[0/4] Lo que recibe, escapado');
    $check($html !== '' && str_contains($html, 'data-image-card'), 'a0 CANARIO: la pieza se pinta', mb_substr($html, 0, 200));
    $check(str_contains($html, '&lt;script&gt;zzMarca(1)&lt;/script&gt;'), 'a1 CANARIO: la marca del texto llega a la salida, escapada');
    //Con un analizador y nunca por texto: render() reformatea el HTML que devuelve, y un literal no casaría aunque saliera crudo.
    $dom = new \DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $tarjeta = $dom->getElementsByTagName('article')->item(0);
    $imagenes = $dom->getElementsByTagName('img');
    $selector = $dom->getElementsByTagName('input')->item(0);
    $check($dom->getElementsByTagName('script')->length === 0, 'a2 DISCRIMINANTE: título, descripción y ayuda no crean ninguna etiqueta');
    $check($tarjeta?->getAttribute('data-title') === "Título {$marca}", 'a3 DISCRIMINANTE: el título, entero dentro de su atributo', (string) $tarjeta?->getAttribute('data-title'));
    foreach (['data-field' => "campo{$comillas}", 'data-action' => "http://localhost/zz{$comillas}", 'data-fill-color' => "white{$comillas}", 'data-accept' => "image/*{$comillas}"] as $atributo => $valor) {
        $check($tarjeta?->getAttribute($atributo) === $valor, "a4 DISCRIMINANTE: {$atributo}, entero dentro de su atributo", (string) $tarjeta?->getAttribute($atributo));
    }
    $imagen = $imagenes->item(0);
    $check($imagenes->length === 1 && $imagen instanceof \DOMElement && !$imagen->hasAttribute('onerror'), 'a5 DISCRIMINANTE: una sola img y sin onerror', (string) $imagenes->length);
    $check($imagen instanceof \DOMElement && $imagen->getAttribute('alt') === "Alternativo {$comillas}" && str_contains($imagen->getAttribute('src'), $comillas), 'a6 la URL y el texto alternativo, enteros en sus atributos');
    $check($selector?->getAttribute('accept') === "image/*{$comillas}", 'a7 el accept del selector');
    echoTerminal(' ');

    echoTerminal('[1/4] Lo que necesita el JavaScript');
    foreach (['data-image-card', 'data-width="400"', 'data-height="300"', 'data-field=', 'data-action=', 'data-format="image/png"', 'data-quality="0.8"', 'data-fill-color=', 'data-title=', 'data-accept='] as $dato) {
        $check(str_contains($html, $dato), "d1 la tarjeta lleva {$dato}");
    }
    $check(str_contains($html, '400 × 300 px'), 'd2 la medida, con ×');
    $check(str_contains($html, '<button class="mobile stretched ui basic labeled icon button" type="button" data-input="zz-tarjeta-file">'), 'd3 Cambiar imagen es un botón real');
    $check(preg_match('/<input type="file" id="zz-tarjeta-file"[^>]* hidden>/', $html) === 1 && !str_contains($html, ' name='), 'd4 el selector va oculto y sin name: en un formulario no se envía solo');
    $check(str_contains($html, '<h3 class="ui medium header"'), 'd5 el título, h3 por omisión');
    $h2 = $pintar(['id' => 'zz-h2', 'title' => 'x', 'headingTag' => 'h2', 'width' => 1, 'height' => 1]);
    $check(str_contains($h2, '<h2 class="ui medium header"') && str_contains($h2, '</h2>'), 'd6 y h2 si la vista lo pide');
    $check(!str_contains($pintar(['id' => 'zz', 'title' => 'x', 'headingTag' => 'script', 'width' => 1, 'height' => 1]), '<script'), 'd7 ninguna otra etiqueta');
    $formato = $pintar(['id' => 'zz-jpg', 'title' => 'x', 'format' => 'image/jpeg', 'quality' => 0.5, 'width' => 1, 'height' => 1]);
    $check(str_contains($formato, 'data-format="image/jpeg"') && str_contains($formato, 'data-quality="0.5"'), 'd8 el formato y la calidad que se pidan');
    $check(str_contains($pintar(['id' => 'zz-otro', 'title' => 'x', 'format' => 'text/html', 'width' => 1, 'height' => 1]), 'data-format="image/png"'), 'd9 un formato desconocido cae en PNG');
    echoTerminal(' ');

    echoTerminal('[2/4] Las variantes');
    $vacia = $pintar(['id' => 'zz-vacia', 'title' => 'x', 'width' => 1, 'height' => 1]);
    $sinPlantilla = (string) preg_replace('/<template.*?<\/template>/s', '', $vacia);
    $check(!str_contains($vacia, '<img'), 'v1 sin imagen no hay img');
    $check(!str_contains($sinPlantilla, 'data-image-card-download'), 'v2 DISCRIMINANTE: sin imagen, Descargar no se ve');
    $check(str_contains($vacia, 'data-image-card-download-template'), 'v3 y queda su plantilla para cuando se aplique una');
    $check(str_contains($vacia, 'Cambiar imagen') && str_contains($vacia, 'class="checkered image"'), 'v4 el mismo Cambiar imagen y el mismo damero');
    $check(str_contains($html, ' download ') && !str_contains($html, '<template'), 'v5 con imagen, Descargar y sin plantilla');
    $check(str_contains($pintar(['id' => 'zz-oscura', 'title' => 'x', 'dark' => true, 'width' => 1, 'height' => 1]), 'class="inverted checkered image"'), 'v6 la variante oscura');
    echoTerminal(' ');

    $conImagen = new \DOMDocument();
    @$conImagen->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $control = (new \DOMXPath($conImagen))->query('//button[@data-image-card-edit]')?->item(0);
    $check($control instanceof \DOMElement && str_contains($control->getAttribute('aria-label'), "Título {$marca}"), 'v7 DISCRIMINANTE: con imagen, un botón real con su aria-label entero, y la imagen dentro');
    $check($control instanceof \DOMElement && $control->getAttribute('type') === 'button' && $control->getElementsByTagName('img')->length === 1, 'v8 no envía un formulario: type=button');
    $check(!str_contains($vacia, 'data-image-card-edit'), 'v9 la tarjeta vacía no lo lleva');
    $check(str_contains($vacia, 'data-edit-label='), 'v10 pero lleva el texto para cuando se llene');
    $check(!str_contains($vacia, 'data-image-card-empty'), 'v15 DISCRIMINANTE: sin emptyText, la vacía no lleva texto (el valor por omisión no cambia)');
    $conTexto = $pintar(['id' => 'zz-sin', 'title' => 'x', 'emptyText' => "Sin foto {$marca}", 'width' => 1, 'height' => 1]);
    $check(str_contains($conTexto, 'data-image-card-empty') && str_contains($conTexto, '&lt;script&gt;'), 'v16 con emptyText, el texto sobre el damero, escapado');
    $check(!str_contains($pintar(['id' => 'zz-con', 'title' => 'x', 'image' => 'statics/zz.jpg', 'emptyText' => 'Sin foto', 'width' => 1, 'height' => 1]), 'data-image-card-empty'), 'v17 y con imagen no sale');
    $check(!str_contains($html, 'data-image-card-delete'), 'v11 DISCRIMINANTE: sin deleteValue no hay Eliminar');
    $borrable = new \DOMDocument();
    @$borrable->loadHTML('<?xml encoding="utf-8" ?>' . $pintar(['id' => 'zz-b', 'title' => 'x', 'image' => 'statics/zz.jpg', 'deleteValue' => "statics/zz.jpg{$comillas}", 'width' => 1, 'height' => 1]));
    $botonBorrar = (new \DOMXPath($borrable))->query('//button[@data-image-card-delete]')?->item(0);
    $check($botonBorrar instanceof \DOMElement && $botonBorrar->getAttribute('data-value') === "statics/zz.jpg{$comillas}" && !$botonBorrar->hasAttribute('disabled') && $botonBorrar->getAttribute('type') === 'button', 'v12 con deleteValue, Eliminar con su valor entero en data-value, habilitado');
    $sinImagenBorrable = $pintar(['id' => 'zz-bv', 'title' => 'x', 'deleteValue' => '', 'width' => 1, 'height' => 1]);
    $check(preg_match('/<div data-image-card-delete-holder[^>]* hidden>/', $sinImagenBorrable) === 1, 'v13 sin imagen, Eliminar está pero oculto');
    $soloUna = new \DOMDocument();
    @$soloUna->loadHTML('<?xml encoding="utf-8" ?>' . $pintar(['id' => 'zz-bd', 'title' => 'x', 'image' => 'statics/zz.jpg', 'deleteValue' => 'statics/zz.jpg', 'deleteDisabledReason' => "Motivo {$comillas}", 'width' => 1, 'height' => 1]));
    $holder = (new \DOMXPath($soloUna))->query('//div[@data-image-card-delete-holder]')?->item(0);
    $check($holder instanceof \DOMElement && $holder->getAttribute('data-tooltip') === "Motivo {$comillas}" && $holder->getElementsByTagName('button')->item(0)?->hasAttribute('disabled') === true, 'v14 con motivo, deshabilitado y el envoltorio lo explica en su tooltip');
    echoTerminal(' ');

    echoTerminal('[3/4] El editor');
    $editor = (string) (new BaseController())->render('panel/built-in/content/image-editor', [], false);
    $check(str_contains($editor, 'class="ui fullscreen modal"') && str_contains($editor, 'data-image-editor=""'), 'e1 un ui fullscreen modal');
    //El menú de usuario lleva el suyo en todas las páginas: sin alcance propio chocaría con el de la página.
    $conAlcance = (string) (new BaseController())->render('panel/built-in/content/image-editor', ['scope' => 'zz-alcance'], false);
    $check(str_contains($conAlcance, 'data-image-editor="zz-alcance"') && str_contains($conAlcance, 'id="image-editor-zz-alcance"') && !str_contains($conAlcance, 'id="image-editor"'), 'e7 con alcance, su propio gancho y sus propios id');
    $tarjetaConAlcance = (string) (new BaseController())->render('panel/built-in/content/image-card', ['id' => 'zz-c', 'field' => 'f', 'scope' => 'zz-alcance'], false);
    $check(str_contains($tarjetaConAlcance, 'data-image-card="zz-alcance"'), 'e8 la tarjeta lleva el alcance de su editor');
    //El alta exige la imagen: el rechazo es el del navegador sobre el input, y al aplicar deja de pedirla.
    $obligatoria = new \DOMDocument();
    @$obligatoria->loadHTML('<?xml encoding="utf-8" ?>' . (string) (new BaseController())->render('panel/built-in/content/image-field', ['card' => ['id' => 'zz-r', 'title' => "Título {$marca}", 'field' => 'f', 'required' => true]], false));
    $xpath = new \DOMXPath($obligatoria);
    $check($xpath->query('//input[@type="file"][@required]')?->length === 1 && $xpath->query('//div[contains(@class,"required")][contains(@class,"field")]/label[@hidden]')?->length === 1, 'e9 obligatoria: input con required, y un .field con label oculta para el nombre y el error');
    $opcional = (string) (new BaseController())->render('panel/built-in/content/image-field', ['card' => ['id' => 'zz-o', 'title' => 'x', 'field' => 'f']], false);
    $check(!str_contains($opcional, ' required'), 'e10 DISCRIMINANTE: sin required, ni el input ni el .field la piden');
    //Sin `stacked`, el marcado de siempre: los usos que no lo piden no cambian.
    $campo = fn(array $extra): string => (string) (new BaseController())->render('panel/built-in/content/image-field', ['card' => ['id' => 'zz-h', 'title' => 'x', 'field' => 'f']] + $extra, false);
    $horizontal = $campo([]);
    $apilada = $campo(['stacked' => true]);
    $check(str_contains($horizontal, '<div class="ui one stackable horizontal cards">') && $campo(['stacked' => false]) === $horizontal, 'e13 sin stacked o con false, la horizontal de siempre');
    $check(str_contains($apilada, '<div class="ui one cards">') && !str_contains($apilada, 'horizontal'), 'e14 DISCRIMINANTE: con stacked, apilada: sin horizontal');
    $check(str_replace('<div class="ui one cards">', '<div class="ui one stackable horizontal cards">', $apilada) === $horizontal, 'e15 y no cambia nada más');
    foreach (['replaced', 'replaced-detail', 'added', 'added-detail', 'error', 'error-detail', 'unreadable', 'unreadable-detail', 'export'] as $texto) {
        $check(preg_match('/data-text-' . $texto . '="[^"]+"/', $editor) === 1, "e2 trae el texto {$texto}");
    }
    $check(str_contains($editor, '${size}'), 'e3 el texto de la exportación deja hueco a la medida');
    foreach (['zoom-in', 'zoom-out', 'rotate-left', 'rotate-right', 'flip-horizontal', 'flip-vertical', 'reset'] as $accion) {
        $check(str_contains($editor, 'data-image-editor-action="' . $accion . '"'), "e4 trae {$accion}");
    }
    $js = (string) file_get_contents(basepath('statics/core/own-plugins/ContentImageEditor.js'));
    $sinComentarios = (string) preg_replace('/\/\*.*?\*\/|^\s*\/\/.*$/ms', '', $js);
    $check(preg_match('/[áéíóúñÁÉÍÓÚÑ]/u', $sinComentarios) !== 1, 'e5 el JavaScript no lleva textos en español');
    $check(str_contains($js, ".required = false"), 'e11 al aplicar la imagen, el input deja de ser obligatorio');
    $check(str_contains($js, "querySelector('[data-image-card-empty]')"), 'e12 al aplicar la imagen, el texto de la vacía se quita');
    $check(str_contains($js, 'cropBoxMovable: false') && str_contains($js, 'cropBoxResizable: false'), 'e6 el recuadro es fijo');
    echoTerminal(' ');

    echoTerminal('[4/4] La lista de fondos que recibe la imagen de una tarjeta');
    $carpeta = 'statics/login-and-recovery/images/login';
    $lista = ["{$carpeta}/bg1.jpg", "{$carpeta}/bg2.jpg", "{$carpeta}/bg3.jpg"];
    $check(SettingsController::backgroundsWithImage($lista, 'bg2', "{$carpeta}/bg2.webp") === ["{$carpeta}/bg1.jpg", "{$carpeta}/bg2.webp", "{$carpeta}/bg3.jpg"], 'f1 sustituye la suya, en su sitio');
    $check(SettingsController::backgroundsWithImage($lista, 'bg5', "{$carpeta}/bg5.jpg") === [...$lista, "{$carpeta}/bg5.jpg"], 'f2 DISCRIMINANTE: la que falta se añade, y los demás no cambian');
    $check(SettingsController::backgroundsWithImage([], 'bg1', "{$carpeta}/bg1.jpg") === ["{$carpeta}/bg1.jpg"], 'f3 una lista vacía recibe la suya');
    $mismo = SettingsController::backgroundsWithImage($lista, 'bg1', "{$carpeta}/bg1.jpg");
    $check($mismo === $lista, 'f4 el mismo archivo no duplica ni cambia la lista');
    $check(array_is_list(SettingsController::backgroundsWithImage([3 => "{$carpeta}/bg1.jpg", 7 => "{$carpeta}/bg2.jpg"], 'bg3', "{$carpeta}/bg3.jpg")), 'f5 sale una lista: en JSON sigue siendo un arreglo');
    $check(SettingsController::backgroundsWithImage([null, "{$carpeta}/bg1.jpg"], 'bg1', "{$carpeta}/bg1.webp") === [null, "{$carpeta}/bg1.webp"], 'f6 un valor que no es texto no la tumba');
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:ImageCard] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? 'Suite ejecutada correctamente.'
            : "Suite completada con {$failed} prueba(s) fallida(s).",
    ];

})->setDescription('La tarjeta de imagen y su editor: escapan lo que reciben, las variantes y los data-* que usa el JavaScript. No escribe.')->setEffects([CliActions::EFFECT_NONE])->register();
