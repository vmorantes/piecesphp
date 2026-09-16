<?php

/**
 * RepairEscapedTextTask.php
 */

namespace Terminal\Tasks;

use App\Model\AppConfigModel;
use App\Model\UsersModel;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\EntityMapper;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\TerminalData;

/**
 * RepairEscapedTextTask.
 *
 * Deshace UNA vez el escape que piecesphp/database 4 guardaba en las columnas de texto de los mappers
 * (O\'Brien pasa a O'Brien). Por defecto solo cuenta. Ver la ruptura 30 del CHANGELOG.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class RepairEscapedTextTask extends SchemeSqlTask
{

    const TEXT_TYPES = ['varchar', 'text', 'mediumtext', 'longtext'];
    const MARKER = 'escaped_text_repaired';

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }

        $this->description = new StringArray([
            "Deshace el escape (addslashes) que piecesphp/database 4 guardaba en las columnas de texto de los mappers.\r\n",
            "\tPOR DEFECTO SOLO CUENTA, por tabla.columna, sin imprimir valores.\r\n",
            "\tParámetros:\r\n",
            "\t  apply=yes  aplica. Exige un volcado de la última hora en dumps/ (bin/cli db-backup)\r\n",
            "\t             y corre una sola vez por base: deja una marca y se niega si ya la hay.\r\n",
            "\tNo distingue lo guardado después de actualizar el paquete ni lo insertado con SQL propio:\r\n",
            "\tun valor con barras legítimas también cuenta como candidato. Revisa la cuenta antes de aplicar.",
        ]);
        $this->route = "{$startRoute}/repair-escaped-text[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'repair-escaped-text';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = 'Reparación del escape de texto';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");

        $apply = TerminalData::instance()->getArgument('apply', '') === 'yes';

        $db = (new BaseModel())->getDatabase();
        if ($db === null) {
            echoTerminal("\e[31mERROR:\e[39m sin conexión a base de datos.");
            exit(1);
        }

        //LAS DOS NEGATIVAS VAN ANTES DE LEER NADA: con apply, nada se toca si falta una.
        if ($apply) {
            if (self::isMarked()) {
                $marker = new AppConfigModel(self::MARKER);
                $value = (array) $marker->value;
                $date = is_scalar($value['date'] ?? null) ? (string) $value['date'] : 'fecha desconocida';
                echoTerminal("\e[31mERROR:\e[39m esta base ya se reparó ({$date}). No se aplica dos veces.");
                exit(1);
            }
            if (!self::hasRecentDump(basepath('dumps'), time())) {
                echoTerminal("\e[31mERROR:\e[39m no hay un volcado de la última hora en dumps/. Haz bin/cli db-backup antes.");
                exit(1);
            }
        }

        $found = self::discover('all');
        foreach ($found['skipped'] as $line) {
            echoTerminal("\e[33mAVISO:\e[39m fuera de la reparación — {$line}");
        }

        $seen = [];
        $totalRows = 0;
        $totalColumns = 0;

        foreach ($found['mappers'] as $mapper) {
            try {
                $info = self::textColumns($mapper);
            } catch (\Throwable $e) {
                echoTerminal("\e[33mAVISO:\e[39m no se pudieron leer los campos de " . get_class($mapper) . ': ' . mb_substr($e->getMessage(), 0, 70));
                continue;
            }
            foreach ($info['columns'] as $column) {
                //Dos mappers pueden compartir tabla: cada columna se trata una vez.
                $key = $info['table'] . '.' . $column;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                try {
                    $result = self::repairColumn($db, $info['table'], $info['primaryKey'], $column, $apply);
                } catch (\Throwable $e) {
                    echoTerminal("\e[33mAVISO:\e[39m no se pudo leer '{$key}': " . mb_substr($e->getMessage(), 0, 70));
                    continue 2;
                }
                if ($result['candidates'] === 0) {
                    continue;
                }
                $totalColumns++;
                if ($apply) {
                    $totalRows += $result['updated'];
                    echoTerminal("\e[34mACTUALIZADAS:\e[39m {$key} — {$result['updated']} de {$result['candidates']} fila(s)");
                } else {
                    $totalRows += $result['candidates'];
                    echoTerminal("\e[33mCANDIDATAS:\e[39m {$key} — {$result['candidates']} fila(s)");
                }
            }
        }

        echoTerminal('');
        if ($apply) {
            self::mark(['date' => date('Y-m-d H:i:s'), 'updated' => $totalRows]);
            echoTerminal("\e[94mINFO:\e[39m {$totalRows} fila(s) actualizada(s) en {$totalColumns} columna(s). Marca '" . self::MARKER . "' guardada.");
        } else {
            echoTerminal("\e[94mINFO:\e[39m {$totalRows} fila(s) candidata(s) en {$totalColumns} columna(s).");
            echoTerminal("\e[33mSOLO CUENTA:\e[39m no se ha cambiado nada. Para aplicar: bin/cli db-backup y después bin/cli repair-escaped-text apply=yes");
        }
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
        exit(0);
    }

    /**
     * Tabla, clave primaria y columnas de texto de un mapper.
     *
     * @param EntityMapper $mapper
     * @return array{table: string, primaryKey: string, columns: string[]}
     */
    public static function textColumns(EntityMapper $mapper): array
    {
        $columns = [];
        foreach ($mapper::getFields() as $name => $definition) {
            $type = is_array($definition) ? mb_strtolower((string) ($definition['type'] ?? '')) : '';
            if (in_array($type, self::TEXT_TYPES, true)) {
                $columns[] = (string) $name;
            }
        }
        return [
            'table' => (string) $mapper->getModel()->getTable(),
            'primaryKey' => (string) $mapper::getPrimaryKey(),
            'columns' => $columns,
        ];
    }

    /**
     * Cuenta, y con $apply deshace, el escape de una columna.
     *
     * @param \PDO $db
     * @param string $table Sale del mapper, nunca de la petición
     * @param string $primaryKey
     * @param string $column
     * @param bool $apply
     * @param int[]|null $onlyIds Acota las filas; null, todas
     * @return array{candidates: int, updated: int}
     */
    public static function repairColumn(\PDO $db, string $table, string $primaryKey, string $column, bool $apply, ?array $onlyIds = null): array
    {
        $quote = fn(string $identifier): string => '`' . str_replace('`', '``', $identifier) . '`';
        $sql = 'SELECT ' . $quote($primaryKey) . ', ' . $quote($column) . ' FROM ' . $quote($table);
        $values = [];
        if ($onlyIds !== null) {
            if (count($onlyIds) === 0) {
                return ['candidates' => 0, 'updated' => 0];
            }
            $values = array_map('intval', array_values($onlyIds));
            $sql .= ' WHERE ' . $quote($primaryKey) . ' IN (' . implode(', ', array_fill(0, count($values), '?')) . ')';
        }
        $select = $db->prepare($sql);
        $select->execute($values);
        $rows = $select->fetchAll(\PDO::FETCH_NUM);

        $update = $db->prepare('UPDATE ' . $quote($table) . ' SET ' . $quote($column) . ' = ? WHERE ' . $quote($primaryKey) . ' = ?');
        $candidates = 0;
        $updated = 0;
        foreach ($rows as [$id, $value]) {
            if (!is_string($value) || stripslashes($value) === $value) {
                continue;
            }
            $candidates++;
            if ($apply) {
                $update->execute([stripslashes($value), $id]);
                $updated += $update->rowCount();
            }
        }
        return ['candidates' => $candidates, 'updated' => $updated];
    }

    /**
     * Si hay algún volcado (*.sql o *.sql.gz) de hace menos de $maxAgeSeconds.
     *
     * @param string $directory
     * @param int $now
     * @param int $maxAgeSeconds
     * @return bool
     */
    public static function hasRecentDump(string $directory, int $now, int $maxAgeSeconds = 3600): bool
    {
        $dumps = array_merge(glob(rtrim($directory, '/') . '/*.sql') ?: [], glob(rtrim($directory, '/') . '/*.sql.gz') ?: []);
        foreach ($dumps as $dump) {
            $modified = filemtime($dump);
            if ($modified !== false && $modified >= $now - $maxAgeSeconds) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isMarked(string $name = self::MARKER): bool
    {
        return (new AppConfigModel($name))->id !== null;
    }

    /**
     * @param array<string,mixed> $data
     * @param string $name
     * @return bool
     */
    public static function mark(array $data, string $name = self::MARKER): bool
    {
        $marker = new AppConfigModel();
        $marker->name = $name;
        $marker->value = $data;
        return $marker->save() === true;
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new RepairEscapedTextTask($startRoute, $namePrefix);
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
