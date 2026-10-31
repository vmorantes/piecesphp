<?php
namespace PiecesPHP\Core\Database\Export\Plugins;

use PDO;
use PiecesPHP\Core\Database\Database;
use PiecesPHP\Core\Database\Export\Interfaces\FormatPluginInterface;

/**
 * Formato de array nativo de PHP, para archivos de configuración o seeders.
 */
class PhpFormat implements FormatPluginInterface
{
    /** @var array Datos recolectados (Este formato no es ideal para streaming por var_export) */
    protected array $data = [];

    /**
     * @inheritDoc
     */
    public function getHeader(Database $db, string $database, string $charset): string
    {
        return "<?php\n\nreturn [\n";
    }

    /**
     * @inheritDoc
     */
    public function getFooter(): string
    {
        return "];\n";
    }

    /**
     * @inheritDoc
     */
    public function getTableStructure(Database $db, string $table, array $options): string
    {
        if ($this->isView($db, $table)) {
            return "";
        }
        return "  '" . addcslashes($table, "'\\") . "' => [\n";
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

            // var_export genera la representación PHP
            $line = "    " . str_replace("\n", "\n    ", var_export($row, true)) . ",\n";

            if ($writeCallback) {
                $writeCallback($line);
            } else {
                $outputBuffer .= $line;
            }
        }

        $footer = "  ],\n";
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
        $output = "  '" . addcslashes($table, "'\\") . "' => [\n";
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
