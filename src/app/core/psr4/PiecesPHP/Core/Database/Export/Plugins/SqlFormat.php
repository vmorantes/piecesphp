<?php

namespace PiecesPHP\Core\Database\Export\Plugins;

use PDO;
use PiecesPHP\Core\Database\Export\Interfaces\FormatPluginInterface;
use PiecesPHP\Core\Database\Export\Enums\TableStyle;
use PiecesPHP\Core\Database\Export\Enums\DataStyle;
use Exception;

/**
 * Class SqlFormat
 * 
 * Plugin de formato encargado de generar sentencias SQL compatibles con MySQL/MariaDB.
 * Soporta remoción de DEFINER, Bulk Inserts y diferentes estilos de creación y datos.
 * 
 * Opciones soportadas:
 * - table_style: Enums\TableStyle (DROP_CREATE, CREATE).
 * - data_style: Enums\DataStyle (INSERT, REPLACE, TRUNCATE_INSERT).
 * - auto_increment: bool (incluir o no AUTO_INCREMENT en CREATE TABLE).
 * - remove_definer: bool (eliminar DEFINER de todos los objetos).
 * - create_if_not_exists: bool (usar CREATE TABLE IF NOT EXISTS).
 * - drop_if_exists_on_functions: bool (usar DROP FUNCTION/PROCEDURE IF EXISTS).
 */
class SqlFormat implements FormatPluginInterface
{
    /**
     * @inheritDoc
     */
    public function getHeader(PDO $db, string $database, string $charset): string
    {
        $server_info = $db->getAttribute(PDO::ATTR_SERVER_INFO);
        $header = "-- PiecesPHP SQL Dump\n";
        $header .= "-- Generation Time: " . date('Y-m-d H:i:s') . "\n";
        $header .= "-- Server: " . $server_info . "\n\n";

        $header .= "SET NAMES " . $charset . ";\n";
        $header .= "SET time_zone = '+00:00';\n";
        $header .= "SET foreign_key_checks = 0;\n";
        $header .= "SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';\n\n";

        return $header;
    }

    /**
     * @inheritDoc
     */
    public function getTableStructure(PDO $db, string $table, array $options): string
    {
        $is_view = $this->isView($db, $table);

        $sql = ($is_view ? "SHOW CREATE VIEW " : "SHOW CREATE TABLE ") . $this->idfEscape($table);
        $stmt = $db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $create = $row[1] ?? '';

        if (!$is_view && !($options['auto_increment'] ?? true)) {
            $create = preg_replace('~ AUTO_INCREMENT=\d+~', '', $create);
        }

        if (!empty($options['remove_definer'])) {
            $create = preg_replace('~ DEFINER=`[^`]+`@`[^`]+`~i', '', $create);
            if ($is_view) {
                // También eliminar SQL SECURITY DEFINER en vistas si se pide quitar definer
                $create = preg_replace('~ SQL SECURITY DEFINER~i', '', $create);
            }
        }

        $output = '';
        $tableStyle = $options['table_style'] ?? TableStyle::DROP_CREATE->value;

        if ($is_view) {
            $output .= "DROP TABLE IF EXISTS " . $this->idfEscape($table) . ";\n";
            $output .= "DROP VIEW IF EXISTS " . $this->idfEscape($table) . ";\n";
        } elseif ($tableStyle === TableStyle::DROP_CREATE->value) {
            $output .= "DROP TABLE IF EXISTS " . $this->idfEscape($table) . ";\n";
        }

        if (!empty($options['create_if_not_exists']) && !$is_view) {
            $create = preg_replace('~^CREATE TABLE ~i', 'CREATE TABLE IF NOT EXISTS ', $create);
        }

        $output .= "$create;\n\n";

        return $output;
    }

