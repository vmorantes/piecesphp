<?php

/**
 * HelpTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;

/**
 * HelpTask.
 *
 * Muestra las tareas disponibles
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 * @see https://misc.flogisoft.com/bash/tip_colors_and_formatting Colores para texto de terminal
 */
class HelpTask extends TerminalTaskAbstract
{

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        //Procesar entrada
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'help';
        $alias = ($namePrefix !== null ? $namePrefix . '-' : '') . 'h';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];

        //Establecer propiedades
        $this->description = null;
        $this->route = "{$startRoute}/" . uniqid() . "[/]";
        $this->controller = self::class . '::main';
        $this->name = $name;
        $this->alias = $alias;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray($permissions);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {

        $cliArguments = TerminalData::getInstance()->arguments();
        $taskName = $cliArguments['task'] ?? null;

        //Tareas disponibles
        $terminalTaskAvailables = get_config('terminalTaskAvailablesVerbose');

        if (is_array($terminalTaskAvailables)) {

            $titleTask = "Tareas disponibles";
            $message = [
                "\e[32m*** {$titleTask} ***\e[39m",
            ];

            //Filtrar por tarea específica
            $taskFilterFound = false;
            if (is_string($taskName)) {
                array_map(function ($e) use (&$taskFilterFound, $taskName) {
                    if ($e['name'] == $taskName) {
                        $taskFilterFound = true;
                    }
                }, $terminalTaskAvailables);
            }

            //Obtener mensajes
            foreach ($terminalTaskAvailables as $task) {
                if ($taskFilterFound && $task['name'] != $taskName) {
                    continue;
                }
                $name = array_key_exists('name', $task) ? $task['name'] : null;
                $description = array_key_exists('description', $task) ? $task['description'] : null;
                if (is_string($name) && is_string($description)) {
                    $message[] = "\e[94mTarea: {$name}\e[39m";
                    $message[] = "\e[33m  Descripción: {$description}\e[39m";
                }
            }

            $message[] = "\e[32m*** {$titleTask}, tarea finalizada ***\e[39m";
            if (count($message) > 1) {
                echoTerminal(implode("\r\n", $message));
            }
        }
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new HelpTask($startRoute, $namePrefix);
        $route = new Route(
            $instance->route,
            $instance->controller,
            $instance->name,
            $instance->method,
            $instance->requireLogin,
            $instance->alias,
            $instance->rolesAllowed->getArrayCopy(),
            $instance->defaultParamsValues,
            $instance->middlewares
        );
        return $route;
    }

}
