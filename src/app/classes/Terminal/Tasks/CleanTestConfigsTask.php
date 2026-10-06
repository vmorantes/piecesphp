<?php

/**
 * CleanTestConfigsTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\TerminalData;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * CleanTestConfigsTask.
 *
 * Retira de `pcsphp_app_config` las filas que dejan las pruebas, y SOLO esas.
 *
 * El cerrojo es el prefijo: una clave que no empiece por `zz` se rechaza ANTES de tocar nada, así
 * que esta herramienta no puede borrar una configuración real ni por error. Existe porque crear
 * una fila de prueba se podía y retirarla no: sin ella, limpiar el rastro exigía un cliente de
 * base de datos, que está bloqueado, o restaurar un volcado, que deshace también lo legítimo.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class CleanTestConfigsTask extends TerminalTaskAbstract
{

    /**
     * El único prefijo que esta tarea puede borrar.
     *
     * @var string
     */
    const TEST_PREFIX = 'zz';

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        //Procesar entrada
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'clean-test-configs';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        //Establecer propiedades
        $this->description = new StringArray([
            "Retira las configuraciones de prueba, las que empiezan por «" . self::TEST_PREFIX . "».\r\n",
            "\tSin apply=yes solo las enumera. Una clave sin ese prefijo se rechaza.\r\n",
            "\tParámetros:\r\n",
            "\t  apply=yes      borra. Sin esto, no escribe nada\r\n",
            "\t  key=<nombre>   solo esa clave, que también tiene que llevar el prefijo",
        ]);
        $this->route = "{$startRoute}/clean-test-configs[/]";
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
        $titleTask = "Retirando configuraciones de prueba";
        $message = [
            "\e[32m*** {$titleTask} ***\e[39m",
        ];

        //Los argumentos de la línea de órdenes se leen por TerminalData, como en
        //RepairEscapedTextTask:70. El array $parameters son los de la RUTA, no los del CLI.
        $apply = TerminalData::instance()->getArgument('apply', '') === 'yes';
        $key = TerminalData::instance()->getArgument('key', '');
        $key = is_string($key) && mb_strlen(trim($key)) > 0 ? trim($key) : null;

        try {

            //EL CERROJO, Y VA PRIMERO: antes de leer la tabla, antes del volcado y antes de borrar.
            //Una comprobación después de actuar rechaza igual y ya ha hecho el daño.
            if ($key !== null && mb_strpos($key, self::TEST_PREFIX) !== 0) {
                $message[] = "\e[31mSE NIEGA: «{$key}» no empieza por «" . self::TEST_PREFIX . "».\e[39m";
                $message[] = "\e[31mEsta tarea solo puede borrar configuraciones de prueba. No se ha tocado nada.\e[39m";
                $message[] = "\e[32m*** {$titleTask}, tarea finalizada ***\e[39m";
                echoTerminal(implode("\r\n", $message));
                return;
            }

            $todas = SettingsModel::getConfigurations();
            $candidatas = [];
            foreach (array_keys($todas) as $nombre) {
                if (!is_string($nombre) || mb_strpos($nombre, self::TEST_PREFIX) !== 0) {
                    continue;
                }
                if ($key !== null && $nombre !== $key) {
                    continue;
                }
                $candidatas[] = $nombre;
            }
            sort($candidatas);

            $message[] = "\e[34mUniverso: " . count($todas) . " configuración(es) en la tabla; con el prefijo «"
                . self::TEST_PREFIX . "»: " . count($candidatas) . ".\e[39m";

            if ($candidatas === []) {
                $message[] = "\e[34mNo hay nada que retirar.\e[39m";
                $message[] = "\e[32m*** {$titleTask}, tarea finalizada ***\e[39m";
                echoTerminal(implode("\r\n", $message));
                return;
            }

            foreach ($candidatas as $nombre) {
                $message[] = "\e[34m  · {$nombre}\e[39m";
            }

            if (!$apply) {
                $message[] = "\e[33mNo se ha borrado nada: falta apply=yes.\e[39m";
                $message[] = "\e[32m*** {$titleTask}, tarea finalizada ***\e[39m";
                echoTerminal(implode("\r\n", $message));
                return;
            }

            //El volcado ANTES de escribir, dentro de la propia tarea: quien la corre no tiene que
            //acordarse.
            $message[] = "\e[34mVolcando la base antes de borrar…\e[39m";
            call_user_func([DbBackupTask::class, 'main'], $requestRoute, $responseRoute, []);

            $borradas = [];
            $fallidas = [];
            foreach ($candidatas as $nombre) {
                //Por marcador, que el nombre viene de la línea de órdenes.
                $borrado = SettingsModel::model()->delete(['name' => $nombre])->execute();
                if ($borrado) {
                    $borradas[] = $nombre;
                } else {
                    $fallidas[] = $nombre;
                }
            }

            $message[] = "\e[32mBorradas: " . count($borradas) . " de " . count($candidatas) . ".\e[39m";
            foreach ($borradas as $nombre) {
                $message[] = "\e[32m  · {$nombre}\e[39m";
            }
            foreach ($fallidas as $nombre) {
                $message[] = "\e[31m  · NO se pudo borrar: {$nombre}\e[39m";
            }

            //Se vuelve a leer la tabla: lo que dice la tarea que hizo se comprueba contra la base,
            //no contra su propia cuenta.
            $quedan = [];
            foreach (array_keys(SettingsModel::getConfigurations()) as $nombre) {
                if (is_string($nombre) && mb_strpos($nombre, self::TEST_PREFIX) === 0) {
                    $quedan[] = $nombre;
                }
            }
            $message[] = count($quedan) === 0
                ? "\e[32mComprobado contra la base: no queda ninguna con el prefijo.\e[39m"
                : "\e[33mComprobado contra la base: quedan " . count($quedan) . " (" . implode(', ', $quedan) . ").\e[39m";

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
        $instance = new CleanTestConfigsTask($startRoute, $namePrefix);
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
