<?php

//El catálogo de opciones que la base puede dar (ADR 0055): una declaración por nombre, un solo dueño. En proceso, sin
//base ni red; los nombres de la prueba llevan zz- y solo viven en este proceso.
//@areas nucleo

use PiecesPHP\Core\ConfigHelpers\ConfigOptions;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/config-options', function ($args) {

    echoTerminal("\e[33m[TEST:ConfigOptions] Una opción, una declaración y un solo dueño\e[39m");
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
    $lanza = function (callable $accion): ?\Throwable {
        try {
            $accion();
        } catch (\Throwable $e) {
            return $e;
        }
        return null;
    };

    $marca = 'zz-config-options-' . bin2hex(random_bytes(3));
    $nombre = "{$marca}-titulo";
    $calculado = sha1(ConfigOptions::class) . "|{$marca}-contenido";

    echoTerminal('[a] Declarar y preguntar');
    $check(!ConfigOptions::isDeclared($nombre), 'a1 CANARIO: antes de declararla, no está');
    ConfigOptions::declare($nombre, ConfigOptions::class, ConfigOptions::SHAPE_TEXT);
    $check(ConfigOptions::isDeclared($nombre), 'a2 después, sí');
    $todas = ConfigOptions::all();
    $check(($todas[$nombre] ?? null) === ['owner' => ConfigOptions::class, 'shape' => ConfigOptions::SHAPE_TEXT], 'a3 all() la da con su dueño y su forma', (string) json_encode($todas[$nombre] ?? null));
    $claves = array_keys($todas);
    $ordenadas = $claves;
    sort($ordenadas);
    $check($claves === $ordenadas, 'a4 all() sale ordenado por nombre');
    ConfigOptions::declare($nombre, ConfigOptions::class, ConfigOptions::SHAPE_TEXT);
    $check(count(ConfigOptions::all()) === count($todas), 'a5 la misma declaración otra vez no duplica ni lanza');
    echoTerminal(' ');

    echoTerminal('[b] Una sola fuente');
    $otroDueno = $lanza(fn () => ConfigOptions::declare($nombre, CliActions::class, ConfigOptions::SHAPE_TEXT));
    $check($otroDueno instanceof \LogicException, 'b1 otro dueño para el mismo nombre lanza', $otroDueno !== null ? get_class($otroDueno) : 'no lanzó');
    $check(ConfigOptions::all()[$nombre]['owner'] === ConfigOptions::class, 'b2 y la declaración primera se queda');
    $otraForma = $lanza(fn () => ConfigOptions::declare($nombre, ConfigOptions::class, ConfigOptions::SHAPE_JSON));
    $check($otraForma instanceof \LogicException, 'b3 el mismo dueño con otra forma también lanza', $otraForma !== null ? get_class($otraForma) : 'no lanzó');
    $formaRara = $lanza(fn () => ConfigOptions::declare("{$marca}-rara", ConfigOptions::class, 'zz-forma'));
    $check($formaRara instanceof \InvalidArgumentException && !ConfigOptions::isDeclared("{$marca}-rara"), 'b4 una forma fuera de SHAPES lanza y no declara');
    $vacio = $lanza(fn () => ConfigOptions::declare('', ConfigOptions::class));
    $check($vacio instanceof \InvalidArgumentException, 'b5 un nombre vacío lanza');
    echoTerminal(' ');

    echoTerminal('[c] Lo no declarado, y un nombre calculado');
    ConfigOptions::declare($calculado, ConfigOptions::class, ConfigOptions::SHAPE_JSON);
    $check(ConfigOptions::isDeclared($calculado), 'c1 un nombre calculado (sha1|contenido) se declara tal cual');
    $sin = ConfigOptions::undeclared([$nombre, "{$marca}-huerfana", $calculado, "{$marca}-huerfana", "{$marca}-otra"]);
    $check($sin === ["{$marca}-huerfana", "{$marca}-otra"], 'c2 undeclared() da solo los que nadie declaró, sin repetir y en su orden', (string) json_encode($sin));
    $check(ConfigOptions::undeclared([]) === [], 'c3 de una lista vacía, nada');

    $total = $passed + $failed;
    echoTerminal(' ');
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El catálogo de opciones que la base puede dar (ADR 0055): declarar, una sola fuente, lo no declarado y los nombres calculados. En proceso.')->setEffects([CliActions::EFFECT_NONE])->register();