    /**
     * @inheritDoc
     */
    public function getTableData(PDO $db, string $table, array $options, ?callable $writeCallback = null): ?string
    {
        if ($this->isView($db, $table)) {
            return "";
        }

        $outputBuffer = "";
        $rowCount = 0;
        $insertHeader = "";
        
        $dataStyle = $options['data_style'] ?? DataStyle::INSERT->value;
        $verb = ($dataStyle === DataStyle::REPLACE->value) ? 'REPLACE' : 'INSERT';
        $where = isset($options['where'][$table]) ? " WHERE " . $options['where'][$table] : "";
        $transforms = $options['transformations'][$table] ?? [];
        $useHexBlob = $options['hex_blob'] ?? true;
        $fields = $this->getFields($db, $table);

        $stmt = $db->query("SELECT * FROM " . $this->idfEscape($table) . $where);

        if ($dataStyle === DataStyle::TRUNCATE_INSERT->value) {
            $truncate = "TRUNCATE TABLE " . $this->idfEscape($table) . ";\n";
            if ($writeCallback) {
                $writeCallback($truncate);
            } else {
                $outputBuffer .= $truncate;
            }
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        while ($row) {
            if ($rowCount === 0) {
                $insertHeader = "$verb INTO " . $this->idfEscape($table) . " (" . implode(', ', array_map([$this, 'idfEscape'], array_keys($row))) . ") VALUES\n";
                if ($writeCallback) {
                    $writeCallback($insertHeader);
                } else {
                    $outputBuffer .= $insertHeader;
                }
            }

            $values = [];
            foreach ($row as $key => $val) {

                // Aplicar Transformaciones (GDPR / Masking)
                if (isset($transforms[$key])) {
                    $val = $transforms[$key]($val, $key);
                }

                if ($val === null) {
                    $values[] = "NULL";
                } elseif ($useHexBlob && $this->isBinaryType($fields[$key] ?? '')) {
                    // Exportar como literal hexadecimal (0x...) para integridad total en binarios
                    $values[] = "0x" . bin2hex($val);
                } elseif ($this->isNumericType($fields[$key] ?? '')) {
                    $values[] = $val;
                } else {
                    $values[] = $db->quote($val);
                }
            }

            $rowLine = "(" . implode(', ', $values) . ")";
            
            // Ver si hay una siguiente fila
            $nextRow = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $lineEnd = ($nextRow ? ",\n" : ";\n\n");
            $fullLine = $rowLine . $lineEnd;

            if ($writeCallback) {
                $writeCallback($fullLine);
            } else {
                $outputBuffer .= $fullLine;
            }
            
            $rowCount++;
            $row = $nextRow;
        }

        return $writeCallback ? null : $outputBuffer;
    }

    /**
     * @inheritDoc
     */
    public function getTableFakeView(PDO $db, string $table): string
    {
        $output = "DROP VIEW IF EXISTS " . $this->idfEscape($table) . ";\n";
        $output .= "CREATE TABLE " . $this->idfEscape($table) . " (\n";
        
        $sql = "SHOW COLUMNS FROM " . $this->idfEscape($table);
        $stmt = $db->query($sql);
        $cols = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cols[] = "  " . $this->idfEscape($row['Field']) . " " . $row['Type'];
        }
        
        $output .= implode(",\n", $cols) . "\n);\n\n";
        
        return $output;
    }

