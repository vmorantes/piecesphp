<?php

/**
 * SyncOTPRecordsTask.php
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
use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;

/**
 * SyncOTPRecordsTask.
 *
 * Crea los registros OTP que falten, uno por usuario y método.
 *
 * NO LA LLAMES DESDE EL REGISTRO DE RUTAS NI DE UNA PETICIÓN: recorre la tabla entera de
 * usuarios con `GROUP_CONCAT` y `LEFT JOIN`, y `routes()` corre en cada carga de página.
 *
 * SIN `apply=yes` SOLO INFORMA. Una tarea que escribe en cuanto se teclea su nombre es una
 * trampa, y el inventario en seco es además la forma de comprobar que cuadra antes de tocar
 * nada.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SyncOTPRecordsTask extends TerminalTaskAbstract
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
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'sync-otp-records';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        //Establecer propiedades
        $this->description = new StringArray([
            "Crea los registros OTP que falten, uno por usuario y método.\r\n",
            "\tSustituye a la llamada que vivía en el registro de rutas y corría en cada petición.\r\n",
            "\tPor defecto SOLO INFORMA: no escribe nada.\r\n",
            "\tParámetros:\r\n",
            "\t  apply (yes|no) aplica los cambios de verdad. Por defecto: no",
        ]);
        $this->route = "{$startRoute}/sync-otp-records[/]";
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
        $titleTask = "Sincronizando registros OTP";
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");

        $apply = TerminalData::instance()->getArgument('apply', 'no') === 'yes';

        $missing = OTPSecretsUsersMapper::missingOTPRecords();
        $total = 0;
        foreach ($missing as $method => $userIDs) {
            $count = count($userIDs);
            $total += $count;
            echoTerminal("\e[94mINFO:\e[39m método {$method}: {$count} usuario(s) sin registro.");
        }

        if ($total === 0) {
            echoTerminal("\e[32mOK:\e[39m todos los usuarios tienen sus registros OTP.");
            echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
            exit(0);
        }

        if (!$apply) {
            echoTerminal("\e[33mSIMULACIÓN:\e[39m se crearían {$total} registro(s). No se ha escrito nada.");
            echoTerminal("\e[33mPara aplicarlo de verdad:\e[39m");
            echoTerminal("  bin/cli sync-otp-records apply=yes");
            echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
            exit(0);
        }

        $created = OTPSecretsUsersMapper::createOTPAlternativesRecords();
        echoTerminal("\e[32mOK:\e[39m {$created} registro(s) creados.");
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
        exit(0);
    }

    /**
     * @param string $startRoute
     * @param string|null $namePrefix
     * @return Route
     */
    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new SyncOTPRecordsTask($startRoute, $namePrefix);
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
