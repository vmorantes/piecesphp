<?php

//import_front_library() quita al reimportar los archivos de los plugins que salen: si quita uno que otro plugin importado
//sigue pidiendo, la página pierde esa hoja (P173: las páginas de acceso perdían ui-pcs.css).

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/import-front-library', function ($args) {

    echoTerminal("\e[33m[TEST:ImportFrontLibrary] Reimportar una librería no quita lo que otro plugin importado sigue pidiendo\e[39m");
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

    //Los archivos de verdad del panel: el compartido (ui-pcs.css) y el exclusivo de adminStyle (admin_app_base.css).
    $adminStyle = get_config('default_assets')['app_libraries']['plugins']['adminStyle']['css'] ?? [];
    $shared = 'statics/core/css/ui-pcs.css';
    $exclusive = 'statics/core/css/admin_app_base.css';
    $expected = in_array($shared, $adminStyle, true) && in_array($exclusive, $adminStyle, true);
    $check($expected, 'p0 adminStyle trae ui-pcs.css y admin_app_base.css', json_encode($adminStyle) ?: '');
    if (!$expected) {
        echoTerminal("\e[31m BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => false, 'message' => 'adminStyle no es el esperado'];
    }

    //Librerías propias de la suite (la guarda vale para cualquier librería), y todo se restaura al final.
    $savedDefaults = get_config('default_assets');
    $savedGlobals = get_config('global_assets');
    $savedImported = get_config('imported_assets');
    $defaults = $savedDefaults;
    $defaults['zz-gates-panel'] = ['plugins' => [
        'wide' => ['css' => $adminStyle],
        'base' => ['css' => [$shared]],
    ]];
    $defaults['zz-gates-other'] = ['plugins' => [
        'base' => ['css' => [$shared]],
    ]];
    $reset = function () use ($defaults, $savedGlobals, $savedImported): void {
        set_config('default_assets', $defaults);
        $globals = is_array($savedGlobals) ? $savedGlobals : [];
        foreach (['css', 'js', 'font'] as $type) {
            $globals[$type] = array_values(array_filter($globals[$type] ?? [], fn($file) => !in_array($file, ['statics/core/css/ui-pcs.css', 'statics/core/css/admin_app_base.css'], true)));
        }
        set_config('global_assets', $globals);
        $imported = is_array($savedImported) ? $savedImported : [];
        unset($imported['zz-gates-panel'], $imported['zz-gates-other']);
        set_config('imported_assets', $imported);
    };
    $has = fn(string $file): bool => has_global_asset($file, 'css') !== false;
    $count = fn(string $file): int => count(array_filter(get_config('global_assets')['css'] ?? [], fn($item) => $item === $file));

    try {

        echoTerminal('[1/3] Del panel a la base');
        $reset();
        import_front_library('zz-gates-panel', ['wide']);
        import_front_library('zz-gates-panel', ['base']);
        $check($has($shared), 'a1 ui-pcs.css SIGUE en los globales: lo pide el plugin que queda');
        $check(!$has($exclusive), 'a2 admin_app_base.css NO: era exclusivo del que sale');
        echoTerminal(' ');

        echoTerminal('[2/3] De la base al panel, y la misma lista dos veces');
        $reset();
        import_front_library('zz-gates-panel', ['base']);
        import_front_library('zz-gates-panel', ['wide']);
        $check($has($shared) && $has($exclusive), 'b1 el orden inverso deja los dos');
        $reset();
        import_front_library('zz-gates-panel', ['wide']);
        import_front_library('zz-gates-panel', ['wide']);
        $check($count($shared) === 1 && $count($exclusive) === 1, 'c1 importar dos veces la misma lista no duplica ningún archivo', "{$count($shared)} y {$count($exclusive)}");
        echoTerminal(' ');

        echoTerminal('[3/3] Otra librería que pide el mismo archivo');
        $reset();
        import_front_library('zz-gates-other', ['base']);
        import_front_library('zz-gates-panel', ['wide']);
        import_front_library('zz-gates-panel', []);
        $check($has($shared), 'd1 ui-pcs.css sigue: lo pide un plugin de OTRA librería');
        $check(!$has($exclusive), 'd2 y el exclusivo se quita');
        echoTerminal(' ');

    } catch (\Throwable $e) {
        $check(false, 'x1 la suite terminó sin excepciones', get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
    } finally {
        set_config('default_assets', $savedDefaults);
        set_config('global_assets', $savedGlobals);
        set_config('imported_assets', $savedImported);
    }

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Reimportar una librería con otra lista no quita un archivo que otro plugin importado sigue pidiendo (P173).')->setEffects([CliActions::EFFECT_NONE])->register();
