<?php

//Dos grupos de rutas con el MISMO prefijo: los dos se registran y ninguna ruta se pierde (P88).
//Antes se guardaban por prefijo y el segundo grupo desaparecía sin error. No toca la aplicación servida.

use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RouteGroupAdapter;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/route-groups-same-prefix', function ($args) {

    echoTerminal("\e[33m[TEST:RouteGroupsSamePrefix] Dos grupos con el mismo prefijo registran las rutas de los dos\e[39m");
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

    //`self::$groups` es protegida: se lee por reflexión y se restaura al terminar.
    //Sin `setAccessible()`: deprecado en PHP 8.5, y desde 8.1 la reflexión ya lee lo protegido.
    $propiedad = new \ReflectionProperty(RouteGroupAdapter::class, 'groups');
    $grupos = fn (): array => (array) $propiedad->getValue();
    $previos = $grupos();

    $rutasDe = function (RouteGroupAdapter $grupo): array {
        $p = new \ReflectionProperty(RouteGroupAdapter::class, 'routes');
        //`name()` es getter y setter a la vez: sin argumento devuelve el nombre, con argumento
        //devuelve la ruta. Se estrecha, no se castea.
        return array_map(function (Route $r): string {
            $nombre = $r->name();
            return is_string($nombre) ? $nombre : '';
        }, (array) $p->getValue($grupo));
    };
    $middlewaresDe = function (RouteGroupAdapter $grupo): array {
        $p = new \ReflectionProperty(RouteGroupAdapter::class, 'middlewares');
        return (array) $p->getValue($grupo);
    };
    $ruta = fn (string $nombre): Route => new Route("/{$nombre}[/]", fn () => null, $nombre, 'GET', false);
    $nuestros = fn (array $todos): array => array_values(array_filter($todos, fn ($g) => str_starts_with($g->getGroupSegment(), '/zz-p88')));

    try {

        //─── a · El canario: un grupo con prefijo propio SÍ se registra ──────────────────────────
        echoTerminal('[a] El canario: sin esto, todo lo demás sería gratis');
        $solo = new RouteGroup('/zz-p88-solo');
        $solo->register([$ruta('zz-p88-solo-uno')]);
        $registrados = $nuestros($grupos());
        $check(count($registrados) === 1, 'a1 un grupo con prefijo propio queda registrado', 'hay ' . count($registrados));
        $check($rutasDe($solo) === ['zz-p88-solo-uno'], 'a2 y con su ruta dentro');

        //─── b · Dos grupos, el mismo prefijo ───────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] Dos grupos DISTINTOS con el mismo prefijo: los dos quedan');
        $primero = new RouteGroup('/zz-p88-igual');
        $primero->addMiddleware('zz-mw-primero');
        $primero->register([$ruta('zz-p88-primera')]);

        $segundo = new RouteGroup('/zz-p88-igual');
        $segundo->addMiddleware('zz-mw-segundo');
        $segundo->register([$ruta('zz-p88-segunda')]);

        $conEsePrefijo = array_values(array_filter($grupos(), fn ($g) => $g->getGroupSegment() === '/zz-p88-igual'));
        $check(count($conEsePrefijo) === 2, 'b1 los DOS grupos del mismo prefijo están registrados', 'hay ' . count($conEsePrefijo));

        $todasLasRutas = [];
        foreach ($conEsePrefijo as $g) {
            $todasLasRutas = array_merge($todasLasRutas, $rutasDe($g));
        }
        sort($todasLasRutas);
        $check($todasLasRutas === ['zz-p88-primera', 'zz-p88-segunda'], 'b2 y las rutas de los dos siguen ahí, ninguna perdida', implode(',', $todasLasRutas));
        $check($middlewaresDe($primero) === ['zz-mw-primero'] && $middlewaresDe($segundo) === ['zz-mw-segundo'], 'b3 cada grupo conserva SUS middlewares, sin mezclarse');

        //─── c · La misma instancia, dos veces ──────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] La misma instancia que registra dos veces no se duplica');
        $antes = count($grupos());
        $primero->register([$ruta('zz-p88-primera-bis')]);
        $despues = count($grupos());
        $check($antes === $despues, 'c1 registrar otra vez la misma instancia no añade otra entrada', "{$antes} -> {$despues}");
        $check($rutasDe($primero) === ['zz-p88-primera', 'zz-p88-primera-bis'], 'c2 y sus rutas se suman, no se pierden', implode(',', $rutasDe($primero)));

        //─── d · Qué hace Slim con dos rutas del mismo nombre ───────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[d] Dos rutas con el MISMO nombre: qué hace Slim (medido, no deducido)');
        //Sobre una aplicación de Slim PROPIA, no la servida: así la medida no toca el router real.
        $slim = \Slim\Factory\AppFactory::create();
        $slim->get('/zz-dup-a[/]', fn () => null)->setName('zz-p88-duplicada');
        $slim->get('/zz-dup-b[/]', fn () => null)->setName('zz-p88-duplicada');
        $colector = $slim->getRouteCollector();
        $lanzo = false;
        $resuelta = null;
        try {
            $resuelta = $colector->getNamedRoute('zz-p88-duplicada')->getPattern();
        } catch (\Throwable $e) {
            $lanzo = true;
        }
        $cuantas = count(array_filter($colector->getRoutes(), fn ($r) => $r->getName() === 'zz-p88-duplicada'));
        $check($cuantas === 2, 'd1 Slim ACEPTA las dos rutas con el mismo nombre: ' . $cuantas);
        $check($lanzo === false, 'd2 y resolver ese nombre NO lanza: ' . ($lanzo ? 'lanzó' : 'devolvió ' . (string) $resuelta));
        echoTerminal("      \e[33mMEDIDO: Slim CALLA ante un nombre duplicado y resuelve «{$resuelta}», la primera registrada.\e[39m");
        echoTerminal("      \e[33mEl nombre de una ruta ES el identificador de permiso: un duplicado silencioso es decisión del arquitecto.\e[39m");

    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    }

    //─── z · Limpieza: `self::$groups` vuelve a lo que había ─────────────────────────────────────
    echoTerminal(' ');
    echoTerminal('[z] Limpieza');
    $propiedad->setValue(null, $previos);
    $check(count($grupos()) === count($previos) && count($nuestros($grupos())) === 0, 'z1 los grupos de la prueba salieron del registro', 'quedan ' . count($grupos()));

    return $balance();

})->setDescription('Dos grupos con el mismo prefijo registran las rutas de los dos; la misma instancia dos veces no duplica; y qué hace Slim con dos rutas del mismo nombre.')->setEffects([CliActions::EFFECT_NONE])->register();
