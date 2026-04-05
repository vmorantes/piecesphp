<?php

/**
 * @package PiecesPHP
 * @author  Vicsen Morantes <vicsen.morantes@gmail.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://github.com/vicsen/piecesphp
 */

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\Export\Enums\DataStyle;
use PiecesPHP\Core\Database\Export\Enums\TableStyle;
use PiecesPHP\Core\Database\Export\Exporter;
use PiecesPHP\Core\Database\Export\Plugins\Bz2FileOutput;
use PiecesPHP\Core\Database\Export\Plugins\FileOutput;
use PiecesPHP\Core\Database\Export\Plugins\GzipFileOutput;
use PiecesPHP\Core\Database\Export\Plugins\JsonFormat;
use PiecesPHP\Core\Database\Export\Plugins\PhpFormat;
use PiecesPHP\Core\Database\Export\Plugins\SqlFormat;
use PiecesPHP\Core\Database\Export\Plugins\XmlFormat;
use PiecesPHP\Core\Database\Export\Plugins\ZipFileOutput;
use PiecesPHP\Terminal\CliActions;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/database-exporter';
$cliTaskDescription = 'Pruebas unitarias para el módulo DatabaseExporter';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    systemOutFormatted('[TEST:DatabaseExporter] Iniciando suite de pruebas unitarias...', ['color' => '33']);
    systemOutFormatted('');

    try {
        // 1. Preparar PDO
        $db = (new BaseModel())->getDatabase();
        $databaseName = $db->getDatabaseName();

        // 2. Instanciar el Exporter
        $exporter = new Exporter($db, $databaseName);

        // 3. Configuración de casos
        $baseOptions = [
            'table_style' => TableStyle::DROP_CREATE,
            'data_style' => DataStyle::TRUNCATE_INSERT,
            'auto_increment' => false,
            'triggers' => true,
            'routines' => true,
            'drop_if_exists_on_functions' => true,
            'create_if_not_exists' => true,
        ];

        $formatCases = [
            [
                'name' => 'SQL',
                'format' => new SqlFormat(),
                'extension' => 'sql',
            ],
            [
                'name' => 'JSON',
                'format' => new JsonFormat(),
                'extension' => 'json',
            ],
            [
                'name' => 'PHP',
                'format' => new PhpFormat(),
                'extension' => 'php',
            ],
            [
                'name' => 'XML',
                'format' => new XmlFormat(),
                'extension' => 'xml',
            ],
        ];

        $outputCases = [
            [
                'name' => 'Archivo Plano',
                'plugin' => new FileOutput(),
            ],
            [
                'name' => 'Gzip',
                'plugin' => new GzipFileOutput(),
            ],
            [
                'name' => 'Bz2',
                'plugin' => new Bz2FileOutput(),
            ],
            [
                'name' => 'Zip',
                'plugin' => new ZipFileOutput(),
            ],
        ];

        // 4. Ejecutar Exportación
        $tables = $exporter->getTables();
        $outputDir = basepath('tmp/database-exporter-tests');

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $checkResult = function (bool $condition, string $name) {
            $status = $condition ? '[PASÓ]' : '[FALLÓ]';
            $color = $condition ? '32' : '31';
            systemOutFormatted("   $status $name", ['color' => $color]);
            return $condition;
        };

        systemOutFormatted("   Base de datos: $databaseName");
        systemOutFormatted("   Tablas encontradas: " . count($tables));
        systemOutFormatted('');

        $totalTests = count($formatCases) * count($outputCases);
        $currentTest = 1;

        foreach ($formatCases as $formatCase) {
            $exporter->setFormatPlugin($formatCase['format']);

            foreach ($outputCases as $outputCase) {
                $testName = "{$formatCase['name']} via {$outputCase['name']}";
                systemOutFormatted("[$currentTest/$totalTests] Probando: $testName...");

                $filename = "test_export_{$formatCase['name']}_" . str_replace(' ', '_', $outputCase['name']);
                $filename = strtolower($filename) . '.' . $formatCase['extension'];
                $fullPath = append_to_path_system($outputDir, $filename);

                $options = array_merge($baseOptions, [
                    'filename' => $fullPath,
                    'tables' => $tables,
                ]);

                $exporter->setOutputPlugin($outputCase['plugin']);
                $exporter->export($options);

                $generatedFile = $outputCase['plugin']->getFilename();
                $exists = file_exists($generatedFile);
                $hasSize = $exists ? filesize($generatedFile) > 0 : false;

                $checkResult($exists && $hasSize, "Generación de archivo: $generatedFile");

                if ($exists) {
                    systemOutFormatted("      Ruta: $generatedFile");
                    systemOutFormatted("      Tamaño: " . filesize($generatedFile) . " bytes");
                }

                $currentTest++;
                systemOutFormatted('');
            }
        }

        systemOutFormatted('[TEST:DatabaseExporter] Suite finalizada.', ['color' => '32']);
        systemOutFormatted('Los archivos generados se encuentran en: ' . $outputDir);

    } catch (Exception $e) {
        systemOutFormatted("ERROR: " . $e->getMessage(), ['color' => '31']);
    }

})->setDescription($cliTaskDescription)->register();
