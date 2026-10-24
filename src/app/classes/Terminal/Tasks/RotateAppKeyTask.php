<?php

/**
 * RotateAppKeyTask.php
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
 * RotateAppKeyTask - Cambia la clave de la aplicación sin perder nada (ADR 0061 §7).
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class RotateAppKeyTask extends TerminalTaskAbstract
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
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'rotate-app-key';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        //Establecer propiedades
        $this->description = new StringArray([
            "Cambia la app_key: respaldo de la base, clave nueva, cada dato cifrado declarado vuelto a cifrar con ella (en una\r\n",
            "\ttransacción) y la vieja al llavero. Si algo falla, no cambia nada. Las sesiones y los tokens dejan de valer.\r\n",
            "\tNunca imprime una clave: solo su huella.\r\n",
            "\tParámetros:\r\n",
            "\t  N/A\r\n",
        ]);
        $this->route = "{$startRoute}/rotate-app-key[/]";
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
        $titleTask = 'Rotando la app_key';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");
        $lines = AppKeyCommands::rotate(AppKeyCommands::installationPath(), AppKeyring::get(), AppKeyCommands::database(), static function (): ?string {
            return DbBackupTask::main(null, null, [], true) ? DbBackupTask::lastWrittenFile() : null;
        });
        foreach ($lines as $line) {
            echoTerminal("\e[34m{$line}\e[39m");
        }
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new RotateAppKeyTask($startRoute, $namePrefix);
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
