<?php

/**
 * ScanInvalidUtf8Task.php
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
 * ScanInvalidUtf8Task.
 *
 * Busca UTF-8 inválido en las columnas de texto de la base de datos.
 *
 * SOLO LECTURA: no escribe, no altera y no corrige nada.
 *
 * Importa porque `json_encode()` es lo único que falla ante UTF-8 inválido, y desde que
 * los sitios de codificación llevan `JSON_THROW_ON_ERROR` ese fallo es una excepción —y
 * con el manejador de `bootstrap.php`, un 500—. Antes servía un dato ligeramente mal; hoy
 * corta la petición. Este escaneo dice si hay pólvora antes de desplegar.
 *
 * La comprobación se hace en PHP con `mb_check_encoding()` y no en SQL a propósito: quien
 * tiene que aceptar el dato es PHP, así que preguntárselo a PHP es la única respuesta que
 * vale.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class ScanInvalidUtf8Task extends TerminalTaskAbstract
{

    const TEXT_TYPES = ['char', 'varchar', 'text', 'tinytext', 'mediumtext', 'longtext', 'enum', 'set', 'json'];

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'scan-invalid-utf8';

        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        $this->description = new StringArray([
            "Busca UTF-8 inválido en las columnas de texto de la base de datos.\r\n",
            "\tSOLO LECTURA: no escribe ni corrige nada.\r\n",
            "\tParámetros:\r\n",
            "\t  table  limita el escaneo a una tabla. Por defecto: todas\r\n",
            "\t  limit  filas por tabla. Por defecto: 5000",
        ]);
        $this->route = "{$startRoute}/scan-invalid-utf8[/]";
        $this->controller = self::class . '::main';
        $this->name = $name;
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray($permissions);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = "Buscando UTF-8 inválido";
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");

        $onlyTable = TerminalData::instance()->getArgument('table', '');
        $limit = (int) TerminalData::instance()->getArgument('limit', '5000');
        $limit = $limit > 0 ? $limit : 5000;

        //Mismo camino que DbBackupTask: la conexión configurada, no una construida a mano.
        $db = (new BaseModel())->getDatabase();
        if ($db === null) {
            echoTerminal("\e[31mERROR:\e[39m sin conexión a base de datos.");
            exit(1);
        }
        $schema = $db->getDatabaseName();

        $columnsStatement = $db->prepare(
            'SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS ' .
            'WHERE TABLE_SCHEMA = ? AND DATA_TYPE IN ("' . implode('","', self::TEXT_TYPES) . '") ' .
            'ORDER BY TABLE_NAME, ORDINAL_POSITION'
        );
        $columnsStatement->execute([$schema]);
        $columns = $columnsStatement->fetchAll(\PDO::FETCH_ASSOC);

        $byTable = [];
        foreach ($columns as $column) {
            $table = (string) $column['TABLE_NAME'];
            if ($onlyTable !== '' && $table !== $onlyTable) {
                continue;
            }
            $byTable[$table][] = (string) $column['COLUMN_NAME'];
        }

        echoTerminal("\e[94mINFO:\e[39m esquema '{$schema}': " . count($byTable) . " tabla(s) con columnas de texto.");
        echoTerminal('');

        $findings = 0;
        $scannedRows = 0;

        foreach ($byTable as $table => $tableColumns) {
            $quoted = array_map(fn (string $c): string => '`' . str_replace('`', '``', $c) . '`', $tableColumns);
            $sql = 'SELECT ' . implode(', ', $quoted) . ' FROM `' . str_replace('`', '``', $table) . '` LIMIT ' . $limit;
            try {
                $rowsStatement = $db->query($sql);
            } catch (\Throwable $e) {
                echoTerminal("\e[33mAVISO:\e[39m no se pudo leer '{$table}': " . mb_substr($e->getMessage(), 0, 70));
                continue;
            }
            $badColumns = [];
            while (($row = $rowsStatement->fetch(\PDO::FETCH_ASSOC)) !== false) {
                $scannedRows++;
                foreach ($row as $columnName => $value) {
                    if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                        $badColumns[$columnName] = ($badColumns[$columnName] ?? 0) + 1;
                    }
                }
            }
            foreach ($badColumns as $columnName => $count) {
                $findings++;
                echoTerminal("\e[31mINVÁLIDO:\e[39m {$table}.{$columnName} — {$count} fila(s)");
            }
        }

        echoTerminal('');
        echoTerminal("\e[94mFilas leídas:\e[39m {$scannedRows}");

        if ($findings === 0) {
            echoTerminal("\e[32mOK:\e[39m todo el texto analizado es UTF-8 válido.");
            echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
            exit(0);
        }

        echoTerminal("\e[31mCOLUMNAS AFECTADAS: {$findings}\e[39m");
        echoTerminal("\e[33mjson_encode() lanzará sobre esas filas. Revisar antes de desplegar.\e[39m");
        echoTerminal("\e[31m*** {$titleTask}, tarea finalizada CON HALLAZGOS ***\e[39m");
        exit(1);
    }

    /**
     * @param string $startRoute
     * @param string|null $namePrefix
     * @return Route
     */
    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new ScanInvalidUtf8Task($startRoute, $namePrefix);
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
