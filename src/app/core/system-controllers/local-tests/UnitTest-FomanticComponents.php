<?php

//import_fomantic_components() carga un componente sin el Fomantic entero (P296) con el mapa del tema (H208): tokens y base-ui
//en lugar de reset y site, opcionales a petición, jQuery solo con JS, nada con el tema entero; nombre o mapa malos, lanzan.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/fomantic-components', function ($args) {

    echoTerminal("\e[33m[TEST:FomanticComponents] Un componente del tema sin el Fomantic entero, con el mapa del tema\e[39m");
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
    $savedMap = get_config('fomantic_components_map');
    $base = 'statics/plugins/fomantic-piecesphp/components/';
    $suelta = ["{$base}tokens.css", "{$base}base-ui.css"];
    $jquery = get_config('default_assets')['jquery']['js'] ?? [];
    //Copias del mapa rotas para f1, en una carpeta ignorada por git; se borran en el finally.
    $brokenFiles = [
        'invalid' => 'app/cache/zz-prueba-fomantic-map-invalido.json',
        'format' => 'app/cache/zz-prueba-fomantic-map-formato.json',
        'traversal' => 'app/cache/zz-prueba-fomantic-map-ruta.json',
        'nocargar' => 'app/cache/zz-prueba-fomantic-map-sin-cargar.json',
    ];
    //Cada caso parte de una página sin assets ni librerías importadas, y mide lo que añade.
    $empty = function (): void {
        set_config('global_assets', ['css' => [], 'js' => [], 'font' => []]);
        set_config('imported_assets', []);
    };
    $added = function (string $type): array {
        $globals = get_config('global_assets');
        return array_values(is_array($globals) && is_array($globals[$type] ?? null) ? $globals[$type] : []);
    };
    $show = fn(): string => json_encode([$added('css'), $added('js')], JSON_UNESCAPED_SLASHES) ?: '';
    $hasBase = fn(): bool => array_filter(array_merge($added('css'), $added('js')), fn(string $file): bool => in_array(basename($file), ['reset.css', 'reset.min.css', 'site.css', 'site.min.css', 'site.js', 'site.min.js'], true)) !== [];

    try {
        echoTerminal('[1/7] toast: tokens y base-ui, su cargar del mapa, jQuery delante, ni reset ni site');
        $check($jquery !== [], 'm0 assets.php declara el JS de jquery', json_encode($jquery) ?: '');
        $empty();
        import_fomantic_components(['toast']);
        $check($added('css') === array_merge($suelta, ["{$base}transition.min.css", "{$base}toast.min.css"]), 'm1a css: tokens, base-ui, transition y toast, exactamente', $show());
        $check($added('js') === array_merge($jquery, ["{$base}transition.min.js", "{$base}toast.min.js"]), 'm1b js: jquery, transition y toast, exactamente', $show());
        $check(!$hasBase(), 'm1c ni reset ni site en ninguna lista', $show());
        $all = array_merge($added('css'), $added('js'));
        $existing = array_filter($all, fn(string $file): bool => is_file(basepath($file)));
        $check(count($existing) === count($all), 'a3 todos los archivos añadidos existen', (count($all) - count($existing)) . ' sin archivo');
        echoTerminal(' ');

        echoTerminal('[2/7] Los opcionales, solo si se piden: dropdown y api');
        $empty();
        import_fomantic_components(['dropdown']);
        $check(!in_array("{$base}api.min.js", $added('js'), true) && in_array("{$base}dropdown.min.js", $added('js'), true), 'm2 dropdown solo: dropdown.min.js sin api.min.js', $show());
        $empty();
        import_fomantic_components(['dropdown', 'api']);
        $check(in_array("{$base}api.min.js", $added('js'), true) && in_array("{$base}dropdown.min.js", $added('js'), true), 'm3 [dropdown, api]: los dos', $show());
        echoTerminal(' ');

        echoTerminal('[3/7] Transitivo, resuelto por el tema: modal');
        $empty();
        import_fomantic_components(['modal']);
        $js = $added('js');
        $at = fn(string $name): int|false => array_search("{$base}{$name}.min.js", $js, true);
        $check($at('transition') !== false && $at('dimmer') !== false && $at('modal') !== false && $at('transition') < $at('modal') && $at('dimmer') < $at('modal'), 'b0 transition y dimmer antes que modal', json_encode($js, JSON_UNESCAPED_SLASHES) ?: '');
        echoTerminal(' ');

        echoTerminal('[4/7] Un componente sin JS: button');
        $empty();
        import_fomantic_components(['button']);
        $check($added('css') === array_merge($suelta, ["{$base}button.min.css"]), 'b1 css: tokens, base-ui y button', $show());
        $check($added('js') === [], 'b2 js vacío: ni jQuery', $show());
        echoTerminal(' ');

        echoTerminal('[5/7] Nombres que no son componentes del tema');
        foreach (['c1' => ['toast', 'zz-no-existe'], 'c3' => ['toast', '../x']] as $case => $names) {
            $empty();
            $message = '';
            try {
                import_fomantic_components($names);
            } catch (\InvalidArgumentException $e) {
                $message = $e->getMessage();
            }
            $bad = end($names);
            $check(str_contains($message, $bad) && str_contains($message, 'no es un componente del tema'), "{$case} «{$bad}» lanza InvalidArgumentException con el nombre", $message);
            $check($added('css') === [] && $added('js') === [], "{$case}b y no añade nada, ni los buenos de la misma llamada", $show());
        }
        echoTerminal(' ');

        echoTerminal('[6/7] El mapa falta o no vale: falla cerrada');
        $mapOf = fn(int $format, array $css): string => json_encode(['formato' => $format, 'base' => ['css' => ['reset', 'site'], 'suelta' => []], 'componentes' => ['toast' => ['cargar' => ['css' => $css, 'js' => []]]]]) ?: '';
        $written = [
            file_put_contents(basepath($brokenFiles['invalid']), '{"formato": 1, "componentes": '),
            file_put_contents(basepath($brokenFiles['format']), $mapOf(2, ['toast'])),
            file_put_contents(basepath($brokenFiles['traversal']), $mapOf(1, ['toast', '../../x'])),
            file_put_contents(basepath($brokenFiles['nocargar']), json_encode(['formato' => 1, 'base' => ['css' => ['reset', 'site'], 'suelta' => []], 'componentes' => ['toast' => ['depende' => []]]]) ?: ''),
        ];
        //Sin las copias, f1b-f1d caerían en el caso «falta» y pasarían sin probar lo suyo.
        $check(!in_array(false, $written, true), 'f0 las copias rotas del mapa se escribieron en app/cache/', json_encode($written) ?: '');
        foreach (['f1a falta' => 'app/cache/zz-prueba-fomantic-map-no-existe.json', 'f1b no es JSON' => $brokenFiles['invalid'], 'f1c formato 2' => $brokenFiles['format'], 'f1d el mapa nombra ../../x' => $brokenFiles['traversal'], 'f1e DISCRIMINANTE: el mapa tiene toast sin su cargar (mapa roto, no «no es un componente»)' => $brokenFiles['nocargar']] as $case => $path) {
            $empty();
            set_config('fomantic_components_map', $path);
            $message = '';
            try {
                import_fomantic_components(['toast']);
            } catch (\RuntimeException $e) {
                $message = $e->getMessage();
            }
            $check(str_contains($message, $path) && $added('css') === [] && $added('js') === [], "{$case}: RuntimeException con la ruta y nada añadido", $message . ' ' . $show());
        }
        set_config('fomantic_components_map', $savedMap);
        echoTerminal(' ');

        echoTerminal('[7/7 a] Idempotencia');
        $empty();
        import_fomantic_components(['toast']);
        $first = [$added('css'), $added('js')];
        import_fomantic_components(['toast', 'transition']);
        $check([$added('css'), $added('js')] === $first, 'd1 dos llamadas (y una dependencia pedida aparte) no repiten nada', $show());
        echoTerminal(' ');

        echoTerminal('[7/7 b] Con el tema entero');
        $empty();
        import_fomantic();
        $library = get_config('default_assets')['fomantic-piecesphp'] ?? [];
        $imported = get_config('imported_assets');
        $check(is_array($imported) && array_keys($imported) === ['fomantic-piecesphp'], 'e1 import_fomantic() importa fomantic-piecesphp y ninguna librería más', json_encode(is_array($imported) ? array_keys($imported) : $imported) ?: '');
        $check($added('css') === ($library['css'] ?? null) && $added('js') === ($library['js'] ?? null), 'e2 y añade exactamente sus archivos de assets.php', $show());
        $before = [$added('css'), $added('js')];
        import_fomantic_components(['toast']);
        $check([$added('css'), $added('js')] === $before, 'e3 con el tema entero, import_fomantic_components() no añade nada', $show());
        echoTerminal(' ');

    } catch (\Throwable $e) {
        $check(false, 'x1 la suite terminó sin excepciones', get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
    } finally {
        set_config('global_assets', $savedGlobals);
        set_config('imported_assets', $savedImported);
        set_config('fomantic_components_map', $savedMap);
        foreach ($brokenFiles as $relative) {
            if (is_file(basepath($relative))) {
                unlink(basepath($relative)); //RETORNO-IGNORADO: app/cache/ está ignorado por git y un resto zz-prueba- no altera otra suite
            }
        }
    }

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('import_fomantic_components() carga un componente del tema con el mapa que distribuye el tema (tokens y base-ui, su cargar, opcionales a petición), sin el Fomantic entero (P296, H208).')->setEffects([CliActions::EFFECT_FILES])->register();
