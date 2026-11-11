<?php

//format_date_lang_sql frente a format_date_lang: en los idiomas que deben coincidir, la fecha de MySQL sale como la de
//PHP. Las diferencias que siguen abiertas se imprimen, sin fallar.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/date-format-sql', function ($args) {

    echoTerminal("\e[33m[TEST:DateFormatSql] La fecha de MySQL y la de PHP, por idioma\e[39m");
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

    $php = (array) get_config('format_date_lang');
    $sql = (array) get_config('format_date_lang_sql');
    $comoPhp = fn (string $formato): string => str_replace(['%d', '%m', '%Y'], ['d', 'm', 'Y'], $formato);

    foreach (['es', 'fr', 'pt'] as $idioma) {
        //Un proyecto con otras letras (l, F…) no tiene equivalente directo: se informa, no se juzga.
        if (isset($php[$idioma]) && preg_match('/[^dmY\/\-. ]/', (string) $php[$idioma]) === 1) {
            echoTerminal("      - {$idioma}: el proyecto usa «{$php[$idioma]}», sin comparar");
            continue;
        }
        $check(isset($php[$idioma], $sql[$idioma]) && $comoPhp((string) $sql[$idioma]) === $php[$idioma], "{$idioma}: la de MySQL es la de PHP" . ($idioma === 'fr' ? ' (DISCRIMINANTE)' : ''), ($sql[$idioma] ?? '¿?') . ' frente a ' . ($php[$idioma] ?? '¿?'));
    }
    foreach (array_diff(array_keys($php), ['es', 'fr', 'pt']) as $idioma) {
        $igual = isset($sql[$idioma]) && $comoPhp((string) $sql[$idioma]) === $php[$idioma];
        echoTerminal("      - {$idioma}: " . ($igual ? 'coinciden' : 'no coinciden, abierto: ' . ($sql[$idioma] ?? '¿?') . ' frente a ' . $php[$idioma]));
    }

    return $balance();

})->setDescription('format_date_lang_sql frente a format_date_lang: en español, francés y portugués la fecha de MySQL sale como la de PHP; las demás diferencias se imprimen.')->setEffects([CliActions::EFFECT_NONE])->register();
