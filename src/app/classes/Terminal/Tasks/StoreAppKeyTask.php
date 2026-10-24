<?php

/**
 * StoreAppKeyTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\Crypto\AppKeyring;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\TerminalData;

/**
 * StoreAppKeyTask - Mueve la clave de config.php al llavero de secure-keys, sin cambiarla (ADR 0061 §5).
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class StoreAppKeyTask extends TerminalTaskAbstract
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
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'store-app-key';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        //Establecer propiedades
        $this->description = new StringArray([
            "Mueve la app_key de config.php a secure-keys/app-key.json, SIN cambiarla. Después, quítela de config.php.\r\n",
            "\tSolo si aún no hay llavero. Nunca imprime la clave: solo su huella.\r\n",
            "\tParámetros:\r\n",
            "\t  N/A\r\n",
        ]);
        $this->route = "{$startRoute}/store-app-key[/]";
        $this->controller = self::class . '::main';
        $this->name = $name;
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray($permissions);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    /**
     * @param RequestRoute|null $requestRoute
     * @param ResponseRoute|null $responseRoute
     * @param array<mixed>|null $parameters
     * @return void
     * @throws \Throwable Si falla: bin/cli sale con código distinto de 0
     */
    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = 'Guardando la app_key en el llavero';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");
        $lines = AppKeyCommands::store(AppKeyCommands::installationPath(), AppKeyring::get());
        foreach ($lines as $line) {
            echoTerminal("\e[34m{$line}\e[39m");
        }
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new StoreAppKeyTask($startRoute, $namePrefix);
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
