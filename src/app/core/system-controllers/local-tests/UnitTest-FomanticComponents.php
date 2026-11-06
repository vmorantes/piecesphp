<?php

//import_fomantic_components() carga un componente del tema sin el Fomantic entero (P296): sus archivos y sus dependencias
//(site siempre, y las del mapa medido en el código del tema, provisional hasta H208), nada si el tema entero ya está, y un nombre que no existe no se ignora.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/fomantic-components', function ($args) {

    echoTerminal("\e[33m[TEST:FomanticComponents] Un componente del tema sin el Fomantic entero, con sus dependencias\e[39m");
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

    $savedGlobals = get_config('global_assets');
    $savedImported = get_config('imported_assets');
    $base = 'statics/plugins/fomantic-piecesphp/components/';
    //Cada caso parte de una página sin assets ni librerías importadas, y mide lo que añade.
    $empty = function (): void {
        set_config('global_assets', ['css' => [], 'js' => [], 'font' => []]);
        set_config('imported_assets', []);
    };
    $added = function (string $type): array {
        $globals = get_config('global_assets');
        return array_values(is_array($globals) && is_array($globals[$type] ?? null) ? $globals[$type] : []);
    };

    try {
        echoTerminal('[1/6] El mapa medido en el código del tema, en dos casos fijos');
        $empty();
        import_fomantic_components(['toast']);
        $check(in_array("{$base}transition.min.js", $added('js'), true) && in_array("{$base}icon.min.css", $added('css'), true), 'm1 el toast trae transition (su JS la llama) e icon (su marcado lo pinta)', json_encode([$added('css'), $added('js')]) ?: '');
        $empty();
        import_fomantic_components(['dropdown']);
        $js = $added('js');
        $at = fn(string $name): int|false => array_search("{$base}{$name}.min.js", $js, true);
        $check($at('api') !== false && $at('transition') !== false && $at('dropdown') !== false && $at('api') < $at('dropdown') && $at('transition') < $at('dropdown'), 'm2 el desplegable trae api y transition, y antes que él', json_encode($js) ?: '');
        echoTerminal(' ');

        echoTerminal('[2/6] Un componente con JS que anima y pinta iconos: toast');
        $empty();
        import_fomantic_components(['toast']);
        $css = $added('css');
        $js = $added('js');
        $check(($css[0] ?? '') === "{$base}site.min.css" && end($css) === "{$base}toast.min.css" && in_array("{$base}transition.min.css", $css, true) && in_array("{$base}icon.min.css", $css, true), 'a1 css: site primero, toast al final, y antes transition e icon', json_encode($css) ?: '');
        $check(($js[0] ?? '') === "{$base}site.min.js" && end($js) === "{$base}toast.min.js" && in_array("{$base}transition.min.js", $js, true), 'a2 js: site primero, toast al final, y antes transition', json_encode($js) ?: '');
        $existing = array_filter(array_merge($css, $js), fn(string $file): bool => is_file(basepath($file)));
        $check(count($existing) === count($css) + count($js), 'a3 todos los archivos añadidos existen', (count($css) + count($js) - count($existing)) . ' sin archivo');
        echoTerminal(' ');

        echoTerminal('[3/6] Transitivo: lo que pide una dependencia también va, y antes');
        $empty();
        import_fomantic_components(['modal']);
        $js = $added('js');
        $at = fn(string $name): int|false => array_search("{$base}{$name}.min.js", $js, true);
        //modal pide dimmer, y dimmer pide transition: transition antes que dimmer, y dimmer antes que modal.
        $check($at('transition') !== false && $at('dimmer') !== false && $at('modal') !== false && $at('transition') < $at('dimmer') && $at('dimmer') < $at('modal'), 'b0 transition, dimmer y modal, en ese orden', json_encode($js) ?: '');
        echoTerminal(' ');

        echoTerminal('[4/6] Un componente sin JS: button');
        $empty();
        import_fomantic_components(['button']);
        $check($added('css') === ["{$base}site.min.css", "{$base}button.min.css"], 'b1 css: site y button', json_encode($added('css')) ?: '');
        $check($added('js') === ["{$base}site.min.js"], 'b2 js: solo el de site (button no tiene)', json_encode($added('js')) ?: '');
        echoTerminal(' ');

        echoTerminal('[5/6] Un nombre que no existe');
        $empty();
        $message = '';
        try {
            import_fomantic_components(['toast', 'zz-no-existe']);
        } catch (\InvalidArgumentException $e) {
            $message = $e->getMessage();
        }
        $check(str_contains($message, 'zz-no-existe') && str_contains($message, 'no es un componente del tema'), 'c1 lanza InvalidArgumentException con el nombre', $message);
        $check($added('css') === [] && $added('js') === [], 'c2 y no añade nada, ni los buenos de la misma llamada', json_encode([$added('css'), $added('js')]) ?: '');
        echoTerminal(' ');

        echoTerminal('[6/6 a] Idempotencia');
        $empty();
        import_fomantic_components(['toast']);
        $first = [$added('css'), $added('js')];
        import_fomantic_components(['toast', 'transition']);
        $check([$added('css'), $added('js')] === $first, 'd1 dos llamadas (y una dependencia pedida aparte) no repiten nada', json_encode([$added('css'), $added('js')]) ?: '');
        echoTerminal(' ');

        echoTerminal('[6/6 b] Con el tema entero');
        $empty();
        import_fomantic();
        $library = get_config('default_assets')['fomantic-piecesphp'] ?? [];
        $imported = get_config('imported_assets');
        $check(is_array($imported) && array_keys($imported) === ['fomantic-piecesphp'], 'e1 import_fomantic() importa fomantic-piecesphp y ninguna librería más', json_encode(is_array($imported) ? array_keys($imported) : $imported) ?: '');
        $check($added('css') === ($library['css'] ?? null) && $added('js') === ($library['js'] ?? null), 'e2 y añade exactamente sus archivos de assets.php', json_encode([$added('css'), $added('js')]) ?: '');
        $before = [$added('css'), $added('js')];
        import_fomantic_components(['toast']);
        $check([$added('css'), $added('js')] === $before, 'e3 con el tema entero, import_fomantic_components() no añade nada', json_encode([$added('css'), $added('js')]) ?: '');
        echoTerminal(' ');

    } catch (\Throwable $e) {
        $check(false, 'x1 la suite terminó sin excepciones', get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
    } finally {
        set_config('global_assets', $savedGlobals);
        set_config('imported_assets', $savedImported);
    }

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('import_fomantic_components() carga un componente del tema con site y sus dependencias, sin el Fomantic entero (P296).')->setEffects([CliActions::EFFECT_NONE])->register();
