<?php

/**
 * SnapshotTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\TerminalData;

/**
 * SnapshotTask.
 *
 * Fotografía la base de datos ENTERA y el árbol de archivos servido, para poder comparar un
 * antes y un después y ATRIBUIR cada diferencia a lo que corrió entre las dos.
 *
 * Existe porque «solo GET» resultó no ser una propiedad de seguridad en este código: hay
 * caminos de lectura que escriben, y se descubrieron de uno en uno y por accidente. Esto los
 * convierte en un dato.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SnapshotTask extends TerminalTaskAbstract
{

    const VOLATILE_RELATIVE_PATH = 'files/dev/volatile-state.json';

    //Por encima de esto no hay hash por fila: se guarda el conteo, un hash agregado, y SE DICE.
    const MAX_ROWS_PER_TABLE = 20000;

    /** @var string[] Rutas que no forman parte del árbol servido. */
    const EXCLUDED_PATHS = [
        '/vendor/', '/node_modules/', '/.git/', '/dumps/', '/tmp/', '/files/dev/snapshots/',
        '/logs/', '/cache/',
    ];

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }

        $this->description = new StringArray([
            "Fotografía la base de datos y el árbol SERVIDO —`src/`, no el repositorio—, y compara dos fotos.\r\n",
            "\tLo que vive fuera de `src/` no entra en la foto: `bin/`, `files/dev/` y la documentación.\r\n",
            "\tParámetros:\r\n",
            "\t  label=<nombre>   nombre de la foto. Por defecto: la fecha\r\n",
            "\t  dir=<ruta>       dónde vive. Por defecto: files/dev/snapshots\r\n",
            "\t  files=(yes|no)   incluir el árbol de archivos. Por defecto: yes\r\n",
            "\t  compare=<a>,<b>  compara dos fotos y NO toma ninguna",
        ]);
        $this->route = "{$startRoute}/snapshot[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'snapshot';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = 'Foto de base de datos y archivos';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");

        $repoRoot = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $dir = TerminalData::instance()->getArgument('dir', $repoRoot . '/files/dev/snapshots');
        $dir = rtrim(is_string($dir) ? $dir : $repoRoot . '/files/dev/snapshots', '/');

        $compare = TerminalData::instance()->getArgument('compare', '');
        if (is_string($compare) && trim($compare) !== '') {
            self::compare($dir, trim($compare));
            exit(0);
        }

        $label = TerminalData::instance()->getArgument('label', date('Ymd-His'));
        $label = is_string($label) && trim($label) !== '' ? preg_replace('/[^A-Za-z0-9_-]/', '-', trim($label)) : date('Ymd-His');
        $withFiles = TerminalData::instance()->getArgument('files', 'yes') !== 'no';

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $snapshot = [
            'label' => $label,
            'tables' => self::snapshotTables(),
            'files' => $withFiles ? self::snapshotFiles($repoRoot) : null,
        ];

        $path = $dir . '/' . $label . '.json';
        file_put_contents($path, json_encode($snapshot, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR) . "\n");

        $rows = array_sum(array_column($snapshot['tables'], 'count'));
        $capped = array_filter($snapshot['tables'], static fn (array $t): bool => $t['capped'] === true);
        echoTerminal("\e[94mINFO:\e[39m " . count($snapshot['tables']) . " tablas, {$rows} filas.");
        if (count($capped) > 0) {
            //Un recorte silencioso se lee como cobertura completa: se nombra.
            echoTerminal("\e[33mAVISO:\e[39m sin hash por fila en " . count($capped) . ' tabla(s) por superar '
                . self::MAX_ROWS_PER_TABLE . ' filas: ' . implode(', ', array_keys($capped)) . '.');
        }
        if ($snapshot['files'] !== null) {
            echoTerminal("\e[94mINFO:\e[39m " . count($snapshot['files']) . ' archivos censados.');
        }
        echoTerminal("\e[34mEscrito:\e[39m {$path}");
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
        exit(0);
    }

    /**
     * @return array<string, array{count: int, hash: string, capped: bool, rows: array<string, string>}>
     */
    protected static function snapshotTables(): array
    {
        $db = (new BaseModel())->getDatabase();
        if ($db === null) {
            echoTerminal("\e[31mERROR:\e[39m sin conexión a base de datos no hay foto que tomar.");
            exit(1);
        }
        $tables = $db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME")->fetchAll(\PDO::FETCH_COLUMN);

        $out = [];
        foreach ((array) $tables as $table) {
            $table = (string) $table;

            //La clave se saca del esquema: sin ella el diff sabe que algo cambió pero no QUÉ.
            $keyStatement = $db->prepare("SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY' ORDER BY ORDINAL_POSITION");
            $keyStatement->execute([$table]);
            $keyColumns = $keyStatement->fetchAll(\PDO::FETCH_COLUMN);
            $keyColumns = is_array($keyColumns) ? array_map('strval', $keyColumns) : [];

            $count = (int) $db->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
            $capped = $count > self::MAX_ROWS_PER_TABLE;

            $rows = [];
            $hashes = [];
            if (!$capped) {
                $order = count($keyColumns) > 0 ? ' ORDER BY `' . implode('`, `', $keyColumns) . '`' : '';
                $statement = $db->query('SELECT * FROM `' . $table . '`' . $order);
                $index = 0;
                foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                    $key = count($keyColumns) > 0
                        ? implode('|', array_map(static fn (string $c): string => (string) ($row[$c] ?? ''), $keyColumns))
                        : '#' . $index;
                    $hash = sha1(json_encode($row, \JSON_UNESCAPED_UNICODE | \JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '');
                    $rows[$key] = $hash;
                    $hashes[] = $key . ':' . $hash;
                    $index++;
                }
            } else {
                $hashes[] = 'capped:' . $count;
            }

            $out[$table] = [
                'count' => $count,
                'hash' => sha1(implode("\n", $hashes)),
                'capped' => $capped,
                'rows' => $rows,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    protected static function snapshotFiles(string $repoRoot): array
    {
        $base = $repoRoot . '/src';
        if (!is_dir($base)) {
            return [];
        }
        $out = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = str_replace('\\', '/', (string) $file->getPathname());
            foreach (self::EXCLUDED_PATHS as $skip) {
                if (mb_strpos($path, $skip) !== false) {
                    continue 2;
                }
            }
            $relative = ltrim(str_replace($repoRoot, '', $path), '/');
            //El árbol se mueve mientras se censa: un archivo que desaparece no es un fallo.
            if (!is_file($path)) {
                continue;
            }
            //Tamaño y mtime bastan para detectar una escritura y son mucho más baratos que el
            //contenido; el hash solo se calcula para archivos pequeños.
            $size = (int) $file->getSize();
            $hash = $size <= 1048576 ? @sha1_file($path) : 'grande';
            $out[$relative] = $size . ':' . (int) $file->getMTime() . ':' . (is_string($hash) ? $hash : 'ilegible');
        }
        ksort($out);
        return $out;
    }

    protected static function compare(string $dir, string $pair): void
    {
        $parts = array_map('trim', explode(',', $pair));
        if (count($parts) !== 2) {
            echoTerminal("\e[31mERROR:\e[39m compare espera dos nombres separados por coma.");
            exit(1);
        }

        $load = static function (string $label) use ($dir): array {
            $path = $dir . '/' . $label . '.json';
            if (!is_file($path)) {
                echoTerminal("\e[31mERROR:\e[39m no existe la foto {$path}.");
                exit(1);
            }
            $data = json_decode((string) file_get_contents($path), true);
            return is_array($data) ? $data : [];
        };

        $before = $load($parts[0]);
        $after = $load($parts[1]);

        //Un comparador con ruido ensena a ignorar los diffs. Lo declarado se separa; lo no
        //declarado hace fallar la comparacion.
        $repoRoot = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        $volatileRaw = @file_get_contents($repoRoot . '/' . self::VOLATILE_RELATIVE_PATH);
        $volatile = is_string($volatileRaw) ? json_decode($volatileRaw, true) : null;
        if (!is_array($volatile)) {
            echoTerminal("\e[31mERROR:\e[39m no se pudo leer " . self::VOLATILE_RELATIVE_PATH . ': sin el registro de volatilidad la comparación no significa nada.');
            exit(1);
        }
        $volatileTables = array_keys((array) ($volatile['tables'] ?? []));
        $volatileFiles = array_keys((array) ($volatile['files'] ?? []));
        $declared = [];

        $findings = 0;

        echoTerminal("\e[32m── BASE DE DATOS ──\e[39m");
        $tablesBefore = (array) ($before['tables'] ?? []);
        $tablesAfter = (array) ($after['tables'] ?? []);
        foreach ($tablesAfter as $table => $dataAfter) {
            $dataBefore = $tablesBefore[$table] ?? null;
            if ($dataBefore === null) {
                echoTerminal("  \e[31m+ TABLA NUEVA\e[39m {$table} ({$dataAfter['count']} filas)");
                $findings++;
                continue;
            }
            if ($dataBefore['hash'] === $dataAfter['hash']) {
                continue;
            }
            if (in_array($table, $volatileTables, true)) {
                $declared[] = 'tabla ' . $table;
                continue;
            }
            $delta = (int) $dataAfter['count'] - (int) $dataBefore['count'];
            echoTerminal("  \e[33m~ {$table}\e[39m — " . $dataBefore['count'] . ' -> ' . $dataAfter['count']
                . ($delta !== 0 ? ' (' . ($delta > 0 ? '+' : '') . $delta . ')' : ' (mismas filas, contenido distinto)'));
            $findings++;

            $rowsBefore = (array) ($dataBefore['rows'] ?? []);
            $rowsAfter = (array) ($dataAfter['rows'] ?? []);
            foreach ($rowsAfter as $key => $hash) {
                if (!array_key_exists($key, $rowsBefore)) {
                    echoTerminal("      \e[31m+ fila\e[39m {$key}");
                } elseif ($rowsBefore[$key] !== $hash) {
                    echoTerminal("      \e[33m~ fila\e[39m {$key}");
                }
            }
            foreach ($rowsBefore as $key => $hash) {
                if (!array_key_exists($key, $rowsAfter)) {
                    echoTerminal("      \e[31m- fila\e[39m {$key}");
                }
            }
        }
        foreach ($tablesBefore as $table => $dataBefore) {
            if (!array_key_exists($table, $tablesAfter)) {
                echoTerminal("  \e[31m- TABLA BORRADA\e[39m {$table}");
                $findings++;
            }
        }

        echoTerminal("\e[32m── ÁRBOL DE ARCHIVOS ──\e[39m");
        $filesBefore = (array) ($before['files'] ?? []);
        $filesAfter = (array) ($after['files'] ?? []);
        if (count($filesBefore) === 0 && count($filesAfter) === 0) {
            echoTerminal("  (no se censaron archivos en alguna de las dos fotos)");
        }
        $esVolatil = static function (string $path) use ($volatileFiles): bool {
            foreach ($volatileFiles as $prefix) {
                if (str_starts_with($path, (string) $prefix)) {
                    return true;
                }
            }
            return false;
        };
        foreach ($filesAfter as $path => $stamp) {
            $cambio = !array_key_exists($path, $filesBefore) ? '+' : ($filesBefore[$path] !== $stamp ? '~' : null);
            if ($cambio === null) {
                continue;
            }
            if ($esVolatil($path)) {
                $declared[] = 'archivo ' . $path;
                continue;
            }
            echoTerminal("  \e[31m{$cambio} {$path}\e[39m");
            $findings++;
        }
        foreach ($filesBefore as $path => $stamp) {
            if (array_key_exists($path, $filesAfter)) {
                continue;
            }
            if ($esVolatil($path)) {
                $declared[] = 'archivo ' . $path;
                continue;
            }
            echoTerminal("  \e[31m- {$path}\e[39m");
            $findings++;
        }

        if (count($declared) > 0) {
            echoTerminal("\e[32m── VOLATILIDAD DECLARADA (no cuenta) ──\e[39m");
            foreach (array_unique($declared) as $line) {
                echoTerminal("  \e[90m· {$line}\e[39m");
            }
        }

        echoTerminal('');
        if ($findings === 0) {
            echoTerminal("\e[32mSIN DIFERENCIAS NO DECLARADAS:\e[39m " . count(array_unique($declared)) . ' cambio(s) declarado(s) en el registro.');
            echoTerminal("\e[32m*** Comparación de fotos, tarea finalizada ***\e[39m");
            exit(0);
        }
        echoTerminal("\e[31mDIFERENCIAS NO DECLARADAS: {$findings}\e[39m");
        echoTerminal("\e[33mCada una se justifica —y entra en " . self::VOLATILE_RELATIVE_PATH . "— o se arregla.\e[39m");
        echoTerminal("\e[31m*** Comparación de fotos, tarea finalizada CON DIFERENCIAS ***\e[39m");
        exit(1);
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new SnapshotTask($startRoute, $namePrefix);
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
