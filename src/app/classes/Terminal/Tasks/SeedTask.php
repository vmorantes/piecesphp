<?php

/**
 * SeedTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\TerminalData;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;
use Terminal\Seeding\SeedPlanner;

/**
 * SeedTask.
 *
 * Siembra datos de prueba de los módulos con seeder. Sin argumentos SIMULA: descubre, ordena,
 * genera y enseña sin escribir. Con `escribir=yes` escribe en la instalación LOCAL, y con
 * `limpiar=yes` deshace lo sembrado por id.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SeedTask extends TerminalTaskAbstract
{

    const MODE_SIMULATE = 'simular';
    const MODE_WRITE = 'escribir';
    const MODE_CLEAN = 'limpiar';

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }

        $this->description = new StringArray([
            "Siembra datos de prueba de los módulos con seeder. Sin escribir=yes ni limpiar=yes, SOLO SIMULA.\r\n",
            "\tParámetros:\r\n",
            "\t  module=<Nombre>  módulo bajo src/app/classes, o `all`. Obligatorio\r\n",
            "\t  cantidad=<n>     filas por tabla. Por defecto, la de cada seeder\r\n",
            "\t  escribir=yes     escribe de verdad, solo en la instalación LOCAL y en una transacción\r\n",
            "\t  limpiar=yes      borra por id lo sembrado (prefijo " . ModuleSeeder::PREFIX . "), en orden inverso",
        ]);
        $this->route = "{$startRoute}/seed[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'seed';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $data = TerminalData::instance();
        $mode = self::mode($data->getArgument('escribir', ''), $data->getArgument('limpiar', ''));
        if ($mode === null) {
            echoTerminal("\e[31mERROR:\e[39m escribir=yes y limpiar=yes no van juntos: pide uno.");
            exit(1);
        }
        echoTerminal("\e[32m*** Siembra (" . ($mode === self::MODE_SIMULATE ? 'simulación' : $mode) . ") ***\e[39m");

        $module = $data->getArgument('module', '');
        $module = is_string($module) ? trim($module) : '';
        if ($module === '' || preg_match('/^[A-Za-z0-9_\/]+$/', $module) !== 1) {
            echoTerminal("\e[31mERROR:\e[39m falta module=<Nombre> (o module=all). Ejemplo: module=Organizations");
            exit(1);
        }
        $count = $data->getArgument('cantidad', '');
        $count = is_scalar($count) ? trim((string) $count) : '';
        if ($count !== '' && preg_match('/^\d+$/', $count) !== 1) {
            echoTerminal("\e[31mERROR:\e[39m cantidad=<n> tiene que ser un entero desde 0.");
            exit(1);
        }
        $count = $count === '' ? null : (int) $count;
        if ($mode !== self::MODE_SIMULATE && mb_strtolower($module) !== 'all') {
            echoTerminal("\e[31mERROR:\e[39m escribir y limpiar van con module=all: las dependencias cruzan módulos.");
            exit(1);
        }

        $all = SeedPlanner::discover('all');
        try {
            //El canario ordena los seeders reales: un ciclo o una dependencia rota aparecen aquí primero.
            $canary = SeedPlanner::canary($all);
        } catch (\RuntimeException $e) {
            echoTerminal("\e[31m{$e->getMessage()}\e[39m");
            exit(2);
        }
        if ($canary !== null) {
            echoTerminal("\e[31mPARA: el canario falla — {$canary}. Sin él no hay siembra.\e[39m");
            exit(2);
        }

        $seeders = mb_strtolower($module) === 'all' ? $all : SeedPlanner::discover($module);
        $wanted = array_map('get_class', $seeders);
        $universe = SeedPlanner::universe($all, 'all');
        $tables = $universe['mappers'];

        try {
            //Las dependencias de un módulo pueden estar en otro: el orden se resuelve con todos.
            $everything = SeedPlanner::order($all);
            $ordered = array_values(array_filter($everything, static fn (ModuleSeeder $s): bool => in_array(get_class($s), $wanted, true)));
            if ($mode === self::MODE_CLEAN) {
                $folders = [];
                $report = SeedPlanner::clean($everything, $folders);
                exit(self::printClean($report, $folders ?? []) ? 0 : 1);
            }
            if ($mode === self::MODE_WRITE) {
                $written = SeedPlanner::write($everything, $count, new SeedContext($tables, true));
                echoTerminal('== Escrito en la base LOCAL, en una transacción (los textos llevan «' . ModuleSeeder::PREFIX . '»)');
                foreach ($everything as $seeder) {
                    $ids = $written[$seeder->mapper()] ?? [];
                    echoTerminal(sprintf('  %s: %d fila(s), ids %s', $tables[$seeder->mapper()] ?? $seeder->mapper(), count($ids), implode(', ', $ids) ?: '-'));
                }
                echoTerminal("\e[32m*** Siembra (escribir), tarea finalizada. Se deshace con limpiar=yes ***\e[39m");
                exit(0);
            }
            $rows = SeedPlanner::simulate($everything, $count, new SeedContext($tables));
        } catch (\RuntimeException $e) {
            echoTerminal("\e[31m{$e->getMessage()}\e[39m");
            exit(2);
        }

        echoTerminal('== Universo (LEY 15)');
        echoTerminal('módulos con mappers: ' . count($universe['modules']) . ' (' . implode(', ', $universe['modules']) . ')');
        echoTerminal('mappers: ' . count($universe['mappers']) . ' · con seeder: ' . count($universe['with']) . ' · SIN seeder, y por tanto sin sembrar: ' . count($universe['without']));
        foreach ($universe['skipped'] as $line) {
            echoTerminal("  fuera del descubrimiento: {$line}");
        }
        echoTerminal('seeders descubiertos: ' . count($all) . ', en src/app/classes/<Módulo>/' . SeedPlanner::SEEDS_DIRECTORY . '/');
        echoTerminal('canario: cantidad=0 no genera nada, una dependencia sin seeder se detecta y un ciclo también — OK');
        echoTerminal('');
        echoTerminal('== Orden resuelto' . ($module !== 'all' ? " (se enseña {$module}; el orden se calcula con todos)" : ''));
        foreach ($ordered as $position => $seeder) {
            $dependencies = array_map(static fn (string $class): string => $tables[$class] ?? $class, $seeder->dependsOn());
            echoTerminal(sprintf('  %d. %s (%s)%s', $position + 1, $tables[$seeder->mapper()] ?? $seeder->mapper(), get_class($seeder), count($dependencies) > 0 ? ' — después de ' . implode(', ', $dependencies) : ''));
        }
        echoTerminal('');
        echoTerminal('== Lo que se insertaría (nada se escribe; los textos llevan el prefijo «' . ModuleSeeder::PREFIX . '», y la contraseña es «<hash>»)');
        foreach ($ordered as $seeder) {
            $mapper = $seeder->mapper();
            $list = $rows[$mapper] ?? [];
            echoTerminal(sprintf('  %s: %d fila(s)', $tables[$mapper] ?? $mapper, count($list)));
            if (count($list) > 0) {
                foreach ($list[0] as $column => $value) {
                    echoTerminal(sprintf('      %-18s %s', $column, is_scalar($value) || $value === null ? var_export($value, true) : (json_encode($value) ?: '')));
                }
            }
        }
        echoTerminal("\e[32m*** Siembra (simulación), tarea finalizada: no se escribió nada ***\e[39m");
        exit(0);
    }

    /**
     * El modo pedido. La simulación es lo que pasa si no se pide nada: lo peligroso hay que escribirlo.
     *
     * @param mixed $write El valor de `escribir=`.
     * @param mixed $clean El valor de `limpiar=`.
     * @return string|null Uno de los MODE_*, o null si se piden los dos.
     */
    public static function mode($write, $clean): ?string
    {
        $write = is_string($write) && mb_strtolower(trim($write)) === 'yes';
        $clean = is_string($clean) && mb_strtolower(trim($clean)) === 'yes';
        if ($write && $clean) {
            return null;
        }
        return $write ? self::MODE_WRITE : ($clean ? self::MODE_CLEAN : self::MODE_SIMULATE);
    }

    /**
     * @param array<string,array{before: int, ids: int[], after: int}> $report
     * @param array<int,array{path: string, status: string}> $folders
     * @return bool Si todas las carpetas quedaron fuera; si una no se pudo borrar, la tarea sale con 1.
     */
    protected static function printClean(array $report, array $folders): bool
    {
        $total = array_sum(array_map(static fn (array $table): int => count($table['ids']), $report));
        echoTerminal('== Limpiado en la base LOCAL, por id y en orden inverso');
        foreach ($report as $table => $data) {
            echoTerminal(sprintf('  %s: antes %d / borradas %d / después %d%s', $table, $data['before'], count($data['ids']), $data['after'], count($data['ids']) > 0 ? ' — ids ' . implode(', ', $data['ids']) : ''));
        }
        echoTerminal($total === 0 ? 'no había nada sembrado: no se borró nada' : "borradas {$total} fila(s) sembradas");
        $failed = array_filter($folders, static fn (array $folder): bool => str_starts_with($folder['status'], SeedPlanner::FOLDER_FAILED));
        if (count($folders) > 0) {
            echoTerminal('== Carpetas propias de lo sembrado, después de confirmar las filas');
            foreach ($folders as $folder) {
                echoTerminal(sprintf('  %s: %s', $folder['path'], $folder['status']));
            }
        }
        if (count($failed) > 0) {
            echoTerminal("\e[31m*** Siembra (limpiar): las filas se borraron, pero " . count($failed) . ' carpeta(s) NO; están arriba ***' . "\e[39m");
            return false;
        }
        echoTerminal("\e[32m*** Siembra (limpiar), tarea finalizada ***\e[39m");
        return true;
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new SeedTask($startRoute, $namePrefix);
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
