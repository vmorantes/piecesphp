<?php

//get_route_sample(): la URL de muestra de una ruta con sus marcadores a la vista, sacados del patrón completo de Slim.
//Solo lee el router: no registra rutas ni escribe nada.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/route-sample', function ($args) {

    echoTerminal("\e[33m[TEST:RouteSample] La URL de muestra de una ruta, con todos sus marcadores\e[39m");
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

    echoTerminal('[r1] Un marcador con expresión, opcional: statics-files («/[{params:.*}]»)');
    $pattern = get_router()->getRouteCollector()->getNamedRoute('statics-files')->getPattern();
    $sample = null;
    $error = '';
    try {
        $sample = get_route_sample('statics-files');
    } catch (\Throwable $e) {
        $error = get_class($e) . ': ' . $e->getMessage();
    }
    $check(str_contains($pattern, '{params:'), 'r1.0 la ruta sigue llevando su marcador con expresión', $pattern);
    $check($error === '' && $sample === get_route('statics-files') . '{params}', 'r1 la muestra lleva {params} y no lanza', $error !== '' ? $error : var_export($sample, true));

    echoTerminal('[r2] Los nombres de los marcadores, con FastRoute: llaves anidadas y opcionales');
    $names = get_route_pattern_placeholders('/zz/{x:\d{2,4}}/{y}');
    $check($names === ['x', 'y'], 'r2 {x:\d{2,4}} da x, y el siguiente marcador no se pierde', json_encode($names) ?: '');
    $names = get_route_pattern_placeholders('/zz/{a}[/{b:\d{2,4}}[/{c:[a-z]+}]]');
    $check($names === ['a', 'b', 'c'], 'r2b los opcionales anidados cuentan, en orden y sin repetir', json_encode($names) ?: '');
    $check(get_route_pattern_placeholders('/zz/sin-marcadores/') === [], 'r2c sin marcadores, ninguno');

    echoTerminal('[r3] Una ruta inexistente en silencio: null y ningún aviso');
    $warnings = [];
    $result = 'sin-llamar';
    $error = '';
    set_error_handler(function (int $level, string $message) use (&$warnings): bool {
        $warnings[] = [$level, $message];
        return true;
    });
    try {
        $result = get_route_sample('zz-no-existe', true);
    } catch (\Throwable $e) {
        $error = get_class($e) . ': ' . $e->getMessage();
    } finally {
        restore_error_handler();
    }
    $check($result === null && $error === '' && count($warnings) === 0, 'r3 devuelve null, no lanza y no avisa', $error !== '' ? $error : (var_export($result, true) . ' ' . (json_encode($warnings, JSON_UNESCAPED_UNICODE) ?: '')));

    echoTerminal('[r4] Una ruta inexistente sin silencio: la misma excepción que get_route(), nunca un TypeError');
    $sampleError = null;
    $routeError = null;
    try {
        get_route_sample('zz-no-existe');
    } catch (\Throwable $e) {
        $sampleError = $e;
    }
    try {
        get_route('zz-no-existe');
    } catch (\Throwable $e) {
        $routeError = $e;
    }
    $check($sampleError !== null && !($sampleError instanceof \TypeError), 'r4 lanza, y no es un TypeError', $sampleError === null ? 'no lanzó' : get_class($sampleError) . ': ' . $sampleError->getMessage());
    $check($sampleError !== null && $routeError !== null && get_class($sampleError) === get_class($routeError) && $sampleError->getMessage() === $routeError->getMessage(), 'r4b la misma clase y el mismo mensaje que get_route()', ($sampleError !== null ? get_class($sampleError) : '-') . ' / ' . ($routeError !== null ? get_class($routeError) : '-'));

    echoTerminal('[r5] Sin marcadores con expresión, la muestra no cambia: coincide con la de los parámetros declarados');
    $compared = 0;
    $different = [];
    foreach (get_routes() as $routeName => $information) {
        $routePattern = get_router()->getRouteCollector()->getNamedRoute((string) $routeName)->getPattern();
        if (str_contains($routePattern, ':')) {
            continue;
        }
        $parameters = [];
        foreach (array_keys($information['parameters']) as $parameter) {
            $parameters[$parameter] = '{' . $parameter . '}';
        }
        $compared++;
        if (get_route_sample((string) $routeName) !== get_route((string) $routeName, $parameters)) {
            $different[] = (string) $routeName;
        }
    }
    $check($compared > 0 && $different === [], "r5 {$compared} rutas comparadas, todas iguales", implode(', ', array_slice($different, 0, 5)));

    echoTerminal('[r6] Los parámetros declarados de una ruta salen de FastRoute: get_route(), el inventario y la 61 dependen de ellos');
    $staticsParameters = get_routes()['statics-files']['parameters'] ?? null;
    $check($staticsParameters === ['params' => null], 'r6 statics-files («/[{params:.*}]») declara params', var_export($staticsParameters, true));
    //Sin register(): la ruta no entra en el router.
    $adapter = new \PiecesPHP\Core\Routing\RouteAdapter('/zz/{x:\d{4}}/{y}', fn () => null, 'zz-route-sample-llaves', 'GET', false, null, [], ['y' => 'def', 'zz' => 'ignorado']);
    $check($adapter->getParameters() === ['x' => null, 'y' => 'def'], 'r6b {x:\d{4}} da x (no «4») y el valor por omisión de y se conserva', var_export($adapter->getParameters(), true));
    $mismatched = [];
    foreach (get_routes() as $routeName => $information) {
        $routePattern = get_router()->getRouteCollector()->getNamedRoute((string) $routeName)->getPattern();
        if (array_keys((array) $information['parameters']) !== get_route_pattern_placeholders($routePattern)) {
            $mismatched[] = (string) $routeName;
        }
    }
    $check($mismatched === [], 'r6c en todas las rutas registradas, los parámetros son los marcadores del patrón de Slim', implode(', ', array_slice($mismatched, 0, 5)));

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('get_route_sample(): los marcadores salen del patrón completo (con expresión, anidados y opcionales); una ruta inexistente da null en silencio y, sin él, la excepción de get_route(). Solo lee el router.')->setEffects([CliActions::EFFECT_NONE])->register();
