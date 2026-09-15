<?php

/**
 * GenerateAppKeyTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;

/**
 * GenerateAppKeyTask - Imprime una app_key nueva para pegarla en config.php. No escribe nada.
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
            "Imprime una app_key nueva (64 caracteres hexadecimales, 32 bytes aleatorios) para pegarla en config.php.\r\n",
            "\tNo escribe nada: cambiarla invalida las sesiones y los tokens emitidos con la anterior.\r\n",
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

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = 'Generando app_key';
        echoTerminal(implode("\r\n", [
            "\e[32m*** {$titleTask} ***\e[39m",
            "\e[34mCópiela en config.php como \$config['app_key']. Las sesiones y los tokens emitidos con la anterior dejarán de valer.\e[39m",
            self::generate(),
            "\e[32m*** {$titleTask}, tarea finalizada ***\e[39m",
        ]));
    }

    /**
     * @return string 64 caracteres hexadecimales
     */
    public static function generate(): string
    {
        return bin2hex(random_bytes(32));
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
