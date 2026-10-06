<?php

//El viaje de ida y vuelta de la caché de controladores: lo que sale tiene que ser del mismo
//tipo que lo que entró. La ida NO la escribe la prueba: la produce el objeto. Ver T123.

use PiecesPHP\Core\Cache\CacheControllersCriteries;
use PiecesPHP\Core\Cache\CacheControllersCritery;
use PiecesPHP\Core\Cache\CacheControllersManager;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/cache-criteries-round-trip', function ($args) {

    echoTerminal("\e[33m[TEST:CacheCriteriesRoundTrip] Serializar, restaurar, y comprobar el tipo\e[39m");
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

    //─── 1/3 · La ida sale del objeto, no de la prueba ─────────────────────────────────
    echoTerminal('[1/3] La ida la produce el objeto real');

    $original = new CacheControllersManager(CacheControllersManager::class, 'process', 60);
    $original->setCriteries(new CacheControllersCriteries([
        new CacheControllersCritery('idioma', 'es'),
        new CacheControllersCritery('pagina', 3),
    ]));

    //Esto es la IDA: `json_encode` pasa por el `jsonSerialize()` de producción.
    $ida = json_encode($original, \JSON_THROW_ON_ERROR);
    $decodificado = json_decode($ida, true);

    $check(is_array($decodificado) && array_key_exists('criteries', $decodificado),
        'el volcado trae la clave `criteries`');
    $check($original->getCriteries() instanceof CacheControllersCriteries,
        'y lo que entra ES un CacheControllersCriteries',
        get_debug_type($original->getCriteries()));
    echoTerminal(' ');

    //─── 2/3 · La vuelta ───────────────────────────────────────────────────────────────
    echoTerminal('[2/3] La vuelta, por el mismo camino que usa `process()`');

    $restaurado = new CacheControllersManager(CacheControllersManager::class, 'process', 60);
    $restaurado->jsonUnserialize(is_array($decodificado) ? $decodificado : []);

    $salida = $restaurado->getCriteries();
    $check($salida instanceof CacheControllersCriteries,
        'lo que sale es del MISMO TIPO que lo que entró',
        get_debug_type($salida));
    echoTerminal(' ');

    //─── 3/3 · Y no es un envoltorio vacío ─────────────────────────────────────────────
    echoTerminal('[3/3] Y el contenido sobrevive al viaje');

    //Sin esto, envolver cualquier cosa en un objeto vacío pasaría la comprobación anterior.
    $nombres = [];
    //`criteries()` declara `ArrayObject|static`: se estrecha antes de recorrerlo.
    $contenedor = $salida instanceof CacheControllersCriteries ? $salida->criteries() : null;
    if ($contenedor instanceof \ArrayObject) {
        foreach ($contenedor->getArrayCopy() as $clave => $valor) {
            $nombres[] = (string) $clave;
        }
    }
    sort($nombres);
    $check($nombres === ['idioma', 'pagina'], 'los dos criterios siguen ahí, por su nombre',
        implode(', ', $nombres));

    //Un objeto recién construido tiene el mismo tipo: la firma y el constructor concuerdan.
    $recien = new CacheControllersManager(CacheControllersManager::class, 'process', 60);
    $check($recien->getCriteries() instanceof CacheControllersCriteries,
        'y un objeto recién construido ya es de ese tipo, no un \\ArrayObject',
        get_debug_type($recien->getCriteries()));

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La caché de controladores devuelve `criteries` con su tipo, no como array crudo.')->setEffects([CliActions::EFFECT_FILES])->register();
