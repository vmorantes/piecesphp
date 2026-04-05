<?php

namespace PiecesPHP\Core\Database\Export;

use PDO;
use PiecesPHP\Core\Database\Export\Interfaces\ExporterInterface;
use PiecesPHP\Core\Database\Export\Interfaces\FormatPluginInterface;
use PiecesPHP\Core\Database\Export\Interfaces\OutputPluginInterface;
use PiecesPHP\Core\Database\Export\Enums\TableStyle;
use PiecesPHP\Core\Database\Export\Enums\DataStyle;
use Exception;

/**
 * Class Exporter
 * 
 * Clase principal que coordina la exportación de la base de datos.
 * Utiliza un enfoque de "Estrategia" para el formato y la salida.
 */
class Exporter implements ExporterInterface
{
    /** @var FormatPluginInterface|null Plugin de formato responsable de generar el contenido */
    protected ?FormatPluginInterface $formatPlugin = null;
    /** @var OutputPluginInterface|null Plugin de salida responsable de persistir el contenido */
    protected ?OutputPluginInterface $outputPlugin = null;
    /** @var string[] Lista de errores ocurridos durante la exportación */
    protected array $errors = [];

    /**
     * Constructor de la clase Exporter.
     * 
     * @param PDO $db Instancia de PDO ya configurada y conectada.
     * @param string $database Nombre de la base de datos a exportar.
     * @param string $charset Juego de caracteres para el volcado (default: utf8mb4).
     */
    public function __construct(
        protected PDO $db,
        protected string $database,
        protected string $charset = 'utf8mb4'
    ) {
        // Asegurar que PDO lance excepciones para un manejo uniforme
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Establece el plugin de formato.
     * 
     * @param FormatPluginInterface $plugin
     * @return self
     */
    public function setFormatPlugin(FormatPluginInterface $plugin): self
    {
        $this->formatPlugin = $plugin;
        return $this;
    }

    /**
     * Establece el plugin de salida.
     * 
     * @param OutputPluginInterface $plugin
     * @return self
     */
    public function setOutputPlugin(OutputPluginInterface $plugin): self
    {
        $this->outputPlugin = $plugin;
        return $this;
    }

    /**
     * Realiza la exportación de la base de datos según las opciones dadas.
     * 
     * El flujo sigue 3 fases principales para garantizar la integridad de las dependencias:
     * 1. Rutinas (Funciones/Procesos) primero.
     * 2. Mezcla alfabética de Tablas y Vistas Temporales.
     * 3. Recreación de Vistas Reales al final.
     * 
     * @param array{
     *     tables?: string[],
     *     table_style?: TableStyle|string,
     *     data_style?: DataStyle|string,
     *     auto_increment?: bool,
     *     triggers?: bool,
     *     routines?: bool,
     *     remove_definer?: bool,
     *     drop_if_exists_on_functions?: bool,
     *     create_if_not_exists?: bool,
     *     include_data?: bool,
     *     include_views?: bool,
     *     single_transaction?: bool,
     *     filename?: string
     * } $options Configuración detallada de la exportación.
     * @return mixed El resultado depende del plugin de salida configurado.
     */
    public function export(array $options = []): mixed
    {
        if (!$this->formatPlugin || !$this->outputPlugin) {
            throw new Exception("Se deben configurar los plugins de formato y salida antes de exportar.");
        }

        $defaults = [
            'tables' => [],
            'table_style' => TableStyle::DROP_CREATE->value,
            'data_style' => DataStyle::INSERT->value,
            'auto_increment' => true,
            'triggers' => true,
            'routines' => true,
            'remove_definer' => true,
            'drop_if_exists_on_functions' => false,
            'create_if_not_exists' => true,
            'include_data' => true,
            'include_views' => true,
            'single_transaction' => true,
        ];

        $opts = array_merge($defaults, $options);

        // Convertir Enums a su valor string si es necesario
        if ($opts['table_style'] instanceof TableStyle) {
            $opts['table_style'] = $opts['table_style']->value;
        }
        if ($opts['data_style'] instanceof DataStyle) {
            $opts['data_style'] = $opts['data_style']->value;
        }

        if (empty($opts['tables'])) {
            $opts['tables'] = $this->getTables();
        }

        // Filtrar vistas si no se deben incluir
        if (!$opts['include_views']) {
            $opts['tables'] = array_filter($opts['tables'], function ($table) {
                return !$this->formatPlugin->isView($this->db, $table);
            });
            $opts['tables'] = array_values($opts['tables']);
        }

        try {

            if ($opts['single_transaction']) {
                $this->db->exec("SET TRANSACTION ISOLATION LEVEL REPEATABLE READ");
                $this->db->beginTransaction();
            }

            if (!$this->outputPlugin->init($opts)) {
                return false;
            }

            // Ordenar todos los objetos alfabéticamente (orden natural e insensible a mayúsculas)
            $allObjects = $opts['tables'];
            natcasesort($allObjects);
            $allObjects = array_values($allObjects); // Reindexar

            // Identificar cuáles son tablas y cuáles son vistas
            $views = [];
            foreach ($allObjects as $obj) {
                if ($this->formatPlugin->isView($this->db, $obj)) {
                    $views[] = $obj;
                }
            }

            // 1. Encabezado
            $this->outputPlugin->write($this->formatPlugin->getHeader($this->db, $this->database, $this->charset));

            // 2. Rutinas (Funciones y Procedimientos)
            if ($opts['routines']) {
                $this->outputPlugin->write($this->formatPlugin->getFunctions($this->db, $this->database, $opts));
                $this->outputPlugin->write($this->formatPlugin->getProcedures($this->db, $this->database, $opts));
            }

            // 3. Mezcla Alfabética (Tablas y Vistas Temporales)
            foreach ($allObjects as $obj) {
                $isObjectView = $this->formatPlugin->isView($this->db, $obj);

                if ($isObjectView) {
                    // Vista como Tabla Temporal (para dependencias)
                    $this->outputPlugin->write($this->formatPlugin->getTableFakeView($this->db, $obj));
                } else {
                    // Tabla Real
                    $this->outputPlugin->write($this->formatPlugin->getTableStructure($this->db, $obj, $opts));

                    // Disparadores
                    if ($opts['triggers']) {
                        $this->outputPlugin->write($this->formatPlugin->getTableTriggers($this->db, $obj, $opts));
                    }

                    // Datos (Bulk Insert)
                    if ($opts['include_data']) {
                        $this->formatPlugin->getTableData($this->db, $obj, $opts, function ($chunk) {
                            $this->outputPlugin->write($chunk);
                        });
                    }
                }
            }

            // 4. Vistas Reales (Al final de todo)
            foreach ($views as $view) {
                $this->outputPlugin->write($this->formatPlugin->getTableStructure($this->db, $view, $opts));
            }

            // 5. Pie
            $this->outputPlugin->write($this->formatPlugin->getFooter());

            if ($opts['single_transaction']) {
                $this->db->commit();
            }

            return $this->outputPlugin->finalize();

        } catch (Exception $e) {
            if (isset($opts) && $opts['single_transaction'] && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * Obtiene la lista completa de tablas y vistas de la base de datos.
     * 
     * @return string[]
     */
    public function getTables(): array
    {
        $tables = [];
        try {
            $stmt = $this->db->query("SHOW TABLES");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return $tables;
    }

    /**
     * Devuelve los errores acumulados durante la ejecución.
     * 
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Escapa identificadores para compatibilidad (usado internamente).
     * 
     * @param string $idf
     * @return string
     */
    public function idf_escape(string $idf): string
    {
        return "`" . str_replace("`", "``", $idf) . "`";
    }
}
