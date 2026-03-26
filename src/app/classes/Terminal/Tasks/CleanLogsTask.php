<?php

/**
 * CleanLogsTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\Core\Helpers\Directories\FilesIgnore;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;

/**
 * CleanLogsTask.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 * @see https://misc.flogisoft.com/bash/tip_colors_and_formatting Colores para texto de terminal
 */
class CleanLogsTask extends TerminalTaskAbstract
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
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'clean-logs';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        //Establecer propiedades
        $this->description = new StringArray([
            "Limpia los archivos de logs.\r\n",
            "\tParámetros:\r\n",
            "\t  N/A",
        ]);
        $this->route = "{$startRoute}/clean-logs[/]";
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

        //Mensaje de respuesta
        $titleTask = "Eliminando archivos de logs";
        $message = [
            "\e[32m*** {$titleTask} ***\e[39m",
        ];

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            $baseLogsDirectory = basepath("app/logs");
            $oldsErrorLogsDirectory = basepath("app/logs/olds");
            $expiredSessionsLogsDirectory = basepath("app/logs/expired-sessions");
            $errorLogFile = basepath("app/logs/error.log.json");
            $errorLogPlanFile = basepath("app/logs/error.plain.log");

            if (file_exists($baseLogsDirectory)) {

                //Log de errores
                file_put_contents($errorLogFile, '[]');
                chmod($errorLogFile, 0777);
                $message[] = "\e[34merror.log.json vaciado.\e[39m";

                //Log de errores planto
                file_put_contents($errorLogPlanFile, '');
                chmod($errorLogPlanFile, 0777);
                $message[] = "\e[34merror.plain.log vaciado.\e[39m";

                //Histórico de logs de errores
                $oldsErrorLogsHandler = new DirectoryObject($oldsErrorLogsDirectory);
                if ($oldsErrorLogsHandler->directoryExists()) {
                    $oldsErrorLogsHandler->process(new FilesIgnore([
                        '\.keep',
                    ]));
                    $oldsErrorLogsHandler->delete(false);
                    $message[] = "\e[34mLogs de errores antiguos vaciado.\e[39m";
                }

                //Logs de sesiones expiradas
                $expiredSessionsLogsHandler = new DirectoryObject($expiredSessionsLogsDirectory);
                if ($expiredSessionsLogsHandler->directoryExists()) {
                    $expiredSessionsLogsHandler->process(new FilesIgnore([
                        '\.keep',
                    ]));
                    $expiredSessionsLogsHandler->delete(false);
                    $message[] = "\e[34mLogs de sesiones expiradas vaciado.\e[39m";
                }

            }

        } catch (\Exception $e) {

            $message[] = "\e[31mHa ocurrido un error: {$e->getMessage()}\e[39m";
            log_exception($e);

        }

        $message[] = "\e[32m*** {$titleTask}, tarea finalizada ***\e[39m";
        if (count($message) > 1) {
            echoTerminal(implode("\r\n", $message));
        }
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new CleanLogsTask($startRoute, $namePrefix);
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
