<?php

//Los nombres de clase de la 7.1 (LegacyClassAliases, hasta la 11.0.0): cada uno del mapa resuelve a su clase de hoy, uno
//de fuera no, y no se crea ningún alias sin pedirlo. En proceso; no escribe nada.
//@areas nucleo

use PiecesPHP\Core\LegacyClassAliases;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/legacy-class-aliases', function ($args) {

    echoTerminal("\e[33m[TEST:LegacyClassAliases] Los nombres de clase de la 7.1 cargan su clase de hoy\e[39m");
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

    $loader = [LegacyClassAliases::class, 'load'];
    $oldNames = array_keys(LegacyClassAliases::MAP);
    $lower = array_map('strtolower', $oldNames);
    $requested = fn (array $classes): array => array_values(array_intersect($lower, array_map('strtolower', $classes)));

    //a1, antes que nada: con el cargador que pone bootstrap.php y sin haber pedido ningún nombre viejo, no hay ninguno.
    $check($requested(get_declared_classes()) === [], 'a1 DISCRIMINANTE: al arrancar ningún nombre viejo está declarado; el alias espera a que lo pidan', implode(', ', $requested(get_declared_classes())));
    $check(in_array($loader, spl_autoload_functions(), true), 'a2 DISCRIMINANTE: bootstrap.php registra el cargador (LegacyClassAliases::register())');

    //b. Sin el cargador, el nombre viejo no resuelve; con él, sí.
    $probe = 'App\\Model\\UsersModel';
    spl_autoload_unregister($loader);
    $withoutLoader = class_exists($probe);
    //Se vuelve a registrar por register(), no a mano: así b2 y c1 caen si register() no añade el cargador.
    (new \ReflectionProperty(LegacyClassAliases::class, 'registered'))->setValue(null, false);
    LegacyClassAliases::register();
    $check($withoutLoader === false, 'b1 CANARIO: sin el cargador, ' . $probe . ' no existe (si existiera, la prueba no mediría el alias)');
    $before = get_declared_classes();
    $resolves = class_exists($probe);
    $newOld = $requested(array_diff(get_declared_classes(), $before));
    $check($resolves && is_a($probe, \PiecesPHP\UserSystem\ORM\UsersModel::class, true), 'b2 DISCRIMINANTE: con el cargador, ' . $probe . ' es UsersModel de hoy');
    $check($newOld === [strtolower($probe)], 'b3 pedir un nombre crea su alias y ningún otro', implode(', ', $newOld));

    //c. Cada nombre del mapa resuelve a su clase de hoy.
    $broken = [];
    foreach (LegacyClassAliases::MAP as $old => $new) {
        if (!class_exists($new) || !(class_exists($old) && is_a($old, $new, true))) {
            $broken[] = "{$old} → {$new}";
        }
    }
    $check(count(LegacyClassAliases::MAP) > 0 && $broken === [], 'c1 los ' . count(LegacyClassAliases::MAP) . ' nombres del mapa resuelven a su clase de hoy', implode('; ', $broken));

    //d. Un nombre fuera del mapa no resuelve: ni uno de la 7.1 sin sustituta ni uno inventado.
    foreach (['ApplicationCalls\\Controllers\\ApplicationCallsController', 'App\\Model\\ZzNoExiste'] as $index => $outside) {
        $check(!class_exists($outside), 'd' . ($index + 1) . ' un nombre fuera del mapa no resuelve: ' . $outside);
    }
    //El PO los quitó sin compatibilidad (P146): no vuelven aunque P416 devuelva los demás.
    foreach (['PiecesPHP\\Core\\ServerStatics', 'PiecesPHP\\Core\\Helpers\\Directories\\ProtectFileMiddleware'] as $index => $removed) {
        $check(!array_key_exists($removed, LegacyClassAliases::MAP) && !class_exists($removed), 'd' . ($index + 3) . ' DISCRIMINANTE: el nombre viejo de ' . $removed . ' sigue sin resolver (P146)');
    }

    //e. register() dos veces no añade otro cargador.
    LegacyClassAliases::register();
    $count = count(array_filter(spl_autoload_functions(), fn ($f): bool => $f === $loader));
    $check($count === 1, 'e1 register() no duplica el cargador', (string) $count);

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Los nombres de clase de la 7.1 cargan su clase de hoy (LegacyClassAliases, hasta la 11.0.0), y solo cuando se piden.')->setEffects([CliActions::EFFECT_NONE])->register();
