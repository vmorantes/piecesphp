<?php

/**
 * CleanCacheTask.php
 */

namespace Terminal\Tasks;

use App\Controller\AppConfigController;
use App\Model\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\RequestRouteFactory;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;

/**
 * CleanCacheTask.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 * @see https://misc.flogisoft.com/bash/tip_colors_and_formatting Colores para texto de terminal
 */
class CleanCacheTask extends TerminalTaskAbstract
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
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'clean-cache';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        //Establecer propiedades
        $this->description = new StringArray([
            "Fuerza la limpieza de caché de estáticos mediante la renovación del token.\r\n",
            "\tParámetros:\r\n",
            "\t  N/A\r\n",
        ]);
        $this->route = "{$startRoute}/clean-cache[/]";
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
        $titleTask = "Limpiando caché";
        $message = [
            "\e[32m*** {$titleTask} ***\e[39m",
        ];

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            $controllerConfig = new AppConfigController();
            $response = $controllerConfig->recreateStaticCacheStamp(RequestRouteFactory::createFromGlobals(), new ResponseRoute());
            $responseJSON = json_decode($response->getLastWriteBodyData(), true);
            $responseMessage = $responseJSON['message'];

            $message[] = "\e[34m{$responseMessage}\e[39m";

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
        $instance = new CleanCacheTask($startRoute, $namePrefix);
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