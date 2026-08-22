<?php

namespace PiecesPHP\Core\Database\Export\Plugins;

use PiecesPHP\Core\Database\Export\Interfaces\FormatPluginInterface;
use PiecesPHP\Core\Database\Database;
use PDO;

/**
 * Class XmlFormat
 * 
 * Plugin de formato para exportar datos en formato XML.
 * Estructura: <database><table name=""><row><column name="">...
 */
class XmlFormat implements FormatPluginInterface
{
    /**
     * Nombres de charset de MySQL traducidos al nombre IANA que exige XML.
     *
     * `utf8mb4` NO es un encoding XML: un parser estándar rechaza el documento ENTERO por
     * la primera línea, por muy bien formado que esté el resto. Lo que no esté aquí pasa
     * tal cual, porque inventar una traducción sería peor que no traducir.
     *
     * @var array<string,string>
     */
    private const XML_ENCODINGS = [
        'utf8' => 'UTF-8',
        'utf8mb3' => 'UTF-8',
        'utf8mb4' => 'UTF-8',
        'latin1' => 'ISO-8859-1',
        'ascii' => 'US-ASCII',
    ];

    /**
     * @inheritDoc
     */
    public function getHeader(Database $db, string $database, string $charset): string
    {
        $encoding = self::XML_ENCODINGS[mb_strtolower($charset)] ?? $charset;

        return "<?xml version=\"1.0\" encoding=\"$encoding\"?>\n<database name=\"" . htmlspecialchars($database) . "\">\n";
    }

    /**
     * @inheritDoc
     */
    public function getFooter(): string
    {
        return "</database>\n";
    }

    /**
     * @inheritDoc
     */
    public function getTableStructure(Database $db, string $table, array $options): string
    {
        if ($this->isView($db, $table)) {
            return "";
        }
        return "  <table name=\"" . htmlspecialchars($table) . "\">\n";
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
        $transforms = $options['transformations'][$table] ?? [];
        $useHexBlob = $options['hex_blob'] ?? true;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            
            // Aplicar Transformaciones (GDPR / Masking)
            foreach ($transforms as $col => $callback) {
                if (array_key_exists($col, $row)) {
                    $row[$col] = $callback($row[$col], $col);
                }
            }

            // Manejo de Binarios
            if ($useHexBlob) {
                foreach ($row as $col => &$val) {
                    if (is_string($val) && !mb_check_encoding($val, 'UTF-8')) {
                        $val = "0x" . bin2hex($val);
                    }
                }
            }

            $line = "    <row>\n";
            foreach ($row as $key => $val) {
                $line .= "      <column name=\"" . htmlspecialchars($key) . "\"" . (isset($val) ? "" : " null=\"true\"") . ">" . htmlspecialchars($val ?? '') . "</column>\n";
            }
            $line .= "    </row>\n";

            if ($writeCallback) {
                $writeCallback($line);
            } else {
                $outputBuffer .= $line;
            }
        }

        $footer = "  </table>\n";
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
        $output = "  <table name=\"" . htmlspecialchars($table) . "\">\n";
        $output .= $this->getTableData($db, $table, []);
        return $output;
    }

    /**
     * @inheritDoc
     */
    public function getTableTriggers(Database $db, string $table, array $options): string
    {
        return "";
    }
}
