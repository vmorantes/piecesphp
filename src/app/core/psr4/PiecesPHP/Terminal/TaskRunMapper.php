<?php

/**
 * TaskRunMapper.php
 */

namespace PiecesPHP\Terminal;

use PiecesPHP\Core\BaseEntityMapper;

/**
 * TaskRunMapper - El libro de las tareas programadas: una línea por ejecución real (G10).
 *
 * Solo se anota lo que ejecutó la tarea (executed, failed): «no toca», «en curso» y «ya hecha» llenarían la tabla cada
 * minuto. Sin la tabla, la tarea corre igual y queda UN aviso suave por proceso (ADR 0060).
 *
 * @property int|null $id
 * @property string $taskName
 * @property string|null $slot
 * @property string $triggeredBy
 * @property string $status
 * @property int $attempt
 * @property string $startedAt
 * @property string|null $finishedAt
 * @property int|null $durationMs
 * @property string|null $message
 * @property string|null $host
 * @package     PiecesPHP\Terminal
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class TaskRunMapper extends BaseEntityMapper
{
    const TABLE = 'pcsphp_task_runs';

    /**
     * El SQL que crea la tabla en una instalación que ya existe.
     */
    const UPDATE_FILE = 'databases/actualizaciones/2026-10-14-libro-de-tareas-y-cola.sql';

    const TRIGGER_HTTP = 'http';
    const TRIGGER_CLI = 'cli';

    /**
     * El tope de líneas, como el registro de correos. Una línea pesa menos de 1 KB: 20.000 son unos 20 MB como mucho.
     */
    const MAX_ROWS = 20000;

    /**
     * El tope por tarea: una que corre cada minuto (la cola) no expulsa la historia de las diarias. 1000 son ~16 h de la
     * cola y casi tres años de una diaria.
     */
    const MAX_ROWS_PER_TASK = 1000;

    /**
     * El largo de `message` en la tabla.
     */
    const MESSAGE_LENGTH = 500;

    /**
     * @var string
     */
    protected $table = self::TABLE;

    /**
     * @var array<string,array<string,mixed>>
     */
    protected $fields = [
        'id' => [
            'type' => 'bigint',
            'primary_key' => true,
        ],
        'taskName' => [
            'type' => 'varchar',
            'length' => 191,
        ],
        'slot' => [
            'type' => 'datetime',
            'null' => true,
        ],
        'triggeredBy' => [
            'type' => 'varchar',
            'length' => 16,
        ],
        'status' => [
            'type' => 'varchar',
            'length' => 24,
        ],
        'attempt' => [
            'type' => 'int',
        ],
        'startedAt' => [
            'type' => 'datetime',
        ],
        'finishedAt' => [
            'type' => 'datetime',
            'null' => true,
        ],
        'durationMs' => [
            'type' => 'int',
            'null' => true,
        ],
        'message' => [
            'type' => 'varchar',
            'length' => self::MESSAGE_LENGTH,
            'null' => true,
        ],
        'host' => [
            'type' => 'varchar',
            'length' => 64,
            'null' => true,
        ],
    ];

    /**
     * @var int
     */
    protected static $maxRows = self::MAX_ROWS;

    /**
     * @var int
     */
    protected static $maxRowsPerTask = self::MAX_ROWS_PER_TASK;

    /**
     * @var string|null
     */
    protected static $tableForTesting = null;

    /**
     * @var bool|null
     */
    protected static $tableExistsMemo = null;

    /**
     * Si ya se avisó en este proceso de que falta la tabla.
     *
     * @var bool
     */
    protected static $missingWarned = false;

    /**
     * @param int|null $id
     * @param string $fieldCompare
     */
    public function __construct($id = null, string $fieldCompare = 'primary_key')
    {
        $this->table = self::tableName();
        parent::__construct($id, $fieldCompare);
    }

    /**
     * Anota una ejecución. **Nunca lanza**: el libro no puede tumbar la tarea que anota.
     *
     * @param string $taskName
     * @param string|null $slot La franja (Y-m-d H:i), si la tarea la tiene
     * @param string $triggeredBy TRIGGER_HTTP o TRIGGER_CLI
     * @param string $status CronJobTask::STATUS_EXECUTED o STATUS_FAILED
     * @param int $attempt
     * @param \DateTimeInterface $startedAt
     * @param \DateTimeInterface|null $finishedAt
     * @param int|null $durationMs
     * @param string|null $message Se recorta a MESSAGE_LENGTH
     * @return bool
     */
    public static function record(string $taskName, ?string $slot, string $triggeredBy, string $status, int $attempt, \DateTimeInterface $startedAt, ?\DateTimeInterface $finishedAt, ?int $durationMs, ?string $message): bool
    {
        try {
            if (!self::tableExists()) {
                if (!self::$missingWarned) {
                    self::$missingWarned = true;
                    log_soft_error('Falta la tabla ' . self::tableName() . ': las ejecuciones de las tareas programadas no se anotan. Aplique ' . self::UPDATE_FILE . '.');
                }
                return false;
            }
            $database = self::model()->getDatabase();
            if ($database === null) {
                return false;
            }
            $host = gethostname();
            $statement = $database->prepare('INSERT INTO `' . self::tableName() . '` (taskName, slot, triggeredBy, status, attempt, startedAt, finishedAt, durationMs, message, host) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $saved = $statement->execute([
                mb_substr($taskName, 0, 191),
                $slot !== null ? $slot . ':00' : null,
                mb_substr($triggeredBy, 0, 16),
                mb_substr($status, 0, 24),
                $attempt,
                $startedAt->format('Y-m-d H:i:s'),
                $finishedAt !== null ? $finishedAt->format('Y-m-d H:i:s') : null,
                $durationMs,
                $message !== null ? mb_substr($message, 0, self::MESSAGE_LENGTH) : null,
                is_string($host) ? mb_substr($host, 0, 64) : null,
            ]);
            //Primero el tope de su tarea, después el global.
            self::trimTask($taskName);
            self::trim();
            return $saved;
        } catch (\Throwable $throwable) {
            log_exception($throwable);
            return false;
        }
    }

    /**
     * La última ejecución anotada de cada tarea, por nombre. Vacío sin la tabla. Nunca lanza.
     *
     * @return array<string,array{status:string,startedAt:string,message:string|null}>
     */
    public static function latestByTask(): array
    {
        try {
            if (!self::tableExists()) {
                return [];
            }
            $database = self::model()->getDatabase();
            if ($database === null) {
                return [];
            }
            $table = self::tableName();
            $rows = $database->query("SELECT t.taskName, t.status, t.startedAt, t.message FROM `{$table}` t JOIN (SELECT MAX(id) AS id FROM `{$table}` GROUP BY taskName) m ON m.id = t.id");
            $latest = [];
            foreach ($rows !== false ? $rows->fetchAll(\PDO::FETCH_ASSOC) : [] as $row) {
                $latest[(string) $row['taskName']] = [
                    'status' => (string) $row['status'],
                    'startedAt' => (string) $row['startedAt'],
                    'message' => $row['message'] !== null ? (string) $row['message'] : null,
                ];
            }
            return $latest;
        } catch (\Throwable $throwable) {
            return [];
        }
    }

    /**
     * Deja las `maxRows()` más recientes, por id (creciente). Nunca lanza.
     *
     * @return int Cuántas líneas se retiraron
     */
    public static function trim(): int
    {
        try {
            $database = self::model()->getDatabase();
            if ($database === null) {
                return 0;
            }
            $table = self::tableName();
            $limit = $database->query("SELECT `id` FROM `{$table}` ORDER BY `id` DESC LIMIT 1 OFFSET " . (self::maxRows() - 1));
            $cut = $limit !== false ? $limit->fetchColumn() : false;
            if ($cut === false || $cut === null) {
                return 0;
            }
            $statement = $database->prepare("DELETE FROM `{$table}` WHERE `id` < ?");
            //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o hace su trabajo o lanza. Lo retirado lo dice rowCount().
            $statement->execute([(int) $cut]);
            return $statement->rowCount();
        } catch (\Throwable $throwable) {
            return 0;
        }
    }

    /**
     * Deja las `maxRowsPerTask()` más recientes de UNA tarea, por id (creciente); las demás tareas no se tocan. Nunca lanza.
     *
     * @param string $taskName
     * @return int Cuántas líneas se retiraron
     */
    public static function trimTask(string $taskName): int
    {
        try {
            $database = self::model()->getDatabase();
            if ($database === null) {
                return 0;
            }
            $table = self::tableName();
            $taskName = mb_substr($taskName, 0, 191);
            $limit = $database->prepare("SELECT `id` FROM `{$table}` WHERE `taskName` = ? ORDER BY `id` DESC LIMIT 1 OFFSET " . (self::maxRowsPerTask() - 1));
            //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o hace su trabajo o lanza. Lo que cuenta es el fetchColumn().
            $limit->execute([$taskName]);
            $cut = $limit->fetchColumn();
            if ($cut === false || $cut === null) {
                return 0;
            }
            $statement = $database->prepare("DELETE FROM `{$table}` WHERE `taskName` = ? AND `id` < ?");
            //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o hace su trabajo o lanza. Lo retirado lo dice rowCount().
            $statement->execute([$taskName, (int) $cut]);
            return $statement->rowCount();
        } catch (\Throwable $throwable) {
            return 0;
        }
    }

    /**
     * Si la tabla existe, en la base ACTUAL (`information_schema`: `SHOW TABLES LIKE` toma el `_` por comodín). Falla
     * cerrado.
     *
     * @return bool
     */
    public static function tableExists(): bool
    {
        if (self::$tableExistsMemo !== null) {
            return self::$tableExistsMemo;
        }
        try {
            $database = self::model()->getDatabase();
            if ($database === null) {
                return false;
            }
            $statement = $database->prepare('SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = DATABASE() AND `table_name` = ?');
            //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o hace su trabajo o lanza. Lo que cuenta es el COUNT que sigue.
            $statement->execute([self::tableName()]);
            self::$tableExistsMemo = ((int) $statement->fetchColumn()) > 0;
            return self::$tableExistsMemo;
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * El tope en uso. Con null vuelve a MAX_ROWS.
     *
     * @param int|null $rows
     * @return void
     */
    public static function setMaxRows(?int $rows): void
    {
        self::$maxRows = $rows === null ? self::MAX_ROWS : max(1, $rows);
    }

    /**
     * @return int
     */
    public static function maxRows(): int
    {
        return self::$maxRows;
    }

    /**
     * SOLO PARA PRUEBAS: otro tope por tarea. Con null, el de la constante.
     *
     * @param int|null $rows
     * @return void
     */
    public static function setMaxRowsPerTask(?int $rows): void
    {
        self::$maxRowsPerTask = $rows === null ? self::MAX_ROWS_PER_TASK : max(1, $rows);
    }

    /**
     * @return int
     */
    public static function maxRowsPerTask(): int
    {
        return self::$maxRowsPerTask;
    }

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return self::$tableForTesting ?? self::TABLE;
    }

    /**
     * SOLO PARA PRUEBAS: una tabla propia `pcsphp_task_runs_zz_…`, exista o no, sin tocar la real. Con null, la real.
     *
     * @param string|null $table
     * @return void
     * @throws \InvalidArgumentException si el nombre no es de una tabla de prueba
     */
    public static function useTableForTesting(?string $table): void
    {
        if ($table !== null && preg_match('/^' . self::TABLE . '_zz_[a-z0-9_]+$/', $table) !== 1) {
            throw new \InvalidArgumentException('Solo una tabla de prueba `' . self::TABLE . '_zz_…`.');
        }
        self::$tableForTesting = $table;
        self::$tableExistsMemo = null;
        self::$missingWarned = false;
    }
}
