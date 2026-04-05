<?php

namespace PiecesPHP\Core\Database\Export\Plugins;

use PiecesPHP\Core\Database\Export\Interfaces\FormatPluginInterface;
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
     * @inheritDoc
     */
    public function getHeader(PDO $db, string $database, string $charset): string
    {
        return "<?xml version=\"1.0\" encoding=\"$charset\"?>\n<database name=\"" . htmlspecialchars($database) . "\">\n";
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
    public function getTableStructure(PDO $db, string $table, array $options): string
    {
        if ($this->isView($db, $table)) {
            return "";
        }
        return "  <table name=\"" . htmlspecialchars($table) . "\">\n";
    }

    /**
     * @inheritDoc
     */
    public function getTableData(PDO $db, string $table, array $options, ?callable $writeCallback = null): ?string
    {
        $stmt = $db->query("SELECT * FROM `" . str_replace("`", "``", $table) . "`");
        $outputBuffer = "";

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
        $output = "  <table name=\"" . htmlspecialchars($table) . "\">\n";
        $output .= $this->getTableData($db, $table, []);
        return $output;
    }

    /**
     * @inheritDoc
     */
    public function getTableTriggers(PDO $db, string $table, array $options): string
    {
        return "";
    }
}
