<?php

//array_to_html_options() escapa el valor y el texto de cada opción, sin escapar dos veces lo ya escapado (P211). Función pura.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/html-options-escape';
$cliTaskDescription = 'array_to_html_options(): valor y texto escapados, entidades conservadas y la selección contra el valor sin escapar';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:HtmlOptionsEscape] Iniciando suite...', true, "\r\n", '33');
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
        }
        if ($detail !== null) {
            echoTerminal("      - {$detail}");
        }
        return $condition;
    };

    //──── Canario ──────────────────────────────────────────────────────────────────────────────
    //Sin la función no hay nada que medir: el resto daría fallos que no dicen la causa.
    if (!$check(function_exists('array_to_html_options'), 'CANARIO: array_to_html_options() está cargada')) {
        return [
            'success' => false,
            'message' => 'array_to_html_options() no está cargada.',
        ];
    }
    echoTerminal(' ');

    //──── 1. Escapado ──────────────────────────────────────────────────────────────────────────
    echoTerminal('[1/3] valor y texto se escapan');

    $salida = array_to_html_options(['a"b' => "<b>x</b> & 'y'"]);
    $check(str_contains($salida, "value='a&quot;b'"), 'h1: la comilla doble del valor → &quot;', $salida);
    $check(str_contains($salida, '&lt;b&gt;x&lt;/b&gt; &amp; &#039;y&#039;'), 'DISCRIMINANTE h1: el texto, escapado entero');
    $check(!str_contains($salida, '<b>'), 'h1: sin la etiqueta <b> cruda');

    $salida = array_to_html_options(["x' onmouseover='pwn()" => 't']);
    $check(!str_contains($salida, "' onmouseover"), 'DISCRIMINANTE h2: la comilla simple no cierra el atributo', $salida);
    $check(str_contains($salida, '&#039; onmouseover'), 'h2: la comilla simple → &#039;');

    $salida = array_to_html_options(['A' => 'x<y'], null, false, false);
    $check(str_contains($salida, "value='x&lt;y'") && str_contains($salida, '>A</option>'), 'h6: key_as_value en false → el valor es el texto del arreglo, escapado; el texto, la clave', $salida);
    echoTerminal(' ');

    //──── 2. Entidades ─────────────────────────────────────────────────────────────────────────
    echoTerminal('[2/3] lo ya escapado no se escapa dos veces');

    $salida = array_to_html_options(['Caf&eacute;' => 'A &amp; B']);
    $check($salida === "<option value='Caf&eacute;'>A &amp; B</option>", 'DISCRIMINANTE h3: las entidades salen tal cual', $salida);
    $check(!str_contains($salida, '&amp;amp;') && !str_contains($salida, '&amp;eacute;'), 'h3: sin &amp;amp; ni &amp;eacute;');
    echoTerminal(' ');

    //──── 3. Selección ─────────────────────────────────────────────────────────────────────────
    echoTerminal('[3/3] la selección se compara contra el valor sin escapar');

    $salida = array_to_html_options(['<1>' => 'uno', '2' => 'dos'], '<1>');
    $check(str_contains($salida, "<option selected value='&lt;1&gt;'>uno</option>"), 'DISCRIMINANTE h4: la opción de «<1>» lleva selected', $salida);
    $check(substr_count($salida, ' selected ') === 1, 'h4: una sola seleccionada');

    $salida = array_to_html_options(['a' => 'A', 'b' => 'B', 'c' => 'C'], ['a', 'c'], true);
    $check(substr_count($salida, ' selected ') === 2, 'h5: multiple → dos selected', $salida);
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:HtmlOptionsEscape] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "Opciones escapadas sin doble escapado ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_NONE])->register();
