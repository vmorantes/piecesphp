<?php

/**
 * SealLegacyDataTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Core\Crypto\AppKeyring;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * SealLegacyDataTask - Cifra con su propósito lo que quedó en un formato de antes de la 9, sin cambiar la clave (ADR 0061 §11).
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SealLegacyDataTask extends TerminalTaskAbstract
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
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'seal-legacy-data';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        //Establecer propiedades
        $this->description = new StringArray([
            "Vuelve a cifrar con la clave actual lo que quedó en un formato de antes de la 9 (el secreto TOTP en claro, el\r\n",
            "\tcuerpo de los correos en AES-CBC, opciones ofuscadas) o con una clave anterior. No cambia la clave ni cierra sesiones.\r\n",
            "\tHace un respaldo de la base antes y escribe en una transacción. Se puede repetir: la segunda vez no hay nada.\r\n",
            "\tParámetros:\r\n",
            "\t  N/A\r\n",
        ]);
        $this->route = "{$startRoute}/seal-legacy-data[/]";
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
        $titleTask = 'Cifrando lo de antes de la 9';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");
        $lines = AppKeyCommands::sealLegacy(AppKeyring::get(), AppKeyCommands::database(), static function (): ?string {
            return DbBackupTask::main(null, null, [], true) ? DbBackupTask::lastWrittenFile() : null;
        });
        foreach ($lines as $line) {
            echoTerminal("\e[34m{$line}\e[39m");
        }
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new SealLegacyDataTask($startRoute, $namePrefix);
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
