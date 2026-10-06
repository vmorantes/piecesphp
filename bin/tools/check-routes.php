<?php

/**
 * Arranca el framework hasta tener todas las rutas registradas y analiza cada patrón por separado.
 *
 * NO DESPACHA. Entra por `_list-actions`, la acción de autocompletado de la CLI, que en src/index.php
 * devuelve (`return`) antes de `$app->run()`: las rutas ya están registradas y FastRoute todavía no
 * ha analizado ninguna. Si esa rama cambia y index.php llega a despachar, el arranque cae con el
 * patrón malo y esto lo dice («no llegó a registrar las rutas»): no aprueba en silencio.
 *
 * Se lanza con bin/check-routes, que elige el PHP y se sitúa en src/.
 */

use FastRoute\RouteParser\Std;

$argv = ['index.php', 'cli', '--local', '_list-actions'];
$_SERVER['argv'] = $argv;
$_SERVER['argc'] = count($argv);
//El arranque reconoce la CLI por el script de entrada: se presenta como `php index.php`, igual que bin/cli.
$_SERVER['PHP_SELF'] = 'index.php';
$_SERVER['SCRIPT_NAME'] = 'index.php';
$_SERVER['SCRIPT_FILENAME'] = 'index.php';

//Si index.php termina el proceso (exit o excepción) no se vuelve aquí: se dice en vez de salir con 0.
register_shutdown_function(function (): void {
    if (defined('PCSPHP_CHECK_ROUTES_REGISTERED')) {
        return;
    }
    $output = ob_get_level() > 0 ? (string) ob_get_clean() : '';
    fwrite(STDERR, "ERROR: el framework no llegó a registrar las rutas sin despachar. Salida del arranque:\n");
    fwrite(STDERR, mb_substr(trim($output), 0, 2000) . "\n");
    exit(2);
});

ob_start();
require getcwd() . '/index.php';
ob_end_clean();

$app = get_config('slim_app');
if (!is_object($app) || !method_exists($app, 'getRouteCollector')) {
    fwrite(STDERR, "ERROR: `slim_app` no está en la configuración: no hay rutas que mirar.\n");
    exit(2);
}
define('PCSPHP_CHECK_ROUTES_REGISTERED', true);

$parser = new Std();
$routes = $app->getRouteCollector()->getRoutes();
$invalid = [];
foreach ($routes as $route) {
    try {
        $parser->parse($route->getPattern());
    } catch (\Throwable $e) {
        $invalid[] = [(string) $route->getName(), $route->getPattern(), get_class($e) . ': ' . $e->getMessage()];
    }
}

$total = count($routes);
echo "Rutas registradas: {$total}\n";

//Canario (LEY 15): sin rutas, el arranque no registró nada y no se miró nada.
if ($total === 0) {
    fwrite(STDERR, "ERROR: cero rutas registradas: la comprobación no miró nada.\n");
    exit(2);
}

foreach ($invalid as [$name, $pattern, $message]) {
    echo "INVÁLIDA: {$name}  {$pattern}  → {$message}\n";
}

if (count($invalid) > 0) {
    echo count($invalid) . " de {$total} rutas con patrón inválido.\n";
    exit(1);
}

echo "OK: todas las {$total} rutas analizables.\n";
exit(0);
