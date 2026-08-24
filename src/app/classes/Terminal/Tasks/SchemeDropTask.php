<?php

/**
 * SchemeDropTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use PiecesPHP\Core\Database\EntityMapper;
use PiecesPHP\Core\Database\SchemeCreator;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\TerminalData;

/**
 * SchemeDropTask.
 *
 * EMITE el SQL de borrado de las tablas de un módulo. No lo ejecuta.
 *
 * La regla 7 dice que el SQL de las tablas se genera y no se escribe a mano — pero solo
 * existía hacia adelante. Deshacer un módulo obligaba a escribir a mano justo lo que la
 * regla prohíbe, y cada despliegue que actualice necesita ese SQL.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SchemeDropTask extends TerminalTaskAbstract
{

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }

        $this->description = new StringArray([
            "EMITE el SQL de borrado de las tablas de un módulo. NO lo ejecuta.\r\n",
            "\tParámetros:\r\n",
            "\t  module=<Nombre>  módulo bajo src/app/classes. Obligatorio\r\n",
            "\t  output=<ruta>    archivo de salida. Por defecto: se imprime",
        ]);
        $this->route = "{$startRoute}/scheme-drop[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'scheme-drop';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = 'SQL de borrado de un módulo';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");

        $module = TerminalData::instance()->getArgument('module', '');
        $module = is_string($module) ? trim($module) : '';
        if ($module === '' || preg_match('/^[A-Za-z0-9_\/]+$/', $module) !== 1) {
            echoTerminal("\e[31mERROR:\e[39m falta module=<Nombre>. Ejemplo: bin/cli scheme-drop module=Publications");
            exit(1);
        }

        //`dropScript()` llega en piecesphp/database v3.3.0: sin ella esto no puede emitir nada.
        if (!method_exists(SchemeCreator::class, 'dropScript')) {
            echoTerminal("\e[31mERROR:\e[39m esta tarea necesita piecesphp/database >= 3.3.0. Corre: composer update piecesphp/database");
            exit(1);
        }

        $repoRoot = rtrim(str_replace('\\', '/', basepath('')), '/');
        $mappersDirectory = $repoRoot . '/app/classes/' . $module . '/Mappers';
        if (!is_dir($mappersDirectory)) {
            echoTerminal("\e[31mERROR:\e[39m no existe {$mappersDirectory}.");
            exit(1);
        }

        $creators = [];
        $skipped = [];
        foreach (glob($mappersDirectory . '/*.php') ?: [] as $file) {
            $class = self::declaredClassOf($file);
            if ($class === null || !class_exists($class)) {
                $skipped[] = basename($file) . ' (no se pudo cargar la clase)';
                continue;
            }
            try {
                $mapper = new $class();
                if (!$mapper instanceof EntityMapper) {
                    $skipped[] = basename($file) . ' (no es un EntityMapper)';
                    continue;
                }
                $creators[] = new SchemeCreator($mapper);
            } catch (\Throwable $exception) {
                //Un mapper que no se puede instanciar NO se calla: si falta, el script miente.
                $skipped[] = basename($file) . ' (' . mb_substr($exception->getMessage(), 0, 60) . ')';
            }
        }

        if (count($creators) === 0) {
            echoTerminal("\e[31mERROR:\e[39m no se pudo construir ningún mapper de {$module}.");
            foreach ($skipped as $line) {
                echoTerminal("  \e[33m{$line}\e[39m");
            }
            exit(1);
        }

        $script = SchemeCreator::dropScript($creators);

        $output = TerminalData::instance()->getArgument('output', '');
        $output = is_string($output) ? trim($output) : '';
        if ($output !== '') {
            file_put_contents($output, $script);
            echoTerminal("\e[34mEscrito:\e[39m {$output}");
        } else {
            echoTerminal('');
            echoTerminal($script);
        }

        echoTerminal("\e[94mINFO:\e[39m " . count($creators) . ' tabla(s) en el script.');
        foreach ($skipped as $line) {
            echoTerminal("\e[33mAVISO:\e[39m fuera del script — {$line}");
        }
        echoTerminal("\e[33mREVÍSALO ANTES DE APLICARLO.\e[39m Esta tarea emite, no ejecuta.");
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
        exit(0);
    }

    /**
     * FQCN declarado en un archivo, por tokens.
     *
     * @param string $file
     * @return string|null
     */
    protected static function declaredClassOf(string $file): ?string
    {
        $tokens = @token_get_all((string) @file_get_contents($file));
        $namespace = '';
        $total = count($tokens);
        for ($i = 0; $i < $total; $i++) {
            if (!is_array($tokens[$i])) {
                continue;
            }
            if ($tokens[$i][0] === \T_NAMESPACE) {
                $parts = [];
                for ($j = $i + 1; $j < $total; $j++) {
                    if (is_string($tokens[$j]) && $tokens[$j] === ';') {
                        break;
                    }
                    if (is_array($tokens[$j]) && in_array($tokens[$j][0], [\T_STRING, \T_NAME_QUALIFIED], true)) {
                        $parts[] = $tokens[$j][1];
                    }
                }
                $namespace = implode('\\', $parts);
            }
            if ($tokens[$i][0] === \T_CLASS) {
                for ($j = $i + 1; $j < $total; $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === \T_STRING) {
                        return ($namespace !== '' ? $namespace . '\\' : '') . $tokens[$j][1];
                    }
                }
            }
        }
        return null;
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new SchemeDropTask($startRoute, $namePrefix);
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
