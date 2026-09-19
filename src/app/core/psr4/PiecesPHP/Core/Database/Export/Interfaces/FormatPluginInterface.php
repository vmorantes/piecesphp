<?php

namespace PiecesPHP\Core\Database\Export\Interfaces;

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
     * @param PDO $db
     * @param string $database
     * @param string $charset
     * @return string
     */
    public function getHeader(PDO $db, string $database, string $charset): string;

    /**
     * Devuelve la estructura de una tabla.
     * 
     * @param PDO $db
     * @param string $table
     * @param array $options
     * @return string
     */
    public function getTableStructure(PDO $db, string $table, array $options): string;

    /**
     * Devuelve los datos de una tabla.
     * 
     * @param PDO $db
     * @param string $table
     * @param array $options
     * @return string
     */
    public function getTableData(PDO $db, string $table, array $options, ?callable $writeCallback = null): ?string;

    /**
     * Devuelve las funciones de la base de datos.
     * 
     * @param PDO $db
     * @param string $database
     * @param array $options
     * @return string
     */
    public function getFunctions(PDO $db, string $database, array $options): string;

    /**
     * Devuelve los procedimientos de la base de datos.
     * 
     * @param PDO $db
     * @param string $database
     * @param array $options
     * @return string
     */
    public function getProcedures(PDO $db, string $database, array $options): string;

    /**
     * Determina si una tabla es una vista.
     * 
     * @param PDO $db
     * @param string $table
     * @return bool
     */
    public function isView(PDO $db, string $table): bool;

    /**
     * Devuelve una "tabla de mentira" para representar una vista en la primera fase.
     * 
     * @param PDO $db
     * @param string $table
     * @return string
     */
    public function getTableFakeView(PDO $db, string $table): string;

    /**
     * Devuelve los disparadores (triggers) de una tabla.
     * 
     * @param PDO $db
     * @param string $table
     * @param array $options
     * @return string
     */
    public function getTableTriggers(PDO $db, string $table, array $options): string;

    /**
     * Devuelve el pie de la exportación.
     * 
     * @return string
     */
    public function getFooter(): string;
}
