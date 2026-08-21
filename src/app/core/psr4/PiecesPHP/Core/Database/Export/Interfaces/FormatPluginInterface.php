<?php

namespace PiecesPHP\Core\Database\Export\Interfaces;

use PiecesPHP\Core\Database\Database;
use PDO;

/**
 * Interface FormatPluginInterface
 * 
 * Interfaz para los plugins que definen el formato de la exportación (SQL, JSON, etc).
 */
interface FormatPluginInterface
{
    /**
     * Devuelve el encabezado de la exportación.
     * 
     * @param Database $db
     * @param string $database
     * @param string $charset
     * @return string
     */
    public function getHeader(Database $db, string $database, string $charset): string;

    /**
     * Devuelve la estructura de una tabla.
     * 
     * @param Database $db
     * @param string $table
     * @param array $options
     * @return string
     */
    public function getTableStructure(Database $db, string $table, array $options): string;

    /**
     * Devuelve los datos de una tabla.
     * 
     * @param Database $db
     * @param string $table
     * @param array $options
     * @return string
     */
    public function getTableData(Database $db, string $table, array $options, ?callable $writeCallback = null): ?string;

    /**
     * Devuelve las funciones de la base de datos.
     * 
     * @param Database $db
     * @param string $database
     * @param array $options
     * @return string
     */
    public function getFunctions(Database $db, string $database, array $options): string;

    /**
     * Devuelve los procedimientos de la base de datos.
     * 
     * @param Database $db
     * @param string $database
     * @param array $options
     * @return string
     */
    public function getProcedures(Database $db, string $database, array $options): string;

    /**
     * Determina si una tabla es una vista.
     * 
     * @param Database $db
     * @param string $table
     * @return bool
     */
    public function isView(Database $db, string $table): bool;

    /**
     * Devuelve una "tabla de mentira" para representar una vista en la primera fase.
     * 
     * @param Database $db
     * @param string $table
     * @return string
     */
    public function getTableFakeView(Database $db, string $table): string;

    /**
     * Devuelve los disparadores (triggers) de una tabla.
     * 
     * @param Database $db
     * @param string $table
     * @param array $options
     * @return string
     */
    public function getTableTriggers(Database $db, string $table, array $options): string;

    /**
     * Devuelve el pie de la exportación.
     * 
     * @return string
     */
    public function getFooter(): string;
}
