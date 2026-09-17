<?php

/**
 * SpreadsheetExportWriter.php
 */

namespace PiecesPHP\Core\DataTransfer\Export;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * SpreadsheetExportWriter - Escribe una exportación en XLSX o CSV sin que una celda pueda ejecutarse como fórmula.
 *
 * @package     PiecesPHP\Core\DataTransfer\Export
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class SpreadsheetExportWriter
{
    /**
     * @param ExportDefinition $definition
     * @param string $path
     * @return void
     */
    public function toXlsx(ExportDefinition $definition, string $path): void
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $columns = $definition->columns();

        foreach ($columns as $index => $column) {
            $sheet->setCellValueExplicit([$index + 1, 1], $column->label(), DataType::TYPE_STRING2);
        }
        $rowIndex = 2;
        foreach ($definition->rows() as $row) {
            foreach ($columns as $index => $column) {
                //Todo como texto (TYPE_STRING2): así «=1+1» nunca se evalúa.
                $sheet->setCellValueExplicit([$index + 1, $rowIndex], self::toText($row[$column->key()] ?? null), DataType::TYPE_STRING2);
            }
            $rowIndex++;
        }

        (new Xlsx($book))->save($path);
    }

    /**
     * @param ExportDefinition $definition
     * @param string $path
     * @return void
     * @throws \RuntimeException si no se puede escribir el archivo
     */
    public function toCsv(ExportDefinition $definition, string $path): void
    {
        $handle = @fopen($path, 'w');
        if ($handle === false) {
            throw new \RuntimeException("No se puede escribir «{$path}».");
        }
        try {
            $columns = $definition->columns();
            //BOM: sin él, Excel abre el UTF-8 como si fuera Windows-1252.
            if (fwrite($handle, "\xEF\xBB\xBF") === false) {
                throw new \RuntimeException("No se puede escribir «{$path}».");
            }
            if (fputcsv($handle, array_map(fn(ExportColumn $c) => self::neutralize($c->label()), $columns), ',', '"', '') === false) {
                throw new \RuntimeException("No se puede escribir «{$path}».");
            }
            foreach ($definition->rows() as $row) {
                $line = [];
                foreach ($columns as $column) {
                    $line[] = self::neutralize(self::toText($row[$column->key()] ?? null));
                }
                if (fputcsv($handle, $line, ',', '"', '') === false) {
                    throw new \RuntimeException("No se puede escribir «{$path}».");
                }
            }
        } catch (\Throwable $e) {
            //RETORNO-IGNORADO: ya se está propagando el fallo de escritura; el cierre es solo limpieza.
            fclose($handle);
            throw $e;
        }
        //Un fclose() fallido en escritura puede dejar datos sin volcar.
        if (!fclose($handle)) {
            throw new \RuntimeException("No se puede escribir «{$path}».");
        }
    }

    /**
     * Inyección de fórmulas en CSV: una hoja de cálculo ejecuta lo que empieza por =, +, -, @, tabulador o retorno.
     *
     * @param string $value
     * @return string
     */
    private static function neutralize(string $value): string
    {
        //Un número suelto (-10, +3.5) no es fórmula en una hoja de cálculo y así hace ida y vuelta.
        if (preg_match('/^[+-]?\d+(\.\d+)?$/', $value) === 1) {
            return $value;
        }
        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'" . $value : $value;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function toText($value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        return is_scalar($value) ? (string) $value : '';
    }
}
