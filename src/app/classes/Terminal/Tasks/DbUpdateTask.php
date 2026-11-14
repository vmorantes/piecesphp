<?php

/**
 * DbUpdateTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * DbUpdateTask.
 *
 * Aplica una actualización de `databases/actualizaciones/`: lista, simula por omisión y aplica con apply=yes, siempre con
 * un volcado de la última hora. A diferencia de `db-restore`, NO escribe `files/dev/last-restore.json`: aplicar una
 * actualización no hace nueva la base, y `bin/walk-attribute` lee ese rastro para saberlo.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class DbUpdateTask extends TerminalTaskAbstract
{

    /** Cuántos caracteres de cada sentencia se enseñan. */
    const PREVIEW_LENGTH = 80;

    /** Cuántos caracteres del mensaje de un fallo se enseñan. */
    const ERROR_LENGTH = 100;

    /**
     * @var string|null null: la carpeta del producto
     */
    private static $updatesDirectoryOverride = null;

    /**
     * @var string|null null: la carpeta del producto
     */
    private static $dumpsDirectoryOverride = null;

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }

        $this->description = new StringArray([
            "Aplica las actualizaciones de la base que trae databases/actualizaciones/ (tablas nuevas, columnas, índices).\r\n",
            "\tTres modos:\r\n",
            "\t  sin file=          lista las actualizaciones, con su primera línea de comentario. No escribe nada\r\n",
            "\t                     bin/cli db-update\r\n",
            "\t  file=<nombre>      SIMULA: cuenta las sentencias y enseña el principio de cada una. No toca la base\r\n",
            "\t                     bin/cli db-update file=2026-11-13-consumo-de-la-ia\r\n",
            "\t  file=<nombre> apply=yes  aplica sentencia a sentencia y sigue tras un fallo. Exige un volcado de la\r\n",
            "\t                     última hora en dumps/ (bin/cli db-backup antes)\r\n",
            "\t                     bin/cli db-update file=2026-11-13-consumo-de-la-ia apply=yes\r\n",
            "\tEl nombre es el de un archivo .sql de esa carpeta, con o sin «.sql»: nunca una ruta.\r\n",
            "\tSale con 1 si alguna sentencia falla (un ALTER ya aplicado falla sin daño).",
        ]);
        $this->route = "{$startRoute}/db-update[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'db-update';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = 'Actualizaciones de la base de datos';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");

        $file = TerminalData::instance()->getArgument('file', '');
        $file = is_string($file) ? trim($file) : '';
        $apply = TerminalData::instance()->getArgument('apply', '') === 'yes';

        if ($file === '') {
            $updates = self::listUpdates();
            echoTerminal("\e[94mINFO:\e[39m " . count($updates) . ' actualización(es) en ' . self::updatesDirectory() . ':');
            foreach ($updates as $update) {
                echoTerminal("  {$update['name']}" . ($update['summary'] !== '' ? " — {$update['summary']}" : ''));
            }
            echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
            exit(0);
        }

        $path = self::resolveUpdate($file);
        if ($path === null) {
            echoTerminal("\e[31mERROR:\e[39m «{$file}» no es el nombre de una actualización de " . self::updatesDirectory() . '. Lístalas con bin/cli db-update');
            exit(1);
        }

        if (!$apply) {
            $statements = self::simulate($path);
            echoTerminal("\e[94mSIMULACRO:\e[39m " . basename($path) . ': ' . count($statements) . ' sentencia(s). Nada se aplica sin apply=yes.');
            foreach ($statements as $statement) {
                echoTerminal('  ' . self::preview($statement, self::PREVIEW_LENGTH));
            }
            echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
            exit(0);
        }

        $result = self::apply($path, time());
        if ($result['refused'] !== null) {
            echoTerminal("\e[31mERROR:\e[39m {$result['refused']}");
            exit(1);
        }
        foreach ($result['lines'] as $line) {
            $color = $line['status'] === 'OK' ? "\e[32m" : ($line['status'] === 'FALLO' ? "\e[31m" : "\e[33m");
            echoTerminal("{$color}{$line['status']}:\e[39m {$line['statement']}" . ($line['detail'] !== '' ? " — {$line['detail']}" : ''));
        }
        echoTerminal("\e[94mINFO:\e[39m {$result['applied']} aplicada(s), {$result['failed']} fallida(s), {$result['ignored']} ignorada(s).");
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
        exit($result['failed'] > 0 ? 1 : 0);
    }

    /**
     * Las actualizaciones, en orden de nombre, con su primera línea de comentario.
     *
     * @return array<int,array{name:string,summary:string}>
     */
    public static function listUpdates(): array
    {
        $files = glob(self::updatesDirectory() . '/*.sql') ?: [];
        sort($files, \SORT_STRING);
        $updates = [];
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            $summary = '';
            $handle = fopen($file, 'r');
            if ($handle !== false) {
                while (($line = fgets($handle)) !== false) {
                    $line = trim($line);
                    if (str_starts_with($line, '--')) {
                        $summary = trim(mb_substr($line, 2));
                        break;
                    }
                }
                //RETORNO-IGNORADO: solo se leyó; cerrar un archivo de lectura no pierde nada.
                fclose($handle);
            }
            $updates[] = ['name' => basename($file, '.sql'), 'summary' => $summary];
        }
        return $updates;
    }

    /**
     * La ruta de la actualización con ese nombre, o null. Se decide SOLO por el nombre, sin leer el archivo.
     *
     * @param string $name Con o sin «.sql»
     * @return string|null
     */
    public static function resolveUpdate(string $name): ?string
    {
        //Un nombre, nunca una ruta: sin esto, file= saldría de la carpeta.
        if ($name === '' || str_contains($name, '/') || str_contains($name, '\\') || str_contains($name, '..')) {
            return null;
        }
        if (!str_ends_with($name, '.sql')) {
            $name .= '.sql';
        }
        $path = self::updatesDirectory() . '/' . $name;
        return is_file($path) ? $path : null;
    }

    /**
     * Las sentencias que se aplicarían, con el partidor de db-restore. No toca la base.
     *
     * @param string $path Una ruta que devolvió resolveUpdate()
     * @return string[]
     */
    public static function simulate(string $path): array
    {
        return DbRestoreTask::statementsOf((string) file_get_contents($path));
    }

    /**
     * Aplica la actualización sentencia a sentencia y sigue tras un fallo. Sin un volcado de la última hora en la carpeta
     * de volcados no aplica nada.
     *
     * @param string $path Una ruta que devolvió resolveUpdate()
     * @param int $now
     * @return array{refused:string|null,applied:int,failed:int,ignored:int,lines:array<int,array{status:string,statement:string,detail:string}>}
     */
    public static function apply(string $path, int $now): array
    {
        $result = ['refused' => null, 'applied' => 0, 'failed' => 0, 'ignored' => 0, 'lines' => []];

        if (!RepairEscapedTextTask::hasRecentDump(self::dumpsDirectory(), $now)) {
            $result['refused'] = 'no hay un volcado de la última hora en dumps/. Haz bin/cli db-backup antes.';
            return $result;
        }

        $database = (new BaseModel())->getDatabase();
        if ($database === null) {
            $result['refused'] = 'sin conexión a base de datos.';
            return $result;
        }

        foreach (self::simulate($path) as $statement) {
            $preview = self::preview($statement, self::PREVIEW_LENGTH);
            $reason = DbRestoreTask::whyIgnored($statement);
            if ($reason !== null) {
                $result['ignored']++;
                $result['lines'][] = ['status' => 'IGNORADA', 'statement' => $preview, 'detail' => $reason];
                continue;
            }
            try {
                $database->exec($statement);
                $result['applied']++;
                $result['lines'][] = ['status' => 'OK', 'statement' => $preview, 'detail' => ''];
            } catch (\Throwable $exception) {
                $result['failed']++;
                $result['lines'][] = ['status' => 'FALLO', 'statement' => $preview, 'detail' => self::preview($exception->getMessage(), self::ERROR_LENGTH)];
            }
        }

        return $result;
    }

    /**
     * El principio de un texto, en una línea.
     *
     * @param string $text
     * @param int $length
     * @return string
     */
    public static function preview(string $text, int $length): string
    {
        return mb_substr(trim((string) preg_replace('/\s+/', ' ', $text)), 0, $length);
    }

    /**
     * En el producto, databases/actualizaciones/ de la raíz del proyecto.
     *
     * @return string
     */
    public static function updatesDirectory(): string
    {
        return self::$updatesDirectoryOverride ?? dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/databases/actualizaciones';
    }

    /**
     * En el producto, dumps/ de src/.
     *
     * @return string
     */
    public static function dumpsDirectory(): string
    {
        return self::$dumpsDirectoryOverride ?? rtrim(str_replace('\\', '/', basepath('dumps')), '/');
    }

    /**
     * SOLO PARA PRUEBAS: cambia las dos carpetas (null: la del producto) y devuelve las de antes, para reponerlas.
     *
     * @internal
     * @param string|null $updatesDirectory
     * @param string|null $dumpsDirectory
     * @return array{0:string|null,1:string|null}
     */
    public static function swapForTesting(?string $updatesDirectory = null, ?string $dumpsDirectory = null): array
    {
        $previous = [self::$updatesDirectoryOverride, self::$dumpsDirectoryOverride];
        self::$updatesDirectoryOverride = $updatesDirectory !== null ? rtrim($updatesDirectory, '/') : null;
        self::$dumpsDirectoryOverride = $dumpsDirectory !== null ? rtrim($dumpsDirectory, '/') : null;
        return $previous;
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new DbUpdateTask($startRoute, $namePrefix);
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
