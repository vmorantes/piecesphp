<?php

//Cada cadena del listado de publicaciones tiene su inglés. La lista sale de la vista, no se escribe aquí; el diccionario se
//carga como array (su clave va entre comillas dobles: buscarla con simples da cero, G17).
//@areas src/app/classes/Publications/

use PiecesPHP\Terminal\CliActions;
use Publications\Controllers\PublicationsController;

CliActions::make('unit-tests:core/publications-list-lang', function ($args) {

    echoTerminal("\e[33m[TEST:PublicationsListLang] El listado de publicaciones, entero en inglés\e[39m");
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
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal(' ');
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];
    };

    $vista = basepath('app/classes/Publications/Views/publications/list.php');
    $diccionario = basepath('app/classes/Publications/lang/en.php');
    $fuente = is_file($vista) ? (string) file_get_contents($vista) : '';
    $ingles = is_file($diccionario) ? require $diccionario : null;
    if (!$check($fuente !== '' && is_array($ingles), 'p0 la vista y el diccionario inglés se leen', "{$vista} · {$diccionario}")) {
        return $balance();
    }

    //Las claves de __($langGroup, '…') o "…", con las dos comillas.
    $leidas = preg_match_all('/__\(\$langGroup,\s*(\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*")\)/u', $fuente, $coincidencias);
    $claves = $leidas !== false ? array_values(array_unique(array_map(fn (string $literal): string => stripcslashes(substr($literal, 1, -1)), $coincidencias[1]))) : [];
    $check(count($claves) >= 10, 'p1 CANARIO: la vista pide sus cadenas con __($langGroup, …): ' . count($claves));
    $check(PublicationsController::LANG_GROUP === 'publications-lang', 'p2 el grupo de la vista es el del diccionario del módulo', PublicationsController::LANG_GROUP);

    $faltan = array_values(array_filter($claves, fn (string $clave): bool => !array_key_exists($clave, $ingles) || !is_string($ingles[$clave]) || trim($ingles[$clave]) === ''));
    $check($faltan === [], 'a1 DISCRIMINANTE: cada cadena de list.php tiene su inglés en lang/en.php', implode(' · ', $faltan));

    return $balance();

})->setDescription('Cada cadena del listado de publicaciones (Views/publications/list.php) tiene su traducción en Publications/lang/en.php; la lista sale de la vista.')->setEffects([CliActions::EFFECT_NONE])->register();
