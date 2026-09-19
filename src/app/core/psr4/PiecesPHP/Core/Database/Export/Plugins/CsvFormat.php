<?php

namespace PiecesPHP\Core\Database\Export\Plugins;

use PiecesPHP\Core\Database\Export\Interfaces\FormatPluginInterface;
use PDO;

/**
 * Class CsvFormat
 * 
 * Plugin de formato para exportar datos en formato CSV (Valores separados por comas).
 * Soporta transformaciones de datos, filtros WHERE y detección de binarios.
 */
class CsvFormat implements FormatPluginInterface
{
    /** @var string Delimitador de columnas */
    protected string $delimiter = ",";
    /** @var string Encerramiento de campos */
    protected string $enclosure = '"';
    /** @var string Carácter de escape */
    protected string $escape = "\\";

    /**
     * @inheritDoc
     */
    public function getHeader(PDO $db, string $database, string $charset): string
    {
        // El CSV no tiene un encabezado global de archivo en este contexto
        return "";
    }

    /**
     * @inheritDoc
     */
    public function getFooter(): string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    public function getTableStructure(PDO $db, string $table, array $options): string
    {
        // En CSV no exportamos la sentencia CREATE TABLE.
        // Pero podemos exportar los nombres de las columnas como primera fila si se desea.
        if ($this->isView($db, $table)) {
            return "";
        }

        $stmt = $db->query("SELECT * FROM `" . str_replace("`", "``", $table) . "` LIMIT 0");
        $cols = [];
        for ($i = 0; $i < $stmt->columnCount(); $i++) {
            $meta = $stmt->getColumnMeta($i);
            $cols[] = $meta['name'];
        }

        return $this->arrayToCsvLine($cols) . "\n";
    }

    /**
     * @inheritDoc
     */
    public function getTableData(PDO $db, string $table, array $options, ?callable $writeCallback = null): ?string
    {
        $where = isset($options['where'][$table]) ? " WHERE " . $options['where'][$table] : "";
        $sql = "SELECT * FROM `" . str_replace("`", "``", $table) . "`" . $where;
        $stmt = $db->query($sql);
        
        $outputBuffer = "";
        $transforms = $options['transformations'][$table] ?? [];
        $useHexBlob = $options['hex_blob'] ?? true;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            
            // Aplicar Transformaciones (GDPR / Masking)
            foreach ($transforms as $col => $callback) {
                if (array_key_exists($col, $row)) {
                    $row[$col] = $callback($row[$col], $col);
                }
            }

            // Manejo de Binarios (si no han sido transformados)
            if ($useHexBlob) {
                foreach ($row as $col => &$val) {
                    if (is_string($val) && !mb_check_encoding($val, 'UTF-8')) {
                        // Es probable que sea binario si no es UTF-8 válido
                        $val = "0x" . bin2hex($val);
                    }
                }
            }

            $line = $this->arrayToCsvLine(array_values($row)) . "\n";

            if ($writeCallback) {
                $writeCallback($line);
            } else {
                $outputBuffer .= $line;
            }
        }

        return $writeCallback ? null : $outputBuffer;
    }

    /**
     * @inheritDoc
     */
    public function getFunctions(PDO $db, string $database, array $options): string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    public function getProcedures(PDO $db, string $database, array $options): string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    public function isView(PDO $db, string $table): bool
    {
        $stmt = $db->prepare("SHOW TABLE STATUS LIKE ?");
        $stmt->execute([$table]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return empty($row['Engine']) && !empty($row['Comment']) && strpos(mb_strtolower($row['Comment']), 'view') !== false;
    }

    /**
     * @inheritDoc
     */
    public function getTableFakeView(PDO $db, string $table): string
    {
        // En CSV las vistas se exportan como datos directamente
        return $this->getTableStructure($db, $table, []) . $this->getTableData($db, $table, []);
    }

    /**
     * @inheritDoc
     */
    public function getTableTriggers(PDO $db, string $table, array $options): string
    {
        return "";
    }

    /**
     * Convierte un array en una línea CSV válida.
     * 
     * @param array $fields
     * @return string
     */
    protected function arrayToCsvLine(array $fields): string
    {
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $fields, $this->delimiter, $this->enclosure, $this->escape);
        rewind($fp);
        $line = rtrim(stream_get_contents($fp), "\n");
        fclose($fp);
        return $line;
    }
}
