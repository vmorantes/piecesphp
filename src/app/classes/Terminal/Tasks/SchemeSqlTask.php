<?php

/**
 * SchemeSqlTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Core\Database\EntityMapper;
use PiecesPHP\Core\Database\SchemeCreator;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\TerminalData;

/**
 * SchemeSqlTask.
 *
 * Lo que comparten `scheme-create` y `scheme-drop`: EL DESCUBRIMIENTO. Son la ida y la
 * vuelta de la misma operación, así que leen los mismos mappers, con el mismo criterio y
 * con la misma lista de descartados. Dos listas serían dos verdades.
 *
 * El orden lo pone `SchemeCreator`, que resuelve UN SOLO grafo y lo recorre en un sentido
 * o en el otro.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
abstract class SchemeSqlTask extends TerminalTaskAbstract
{

    //Lista NEGRA: con la blanca se quedaba fuera UserProfileMapper, suelto en Profile/.
    const IGNORED_DIRECTORIES = ['Views', 'Statics', 'lang', 'lang-public', 'Exceptions', 'Controllers'];

    /**
     * Mappers de un módulo, o de la aplicación entera con `module=all`.
     *
     * @param string $module
     * @return array{creators: SchemeCreator[], mappers: EntityMapper[], skipped: string[]}
     */
    protected static function discover(string $module): array
    {
        $repoRoot = rtrim(str_replace('\\', '/', basepath('')), '/');
        $directories = [];

        if (mb_strtolower($module) === 'all') {
            $directories[] = $repoRoot . '/app/model';
            foreach (glob($repoRoot . '/app/classes/*', GLOB_ONLYDIR) ?: [] as $moduleDirectory) {
                foreach (self::mapperDirectoriesUnder($moduleDirectory) as $found) {
                    $directories[] = $found;
                }
            }
        } else {
            $base = $repoRoot . '/app/classes/' . $module;
            if (!is_dir($base)) {
                return ['creators' => [], 'mappers' => [], 'skipped' => ["no existe {$base}"]];
            }
            $directories = self::mapperDirectoriesUnder($base);
        }
        $directories = array_values(array_unique($directories));

        $creators = [];
        $mappers = [];
        $skipped = [];
        $seen = [];

        foreach ($directories as $directory) {
            foreach (glob($directory . '/*.php') ?: [] as $file) {
                $class = self::declaredClassOf($file);
                //Solo se reporta como descarte lo que se llama como un mapper: lo demás es ruido.
                $looksLikeMapper = $class !== null && preg_match('/(Mapper|Model)$/', $class) === 1;
                if ($class === null || !class_exists($class) || isset($seen[$class])) {
                    if ($looksLikeMapper && !class_exists($class)) {
                        $skipped[] = basename($file) . ' (no se pudo cargar la clase)';
                    }
                    continue;
                }
                $seen[$class] = true;
                try {
                    $reflection = new \ReflectionClass($class);
                    //Se filtra ANTES de instanciar: recorriendo el módulo entero aparecen
                    //clases que no son mappers y no hay por qué construirlas.
                    if ($reflection->isAbstract() || !$reflection->isSubclassOf(EntityMapper::class)) {
                        continue;
                    }
                    $mapper = new $class();
                    if (!$mapper instanceof EntityMapper) {
                        continue;
                    }
                    $mappers[] = $mapper;
                    $creators[] = new SchemeCreator($mapper);
                } catch (\Throwable $exception) {
                    //Un mapper que no se puede instanciar NO se calla: si falta, el script miente.
                    $skipped[] = basename($file) . ' (' . mb_substr($exception->getMessage(), 0, 60) . ')';
                }
            }
        }

        return ['creators' => $creators, 'mappers' => $mappers, 'skipped' => $skipped];
    }

    /**
     * Directorios de mappers bajo una carpeta, a cualquier profundidad.
     *
     * @param string $base
     * @return string[]
     */
    protected static function mapperDirectoriesUnder(string $base): array
    {
        $directories = [$base];
        //Se PODA el subárbol entero: saltarse solo la carpeta `Views` seguía entrando en
        //`Views/forms` y en `Views/mailing`, que era de donde salía el ruido.
        $filter = new \RecursiveCallbackFilterIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
            static fn ($current) => !$current->isDir() || !in_array($current->getBasename(), self::IGNORED_DIRECTORIES, true)
        );
        foreach (new \RecursiveIteratorIterator($filter, \RecursiveIteratorIterator::SELF_FIRST) as $item) {
            if ($item->isDir()) {
                $directories[] = $item->getPathname();
            }
        }
        return $directories;
    }

    /**
     * Escribe el script donde toque y explica lo que NO entró.
     *
     * @param string[] $skipped
     * @return void
     */
    protected static function emit(string $script, int $tables, array $skipped): void
    {
        //Se cuentan las SENTENCIAS, no los mappers: dos mappers pueden compartir tabla.
        $inScript = preg_match_all('/^\s*(CREATE TABLE|DROP TABLE)/mi', $script);
        $inScript = is_int($inScript) ? $inScript : 0;
        if ($inScript !== $tables) {
            echoTerminal("\e[33mAVISO:\e[39m {$tables} mapper(s) descubierto(s) pero {$inScript} tabla(s) en el script: hay mappers que comparten tabla.");
        }
        $tables = $inScript;

        $output = TerminalData::instance()->getArgument('output', '');
        $output = is_string($output) ? trim($output) : '';
        if ($output !== '') {
            file_put_contents($output, $script);
            echoTerminal("\e[34mEscrito:\e[39m {$output}");
        } else {
            echoTerminal('');
            echoTerminal($script);
        }

        echoTerminal("\e[94mINFO:\e[39m {$tables} tabla(s) en el script.");
        foreach ($skipped as $line) {
            echoTerminal("\e[33mAVISO:\e[39m fuera del script — {$line}");
        }
        echoTerminal("\e[33mREVÍSALO ANTES DE APLICARLO.\e[39m Esta tarea emite, no ejecuta.");
    }

    /**
     * Módulo pedido por línea de órdenes, validado.
     *
     * @return string
     */
    protected static function requestedModule(): string
    {
        $module = TerminalData::instance()->getArgument('module', '');
        $module = is_string($module) ? trim($module) : '';
        if ($module === '' || preg_match('/^[A-Za-z0-9_\/]+$/', $module) !== 1) {
            echoTerminal("\e[31mERROR:\e[39m falta module=<Nombre> (o module=all). Ejemplo: module=Publications");
            exit(1);
        }
        return $module;
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

}
