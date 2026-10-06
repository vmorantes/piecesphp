<?php

/**
 * DbBackupRotateTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Core\Backups\BackupPolicy;
use PiecesPHP\Core\Backups\BackupRotation;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * DbBackupRotateTask.
 *
 * Aplicar la conservación de respaldos de la política. Ver ADR 0038 §3.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class DbBackupRotateTask extends TerminalTaskAbstract
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
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'db-backup-rotate';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        //Establecer propiedades
        $this->description = new StringArray([
            "Aplica la conservación de respaldos de la política: enseña qué se conserva y qué se borraría.\r\n",
            "\tParámetros:\r\n",
            "\t  apply (yes|no) borrar de verdad lo que sobra. Por defecto: no\r\n",
            "\t  dir (ruta) carpeta a revisar, SOLO para pruebas: tiene que estar bajo el directorio temporal del sistema. Por defecto: dumps/\r\n",
        ]);
        $this->route = "{$startRoute}/db-backup-rotate[/]";
        $this->controller = self::class . '::main';
        $this->name = $name;
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray($permissions);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = [], bool $throwExceptions = false): bool
    {

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $responseText = "";
        $success = false;
        $exceptionToThrow = null;

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            $apply = TerminalData::instance()->getArgument('apply', 'no') === 'yes';
            $directory = (string) TerminalData::instance()->getArgument('dir', '');

            if ($directory !== '') {
                //`dir` existe para las pruebas. Una ruta cualquiera convertiría esta tarea en un
                //borrador de carpetas ajenas, así que solo se acepta bajo el temporal del sistema.
                $temporary = realpath(sys_get_temp_dir());
                $resolved = realpath($directory);
                if ($temporary === false || $resolved === false || !str_starts_with($resolved, $temporary . \DIRECTORY_SEPARATOR)) {
                    throw new \Exception("El parámetro dir solo acepta una carpeta bajo " . sys_get_temp_dir() . ": «{$directory}» no lo es.");
                }
                $directory = $resolved;
            } else {
                $directory = BackupPolicy::dumpsDirectory();
            }

            $policy = BackupPolicy::current();
            $plan = BackupRotation::plan(BackupRotation::namesIn($directory), $policy);

            $responseText = "Carpeta: {$directory}\r\n";
            $responseText .= 'Política: ' . ($policy['rotate'] ? 'conservación activa' : 'conservación DESACTIVADA (rotate=no): no se borra nada') . "\r\n";
            $responseText .= "\tlos últimos {$policy['keep_recent']}, {$policy['keep_daily']} día(s), {$policy['keep_weekly']} semana(s) y {$policy['keep_monthly']} mes(es)\r\n";
            $responseText .= 'Se conservan: ' . count($plan['keep']) . "\r\n";
            $responseText .= 'Se ignoran (no los escribió el framework): ' . count($plan['ignored']) . "\r\n";
            $responseText .= 'Se borrarían: ' . count($plan['delete']) . "\r\n";

            foreach ($plan['delete'] as $name) {
                $responseText .= "\t- {$name}\r\n";
            }

            if ($apply) {
                $result = BackupRotation::apply($directory, $policy);
                $responseText .= 'Borrados: ' . count($result['deleted']) . "\r\n";
                if (count($result['failed']) > 0) {
                    $responseText .= 'NO se pudieron borrar ' . count($result['failed']) . ': ' . implode(', ', $result['failed']) . "\r\n";
                }
                $responseText .= "Quedan: " . count(BackupRotation::namesIn($directory)) . " archivo(s) en la carpeta.\r\n";
            } else {
                $responseText .= "Nada se ha borrado. Para borrar: apply=yes\r\n";
            }

            $success = true;

        } catch (\Exception $e) {

            $exceptionToThrow = $e;
            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        }

        systemOutFormatted($responseText);

        if ($throwExceptions && $exceptionToThrow !== null) {
            throw $exceptionToThrow;
        }

        //Mismo trato que `db-backup`: un fallo tiene que notarse fuera del proceso, y solo en
        //terminal, porque por HTTP el `exit` truncaría la respuesta.
        if (!$success && TerminalData::getInstance()->isTerminal()) {
            exit(1);
        }

        return $success;
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new DbBackupRotateTask($startRoute, $namePrefix);
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
