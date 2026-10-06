<?php

//friendlyURLString() conserva los dígitos (P94), y en lo demás sale igual que antes: las URLs viejas sin
//dígitos no cambian. Los casos «antes» se midieron con la función anterior el 2026-10-05.

use PiecesPHP\Core\StringManipulate;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/friendly-url', function ($args) {

    echoTerminal("\e[33m[TEST:FriendlyUrl] friendlyURLString() conserva los dígitos y nada más cambia\e[39m");
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
    $slug = fn (string $texto, ?int $palabras = null): string => StringManipulate::friendlyURLString($texto, $palabras);

    echoTerminal('[a] Los dígitos se conservan');
    $check($slug('Informe anual 2026') === 'informe-anual-2026', 'a1 «Informe anual 2026» → informe-anual-2026', $slug('Informe anual 2026'));
    $check($slug('Año 2026') === 'anno-2026', 'a2 «Año 2026» → anno-2026', $slug('Año 2026'));
    $check($slug('Informe 2025') !== $slug('Informe 2026'), 'a3 dos años distintos dan dos URLs distintas');
    $check($slug('Informe anual 2026 final', 3) === 'informe-anual-2026', 'a4 maxWords cuenta un número como una palabra', $slug('Informe anual 2026 final', 3));
    $check($slug('2026') === '2026', 'a5 un título que solo es un número ya no da una cadena vacía', $slug('2026'));

    //─── b · Sin dígitos, igual que antes ───────────────────────────────────────────────────────────
    echoTerminal(' ');
    echoTerminal('[b] Un texto sin dígitos sale igual que con la función anterior');
    $antes = [
        'Canción del Ñandú' => 'cancion-del-nnandu',
        'Exportación' => 'exportacion',
        'RESPALDO' => 'respaldo',
        "  Hola,   mundo!  " => 'hola-mundo',
        "Línea\tcon\ttabuladores" => 'lineacontabuladores',
        "Espacio\u{00A0}duro" => 'espacio-duro',
        '¿Qué — pasa?' => 'que-pasa',
        'Garçon über' => 'garcon-uber',
        '---ya-con-guiones---' => 'ya-con-guiones',
    ];
    foreach ($antes as $texto => $esperado) {
        $check($slug($texto) === $esperado, "b «{$texto}» → {$esperado}", $slug($texto));
    }
    $check($slug('Uno dos tres cuatro', 2) === 'uno-dos', 'b maxWords sin números, igual que antes');

    $total = $passed + $failed;
    echoTerminal(' ');
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('friendlyURLString() conserva los dígitos —«Informe anual 2026» ya no pierde el año— y en lo demás sale igual que antes: tildes, ñ como nn, minúsculas, espacios, puntuación y maxWords.')->setEffects([CliActions::EFFECT_NONE])->register();
