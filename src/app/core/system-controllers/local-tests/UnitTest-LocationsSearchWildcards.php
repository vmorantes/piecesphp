<?php

//Las búsquedas de Ubicaciones (países, departamentos, ciudades y puntos) leen % y _ como texto (G3), como Region desde IF.
//En proceso; crea filas zz-buscar-* en las cuatro tablas y las borra.
//@areas nucleo

use PiecesPHP\App\Locations\Controllers\City;
use PiecesPHP\App\Locations\Controllers\Country;
use PiecesPHP\App\Locations\Controllers\Point;
use PiecesPHP\App\Locations\Controllers\State;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/locations-search-wildcards', function ($args) {

    echoTerminal("\e[33m[TEST:LocationsSearchWildcards] Las búsquedas de Ubicaciones leen % y _ como texto\e[39m");
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

    $database = (new BaseModel())->getDatabase();
    if ($database === null) {
        $check(false, 'p0 hay conexión a la base');
        return $balance();
    }
    $base = 'zz-buscar-' . bin2hex(random_bytes(3));
    //Cuatro nombres: con % y su parecido (otra letra donde iba el %), con _ y el suyo.
    $nombres = ["{$base}%x", "{$base}Ax", "{$base}_y", "{$base}By"];
    $creados = ['locations_points' => [], 'locations_cities' => [], 'locations_states' => [], 'locations_countries' => []];
    $buscar = function (Country|State|City|Point $controlador, string $parametro, string $texto): array {
        $peticion = (new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/zz'), new Headers(), [], [], (new StreamFactory())->createStream('')))
            ->withQueryParams([$parametro => $texto]);
        $cuerpo = json_decode((string) $controlador->search($peticion, new ResponseRoute())->getBody(), true);
        $filas = is_array($cuerpo) ? $cuerpo : [];
        $titulos = array_map(fn ($fila): string => is_array($fila) ? (string) ($fila['title'] ?? $fila['name'] ?? '') : '', $filas);
        sort($titulos);
        return $titulos;
    };

    try {
        $insertar = function (string $tabla, array $columnas) use ($database, &$creados): int {
            $database->prepare("INSERT INTO `{$tabla}` (" . implode(', ', array_keys($columnas)) . ') VALUES (' . implode(', ', array_fill(0, count($columnas), '?')) . ')')
                ->execute(array_values($columnas));
            $id = (int) $database->lastInsertId();
            $creados[$tabla][] = $id;
            return $id;
        };
        $pais = 0;
        $departamento = 0;
        $ciudad = 0;
        foreach ($nombres as $nombre) {
            $pais = $insertar('locations_countries', ['name' => $nombre, 'active' => 1]);
            $departamento = $insertar('locations_states', ['country' => $pais, 'name' => $nombre, 'active' => 1]);
            $ciudad = $insertar('locations_cities', ['state' => $departamento, 'name' => $nombre, 'active' => 1]);
            $insertar('locations_points', ['city' => $ciudad, 'address' => 'zz', 'name' => $nombre, 'latitude' => 0, 'longitude' => 0, 'active' => 1]);
        }
        $check(count($creados['locations_points']) === 4, 'p1 cuatro filas zz por tabla en países, departamentos, ciudades y puntos', $base);

        $casos = [
            'países' => [new Country(), 'query'],
            'departamentos' => [new State(), 'query'],
            'ciudades' => [new City(), 'search'],
            'puntos' => [new Point(), 'query'],
        ];
        foreach ($casos as $que => [$controlador, $parametro]) {
            echoTerminal(' ');
            echoTerminal("[{$que}]");
            $todos = $nombres;
            sort($todos);
            $check($buscar($controlador, $parametro, $base) === $todos, "1 CANARIO: una búsqueda normal da las cuatro, como siempre", json_encode($buscar($controlador, $parametro, $base)) ?: '');
            $check($buscar($controlador, $parametro, "{$base}%x") === ["{$base}%x"], '2 DISCRIMINANTE: «%x» encuentra solo la que lleva %, no la parecida', json_encode($buscar($controlador, $parametro, "{$base}%x")) ?: '');
            $check($buscar($controlador, $parametro, "{$base}_y") === ["{$base}_y"], '3 «_y» encuentra solo la que lleva _', json_encode($buscar($controlador, $parametro, "{$base}_y")) ?: '');
        }
    } catch (\Throwable $e) {
        $check(false, 'e* el recorrido corre entero', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        //Hijos primero: los puntos apuntan a ciudades, las ciudades a departamentos y estos a países.
        foreach ($creados as $tabla => $ids) {
            foreach ($ids as $id) {
                $database->exec("DELETE FROM `{$tabla}` WHERE id = " . (int) $id);
            }
        }
        $quedan = 0;
        foreach (array_keys($creados) as $tabla) {
            $quedan += (int) $database->query("SELECT COUNT(*) FROM `{$tabla}` WHERE name LIKE " . $database->quote(str_replace(['|', '%', '_'], ['||', '|%', '|_'], $base) . '%') . " ESCAPE '|'")->fetchColumn();
        }
        $check($quedan === 0, 'z1 no queda ninguna fila de la prueba en las cuatro tablas', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('Las búsquedas de países, departamentos, ciudades y puntos leen % y _ como texto: «x%y» encuentra solo lo que lleva el %; una búsqueda normal, lo de siempre.')->setEffects([CliActions::EFFECT_DATABASE])->register();
