<?php

//El panel lleva <base href>: un «#ancla» suelto se resuelve contra la portada, y al recargar se sale de la página (H420).
//@areas nucleo

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/panel-anchors', function ($args) {

    echoTerminal("\e[33m[TEST:PanelAnchors] Las anclas del panel llevan la dirección de la página, no la de <base>\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}");
        }
        if ($detail !== '') {
            echoTerminal("      - {$detail}");
        }
    };

    echoTerminal('[1/2] section-nav.php pinta el href del ancla sobre la dirección de la página');
    $previoHost = $_SERVER['HTTP_HOST'] ?? null;
    $previoURI = $_SERVER['REQUEST_URI'] ?? null;
    $_SERVER['HTTP_HOST'] = 'zz-anclas.localhost';
    $_SERVER['REQUEST_URI'] = '/zz/panel/pagina/?zz=1';
    try {
        $html = (function (array $vars): string {
            extract($vars);
            ob_start();
            include basepath('app/view/panel/built-in/content/section-nav.php');
            return (string) ob_get_clean();
        })([
            'label' => 'Zz',
            'items' => [
                ['label' => 'Zz otra', 'href' => 'zz/otra/'],
                ['label' => 'Zz sección', 'anchor' => 'zz-seccion'],
            ],
        ]);
    } finally {
        $_SERVER['HTTP_HOST'] = $previoHost;
        $_SERVER['REQUEST_URI'] = $previoURI;
    }
    $pagina = '/zz/panel/pagina/?zz=1';
    $check(str_contains($html, 'href="' . escape_html($pagina . '#zz-seccion') . '" data-section-nav-anchor="zz-seccion"') && !str_contains($html, 'zz-anclas.localhost'),
        'a1 DISCRIMINANTE: el enlace del ancla lleva la ruta de la página (sin esquema ni host) y el fragmento', 'esperado: ' . $pagina . '#zz-seccion');
    //Una petición que empiece por «//» no puede volverse un enlace a otro host.
    $_SERVER['REQUEST_URI'] = '//zz-otro.example/zz/?a=1';
    try {
        $htmlDoble = (function (array $vars): string {
            extract($vars);
            ob_start();
            include basepath('app/view/panel/built-in/content/section-nav.php');
            return (string) ob_get_clean();
        })(['label' => 'Zz', 'items' => [['label' => 'Zz sección', 'anchor' => 'zz-seccion']]]);
    } finally {
        $_SERVER['REQUEST_URI'] = $previoURI;
    }
    $check(str_contains($htmlDoble, 'href="' . escape_html('/zz-otro.example/zz/?a=1#zz-seccion') . '"') && !str_contains($htmlDoble, 'href="//'),
        'a5 DISCRIMINANTE: una ruta que empieza por «//» queda con una sola «/» (no es un enlace a otro host)');
    $check(!str_contains($html, 'href="#zz-seccion"'), 'a2 ningún href="#…" suelto');
    $check(str_contains($html, 'href="zz/otra/"'), 'a3 un enlace a otra página se queda como venía');
    $check(str_contains($html, '<option value="#zz-seccion"'), 'a4 el desplegable de salto conserva «#ancla»: lo lee el JS, no navega');
    echoTerminal(' ');

    echoTerminal('[2/2] El JS del panel no escribe «#…» suelto en la dirección');
    $raices = [basepath('statics/admin-area/js'), basepath('app/classes')];
    $vistos = 0;
    $sueltos = [];
    foreach ($raices as $raiz) {
        $iterador = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterador as $archivo) {
            $ruta = (string) $archivo;
            if (!str_ends_with($ruta, '.js') || str_ends_with($ruta, '.min.js') || !str_contains($ruta, '/js')) {
                continue;
            }
            $vistos++;
            $fuente = (string) file_get_contents($ruta);
            if (preg_match('/(?:replaceState|pushState)\s*\([^)]*[\'"`]#|location\.hash\s*=[^=]/', $fuente) === 1) {
                $sueltos[] = str_replace(basepath(), '', $ruta);
            }
        }
    }
    //Canario: si el universo se vacía, la comprobación pasaría sin mirar nada.
    $check($vistos > 50 && str_contains((string) file_get_contents(basepath('statics/admin-area/js/main.js')), 'replaceState'),
        'b1 el censo ve el JS del panel (y main.js, que reescribe la dirección)', "{$vistos} archivos .js");
    $check($sueltos === [], 'b2 ningún replaceState/pushState con «#…» suelto ni location.hash asignado', implode(', ', $sueltos));
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Las anclas del panel (section-nav.php y el JS que reescribe la dirección) llevan la dirección de la página, no la de <base href>.')->setEffects([CliActions::EFFECT_NONE])->register();
