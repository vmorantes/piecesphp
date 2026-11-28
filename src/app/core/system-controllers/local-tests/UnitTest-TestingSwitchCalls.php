<?php

//verify-integrity 75: swapEnabledForTesting() solo se llama en local-tests/. Casos sintéticos en memoria y el árbol de verdad.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\VerifyIntegrityTask;

CliActions::make('unit-tests:core/testing-switch-calls', function ($args) {

    echoTerminal("\e[33m[TEST:testing-switch-calls] El conmutador de prueba de los módulos solo se llama en las suites\e[39m");
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
    $comprobar = fn (?array $sources, ?bool $canario = null): array => (new \ReflectionMethod(VerifyIntegrityTask::class, 'checkTestingSwitchCalls'))->invoke(null, $sources, $canario);

    try {
        echoTerminal('[1] Casos sintéticos');
        $fallos = $comprobar(['app/classes/Zz/Uno.php' => "<?php\n\\Zz\\ZzRoutes::swapEnabledForTesting(false);\n"]);
        $check(count($fallos) === 1 && str_contains($fallos[0], 'app/classes/Zz/Uno.php:2'), '1a una llamada en app/ falla, con su archivo y su línea', json_encode($fallos, JSON_UNESCAPED_UNICODE) ?: '');
        $definicion = "<?php\nclass ZzRoutes {\n    public static function swapEnabledForTesting(?bool \$enabled): ?bool { return null; }\n}\n";
        $check($comprobar(['app/classes/Zz/ZzRoutes.php' => $definicion]) === [], '1b la definición del método no cuenta');
        $menciones = "<?php\n//swapEnabledForTesting(false) en un comentario\n/** swapEnabledForTesting(true) */\n\$texto = 'swapEnabledForTesting(null)';\n";
        $check($comprobar(['app/classes/Zz/Dos.php' => $menciones]) === [], '1c un comentario, un docblock o una cadena no cuentan');
        $suite = "<?php\nCliActions::make('zz', function (\$args) {\n    \\Zz\\ZzRoutes::swapEnabledForTesting(false);\n    \$f = fn () => \\Zz\\ZzRoutes::swapEnabledForTesting(null);\n});\nfunction zzAyuda(): void { \\Zz\\ZzRoutes::swapEnabledForTesting(true); }\n";
        $check($comprobar(['app/core/system-controllers/local-tests/UnitTest-Zz.php' => $suite]) === [], '1d en local-tests/, dentro de un cierre, de una flecha o de una función, no cuenta');
        $fallos = $comprobar(['app/core/system-controllers/local-tests/UnitTest-Zz.php' => "<?php\n\\Zz\\ZzRoutes::swapEnabledForTesting(false);\nCliActions::make('zz', function (\$args) {});\n"]);
        $check(count($fallos) === 1 && str_contains($fallos[0], 'a nivel de archivo') && str_contains($fallos[0], 'UnitTest-Zz.php:2'), '1d.1 en local-tests/, a nivel de archivo, falla: correría al incluirse la carpeta', json_encode($fallos, JSON_UNESCAPED_UNICODE) ?: '');
        $fallos = $comprobar(['app/core/system-controllers/local-tests/UnitTest-Zz.php' => "<?php\n\$f = fn () => 1;\n\\Zz\\ZzRoutes::swapEnabledForTesting(false);\nfunction zz(): void {}\n\\Zz\\ZzRoutes::swapEnabledForTesting(true);\n"]);
        $check(count($fallos) === 2, '1d.2 y entre funciones o tras una flecha ya cerrada, también', json_encode($fallos, JSON_UNESCAPED_UNICODE) ?: '');
        $check($comprobar(['app/core/system-controllers/local-tests/UnitTest-Zz.php' => "<?php\n\$f = fn () => \\Zz\\ZzRoutes::swapEnabledForTesting(null);\n"]) === [], '1d.3 dentro de una flecha a nivel de archivo no cuenta: corre solo si se llama');
        $fallos = $comprobar(['app/core/system-controllers/local-tests/UnitTest-Zz.php' => "<?php\nuse function Zz\\a;\nif (true) { \\Zz\\ZzRoutes::swapEnabledForTesting(false); }\n"]);
        $check(count($fallos) === 1, '1d.4 tras un «use function», un bloque if no es el cuerpo de una función: falla', json_encode($fallos, JSON_UNESCAPED_UNICODE) ?: '');
        $fallos = $comprobar(['app/classes/Zz/Uno.php' => "<?php\n"], true);
        $check(count($fallos) === 1 && str_contains($fallos[0], 'el instrumento no ve'), '1h CANARIO: sin ninguna llamada en las suites, la comprobación dice que no ve', json_encode($fallos, JSON_UNESCAPED_UNICODE) ?: '');
        $fallos = $comprobar(['index.php' => "<?php\n\$x = \\Zz\\ZzRoutes::swapEnabledForTesting (true);\n"]);
        $check(count($fallos) === 1, '1e con espacio antes del paréntesis, y en index.php, también falla', json_encode($fallos, JSON_UNESCAPED_UNICODE) ?: '');
        $fallos = $comprobar(['app/classes/Zz/Tres.php' => "<?php\n\\Zz\\ZzRoutes::SwapEnabledForTESTING(false);\n"]);
        $check(count($fallos) === 1, '1f con otras mayúsculas (PHP no las distingue en los métodos), también falla', json_encode($fallos, JSON_UNESCAPED_UNICODE) ?: '');
        $check($comprobar(['app/classes/Zz/ZzRef.php' => "<?php\nclass ZzRef {\n    public static function &swapEnabledForTesting(?bool \$e) { static \$x; return \$x; }\n}\n"]) === [], '1g una definición por referencia (function &…) no cuenta');

        echoTerminal('');
        echoTerminal('[2] El árbol de verdad');
        $fallos = $comprobar(null);
        $check($fallos === [], '2a ninguna llamada fuera de local-tests/ ni, dentro, a nivel de archivo', implode(' · ', $fallos));
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('verify-integrity 75: swapEnabledForTesting() solo se llama en local-tests/; la definición, los comentarios y las cadenas no cuentan.')->setEffects([CliActions::EFFECT_NONE])->register();
