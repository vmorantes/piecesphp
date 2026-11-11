<?php

/**
 * PruneTestLogsTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\UserSystem\ORM\TicketsLogModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Terminal\Seeding\SeedPlanner;
use Terminal\TestLeftovers;

/**
 * PruneTestLogsTask.
 *
 * Retira los restos de prueba de los registros de correos y de soporte (H233): una fila es resto si alguna de sus columnas
 * de texto EMPIEZA por la marca, con la misma regla y las mismas columnas que barre gates (TestLeftovers). Solo esas dos
 * tablas y solo en la instalación LOCAL. Sin escribir=yes, SOLO SIMULA: dice tabla, id y columna, nunca el contenido.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class PruneTestLogsTask extends TerminalTaskAbstract
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
            "Los restos de prueba de los registros de correos y de soporte (filas cuyo texto empieza por " . TestLeftovers::MARK . "), solo en la instalación LOCAL. Sin escribir=yes, SOLO SIMULA.\r\n",
            "\tParámetros:\r\n",
            "\t  escribir=yes     borra esas filas, en una transacción",
        ]);
        $this->route = "{$startRoute}/prune-test-logs[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'prune-test-logs';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $write = TerminalData::instance()->getArgument('escribir', '') === 'yes';
        echoTerminal("\e[32m*** Restos de prueba de los registros (" . ($write ? 'escribir' : 'simulación') . ") ***\e[39m");
        $refusal = self::refusal();
        if ($refusal !== null) {
            echoTerminal("\e[31m{$refusal}\e[39m");
            exit(1);
        }
        try {
            $rows = $write ? self::prune() : self::leftovers();
        } catch (\Throwable $e) {
            echoTerminal("\e[31mPARA: {$e->getMessage()}\e[39m");
            exit(2);
        }
        foreach ($rows as $row) {
            echoTerminal("  {$row['table']} #{$row['id']}: " . implode(', ', $row['columns']));
        }
        echoTerminal(($write ? 'borradas: ' : 'se borrarían (nada se borra sin escribir=yes): ') . count($rows) . ' fila(s) en ' . implode(', ', self::tables()));
        echoTerminal("\e[32m*** Restos de prueba de los registros, tarea finalizada ***\e[39m");
        exit(0);
    }

    /**
     * Por qué no corre aquí, o null si es la instalación local (la misma comprobación que la siembra).
     *
     * @return string|null
     */
    public static function refusal(): ?string
    {
        return SeedPlanner::isLocalInstallation() ? null : 'PARA: los restos de prueba solo se retiran en la instalación LOCAL (terminal con --local y environment.php en «local»), y esta no lo es.';
    }

    /**
     * Las dos tablas, y solo ellas.
     *
     * @return string[]
     */
    public static function tables(): array
    {
        return [MailLogMapper::TABLE, (new TicketsLogModel())->getModel()->getTable()];
    }

    /**
     * Las filas que son resto: tabla, id y las columnas que empiezan por la marca. No lee nada más.
     *
     * @param string[]|null $tables Solo para pruebas: tablas propias (con el nombre de una de las dos más `_zz_…`)
     * @return array<int,array{table:string,id:int,columns:string[]}>
     */
    public static function leftovers(?array $tables = null): array
    {
        $tables = self::checkedTables($tables);
        $database = self::database();
        $rows = [];
        foreach ($tables as $table) {
            $byId = [];
            foreach (self::textColumns($database, $table) as $column) {
                $statement = $database->prepare("SELECT `id` FROM `{$table}` WHERE `{$column}` LIKE ? OR `{$column}` LIKE ? ORDER BY `id`");
                $statement->execute([TestLeftovers::MARK . '%', TestLeftovers::likeLiteral(TestLeftovers::MARK_UNDERSCORE) . '%']);
                foreach ($statement->fetchAll(\PDO::FETCH_COLUMN) as $id) {
                    $byId[(int) $id][] = $column;
                }
            }
            ksort($byId);
            foreach ($byId as $id => $columns) {
                $rows[] = ['table' => $table, 'id' => $id, 'columns' => $columns];
            }
        }
        return $rows;
    }

    /**
     * Borra las filas que son resto, en UNA transacción, y devuelve las que borró. Solo en la instalación local.
     *
     * @param string[]|null $tables Solo para pruebas, como en leftovers()
     * @return array<int,array{table:string,id:int,columns:string[]}>
     * @throws \RuntimeException Con «PARA» si no es la instalación local
     */
    public static function prune(?array $tables = null): array
    {
        $refusal = self::refusal();
        if ($refusal !== null) {
            throw new \RuntimeException($refusal);
        }
        $rows = self::leftovers($tables);
        $database = self::database();
        $database->beginTransaction();
        try {
            foreach ($rows as $row) {
                $database->prepare("DELETE FROM `{$row['table']}` WHERE `id` = ?")->execute([$row['id']]);
            }
            $database->commit();
        } catch (\Throwable $e) {
            $database->rollBack();
            throw $e;
        }
        return $rows;
    }

    /**
     * Las tablas pedidas, si son las dos o sus copias de prueba; nunca otra.
     *
     * @param string[]|null $tables
     * @return string[]
     */
    private static function checkedTables(?array $tables): array
    {
        $real = self::tables();
        if ($tables === null) {
            return $real;
        }
        foreach ($tables as $table) {
            $isCopy = array_filter($real, fn(string $base): bool => preg_match('/^' . preg_quote($base, '/') . '_zz_[a-z0-9_]+$/', $table) === 1) !== [];
            if (!$isCopy) {
                throw new \InvalidArgumentException('Solo las copias de prueba de los dos registros (`<tabla>_zz_…`).');
            }
        }
        return $tables;
    }

    /**
     * Las columnas de texto de una tabla que barre gates: las de TestLeftovers::TEXT_DATA_TYPES, menos las que pueden
     * guardar un secreto.
     *
     * @param \PDO $database
     * @param string $table
     * @return string[]
     */
    private static function textColumns(\PDO $database, string $table): array
    {
        $types = TestLeftovers::TEXT_DATA_TYPES;
        $statement = $database->prepare(
            'SELECT `column_name` FROM `information_schema`.`columns` WHERE `table_schema` = DATABASE() AND `table_name` = ?'
                . ' AND `data_type` IN (' . implode(', ', array_fill(0, count($types), '?')) . ') ORDER BY `ordinal_position`'
        );
        $statement->execute(array_merge([$table], $types));
        $columns = array_map('strval', $statement->fetchAll(\PDO::FETCH_COLUMN));
        return array_values(array_filter($columns, fn(string $column): bool => preg_match(TestLeftovers::SENSITIVE_PATTERN, $column) !== 1));
    }

    /**
     * @param string $startRoute
     * @param string|null $namePrefix
     * @return Route
     */
    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new PruneTestLogsTask($startRoute, $namePrefix);
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

    /**
     * @return \PDO
     */
    private static function database(): \PDO
    {
        $database = (new BaseModel())->getDatabase();
        if ($database === null) {
            throw new \RuntimeException('sin conexión a la base de datos');
        }
        return $database;
    }
}
