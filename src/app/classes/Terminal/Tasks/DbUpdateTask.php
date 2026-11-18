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
 * un volcado de la última hora. Registra lo aplicado en pcsphp_db_updates y para en la primera sentencia que falla (H298).
 * A diferencia de `db-restore`, NO escribe `files/dev/last-restore.json`: aplicar una actualización no hace nueva la
 * base, y `bin/walk-attribute` lee ese rastro para saberlo.
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

    /** El registro de las actualizaciones aplicadas o marcadas. */
    const REGISTRY_TABLE = 'pcsphp_db_updates';

    /**
     * La tabla del registro. La MISMA definición está en piecesphp_structure.sql y en la actualización
     * 2026-11-17-registro-de-actualizaciones.sql (core/db-update u6 lo comprueba): db-update la crea si falta.
     */
    const REGISTRY_DEFINITION = "CREATE TABLE IF NOT EXISTS `pcsphp_db_updates` (\n"
        . "  `name` varchar(191) NOT NULL,\n"
        . "  `sha1` char(40) NOT NULL,\n"
        . "  `statements` int(11) NOT NULL,\n"
        . "  `appliedAt` datetime NOT NULL,\n"
        . "  `mode` varchar(16) NOT NULL,\n"
        . "  PRIMARY KEY (`name`)\n"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;";

    /** Los modos del registro. */
    const MODE_APPLIED = 'aplicada';
    const MODE_MARKED = 'marcada';
    const MODE_PENDING = 'pendiente';

    /**
     * @var string|null null: la carpeta del producto
     */
    private static $updatesDirectoryOverride = null;

    /**
     * @var string|null null: la carpeta del producto
     */
    private static $dumpsDirectoryOverride = null;

    /**
     * @var string|null null: REGISTRY_TABLE
     */
    private static $registryTableOverride = null;

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
            "\tLo aplicado queda registrado en la tabla pcsphp_db_updates, que db-update crea si falta. Cuatro modos:\r\n",
            "\t  sin file=          lista las actualizaciones: aplicada (fecha), marcada (fecha) o pendiente, y avisa si\r\n",
            "\t                     el archivo cambió desde que se aplicó. No escribe nada\r\n",
            "\t                     bin/cli db-update\r\n",
            "\t  file=<nombre>      SIMULA: cuenta las sentencias y enseña el principio de cada una. No toca la base\r\n",
            "\t                     bin/cli db-update file=2026-11-13-consumo-de-la-ia\r\n",
            "\t  file=<nombre> apply=yes  aplica sentencia a sentencia y PARA en la primera que falla (las de antes\r\n",
            "\t                     quedan aplicadas: el DDL no se deshace) sin registrar el archivo. Si sale bien, lo\r\n",
            "\t                     registra. Una ya registrada se niega: again=yes la vuelve a aplicar. from=N empieza en la\r\n",
            "\t                     sentencia N (1 = la primera), para seguir tras corregir lo que falló\r\n",
            "\t                     bin/cli db-update file=2026-11-13-consumo-de-la-ia apply=yes\r\n",
            "\t  file=<nombre> mark=yes  la registra como «marcada» SIN ejecutar nada: para lo que se aplicó a mano antes\r\n",
            "\t                     de existir el registro\r\n",
            "\tapply=yes y mark=yes exigen un volcado de la última hora en dumps/ (bin/cli db-backup antes).\r\n",
            "\tEl nombre es el de un archivo .sql de esa carpeta, con o sin «.sql»: nunca una ruta.\r\n",
            "\tSale con 1 si una sentencia falla o la orden se niega.",
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
        $mark = TerminalData::instance()->getArgument('mark', '') === 'yes';
        $again = TerminalData::instance()->getArgument('again', '') === 'yes';
        $fromArgument = TerminalData::instance()->getArgument('from', '1');
        $from = is_string($fromArgument) && preg_match('/^[1-9]\d*$/', trim($fromArgument)) === 1 ? (int) trim($fromArgument) : 0;

        if ($file === '') {
            $status = self::listStatus();
            echoTerminal("\e[94mINFO:\e[39m " . count($status['updates']) . ' actualización(es) en ' . self::updatesDirectory() . ':');
            if (!$status['registry']) {
                echoTerminal("\e[33mAVISO:\e[39m no existe la tabla " . self::registryTable() . ': nada consta como aplicado, todas salen pendientes. db-update la crea al aplicar o marcar.');
            }
            $pending = 0;
            foreach ($status['updates'] as $update) {
                $when = $update['appliedAt'] !== null ? ' ' . mb_substr($update['appliedAt'], 0, 16) : '';
                $pending += $update['status'] === self::MODE_PENDING ? 1 : 0;
                echoTerminal("  {$update['name']}: {$update['status']}{$when}" . ($update['summary'] !== '' ? " — {$update['summary']}" : ''));
                if ($update['changed']) {
                    echoTerminal("    \e[33mAVISO:\e[39m el archivo cambió desde que se " . ($update['status'] === self::MODE_MARKED ? 'marcó' : 'aplicó') . ' (su sha1 no es el registrado).');
                }
            }
            echoTerminal("\e[94mINFO:\e[39m {$pending} pendiente(s).");
            echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
            exit(0);
        }

        $path = self::resolveUpdate($file);
        if ($path === null) {
            echoTerminal("\e[31mERROR:\e[39m «{$file}» no es el nombre de una actualización de " . self::updatesDirectory() . '. Lístalas con bin/cli db-update');
            exit(1);
        }

        if ($apply && $mark) {
            echoTerminal("\e[31mERROR:\e[39m apply=yes y mark=yes a la vez: aplicar ejecuta, marcar no. Elige una.");
            exit(1);
        }

        if ($mark) {
            $result = self::mark($path, time(), $again);
            if ($result['refused'] !== null) {
                echoTerminal("\e[31mERROR:\e[39m {$result['refused']}");
                exit(1);
            }
            echoTerminal("\e[94mINFO:\e[39m " . basename($path, '.sql') . ' registrada como «' . self::MODE_MARKED . "» ({$result['statements']} sentencia(s)), sin ejecutar ninguna.");
            echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
            exit(0);
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

        if ($from === 0) {
            echoTerminal("\e[31mERROR:\e[39m from= es el número de una sentencia, desde 1.");
            exit(1);
        }

        $result = self::apply($path, time(), $again, $from);
        if ($result['refused'] !== null) {
            echoTerminal("\e[31mERROR:\e[39m {$result['refused']}");
            exit(1);
        }
        foreach ($result['lines'] as $line) {
            $color = $line['status'] === 'OK' ? "\e[32m" : ($line['status'] === 'FALLO' ? "\e[31m" : "\e[33m");
            echoTerminal("{$color}{$line['status']}:\e[39m {$line['statement']}" . ($line['detail'] !== '' ? " — {$line['detail']}" : ''));
        }
        echoTerminal("\e[94mINFO:\e[39m {$result['applied']} aplicada(s), {$result['failed']} fallida(s), {$result['ignored']} ignorada(s), {$result['skipped']} saltada(s) por from=.");
        if ($result['stoppedAt'] !== null) {
            $after = $result['total'] - $result['stoppedAt'];
            echoTerminal("\e[31mPARADA:\e[39m falló la sentencia {$result['stoppedAt']} de {$result['total']}. Las anteriores quedan aplicadas (el DDL de MySQL no se deshace); las {$after} siguientes no se ejecutaron. El archivo NO se registra. Corrige y sigue con from={$result['stoppedAt']}.");
        } elseif ($result['registered']) {
            echoTerminal("\e[94mINFO:\e[39m registrada como «" . self::MODE_APPLIED . '» en ' . self::registryTable() . '.');
        }
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
        exit($result['failed'] > 0 || !$result['registered'] ? 1 : 0);
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
     * El estado de cada actualización según el registro: aplicada, marcada o pendiente, con su fecha y si el archivo cambió
     * desde entonces. Sin la tabla, todas pendientes y registry false. No escribe nada.
     *
     * @return array{registry:bool,updates:array<int,array{name:string,summary:string,status:string,appliedAt:string|null,changed:bool}>}
     */
    public static function listStatus(): array
    {
        $database = (new BaseModel())->getDatabase();
        $registered = $database !== null ? self::registered($database) : null;
        $updates = [];
        foreach (self::listUpdates() as $update) {
            $row = $registered[$update['name']] ?? null;
            $path = self::resolveUpdate($update['name']);
            $updates[] = $update + [
                'status' => $row !== null ? $row['mode'] : self::MODE_PENDING,
                'appliedAt' => $row !== null ? $row['appliedAt'] : null,
                'changed' => $row !== null && $path !== null && sha1_file($path) !== $row['sha1'],
            ];
        }
        return ['registry' => $registered !== null, 'updates' => $updates];
    }

    /**
     * Aplica la actualización sentencia a sentencia y PARA en la primera que falla, sin registrarla. Sin un volcado de la
     * última hora no aplica nada; una ya registrada se niega salvo con $again.
     *
     * @param string $path Una ruta que devolvió resolveUpdate()
     * @param int $now
     * @param bool $again Volver a aplicar una ya registrada
     * @param int $from La primera sentencia que se ejecuta (1 = la primera)
     * @return array{refused:string|null,applied:int,failed:int,ignored:int,skipped:int,total:int,stoppedAt:int|null,registered:bool,lines:array<int,array{status:string,statement:string,detail:string}>}
     */
    public static function apply(string $path, int $now, bool $again = false, int $from = 1): array
    {
        $statements = self::simulate($path);
        $result = ['refused' => null, 'applied' => 0, 'failed' => 0, 'ignored' => 0, 'skipped' => 0, 'total' => count($statements), 'stoppedAt' => null, 'registered' => false, 'lines' => []];

        $database = self::writableDatabase($now, $result['refused']);
        if ($database === null) {
            return $result;
        }
        if ($from < 1 || $from > max(1, count($statements))) {
            $result['refused'] = "from={$from} no es una sentencia de " . basename($path) . ' (tiene ' . count($statements) . ').';
            return $result;
        }
        $name = basename($path, '.sql');
        $row = self::registered($database)[$name] ?? null;
        if ($row !== null && !$again) {
            $result['refused'] = "{$name} ya está registrada como «{$row['mode']}» el {$row['appliedAt']}. Para volver a aplicarla, again=yes.";
            return $result;
        }

        foreach ($statements as $index => $statement) {
            $number = $index + 1;
            $preview = self::preview($statement, self::PREVIEW_LENGTH);
            if ($number < $from) {
                $result['skipped']++;
                $result['lines'][] = ['status' => 'SALTADA', 'statement' => $preview, 'detail' => "from={$from}"];
                continue;
            }
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
                $result['stoppedAt'] = $number;
                $result['lines'][] = ['status' => 'FALLO', 'statement' => $preview, 'detail' => self::preview($exception->getMessage(), self::ERROR_LENGTH)];
                return $result;
            }
        }

        self::register($database, $path, count($statements), self::MODE_APPLIED, $now);
        $result['registered'] = true;
        return $result;
    }

    /**
     * Registra la actualización como «marcada» SIN ejecutar nada: para la que se aplicó a mano antes del registro. Pide el
     * volcado de la última hora (escribe en la base); una ya registrada se niega salvo con $again.
     *
     * @param string $path Una ruta que devolvió resolveUpdate()
     * @param int $now
     * @param bool $again
     * @return array{refused:string|null,statements:int}
     */
    public static function mark(string $path, int $now, bool $again = false): array
    {
        $result = ['refused' => null, 'statements' => count(self::simulate($path))];
        $database = self::writableDatabase($now, $result['refused']);
        if ($database === null) {
            return $result;
        }
        $name = basename($path, '.sql');
        $row = self::registered($database)[$name] ?? null;
        if ($row !== null && !$again) {
            $result['refused'] = "{$name} ya está registrada como «{$row['mode']}» el {$row['appliedAt']}. Para volver a marcarla, again=yes.";
            return $result;
        }
        self::register($database, $path, $result['statements'], self::MODE_MARKED, $now);
        return $result;
    }

    /**
     * La conexión si hay un volcado de la última hora y la tabla del registro existe (la crea si falta); si no, null y
     * el motivo en $refused.
     *
     * @param int $now
     * @param string|null $refused
     * @return \PDO|null
     */
    private static function writableDatabase(int $now, ?string &$refused): ?\PDO
    {
        if (!RepairEscapedTextTask::hasRecentDump(self::dumpsDirectory(), $now)) {
            $refused = 'no hay un volcado de la última hora en dumps/. Haz bin/cli db-backup antes.';
            return null;
        }
        $database = (new BaseModel())->getDatabase();
        if ($database === null) {
            $refused = 'sin conexión a base de datos.';
            return null;
        }
        //Un clon que aún no tiene la tabla no puede depender de aplicarla primero.
        $database->exec(str_replace('`' . self::REGISTRY_TABLE . '`', '`' . self::registryTable() . '`', self::REGISTRY_DEFINITION));
        return $database;
    }

    /**
     * Lo registrado, por nombre; null si la tabla no existe.
     *
     * @param \PDO $database
     * @return array<string,array{sha1:string,statements:int,appliedAt:string,mode:string}>|null
     */
    private static function registered(\PDO $database): ?array
    {
        $exists = $database->prepare('SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = DATABASE() AND `table_name` = ?');
        //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o lee o lanza. Lo que cuenta es el COUNT que sigue.
        $exists->execute([self::registryTable()]);
        if ((int) $exists->fetchColumn() === 0) {
            return null;
        }
        $rows = [];
        $select = $database->prepare('SELECT `name`, `sha1`, `statements`, `appliedAt`, `mode` FROM `' . self::registryTable() . '`');
        //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o lee o lanza. Lo que cuenta es el fetchAll() que sigue.
        $select->execute();
        foreach ($select->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $rows[(string) $row['name']] = ['sha1' => (string) $row['sha1'], 'statements' => (int) $row['statements'], 'appliedAt' => (string) $row['appliedAt'], 'mode' => (string) $row['mode']];
        }
        return $rows;
    }

    /**
     * Anota (o actualiza) la actualización en el registro.
     *
     * @param \PDO $database
     * @param string $path
     * @param int $statements
     * @param string $mode
     * @param int $now
     * @return void
     */
    private static function register(\PDO $database, string $path, int $statements, string $mode, int $now): void
    {
        $insert = $database->prepare('INSERT INTO `' . self::registryTable() . '` (`name`, `sha1`, `statements`, `appliedAt`, `mode`) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `sha1` = VALUES(`sha1`), `statements` = VALUES(`statements`), `appliedAt` = VALUES(`appliedAt`), `mode` = VALUES(`mode`)');
        //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o escribe o lanza.
        $insert->execute([basename($path, '.sql'), (string) sha1_file($path), $statements, date('Y-m-d H:i:s', $now), $mode]);
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
     * La tabla del registro: REGISTRY_TABLE, salvo en una prueba que la sustituya.
     *
     * @return string
     */
    public static function registryTable(): string
    {
        return self::$registryTableOverride ?? self::REGISTRY_TABLE;
    }

    /**
     * SOLO PARA PRUEBAS: cambia las dos carpetas y la tabla del registro (null: las del producto) y devuelve las de antes,
     * para reponerlas.
     *
     * @internal
     * @param string|null $updatesDirectory
     * @param string|null $dumpsDirectory
     * @param string|null $registryTable
     * @return array{0:string|null,1:string|null,2:string|null}
     */
    public static function swapForTesting(?string $updatesDirectory = null, ?string $dumpsDirectory = null, ?string $registryTable = null): array
    {
        $previous = [self::$updatesDirectoryOverride, self::$dumpsDirectoryOverride, self::$registryTableOverride];
        self::$updatesDirectoryOverride = $updatesDirectory !== null ? rtrim($updatesDirectory, '/') : null;
        self::$dumpsDirectoryOverride = $dumpsDirectory !== null ? rtrim($dumpsDirectory, '/') : null;
        self::$registryTableOverride = $registryTable;
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
