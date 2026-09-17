<?php

/**
 * SpreadsheetRowSource.php
 */

namespace PiecesPHP\Core\DataTransfer\Source;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * SpreadsheetRowSource - Lee XLSX o CSV con el lector que dice la extensión. Nunca se adivina el formato.
 *
 * @package     PiecesPHP\Core\DataTransfer\Source
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class SpreadsheetRowSource implements RowSource
{
    /**
     * @var string[]
     */
    private $headers;

    /**
     * @var array<int,array<int,string|null>>
     */
    private $rows;

    /**
     * @param string[] $headers
     * @param array<int,array<int,string|null>> $rows
     */
    private function __construct(array $headers, array $rows)
    {
        $this->headers = $headers;
        $this->rows = $rows;
    }

    /**
     * @param string $path
     * @param string $extension 'xlsx' o 'csv'
     * @return self
     * @throws \InvalidArgumentException con otra extensión
     */
    public static function fromFile(string $path, string $extension): self
    {
        $extension = mb_strtolower(trim($extension));

        //Un lector explícito por extensión: IOFactory::load() aceptaría HTML, SLK o XML como hoja.
        if ($extension === 'xlsx') {
            $reader = new Xlsx();
            //Hace falta el estilo para saber qué celda es una fecha.
            $reader->setReadDataOnly(false);
        } elseif ($extension === 'csv') {
            $reader = new Csv();
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(self::csvDelimiter($path));
            $reader->setReadDataOnly(true);
        } else {
            throw new \InvalidArgumentException("Extensión no admitida: «{$extension}». Solo xlsx y csv.");
        }

        $sheet = $reader->load($path)->getActiveSheet();
        $lastColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $lastRow = $sheet->getHighestDataRow();

        $headers = [];
        $rows = [];
        for ($rowIndex = 1; $rowIndex <= $lastRow; $rowIndex++) {
            $values = [];
            for ($columnIndex = 1; $columnIndex <= $lastColumn; $columnIndex++) {
                $cell = $sheet->getCell([$columnIndex, $rowIndex]);
                $values[$columnIndex - 1] = self::cellToString($cell);
            }
            if ($rowIndex === 1) {
                $headers = array_map(fn(?string $v) => $v ?? '', $values);
            } else {
                $rows[] = $values;
            }
        }

        return new self($headers, $rows);
    }

    /**
     * @return string[]
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @return iterable<int,array<int,string|null>>
     */
    public function rows(): iterable
    {
        return $this->rows;
    }

    /**
     * @param Cell $cell
     * @return string|null
     */
    private static function cellToString(Cell $cell): ?string
    {
        //Fechas en ISO y el resto en crudo: el formato visible depende de la configuración regional del libro.
        $value = $cell->getCalculatedValue();
        if ($value === null) {
            return null;
        }
        if ((is_int($value) || is_float($value)) && Date::isDateTime($cell)) {
            $date = Date::excelToDateTimeObject($value);
            return $date->format($date->format('H:i:s') === '00:00:00' ? 'Y-m-d' : 'Y-m-d H:i:s');
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * @param string $path
     * @return string
     */
    private static function csvDelimiter(string $path): string
    {
        $handle = @fopen($path, 'r');
        if ($handle === false) {
            return ',';
        }
        $firstLine = fgets($handle);
        //RETORNO-IGNORADO: el archivo solo se leyó; cerrarlo no puede perder nada.
        fclose($handle);
        return is_string($firstLine) && !str_contains($firstLine, ',') && str_contains($firstLine, ';') ? ';' : ',';
    }
}
