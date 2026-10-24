<?php

/**
 * GenerateAppKeyTask.php
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

/**
 * GenerateAppKeyTask - Crea el llavero de secure-keys con una clave nueva (ADR 0061 §5). Nunca la imprime.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class GenerateAppKeyTask extends TerminalTaskAbstract
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
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'generate-app-key';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        //Establecer propiedades
        $this->description = new StringArray([
            "Crea secure-keys/app-key.json con una app_key nueva (64 caracteres hexadecimales). Nunca la imprime: solo su huella.\r\n",
            "\tSolo si aún no hay llavero (con uno: rotate-app-key). La clave de ahora queda como anterior, para leer lo cifrado.\r\n",
            "\tLas sesiones y los tokens emitidos con la anterior dejan de valer.\r\n",
            "\tParámetros:\r\n",
            "\t  N/A\r\n",
        ]);
        $this->route = "{$startRoute}/generate-app-key[/]";
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
        $titleTask = 'Generando app_key';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");
        foreach (AppKeyCommands::generate(AppKeyCommands::installationPath(), AppKeyring::get()) as $line) {
            echoTerminal("\e[34m{$line}\e[39m");
        }
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
    }

    /**
     * @return string 64 caracteres hexadecimales
     */
    public static function generate(): string
    {
        return AppKeyring::generateKey();
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new GenerateAppKeyTask($startRoute, $namePrefix);
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
