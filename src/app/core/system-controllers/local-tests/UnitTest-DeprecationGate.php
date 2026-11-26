<?php

//La comprobación 73, la puerta de las marcas de desuso, en modo informe: su canario vive, lee la cola de una marca y
//juzga su sustituto, su prueba o su decisión del PO. Que no falle lo dice su `return []`; la corre verify-integrity.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\VerifyIntegrityTask;

CliActions::make('unit-tests:core/deprecation-gate', function ($args) {

    echoTerminal("\e[33m[TEST:DeprecationGate] La comprobación 73, en modo informe\e[39m");
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

    $metodo = function (string $nombre): \ReflectionMethod {
        $m = new \ReflectionMethod(VerifyIntegrityTask::class, $nombre);
        return $m;
    };
    $marcas = fn (string $contenido, string $extension): array => $metodo('deprecationMarks')->invoke(null, $contenido, $extension);
    $partes = fn (string $texto): array => $metodo('deprecationMarkParts')->invoke(null, $texto);
    $veredicto = fn (array $p, ?string $decisiones): array => $metodo('deprecationVerdict')->invoke(null, $p, $decisiones);
    $tag = '@' . 'deprecated';

    //─── c · El canario ────────────────────────────────────────────────────────────────────────────
    echoTerminal('[c] El canario');
    $canario = $metodo('deprecationGateCanary')->invoke(null);
    $check($canario === '', 'c1 el canario está vivo', (string) $canario);

    //─── d · La detección, solo en comentarios ─────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal('[d] La detección');
    $php = "<?php\n/**\n * {$tag} 1.0.0\n */\n\$x = \"{$tag} cadena\";\n// otro, en desuso\n";
    $vistas = $marcas($php, 'php');
    $check(count($vistas) === 2 && $vistas[0]['line'] === 3 && $vistas[1]['line'] === 6, 'd1 en PHP: la del docblock (línea 3) y la del comentario (línea 6), no la cadena', json_encode($vistas) ?: '');
    $scss = "\$a: '{$tag}';\n/* bloque\n   en desuso */\n.b { color: red; } // {$tag} 2.0.0\n";
    $vistasScss = $marcas($scss, 'scss');
    $check(count($vistasScss) === 2 && $vistasScss[0]['line'] === 3 && $vistasScss[1]['line'] === 4, 'd2 en SCSS: la del bloque (línea 3) y la de línea (línea 4), no la cadena', json_encode($vistasScss) ?: '');

    //─── v · El veredicto ──────────────────────────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal('[v] El veredicto');
    //El sustituto existe y la prueba es esta suite con un caso que está en uno de sus $check (v1).
    $buena = $partes("// {$tag} 10.0.0 sale en 11.0.0 · sustituto: PiecesPHP\\Core\\Config::baseurl · prueba: core/deprecation-gate v1");
    $check($veredicto($buena, null)[0] === 'válida', 'v1 con sustituto que existe y prueba con su caso: válida', json_encode($veredicto($buena, null)) ?: '');
    $check($veredicto($partes("// {$tag} 10.0.0 Lo sustituye otra cosa."), null)[0] === 'inválida', 'v2 sin la cola: inválida');
    $sinSuite = $partes("// {$tag} 10.0.0 sale en 11.0.0 · sustituto: PiecesPHP\\Core\\Config · prueba: core/zz-no-existe x1");
    $check($veredicto($sinSuite, null)[0] === 'inválida', 'v3 con una suite que no existe: inválida');
    $sinCaso = $partes("// {$tag} 10.0.0 sale en 11.0.0 · sustituto: PiecesPHP\\Core\\Config · prueba: core/deprecation-gate zz-caso-que-no-esta");
    $check($veredicto($sinCaso, null)[0] === 'inválida', 'v4 con un caso que no está en un $check de la suite: inválida');
    //«v1» como subcadena de «v10» no vale: el caso es el comienzo del nombre (v11 no existe en ningún $check).
    $prefijo = $partes("// {$tag} 10.0.0 sale en 11.0.0 · sustituto: PiecesPHP\\Core\\Config · prueba: core/deprecation-gate v");
    $check($veredicto($prefijo, null)[0] === 'inválida', 'v11 un caso que solo es el principio de otros («v»): inválida');
    $js = "var s = 'a\\\n b';\n// {$tag} x\n";
    $vistasJs = $marcas($js, 'js');
    $check(count($vistasJs) === 1 && $vistasJs[0]['line'] === 3, 'v12 una cadena partida con la barra al final cuenta su línea', json_encode($vistasJs) ?: '');
    $raro = $partes("// {$tag} 10.0.0 sale en 11.0.0 · sustituto: ZzNoExiste\\Clase · prueba: core/deprecation-gate v1");
    $check($veredicto($raro, null)[0] === 'sin comprobar', 'v5 con un sustituto que no reconoce: sin comprobar');
    $decision = $partes("// en desuso 10.0.0 sale en 11.0.0 · muere: decisiones-del-po 2026-11-24 «zz-cita de prueba»");
    $check($decision['form'] === 'decision', 'v6 lee la forma de la decisión del PO');
    $check($veredicto($decision, null)[0] === 'no aplica', 'v7 sin el registro de decisiones (una distribución): no aplica');
    $check($veredicto($decision, "| 2026-11-24 | «zz-cita de prueba» | criterio |\n")[0] === 'válida', 'v8 con su fila: válida');
    $check($veredicto($decision, "| 2026-11-24 | «zz-cita de prueba» | tachada |\n")[0] === 'inválida', 'v9 con su fila tachada: inválida');
    $check($veredicto($decision, "| 2026-11-23 | otra |\n")[0] === 'inválida', 'v10 sin su fila: inválida');

    return $balance();

})->setDescription('La comprobación 73, la puerta de las marcas de desuso, en modo informe: el canario, la detección solo en comentarios (PHP, JS y SCSS), el veredicto de la cola de cada marca y que no falla.')->setEffects([CliActions::EFFECT_NONE])->register();
