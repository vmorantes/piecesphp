<?php

/**
 * RouteInventoryTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\TerminalData;

/**
 * RouteInventoryTask.
 *
 * Vuelca el INVENTARIO DE RUTAS que el propio framework tiene registrado, en JSON.
 *
 * Existe para que el recorredor de rutas no trabaje sobre una lista escrita a mano: una
 * lista a mano envejece en silencio y deja de cubrir justo lo que se acaba de añadir. El
 * framework ya sabe cuáles son todas sus rutas —es lo que muestra `/configurations/routes/`—
 * y esto lo expone sin necesidad de sesión ni de navegador.
 *
 * Es de SOLO LECTURA: no toca base de datos ni escribe nada fuera del archivo de salida.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class RouteInventoryTask extends TerminalTaskAbstract
{

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'route-inventory';

        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        $this->description = new StringArray([
            "Vuelca en JSON el inventario de rutas registrado por el framework.\r\n",
            "\tSolo lectura. Lo consume el recorredor de rutas (bin/walk-routes).\r\n",
            "\tParámetros:\r\n",
            "\t  output=<ruta> archivo de salida. Por defecto: files/dev/route-inventory.json",
        ]);
        $this->route = "{$startRoute}/route-inventory[/]";
        $this->controller = self::class . '::main';
        $this->name = $name;
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray($permissions);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = 'Inventario de rutas';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");

        $repoRoot = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $output = TerminalData::instance()->getArgument('output', $repoRoot . '/files/dev/route-inventory.json');
        $output = is_string($output) ? $output : $repoRoot . '/files/dev/route-inventory.json';

        $routes = get_routes();
        if (!is_array($routes)) {
            echoTerminal("\e[31mERROR:\e[39m el framework no devolvió ningún inventario.");
            exit(1);
        }

        $inventory = [];

        foreach ($routes as $route) {
            $name = $route['name'] ?? null;
            if (!is_string($name) || $name === '') {
                continue;
            }

            $parametersOfRoute = (array) ($route['parameters'] ?? []);

            /**
             * `get_route()` es el resolutor del propio framework: respeta el prefijo de
             * idioma y los grupos. Se le pide en modo silencioso para que una ruta que no
             * resuelva devuelva cadena vacía en vez de lanzar.
             */
            $url = '';
            if (count($parametersOfRoute) === 0) {
                $resolved = get_route($name, [], true);
                $url = is_string($resolved) ? $resolved : '';
            }

            $inventory[] = [
                'name' => $name,
                'method' => $route['method'] ?? 'GET',
                'pattern' => $route['route'] ?? '',
                'requireLogin' => (bool) ($route['require_login'] ?? false),
                'rolesAllowed' => array_values((array) ($route['roles_allowed'] ?? [])),
                'parameters' => array_values($parametersOfRoute),
                'url' => $url,
            ];
        }

        usort($inventory, fn (array $a, array $b) => strcmp($a['name'], $b['name']));

        $json = json_encode($inventory, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
        file_put_contents($output, $json . "\n");

        $withUrl = count(array_filter($inventory, fn (array $r) => $r['url'] !== ''));
        $withParameters = count(array_filter($inventory, fn (array $r) => count($r['parameters']) > 0));

        echoTerminal("\e[94mINFO:\e[39m " . count($inventory) . " rutas registradas.");
        echoTerminal("\e[94mINFO:\e[39m {$withUrl} con URL resoluble sin parámetros.");
        echoTerminal("\e[94mINFO:\e[39m {$withParameters} exigen parámetros y NO se resuelven aquí.");
        echoTerminal("\e[34mEscrito:\e[39m {$output}");
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
        exit(0);
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new RouteInventoryTask($startRoute, $namePrefix);
        return new Route(
            $instance->route,
            $instance->controller,
            $instance->name,
            $instance->method,
            $instance->requireLogin,
            null,
            $instance->rolesAllowed->getArrayCopy(),
            $instance->defaultParamsValues,
            $instance->middlewares
        );
    }

}
