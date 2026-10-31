<?php

//Con lang_by_url en false el prefijo de idioma es '' y el idioma nunca queda nulo (G22): '/' . null daba '/', las rutas
//salían en «//admin» y, con el navegador activo, el arranque reventaba. En proceso; repone la configuración.
//@areas nucleo

use PiecesPHP\Core\Config;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/lang-by-url-off', function ($args) {

    echoTerminal("\e[33m[TEST:LangByUrlOff] Con el idioma fuera de la URL, sin prefijo y con idioma\e[39m");
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

    $nombres = ['lang_by_url', 'lang_by_browser', 'lang_by_cookie'];
    $antes = array_combine($nombres, array_map(fn (string $n) => get_config($n), $nombres));
    $prefijoAntes = get_config('prefix_lang');
    $idiomaAntes = get_config('app_lang');
    $estrategia = function (bool $url, bool $navegador): array {
        set_config('lang_by_url', $url);
        set_config('lang_by_browser', $navegador);
        set_config('lang_by_cookie', false);
        Config::set_lang_strategy();
        return [get_config('prefix_lang'), get_config('app_lang')];
    };

    try {
        [$prefijo, $idioma] = $estrategia(false, true);
        $check($prefijo === '' && is_string($idioma) && $idioma !== '', 'l1 sin URL y con navegador: prefijo vacío y un idioma', var_export([$prefijo, $idioma], true));
        [$prefijo, $idioma] = $estrategia(false, false);
        $check($prefijo === '' && $idioma === Config::get_default_lang(), 'l2 sin URL ni navegador: prefijo vacío y el idioma por omisión', var_export([$prefijo, $idioma], true));
        [$prefijo, $idioma] = $estrategia(true, true);
        $check($prefijo === '' && $idioma === Config::get_default_lang(), 'l3 CANARIO: con la URL, en el terminal (sin idioma en la ruta), lo de siempre', var_export([$prefijo, $idioma], true));
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        foreach ($antes as $nombre => $valor) {
            set_config($nombre, $valor);
        }
        Config::set_lang_strategy();
        $check(get_config('prefix_lang') === $prefijoAntes && get_config('app_lang') === $idiomaAntes, 'z1 la configuración del idioma, como estaba');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Con lang_by_url en false el prefijo de idioma es vacío y el idioma no queda nulo. En proceso; repone la configuración.')->setEffects([CliActions::EFFECT_NONE])->register();
