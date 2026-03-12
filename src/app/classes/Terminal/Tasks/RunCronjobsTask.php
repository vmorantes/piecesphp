<?php

/**
 * RunCronjobsTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CronJobTask;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;

/**
 * RunCronjobsTask.
 *
 * Ejecuta las tareas cronjobs en segundo plano sin límites de tiempo HTTP.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 * @see https://misc.flogisoft.com/bash/tip_colors_and_formatting Colores para texto de terminal
 */
class RunCronjobsTask extends TerminalTaskAbstract
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
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'run-cronjobs';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        //Establecer propiedades
        $this->description = new StringArray([
            "Ejecuta las tareas cronjobs en segundo plano sin límites de tiempo HTTP.\r\n",
            "\tParámetros:\r\n",
            "\t  N/A\r\n",
        ]);
        $this->route = "{$startRoute}/run-cronjobs[/]";
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
        // Mensaje de respuesta
        $titleTask = "Ejecutando Cronjobs del Sistema";
        $message = [
            "\e[32m*** {$titleTask} ***\e[39m",
        ];

        try {
            $systemCronjobs = CronJobTask::getCronJobs();
            $executedCount = 0;

            if (empty($systemCronjobs)) {
                $message[] = "\e[33mNo hay cronjobs registrados.\e[39m";
            } else {
                foreach ($systemCronjobs as $cronTask) {
                    if ($cronTask->shouldExecute()) {
                        $message[] = "\e[34m-> Ejecutando: {$cronTask->getName()}\e[39m";
                        $result = $cronTask->execute();
                        $statusColor = $result['success'] ? "\e[32m" : "\e[31m";
                        $message[] = "{$statusColor}   Resultado: {$result['message']}\e[39m";

                        if (!$result['success'] && isset($result['error'])) {
                            $message[] = "\e[31m   Error Detail: {$result['error']}\e[39m";
                        }

                        $executedCount++;
                    }
                }
                $message[] = "\e[36mSe revisaron " . count($systemCronjobs) . " tareas en total. Se ejecutaron {$executedCount}.\e[39m";
            }

        } catch (\Exception $e) {
            $message[] = "\e[31mHa ocurrido un error general: {$e->getMessage()}\e[39m";
            log_exception($e);
        }

        $message[] = "\e[32m*** {$titleTask}, tarea finalizada ***\e[39m";
        if (count($message) > 1) {
            echoTerminal(implode("\r\n", $message));
        }
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new RunCronjobsTask($startRoute, $namePrefix);
        $route = new Route(
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
        return $route;
    }

}
