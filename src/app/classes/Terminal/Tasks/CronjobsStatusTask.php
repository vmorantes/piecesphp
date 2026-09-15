<?php

/**
 * CronjobsStatusTask.php
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
 * CronjobsStatusTask.
 *
 * Muestra el estado de cada tarea cron: su franja, su último éxito, sus intentos y su último error.
 *
 * Es de SOLO LECTURA: no ejecuta ninguna tarea ni escribe estado.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class CronjobsStatusTask extends TerminalTaskAbstract
{

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'cronjobs-status';

        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        $this->description = new StringArray([
            "Estado de las tareas cron: franja, último éxito, intentos y último error.\r\n",
            "\tSolo lectura: no ejecuta nada.\r\n",
            "\tParámetros:\r\n",
            "\t  N/A\r\n",
        ]);
        $this->route = "{$startRoute}/cronjobs-status[/]";
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
        $titleTask = 'Estado de los cronjobs';
        $message = [
            "\e[32m*** {$titleTask} ***\e[39m",
        ];

        $systemCronjobs = CronJobTask::getCronJobs();
        $now = new \DateTime();

        if (empty($systemCronjobs)) {
            $message[] = "\e[33mNo hay cronjobs registrados.\e[39m";
        }

        foreach ($systemCronjobs as $cronTask) {
            $name = $cronTask->getName();
            if (!$cronTask->hasSchedule()) {
                $message[] = "- {$name}: sin franja (corre cuando su condición lo dice; sin reintentos ni estado)";
                continue;
            }
            $slot = $cronTask->lastDueSlot($now);
            $state = $cronTask->getState();
            $message[] = "- {$name}";
            $message[] = '    franja vencida: ' . ($slot !== null ? $slot->format('Y-m-d H:i') : '-')
                . " · ventana: {$cronTask->getRecoveryWindow()} min · ahora: {$cronTask->dueStatus($now)}";
            $message[] = '    último éxito: ' . ($state['lastSuccessSlot'] ?? 'nunca')
                . " · intentos: {$state['attemptsForSlot']} de {$cronTask->getMaxAttempts()} (franja " . ($state['lastAttemptSlot'] ?? '-') . ')'
                . ' · último intento: ' . ($state['lastAttemptAt'] ?? '-');
            $message[] = '    último resultado: ' . ($state['lastResult'] ?? '-') . ' · último error: ' . ($state['lastError'] ?? '-');
        }

        $message[] = "\e[32m*** {$titleTask}, tarea finalizada ***\e[39m";
        echoTerminal(implode("\r\n", $message));
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new CronjobsStatusTask($startRoute, $namePrefix);
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
