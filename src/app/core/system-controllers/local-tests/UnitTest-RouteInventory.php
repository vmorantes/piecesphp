<?php

//El inventario de rutas refleja el router: una fila por ruta nombrada, sin repetir ni omitir.
//Nace de que una ruta con ALIAS salía duplicada y la del alias no aparecía (297.5).

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/route-inventory', function ($args) {

    echoTerminal("\e[33m[TEST:RouteInventory] El inventario refleja el router: ni repite ni omite\e[39m");
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

    //──── El canario: sin router ni artefacto, comparar dos listas vacías pasaría solo ──────────
    $nombresDelRouter = [];
    foreach (get_router()->getRouteCollector()->getRoutes() as $ruta) {
        $nombre = (string) $ruta->getName();
        if ($nombre === '') {
            continue;
        }
        $nombresDelRouter[$nombre] = ($nombresDelRouter[$nombre] ?? 0) + 1;
    }
    $check(count($nombresDelRouter) > 300, 'a1 CANARIO: el router tiene las rutas de la aplicación cargadas: ' . count($nombresDelRouter));

    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    $ruta = "{$proyecto}/files/dev/route-inventory.json";
    $artefacto = is_file($ruta) ? json_decode((string) file_get_contents($ruta), true) : null;
    if (!$check(is_array($artefacto) && count($artefacto) > 300, 'a2 CANARIO: el inventario existe y tiene filas', 'genéralo con: bin/cli route-inventory')) {
        return $balance();
    }

    $nombresDelArtefacto = [];
    foreach ($artefacto as $fila) {
        $nombre = (string) ($fila['name'] ?? '');
        if ($nombre === '') {
            continue;
        }
        $nombresDelArtefacto[$nombre] = ($nombresDelArtefacto[$nombre] ?? 0) + 1;
    }

    //──── b · Ni repite ─────────────────────────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal('[b] Ningún nombre repetido');
    $repetidosArtefacto = array_filter($nombresDelArtefacto, fn (int $n): bool => $n > 1);
    $check($repetidosArtefacto === [], 'b1 el inventario no repite ningún nombre', (string) json_encode($repetidosArtefacto));
    $repetidosRouter = array_filter($nombresDelRouter, fn (int $n): bool => $n > 1);
    $check($repetidosRouter === [], 'b2 y el router tampoco: la guarda de set_route() funciona', (string) json_encode($repetidosRouter));

    //──── c · Ni omite ──────────────────────────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal('[c] Las mismas rutas en los dos lados');
    //`terminal-help` y su alias llevan un `uniqid()` en el patrón, pero su NOMBRE no cambia.
    $faltanEnArtefacto = array_values(array_diff(array_keys($nombresDelRouter), array_keys($nombresDelArtefacto)));
    $sobranEnArtefacto = array_values(array_diff(array_keys($nombresDelArtefacto), array_keys($nombresDelRouter)));
    sort($faltanEnArtefacto);
    sort($sobranEnArtefacto);
    $check($faltanEnArtefacto === [], 'c1 ninguna ruta del router falta en el inventario', implode(', ', array_slice($faltanEnArtefacto, 0, 6)) . (count($faltanEnArtefacto) > 6 ? '…' : '') . ' — regenera con: bin/cli route-inventory');
    $check($sobranEnArtefacto === [], 'c2 el inventario no inventa ninguna que el router no tenga', implode(', ', array_slice($sobranEnArtefacto, 0, 6)));
    $check(count($nombresDelArtefacto) === count($nombresDelRouter), 'c3 y son tantas como el router: ' . count($nombresDelArtefacto) . ' contra ' . count($nombresDelRouter));

    //──── d · El caso que lo descubrió: una ruta con alias son DOS rutas ─────────────────────────
    echoTerminal('');
    echoTerminal('[d] Una ruta con alias aparece con su nombre Y con su alias');
    $conAlias = [];
    foreach ((array) get_routes() as $clave => $entrada) {
        $nombreDentro = is_object($entrada) ? ($entrada->name ?? null) : (is_array($entrada) ? ($entrada['name'] ?? null) : null);
        if (is_string($nombreDentro) && $nombreDentro !== '' && $clave !== $nombreDentro) {
            $conAlias[(string) $clave] = $nombreDentro;
        }
    }
    //El alias se retiró en el CX (P92): se comprueba lo CONTRARIO, y sobre el registro vivo. La
    //comprobación 42 vigila el código; esta, lo que de verdad se registró.
    $check(count($conAlias) === 0, 'd1 NINGUNA entrada de _routes_ tiene clave distinta de su nombre: no quedan alias', (string) json_encode($conAlias));
    $check(!array_key_exists('terminal-h', $nombresDelArtefacto), 'd2 y el que había, «terminal-h», ya no está en el inventario');
    $check(array_key_exists('terminal-help', $nombresDelArtefacto) && $nombresDelArtefacto['terminal-help'] === 1, 'd3 mientras «terminal-help» sigue, una sola vez');

    return $balance();

})->setDescription('El inventario de rutas refleja el router: ningún nombre repetido ni omitido, y ninguna entrada con clave distinta de su nombre, porque el mecanismo de alias se retiró.')->setEffects([CliActions::EFFECT_NONE])->register();
