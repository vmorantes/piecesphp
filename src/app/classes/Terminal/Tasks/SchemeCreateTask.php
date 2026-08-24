<?php

/**
 * SchemeCreateTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use PiecesPHP\Core\Database\SchemeCreator;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;

/**
 * SchemeCreateTask.
 *
 * EMITE el SQL de creación de las tablas de un módulo. No lo ejecuta.
 *
 * La regla 7 dice que el SQL de las tablas se genera y no se escribe a mano, pero la única
 * forma de invocarla era editar el código fuente de cada `<Modulo>Routes` y poner un
 * literal `$showSQL` en true. Eso no es una herramienta: es un interruptor escondido, y
 * además solo existía en once de los módulos.
 *
 * Comparte descubrimiento con `scheme-drop` y orden con `SchemeCreator`: mismo grafo,
 * recorrido al revés.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SchemeCreateTask extends SchemeSqlTask
{

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }

        $this->description = new StringArray([
            "EMITE el SQL de creación de las tablas de un módulo. NO lo ejecuta.\r\n",
            "\tParámetros:\r\n",
            "\t  module=<Nombre>  módulo bajo src/app/classes, o `all`. Obligatorio\r\n",
            "\t  output=<ruta>    archivo de salida. Por defecto: se imprime",
        ]);
        $this->route = "{$startRoute}/scheme-create[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'scheme-create';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = 'SQL de creación';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");

        $module = self::requestedModule();

        //`createScript()` llega en piecesphp/database v3.3.0: sin ella esto no puede emitir nada.
        if (!method_exists(SchemeCreator::class, 'createScript')) {
            echoTerminal("\e[31mERROR:\e[39m esta tarea necesita piecesphp/database >= 3.4.0. Corre: composer update piecesphp/database");
            exit(1);
        }

        $found = self::discover($module);
        if (count($found['creators']) === 0) {
            echoTerminal("\e[31mERROR:\e[39m no se pudo construir ningún mapper de {$module}.");
            foreach ($found['skipped'] as $line) {
                echoTerminal("  \e[33m{$line}\e[39m");
            }
            exit(1);
        }

        self::emit(SchemeCreator::createScript($found['creators']), count($found['creators']), $found['skipped']);
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
        exit(0);
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new SchemeCreateTask($startRoute, $namePrefix);
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
