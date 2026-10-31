<?php

//addStaticVersions() no toma por fragmento el # de una entidad numérica (G7-20): l&#039;img.png lleva su versión al final
//del nombre, no partido en «l&?cacheStamp=…#039;». En proceso, sobre texto; no escribe nada.
//@areas nucleo

use PiecesPHP\Core\BaseController;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/static-versions-entities', function ($args) {

    echoTerminal("\e[33m[TEST:StaticVersionsEntities] La versión de un estático y el # de una entidad\e[39m");
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

    $marcar = new \ReflectionMethod(BaseController::class, 'addStaticVersions');
    $salida = fn (string $html): string => (string) $marcar->invoke(null, $html);
    $version = '(?:[0-9a-f]{16}|[0-9a-f]{40}|[A-Za-z0-9]+)';

    $entidad = $salida('<img src="statics/images/zz-l&#039;img.png">');
    $check(preg_match('#src="statics/images/zz-l&\#039;img\.png\?cacheStamp=' . $version . '"#', $entidad) === 1 && !str_contains($entidad, 'zz-l&?cacheStamp'), 'e1 con &#039; en el nombre, la versión va al final, no partida', $entidad);
    $fragmento = $salida('<img src="statics/images/zz-a.png#zz-frag">');
    $check(preg_match('#src="statics/images/zz-a\.png\?cacheStamp=' . $version . '\#zz-frag"#', $fragmento) === 1, 'e2 CANARIO: un fragmento de verdad sigue detrás de la versión', $fragmento);
    $ambos = $salida('<img src="statics/images/zz-l&#039;img.png#zz-frag">');
    $check(preg_match('#src="statics/images/zz-l&\#039;img\.png\?cacheStamp=' . $version . '\#zz-frag"#', $ambos) === 1, 'e3 la entidad y un fragmento a la vez', $ambos);

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('addStaticVersions() no toma por fragmento el # de una entidad numérica: la versión va al final del nombre.')->setEffects([CliActions::EFFECT_NONE])->register();
