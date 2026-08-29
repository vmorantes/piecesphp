<?php

/**
 * SchemeDropTask.php
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
 * SchemeDropTask.
 *
 * EMITE el SQL de borrado de las tablas de un módulo. No lo ejecuta.
 *
 * La regla 7 dice que el SQL de las tablas se genera y no se escribe a mano — pero solo
 * existía hacia adelante. Deshacer un módulo obligaba a escribir a mano justo lo que la
 * regla prohíbe, y cada despliegue que actualice necesita ese SQL.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SchemeDropTask extends SchemeSqlTask
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
            "EMITE el SQL de borrado de las tablas de un módulo. NO lo ejecuta.\r\n",
            "\tParámetros:\r\n",
            "\t  module=<Nombre>  módulo bajo src/app/classes, o `all`. Obligatorio\r\n",
            "\t  output=<ruta>    archivo de salida. Por defecto: se imprime",
        ]);
        $this->route = "{$startRoute}/scheme-drop[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'scheme-drop';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = 'SQL de borrado';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");

        $module = self::requestedModule();

        //`dropScript()` llega en piecesphp/database v3.3.0: sin ella esto no puede emitir nada.
        if (!method_exists(SchemeCreator::class, 'dropScript')) {
            echoTerminal("\e[31mERROR:\e[39m esta tarea necesita piecesphp/database >= 3.3.0. Corre: composer update piecesphp/database");
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

        $script = SchemeCreator::dropScript($found['creators']);

        //LAS VISTAS VAN DELANTE: una vista sobre una tabla borrada mata al exportador. Ver T141.
        $tables = array_values(array_unique(array_map(
            static fn (SchemeCreator $creator): string => $creator->getTable(),
            $found['creators']
        )));
        $views = self::viewsDependingOn($tables);

        if (!$views['readable']) {
            //NO se calla: sin la fuente, este guion no puede afirmar que no hay vistas.
            echoTerminal("\e[33mAVISO:\e[39m no se pudo leer {$views['source']}: las vistas NO se han comprobado.");
        } elseif (count($views['views']) > 0) {
            $head = "-- Vistas que seleccionan de estas tablas. VAN PRIMERO: una vista sobre una\r\n";
            $head .= "-- tabla borrada rompe el exportador entero. Fuente: databases/piecesphp_views.sql\r\n";
            foreach ($views['views'] as $view) {
                $head .= "DROP VIEW IF EXISTS `{$view}`;\r\n";
            }
            $script = $head . "\r\n" . $script;
        }

        self::emit($script, count($found['creators']), $found['skipped']);
        echoTerminal("\e[94mINFO:\e[39m " . count($views['views']) . ' vista(s) dependiente(s) en el script.');
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
        exit(0);
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new SchemeDropTask($startRoute, $namePrefix);
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