    /**
     * @inheritDoc
     */
    public function getFunctions(PDO $db, string $database, array $options): string
    {
        $output = "";
        try {
            $stmt = $db->query("SHOW FUNCTION STATUS WHERE Db = " . $db->quote($database));
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $createStmt = $db->query("SHOW CREATE FUNCTION " . $this->idfEscape($row['Name']));
                $create = $createStmt->fetch(PDO::FETCH_ASSOC);
                if ($create) {
                    $output .= "\n\nDELIMITER ;;\n\n";
                    if (!empty($options['drop_if_exists_on_functions'])) {
                        $output .= "DROP FUNCTION IF EXISTS " . $this->idfEscape($row['Name']) . ";;\n";
                    }
                    $statement = $create['Create Function'];
                    if (!empty($options['remove_definer'])) {
                        $statement = preg_replace('~ DEFINER=`[^`]+`@`[^`]+`~i', '', $statement);
                    }
                    $statement = "\n" . $this->wrapRoutineParameters($statement);
                    $output .= $statement . ";;\n";
                    $output .= "DELIMITER ;\n\n";
                }
            }
        } catch (Exception $e) {}
        return $output;
    }

    /**
     * @inheritDoc
     */
    public function getProcedures(PDO $db, string $database, array $options): string
    {
        $output = "";
        try {
            $stmt = $db->query("SHOW PROCEDURE STATUS WHERE Db = " . $db->quote($database));
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $createStmt = $db->query("SHOW CREATE PROCEDURE " . $this->idfEscape($row['Name']));
                $create = $createStmt->fetch(PDO::FETCH_ASSOC);
                if ($create) {
                    $output .= "\nDELIMITER ;;\n\n";
                    if (!empty($options['drop_if_exists_on_functions'])) {
                        $output .= "DROP PROCEDURE IF EXISTS " . $this->idfEscape($row['Name']) . ";;\n";
                    }
                    $statement = $create['Create Procedure'];
                    if (!empty($options['remove_definer'])) {
                        $statement = preg_replace('~ DEFINER=`[^`]+`@`[^`]+`~i', '', $statement);
                    }
                    $statement = "\n" . $this->wrapRoutineParameters($statement);
                    $output .= $statement . ";;\n";
                    $output .= "DELIMITER ;\n\n";
                }
            }
        } catch (Exception $e) {}
        return $output;
    }

    /**
     * @inheritDoc
     */
    public function isView(PDO $db, string $table): bool
    {
        $status = $this->getTableStatus($db, $table);
        return empty($status['Engine']) && !empty($status['Comment']) && strpos(mb_strtolower($status['Comment']), 'view') !== false;
    }

    /**
     * @inheritDoc
     */
    public function getTableTriggers(PDO $db, string $table, array $options): string
    {
        $output = "";
        try {
            $stmt = $db->prepare("SHOW TRIGGERS LIKE ?");
            $stmt->execute([$table]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $createStmt = $db->query("SHOW CREATE TRIGGER " . $this->idfEscape($row['Trigger']));
                $create = $createStmt->fetch(PDO::FETCH_ASSOC);
                if ($create) {
                    $output .= "\nDELIMITER ;;\n\n";
                    $statement = (isset($create['SQL Original Statement']) ? $create['SQL Original Statement'] : $create['Create Trigger']);
                    if (!empty($options['remove_definer'])) {
                        $statement = preg_replace('~ DEFINER=`[^`]+`@`[^`]+`~i', '', $statement);
                    }
                    $output .= $statement . ";;\n";
                    $output .= "DELIMITER ;\n\n";
                }
            }
        } catch (Exception $e) {}
        return $output;
    }

    /**
     * @inheritDoc
     */
    public function getFooter(): string
    {
        return "\n-- Exportación completada.\n";
    }

    /**
     * Obtiene el estado de una tabla para determinar metadatos (como si es una vista).
     * 
     * @param PDO $db
     * @param string $table
     * @return array
     */
    protected function getTableStatus(PDO $db, string $table): array
    {
        $stmt = $db->prepare("SHOW TABLE STATUS LIKE ?");
        $stmt->execute([$table]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Obtiene los nombres y tipos de los campos de una tabla.
     * 
     * @param PDO $db
     * @param string $table
     * @return array
     */
    protected function getFields(PDO $db, string $table): array
    {
        $fields = [];
        $stmt = $db->query("SHOW COLUMNS FROM " . $this->idfEscape($table));
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fields[$row['Field']] = $row['Type'];
        }
        return $fields;
    }

    /**
     * Determina si un tipo de dato es numérico para evitar comillas en el SQL.
     * 
     * @param string $type
     * @return bool
     */
    protected function isNumericType(string $type): bool
    {
        return (bool) preg_match('~int|decimal|float|double|bit~i', $type);
    }

    /**
     * Determina si un tipo de dato es binario para exportar como Hex.
     * 
     * @param string $type
     * @return bool
     */
    protected function isBinaryType(string $type): bool
    {
        return (bool) preg_match('~binary|blob|varbinary~i', $type);
    }

    /**
     * Escapa un identificador (tabla, columna, etc.) con comillas invertidas.
     * 
     * @param string $idf
     * @return string
     */
    protected function idfEscape(string $idf): string
    {
        return "`" . str_replace("`", "``", $idf) . "`";
    }

    /**
     * Envuelve los parámetros de una rutina en acentos graves para evitar conflictos con palabras reservadas.
     * 
     * @param string $sql
     * @return string
     */
    protected function wrapRoutineParameters(string $sql): string
    {
        $sql = preg_replace('~^(CREATE\s+(?:PROCEDURE|FUNCTION)\s+)([^`(\s]+)(\s*\()~i', '$1`$2`$3', $sql);
        return preg_replace_callback('~\((.*)\)\s+(?:RETURNS|BEGIN|LANGUAGE)~isU', function ($m) {
            $params_list = $m[1];
            if (trim($params_list) === '') return $m[0];
            $parts = preg_split('~,\s*~', $params_list);
            foreach ($parts as &$part) {
                $part = trim($part);
                if ($part === '') continue;
                if (preg_match('~^(IN|OUT|INOUT)\s+(`?[^`\s]+`?)\s+(.*)$~i', $part, $pm)) {
                    $mod = $pm[1];
                    $name = trim($pm[2], '`');
                    $rest = $pm[3];
                    $part = "$mod `$name` $rest";
                } elseif (preg_match('~^(`?[^`\s]+`?)\s+(.*)$~i', $part, $pm)) {
                    $name = trim($pm[1], '`');
                    $rest = $pm[2];
                    $part = "`$name` $rest";
                }
            }
            $new_params = implode(', ', $parts);
            return str_replace($m[1], $new_params, $m[0]);
        }, $sql);
    }
}
