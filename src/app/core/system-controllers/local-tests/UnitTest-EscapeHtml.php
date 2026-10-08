<?php

//escape_html() es la función de escape de las vistas: si cambia lo que devuelve, cambian todas a la vez.

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/escape-html', function ($args) {

    echoTerminal("\e[33m[TEST:EscapeHtml] escape_html() escapa texto plano para HTML y atributos\e[39m");
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
    $lanza = function (mixed $value): bool {
        try {
            escape_html($value);
            return false;
        } catch (\TypeError) {
            return true;
        }
    };

    echoTerminal('[1/2] Valores que se pintan');
    $check(escape_html(null) === '', 'e1: null da cadena vacía');
    $check(escape_html('') === '', 'e2: cadena vacía da cadena vacía');
    $salida = escape_html('<b class="x">O\'Neil & co</b>');
    $check($salida === '&lt;b class=&quot;x&quot;&gt;O&#039;Neil &amp; co&lt;/b&gt;', 'e3: <, >, &, comillas dobles y simples se escapan', $salida);
    $check(escape_html('javascript:alert(1)') === 'javascript:alert(1)', 'e4: no valida esquemas: javascript: sale intacto');
    $check(escape_html('Ñandú – ü') === 'Ñandú – ü', 'e5: el UTF-8 válido no cambia');
    $check(escape_html("zz\xC3") === "zz\u{FFFD}", 'e6: el UTF-8 roto se sustituye en vez de vaciar la cadena (ENT_SUBSTITUTE)');
    $check(escape_html(42) === '42' && escape_html(-1.5) === '-1.5', 'e7: enteros y decimales salen como texto');
    $check(escape_html(true) === '1' && escape_html(false) === '', 'e8: los booleanos siguen la conversión de PHP');
    $stringable = new class {
        public function __toString(): string { return '<i>zz</i>'; }
    };
    $check(escape_html($stringable) === '&lt;i&gt;zz&lt;/i&gt;', 'e9: un Stringable se convierte y se escapa');
    $check(escape_html('&amp;') === '&amp;amp;', 'e10: escapa dos veces si se le pasa algo ya escapado (no adivina)');
    echoTerminal(' ');

    echoTerminal('[2/2] Valores que no se pueden pintar');
    $check($lanza(['a']), 'x1: un array lanza TypeError en vez de pintar «Array»');
    $check($lanza(new \stdClass()), 'x2: un objeto sin __toString lanza TypeError');
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('escape_html() escapa texto plano para HTML y atributos, y rechaza lo que no se puede pintar.')->setEffects([CliActions::EFFECT_NONE])->register();
