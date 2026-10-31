<?php

/**
 * SessionNameTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * SessionNameTask.
 *
 * Da a la instalación un nombre de sesión propio (P158): sin argumentos PROPONE uno derivado de la carpeta de la
 * instalación y no escribe; con `escribir=yes` lo fija en `config.php`, en el lugar de la línea comentada de la plantilla.
 * Si esa línea no está, no edita a ciegas: dice qué pegar y dónde. No genera ningún aviso del sistema.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SessionNameTask extends TerminalTaskAbstract
{

    /**
     * La línea de la plantilla que se sustituye: tal cual viene en `config.php`.
     */
    const TEMPLATE_LINE = "//\$config['session_token_name'] = 'JWTAuth';";

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }

        $this->description = new StringArray([
            "Da a esta instalación un nombre de sesión propio. Sin argumentos, PROPONE uno y no escribe nada.\r\n",
            "\tCambiarlo cierra TODAS las sesiones abiertas.\r\n",
            "\tParámetros:\r\n",
            "\t  escribir=yes      lo fija en src/app/config/config.php, en el lugar de la línea comentada de la plantilla\r\n",
            "\t  nombre=<X>        usa ese nombre en vez del propuesto: letras, dígitos y _, hasta 64\r\n",
            "\t  archivo=<ruta>    otro config.php (las pruebas escriben sobre una copia)\r\n",
        ]);
        $this->route = "{$startRoute}/session-name[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'session-name';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = 'Nombre de la sesión de esta instalación';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");
        $data = TerminalData::instance();

        $requested = $data->getArgument('nombre', '');
        $requested = is_string($requested) ? trim($requested) : '';
        $name = $requested !== '' ? $requested : self::proposedName();
        if (!SessionToken::tokenNameIsValid($name)) {
            echoTerminal("\e[31mERROR:\e[39m «{$name}» no vale como nombre de sesión: solo letras, dígitos y _, hasta 64.");
            exit(1);
        }

        $file = $data->getArgument('archivo', '');
        $file = is_string($file) && trim($file) !== '' ? trim($file) : basepath('app/config/config.php');
        $write = $data->getArgument('escribir', '') === 'yes';

        echoTerminal("\e[94mINFO:\e[39m nombre actual: «" . SessionToken::tokenName() . '»');
        echoTerminal("\e[94mINFO:\e[39m nombre " . ($requested !== '' ? 'pedido' : 'propuesto (de la carpeta de la instalación)') . ": «{$name}»");
        echoTerminal("\e[94mINFO:\e[39m archivo: {$file}");
        echoTerminal("\e[33mAVISO:\e[39m cambiar el nombre cierra TODAS las sesiones abiertas: todo el mundo tendrá que volver a entrar.");

        $content = is_file($file) ? file_get_contents($file) : false;
        if (!is_string($content)) {
            echoTerminal("\e[31mERROR:\e[39m no se pudo leer {$file}.");
            exit(1);
        }
        $newLine = "\$config['session_token_name'] = '{$name}';";
        if (substr_count($content, self::TEMPLATE_LINE) !== 1) {
            //Sin la línea de la plantilla no se adivina dónde va: se dice qué pegar y dónde, y no se toca nada.
            echoTerminal("\e[31mPARA:\e[39m {$file} no tiene la línea de la plantilla, una sola vez:");
            echoTerminal('    ' . self::TEMPLATE_LINE);
            echoTerminal("Pega esta línea en la sección «Seguridad», junto a \$config['app_key']:");
            echoTerminal("    {$newLine}");
            exit(2);
        }

        if (!$write) {
            echoTerminal("Haría: cambiar la línea comentada por «{$newLine}». No se ha escrito nada: repite con escribir=yes.");
            echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
            exit(0);
        }

        $updated = str_replace(self::TEMPLATE_LINE, $newLine, $content);
        if (file_put_contents($file, $updated) === false) {
            echoTerminal("\e[31mERROR:\e[39m no se pudo escribir {$file}.");
            exit(1);
        }
        //Relectura: lo que cuenta es lo que leerá la próxima petición, no lo que se quiso escribir.
        if (substr_count((string) file_get_contents($file), $newLine) !== 1) {
            echoTerminal("\e[31mERROR:\e[39m {$file} no quedó con la línea escrita.");
            exit(1);
        }
        echoTerminal("Escrito: «{$newLine}». Desde la próxima petición, las sesiones abiertas dejan de valer.");
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
        exit(0);
    }

    /**
     * Un nombre propio de la instalación: «Pcs_» y el nombre de su carpeta (la que contiene `src/` en el repositorio, o la
     * raíz desplegada en un servidor), reducido a letras, dígitos y _.
     *
     * @return string
     */
    public static function proposedName(): string
    {
        $root = rtrim(str_replace('\\', '/', basepath('')), '/');
        $folder = basename($root) === 'src' ? basename(dirname($root)) : basename($root);
        $folder = (string) preg_replace('/[^A-Za-z0-9_]+/', '_', $folder);
        $folder = trim($folder, '_');
        return mb_substr('Pcs_' . ($folder !== '' ? $folder : 'app'), 0, 64);
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new SessionNameTask($startRoute, $namePrefix);
        return new Route(
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
    }

}
