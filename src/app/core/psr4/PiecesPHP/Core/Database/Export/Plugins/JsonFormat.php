<?php

namespace PiecesPHP\Core\Database\Export\Plugins;

use PiecesPHP\Core\Database\Export\Interfaces\FormatPluginInterface;
use PiecesPHP\Core\Database\Database;
use PDO;

/**
 * Class JsonFormat
 * 
 * Plugin de formato para exportar datos en formato JSON.
 * Ideal para interoperabilidad con otros sistemas y APIs.
 */
class JsonFormat implements FormatPluginInterface
{
    /** @var bool Indica si ya se ha escrito alguna tabla para añadir comas */
    protected bool $firstTable = true;

    /**
     * @inheritDoc
     */
    public function getHeader(Database $db, string $database, string $charset): string
    {
        // Abrir el objeto principal
        return "{\n";
    }

    /**
     * @inheritDoc
     */
    public function getFooter(): string
    {
        // Cerrar el objeto principal
        return "\n}\n";
    }

    /**
     * @inheritDoc
     */
    public function getTableStructure(Database $db, string $table, array $options): string
    {
        if ($this->isView($db, $table)) {
            return "";
        }
        // Para JSON, no exportamos la estructura SQL, pero podríamos imprimir el nombre de la tabla
        $prefix = $this->firstTable ? "" : ",\n";
        $this->firstTable = false;
        
        return $prefix . "  \"" . addcslashes($table, "\r\n\"\\") . "\": [\n";
    }

    /**
     * @inheritDoc
     */
    public function getTableData(Database $db, string $table, array $options, ?callable $writeCallback = null): ?string
    {
        $where = isset($options['where'][$table]) ? " WHERE " . $options['where'][$table] : "";
        $sql = "SELECT * FROM `" . str_replace("`", "``", $table) . "`" . $where;
        $stmt = $db->query($sql);

        $outputBuffer = "";
        $firstRow = true;
        $transforms = $options['transformations'][$table] ?? [];
        $useHexBlob = $options['hex_blob'] ?? true;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            
            // Aplicar Transformaciones (GDPR / Masking)
            foreach ($transforms as $col => $callback) {
                if (array_key_exists($col, $row)) {
                    $row[$col] = $callback($row[$col], $col);
                }
            }

            // Manejo de Binarios para evitar caracteres inválidos en JSON
            if ($useHexBlob) {
                foreach ($row as $col => &$val) {
                    if (is_string($val) && !mb_check_encoding($val, 'UTF-8')) {
                        $val = "0x" . bin2hex($val);
                    }
                }
            }

            $line = ($firstRow ? "" : ",\n") . "    " . json_encode($row, JSON_UNESCAPED_UNICODE);
            $firstRow = false;

            if ($writeCallback) {
                $writeCallback($line);
            } else {
                $outputBuffer .= $line;
            }
        }

        $footer = "\n  ]";
        if ($writeCallback) {
            $writeCallback($footer);
            return null;
        }

        return $outputBuffer . $footer;
    }

    /**
     * @inheritDoc
     */
    public function getFunctions(Database $db, string $database, array $options): string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    public function getProcedures(Database $db, string $database, array $options): string
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    public function isView(Database $db, string $table): bool
    {
        $stmt = $db->prepare("SHOW TABLE STATUS LIKE ?");
        $stmt->execute([$table]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return empty($row['Engine']) && !empty($row['Comment']) && strpos(mb_strtolower($row['Comment']), 'view') !== false;
    }

    /**
     * @inheritDoc
     */
    public function getTableFakeView(Database $db, string $table): string
    {
        // En JSON las vistas son fuentes de datos normales. 
        // Las exportamos completas aquí para aprovechar el flujo de 1 sola fase real para datos.
        $output = $this->getTableStructure($db, $table, []); // Esto devolvería "" si isView es true, así que forzamos:
        
        $prefix = $this->firstTable ? "" : ",\n";
        $this->firstTable = false;
        $output = $prefix . "  \"" . addcslashes($table, "\r\n\"\\") . "\": [\n";

        $data = $this->getTableData($db, $table, []);
        
        return $output . $data;
    }

    /**
     * @inheritDoc
     */
    public function getTableTriggers(Database $db, string $table, array $options): string
    {
        return "";
    }
}
