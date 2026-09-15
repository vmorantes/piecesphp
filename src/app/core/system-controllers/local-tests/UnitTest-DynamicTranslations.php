<?php

use PiecesPHP\LocalizationSystem\Util\DynamicTranslationsHelper;
use PiecesPHP\Terminal\CliActions;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/dynamic-translations';
$cliTaskDescription = 'Lo que guardan las traducciones dinámicas conserva la forma de la clave y no trae carga';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:DynamicTranslations] Iniciando suite...', true, "\r\n", '33');
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

    //──── 1. Lo legítimo se acepta tal cual ─────────────────────────────────────────────
    echoTerminal('[1/3] acceptTranslations() acepta lo que conserva la forma de la clave');

    $claves = ['Hola', 'Hola <b>mundo</b>', 'Ver <a href="/terminos">términos</a>'];
    $resultado = DynamicTranslationsHelper::acceptTranslations($claves, [
        'Hola' => 'Hello',
        'Hola <b>mundo</b>' => 'Hello <b>world</b>',
        'Ver <a href="/terminos">términos</a>' => 'See <a href="/terminos">terms</a>',
    ]);
    $check(($resultado['accepted']['Hola'] ?? null) === 'Hello', 'sin HTML: se acepta');
    $check(($resultado['accepted']['Hola <b>mundo</b>'] ?? null) === 'Hello <b>world</b>', 'con <b>…</b>: se acepta');
    $check(($resultado['accepted']['Ver <a href="/terminos">términos</a>'] ?? null) === 'See <a href="/terminos">terms</a>', 'con <a href>: se acepta y el enlace se conserva');
    $check($resultado['rejected'] === [], 'nada rechazado', json_encode($resultado['rejected'], JSON_UNESCAPED_UNICODE) ?: null);
    echoTerminal(' ');

    //──── 2. Lo que cambia la forma o trae carga se rechaza ─────────────────────────────
    echoTerminal('[2/3] acceptTranslations() rechaza, cada una con su motivo');

    $claves = ['Texto', 'Imagen <img src="a.png">', 'Enlace <a href="/x">aquí</a>', 'Falta', '<script>x</script>'];
    $resultado = DynamicTranslationsHelper::acceptTranslations($claves, [
        'Texto' => 'Text <b>bold</b>',
        'Imagen <img src="a.png">' => 'Image <img src="a.png" onerror="alert(1)">',
        'Enlace <a href="/x">aquí</a>' => 'Link <a href="javascript:alert(1)">here</a>',
        '<script>x</script>' => '<script>x</script>',
        'Sobra' => 'Extra',
    ]);
    $motivo = fn (string $clave): ?string => $resultado['rejected'][$clave] ?? null;
    //DISCRIMINANTE de la guarda: sin la comparación de etiquetas, esta pasaría como aceptada.
    $check($motivo('Texto') === 'etiquetas-distintas', 'una etiqueta añadida: rechazada por etiquetas distintas', var_export($motivo('Texto'), true));
    $check($motivo('Imagen <img src="a.png">') === 'atributo-on', 'un onerror: rechazado', var_export($motivo('Imagen <img src="a.png">'), true));
    $check($motivo('Enlace <a href="/x">aquí</a>') === 'javascript', 'un javascript: rechazado', var_export($motivo('Enlace <a href="/x">aquí</a>'), true));
    $check($motivo('Falta') === 'falta', 'una clave que falta: rechazada', var_export($motivo('Falta'), true));
    $check($motivo('<script>x</script>') === 'etiqueta-prohibida:script', 'un <script> en la clave: rechazado aunque la clave también lo tenga', var_export($motivo('<script>x</script>'), true));
    $check($resultado['accepted'] === [], 'ninguna de las anteriores se acepta', json_encode($resultado['accepted'], JSON_UNESCAPED_UNICODE) ?: null);
    echoTerminal(' ');

    //──── 3. Solo cuentan las claves pedidas ────────────────────────────────────────────
    echoTerminal('[3/3] Lo que la IA añade sin que se pidiera no entra');

    $check(!array_key_exists('Sobra', $resultado['accepted']) && !array_key_exists('Sobra', $resultado['rejected']), 'una clave que sobra: ignorada');
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:DynamicTranslations] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "Las traducciones dinámicas solo aceptan lo que conserva la forma ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_NONE])->register();
