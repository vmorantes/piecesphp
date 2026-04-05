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
use PiecesPHP\Core\Database\Export\Plugins\CsvFormat;
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

    systemOutFormatted('[TEST:DatabaseExporter] Iniciando suite de pruebas unitarias unificada...', ['color' => '33']);
    systemOutFormatted('');

    $passed = 0;
    $failed = 0;

    try {
        // 1. Preparar Entorno
        $db = (new BaseModel())->getDatabase();
        $databaseName = $db->getDatabaseName();

        $testTable = 'pcs_unit_tests_core_database_exporter_v1';
        $seedPath = basepath('app/core/system-controllers/test-data/unit-tests/core/database-exporter/seed_data.php');

        if (!file_exists($seedPath)) {
            throw new Exception("No se encontró el archivo de datos fuente: $seedPath");
        }

        $seeder = require $seedPath;
        $seeder($db, $testTable);

        // 2. Instanciar el Exporter
        $exporter = new Exporter($db, $databaseName);

        // 3. Configuración de casos
        $baseOptions = [
            'table_style' => TableStyle::DROP_CREATE,
            'data_style' => DataStyle::TRUNCATE_INSERT,
            'auto_increment' => false,
            'triggers' => true,
            'routines' => true,
            'remove_definer' => true,
            'drop_if_exists_on_functions' => true,
            'create_if_not_exists' => true,
            'single_transaction' => true,
            'hex_blob' => true,
            'include_data' => true,
            'include_views' => true,
        ];

        // Filtros y Transformaciones Pro
        $advancedOptions = [
            'where' => [
                $testTable => 'username = "pedro"', // Solo Pedro (1 fila)
            ],
            'transformations' => [
                $testTable => [
                    'email' => function ($val) {
                        return "HIDDEN_EMAIL";
                    },
                    'secret_key' => function ($val) {
                        return "********";
                    },
                ],
            ],
        ];

        $formatCases = [
            ['name' => 'SQL', 'format' => new SqlFormat(), 'ext' => 'sql'],
            ['name' => 'JSON', 'format' => new JsonFormat(), 'ext' => 'json'],
            ['name' => 'PHP', 'format' => new PhpFormat(), 'ext' => 'php'],
            ['name' => 'XML', 'format' => new XmlFormat(), 'ext' => 'xml'],
            ['name' => 'CSV', 'format' => new CsvFormat(), 'ext' => 'csv'],
        ];

        $outputCases = [
            ['name' => 'Plano', 'plugin' => new FileOutput(), 'suffix' => ''],
            ['name' => 'Gzip', 'plugin' => new GzipFileOutput(), 'suffix' => '.gz'],
            ['name' => 'Bz2', 'plugin' => new Bz2FileOutput(), 'suffix' => '.bz2'],
            ['name' => 'Zip', 'plugin' => new ZipFileOutput(), 'suffix' => '.zip'],
        ];

        $specialVariants = [
            ['name' => 'Sin Datos', 'opts' => ['include_data' => false], 'suffix' => '_no_data'],
            ['name' => 'Sin Vistas', 'opts' => ['include_views' => false], 'suffix' => '_no_views'],
            ['name' => 'Transacción Desactivada', 'opts' => ['single_transaction' => false], 'suffix' => '_no_transaction'],
        ];

        // 4. Preparar Lista Única de Pruebas
        $allTests = [];

        // Matriz Formatos x Salidas
        foreach ($formatCases as $fc) {
            foreach ($outputCases as $oc) {
                $allTests[] = [
                    'label' => "{$fc['name']} (" . strtolower($oc['name']) . ")",
                    'format' => $fc['format'],
                    'output' => $oc['plugin'],
                    'filename' => "test_pro_{$fc['name']}_" . strtolower(str_replace(' ', '_', $oc['name'])) . ".{$fc['ext']}{$oc['suffix']}",
                    'options' => array_merge($baseOptions, $advancedOptions),
                    'validate' => ($oc['suffix'] === ''), // Solo validamos contenido en archivos planos
                ];
            }
        }

        // Variantes Especiales (siempre usando SQL plano para brevedad en validación)
        foreach ($specialVariants as $sv) {
            $allTests[] = [
                'label' => "Variante: {$sv['name']}",
                'format' => new SqlFormat(),
                'output' => new FileOutput(),
                'filename' => "test_variant{$sv['suffix']}.sql",
                'options' => array_merge($baseOptions, $advancedOptions, $sv['opts']),
                'validate' => true,
            ];
        }

        $totalCount = count($allTests);
        $outputDir = basepath('tmp/database-exporter-tests');

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $checkResult = function (bool $condition, string $name) use (&$passed, &$failed) {
            $status = $condition ? '[PASÓ]' : '[FALLÓ]';
            $color = $condition ? '32' : '31';
            systemOutFormatted("      $status $name", ['color' => $color]);
            if ($condition) {
                $passed++;
            } else {
                $failed++;
            }
            return $condition;
        };

        $tables = $exporter->getTables();

        systemOutFormatted("   Base de datos: $databaseName");
        systemOutFormatted("   Tablas encontradas: " . count($tables));
        systemOutFormatted('');

        // 5. Ejecutar Secuencia Unificada
        foreach ($allTests as $index => $test) {
            $num = $index + 1;
            systemOutFormatted("[$num/$totalCount] Probando: {$test['label']}...");

            $fullPath = append_to_path_system($outputDir, $test['filename']);
            $opts = $test['options'];
            $opts['filename'] = $fullPath;
            $opts['tables'] = $tables;

            $exporter->setFormatPlugin($test['format']);
            $exporter->setOutputPlugin($test['output']);
            $exporter->export($opts);

            $generatedFile = $test['output']->getFilename();
            $exists = file_exists($generatedFile);
            $hasSize = $exists ? filesize($generatedFile) > 0 : false;

            $contentValid = true;
            if ($test['validate'] && $exists) {
                $content = file_get_contents($generatedFile);
                $isSql = strpos($test['filename'], '.sql') !== false;

                // Validación de Filtro WHERE
                if (isset($opts['where'][$testTable]) && $opts['include_data']) {
                    if (strpos($content, 'pedro') === false || strpos($content, 'juan') !== false) {
                        $contentValid = false;
                        systemOutFormatted("      [X] Fallo: Datos filtrados incorrectamente.", ['color' => '31']);
                    }
                }

                // Validación de Transformaciones (Email Masking)
                if (isset($opts['transformations'][$testTable]) && $opts['include_data']) {
                    if (strpos($content, 'HIDDEN_EMAIL') === false) {
                        $contentValid = false;
                        systemOutFormatted("      [X] Fallo: Transformación GDPR no aplicada.", ['color' => '31']);
                    }
                }

                // Validación de include_data = false (No debe haber datos de la tabla de prueba)
                if ($opts['include_data'] === false && $isSql) {
                    if (strpos($content, 'INSERT INTO `' . $testTable . '`') !== false || strpos($content, 'pedro') !== false) {
                        $contentValid = false;
                        systemOutFormatted("      [X] Fallo: Se encontraron datos cuando include_data era false.", ['color' => '31']);
                    }
                }

                // Validación de Hex-Blob (Solo SQL y con datos)
                if ($isSql && $opts['hex_blob'] && $opts['include_data']) {
                    // Buscamos 0x seguido de caracteres hexadecimales (al menos 8 para seguridad)
                    if (!preg_match('~0x[0-9a-f]{8,}~i', $content)) {
                        $contentValid = false;
                        systemOutFormatted("      [X] Fallo: No se detectó literal hexadecimal para el BLOB.", ['color' => '31']);
                    }
                }
            }

            $checkResult($exists && $hasSize && $contentValid, "Resultado de {$test['label']}");
            systemOutFormatted('');
        }

        // 6. Balance Final
        systemOutFormatted('================================================================');
        systemOutFormatted('            BALANCE FINAL DE PRUEBAS UNITARIAS                  ');
        systemOutFormatted('================================================================');
        systemOutFormatted("   TOTAL:   $totalCount");
        systemOutFormatted("   PASADAS: $passed", ['color' => '32']);
        systemOutFormatted("   FALLIDAS: $failed", ['color' => $failed > 0 ? '31' : '32']);
        systemOutFormatted('================================================================');
        systemOutFormatted('');
        systemOutFormatted('Los archivos generados se encuentran en: ' . $outputDir);

    } catch (Exception $e) {
        systemOutFormatted("ERROR CRÍTICO: " . $e->getMessage(), ['color' => '31']);
    }

})->setDescription($cliTaskDescription)->register();
