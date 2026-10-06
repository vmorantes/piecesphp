<?php

/**
 * SpreadsheetExportWriter.php
 */

namespace PiecesPHP\Core\DataTransfer\Export;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PiecesPHP\Core\DataTransfer\Import\ImportRunner;

/**
 * SpreadsheetExportWriter - Escribe una exportación en XLSX o CSV sin que una celda pueda ejecutarse como fórmula.
 *
 * Un valor que no encaja en el tipo de su columna se escribe como texto, tal cual: un informe no se cae por una fila rara.
 *
 * @package     PiecesPHP\Core\DataTransfer\Export
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class SpreadsheetExportWriter
{
    /**
     * Escribe sheets() en orden, con el estilo de cada hoja o el de la definición, y llama a afterBuild() antes de guardar.
     *
     * @param ExportDefinition $definition
     * @param ExportContext $context
     * @param string $path
     * @return int Filas de datos de la primera hoja
     * @throws \InvalidArgumentException si la definición declara algo imposible (sin hojas, total o fórmula inválidos)
     */
    public function toXlsx(ExportDefinition $definition, ExportContext $context, string $path): int
    {
        $sheets = $definition->sheets($context);
        if (count($sheets) === 0) {
            throw new \InvalidArgumentException("La exportación «{$definition->key()}» no declara ninguna hoja.");
        }
        $defaultStyle = $definition->style($context);

        $book = new Spreadsheet();
        $used = [];
        $mainRows = 0;
        foreach (array_values($sheets) as $index => $exportSheet) {
            $sheet = $index === 0 ? $book->getActiveSheet() : $book->createSheet();
            $sheet->setTitle(self::uniqueTitle($exportSheet->title(), $index + 1, $used));
            $written = $this->writeSheet($sheet, $exportSheet, $exportSheet->styleValue() ?? $defaultStyle, $context);
            if ($index === 0) {
                $mainRows = $written;
            }
        }
        $book->setActiveSheetIndex(0);

        $definition->afterBuild($book, $context);

        (new Xlsx($book))->save($path);
        return $mainRows;
    }

    /**
     * @param Worksheet $sheet
     * @param ExportSheet $exportSheet
     * @param ExportStyle $style
     * @param ExportContext $context
     * @return int Filas de datos escritas
     */
    private function writeSheet(Worksheet $sheet, ExportSheet $exportSheet, ExportStyle $style, ExportContext $context): int
    {
        $columns = $exportSheet->columns();
        $count = max(1, count($columns));
        $lastLetter = Coordinate::stringFromColumnIndex($count);
        $letters = [];
        $byKey = [];
        foreach ($columns as $index => $column) {
            $letters[$column->key()] = Coordinate::stringFromColumnIndex($index + 1);
            $byKey[$column->key()] = $column;
        }
        self::validate($exportSheet, $letters);

        //Encima de la tabla: cada línea combinada a lo ancho, y una fila vacía.
        $row = 1;
        $topLines = $exportSheet->topLines();
        foreach ($topLines as $line) {
            $sheet->setCellValueExplicit("A{$row}", $line['text'], DataType::TYPE_STRING2);
            if ($count > 1) {
                $sheet->mergeCells("A{$row}:{$lastLetter}{$row}");
            }
            if ($line['kind'] === 'heading') {
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
            }
            $row++;
        }
        if (count($topLines) > 0) {
            $row++;
        }

        $headerRow = $row;
        foreach ($columns as $index => $column) {
            $sheet->setCellValueExplicit([$index + 1, $headerRow], $column->label(), DataType::TYPE_STRING2);
        }

        $row = $headerRow + 1;
        foreach ($exportSheet->rows() as $data) {
            foreach ($columns as $index => $column) {
                $cell = [$index + 1, $row];
                $template = $column->formulaTemplate();
                if ($template !== null) {
                    //La plantilla sale del código; la fila solo aporta el número de fila.
                    $formula = (string) preg_replace_callback('/\{([^{}]+)\}/', fn($m) => $letters[$m[1]] . $row, $template);
                    $sheet->setCellValueExplicit($cell, $formula, DataType::TYPE_FORMULA);
                    self::applyNumberFormat($sheet, $cell, $column);
                    continue;
                }
                [$value, $isTyped] = self::xlsxValue($column, $column->value($data, $context));
                if ($value === null) {
                    continue;
                }
                if ($isTyped) {
                    $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_NUMERIC);
                    self::applyNumberFormat($sheet, $cell, $column);
                } else {
                    //Texto (TYPE_STRING2): así «=1+1» nunca se evalúa.
                    $sheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING2);
                }
            }
            $row++;
        }
        $firstData = $headerRow + 1;
        //Sin filas se deja una vacía: un rango hacia arriba incluiría el encabezado.
        $lastData = max($firstData, $row - 1);

        $lastRow = $lastData;
        $totals = $exportSheet->totals();
        if (count($totals) > 0) {
            $lastRow = $lastData + 1;
            foreach ($columns as $index => $column) {
                if (!array_key_exists($column->key(), $totals)) {
                    $sheet->setCellValueExplicit([$index + 1, $lastRow], $exportSheet->totalsLabelText(), DataType::TYPE_STRING2);
                    break;
                }
            }
            foreach ($totals as $key => $aggregate) {
                $letter = $letters[$key];
                $function = $aggregate === ExportSheet::AGGREGATE_COUNT ? 'COUNTA' : $aggregate;
                $cell = "{$letter}{$lastRow}";
                $sheet->setCellValueExplicit($cell, "={$function}({$letter}{$firstData}:{$letter}{$lastData})", DataType::TYPE_FORMULA);
                if ($aggregate === ExportSheet::AGGREGATE_COUNT) {
                    //Un conteo es un entero, aunque la columna sea de fechas.
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');
                } else {
                    self::applyNumberFormat($sheet, $cell, $byKey[$key]);
                }
            }
            if ($style->isHeaderBold()) {
                $sheet->getStyle("A{$lastRow}:{$lastLetter}{$lastRow}")->getFont()->setBold(true);
            }
        }

        foreach ($exportSheet->merges() as $range) {
            $sheet->mergeCells($range);
        }

        foreach ($exportSheet->images() as $image) {
            $drawing = new Drawing();
            $drawing->setPath($image['path']);
            $drawing->setCoordinates($image['cell']);
            if ($image['height'] !== null) {
                $drawing->setResizeProportional(true);
                $drawing->setHeight($image['height']);
            }
            $drawing->setWorksheet($sheet);
        }

        self::applyStyle($sheet, $style, $columns, $headerRow, $lastData, $lastRow, $lastLetter);
        return $row - $firstData;
    }

    /**
     * @param ExportSheet $exportSheet
     * @param array<string,string> $letters key => letra de columna
     * @return void
     * @throws \InvalidArgumentException
     */
    private static function validate(ExportSheet $exportSheet, array $letters): void
    {
        $numeric = [ExportColumn::TYPE_INTEGER, ExportColumn::TYPE_DECIMAL, ExportColumn::TYPE_MONEY, ExportColumn::TYPE_PERCENT];
        $aggregates = [ExportSheet::AGGREGATE_SUM, ExportSheet::AGGREGATE_AVERAGE, ExportSheet::AGGREGATE_COUNT, ExportSheet::AGGREGATE_MIN, ExportSheet::AGGREGATE_MAX];
        $types = [];
        foreach ($exportSheet->columns() as $column) {
            $types[$column->key()] = $column->type();
        }
        foreach ($exportSheet->totals() as $key => $aggregate) {
            if (!array_key_exists($key, $types)) {
                throw new \InvalidArgumentException("El total de «{$key}» no es una columna de la hoja.");
            }
            if (!in_array($aggregate, $aggregates, true)) {
                throw new \InvalidArgumentException("Agregado «{$aggregate}» no admitido: SUM, AVERAGE, COUNT, MIN o MAX.");
            }
            if ($aggregate !== ExportSheet::AGGREGATE_COUNT && !in_array($types[$key], $numeric, true)) {
                throw new \InvalidArgumentException("{$aggregate} exige una columna numérica y «{$key}» es {$types[$key]}.");
            }
        }
        foreach ($exportSheet->columns() as $column) {
            foreach ($column->formulaKeys() as $key) {
                if (!array_key_exists($key, $letters)) {
                    throw new \InvalidArgumentException("La fórmula de «{$column->key()}» usa {{$key}}, que no es una columna de la hoja.");
                }
            }
        }
    }

    /**
     * @param Worksheet $sheet
     * @param ExportStyle $style
     * @param ExportColumn[] $columns
     * @param int $headerRow
     * @param int $lastData
     * @param int $lastRow
     * @param string $lastLetter
     * @return void
     */
    private static function applyStyle(Worksheet $sheet, ExportStyle $style, array $columns, int $headerRow, int $lastData, int $lastRow, string $lastLetter): void
    {
        $header = $sheet->getStyle("A{$headerRow}:{$lastLetter}{$headerRow}");
        if ($style->isHeaderBold()) {
            $header->getFont()->setBold(true);
        }
        if ($style->headerFontColor() !== null) {
            $header->getFont()->getColor()->setRGB((string) $style->headerFontColor());
        }
        if ($style->headerFillColor() !== null) {
            $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB((string) $style->headerFillColor());
        }
        $table = "A{$headerRow}:{$lastLetter}{$lastRow}";
        if ($style->borderColorValue() !== null) {
            $sheet->getStyle($table)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB((string) $style->borderColorValue());
        }
        if ($style->isWrapText()) {
            $sheet->getStyle($table)->getAlignment()->setWrapText(true);
        }
        if ($style->isFreezeHeader()) {
            $sheet->freezePane('A' . ($headerRow + 1));
        }
        if ($style->isAutoFilter()) {
            $sheet->setAutoFilter("A{$headerRow}:{$lastLetter}{$lastData}");
        }

        foreach ($columns as $index => $column) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);
            $width = $column->widthValue();
            if ($width !== null) {
                $sheet->getColumnDimension($letter)->setWidth($width);
            } else {
                $sheet->getColumnDimension($letter)->setAutoSize(true);
            }
            $alignment = $column->alignment();
            if ($alignment !== null) {
                $horizontal = [
                    ExportColumn::ALIGN_LEFT => Alignment::HORIZONTAL_LEFT,
                    ExportColumn::ALIGN_CENTER => Alignment::HORIZONTAL_CENTER,
                    ExportColumn::ALIGN_RIGHT => Alignment::HORIZONTAL_RIGHT,
                ][$alignment];
                $sheet->getStyle("{$letter}" . ($headerRow + 1) . ":{$letter}{$lastRow}")->getAlignment()->setHorizontal($horizontal);
            }
        }

        //El tope se aplica sobre el ancho ya calculado: la columna pasa a ancho fijo.
        $max = $style->maxAutoWidthValue();
        if ($max !== null) {
            $sheet->calculateColumnWidths();
            foreach ($columns as $index => $column) {
                $dimension = $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1));
                if ($column->widthValue() === null && $dimension->getWidth() > $max) {
                    $dimension->setAutoSize(false);
                    $dimension->setWidth($max);
                }
            }
        }
    }

    /**
     * @param Worksheet $sheet
     * @param array{0:int,1:int}|string $cell
     * @param ExportColumn $column
     * @return void
     */
    private static function applyNumberFormat(Worksheet $sheet, $cell, ExportColumn $column): void
    {
        $format = $column->numberFormat();
        if ($format !== null) {
            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($format);
        }
    }

    /**
     * Excel: 31 caracteres y sin repetir; vacío → «Hoja N»; repetido → « (2)», « (3)»…
     *
     * @param string $title
     * @param int $position
     * @param array<string,true> $used en minúsculas, porque Excel no distingue
     * @return string
     */
    private static function uniqueTitle(string $title, int $position, array &$used): string
    {
        $base = $title !== '' ? $title : sprintf(__(ImportRunner::LANG_GROUP, 'Hoja %d'), $position);
        $candidate = $base;
        $n = 2;
        while (array_key_exists(mb_strtolower($candidate), $used)) {
            $suffix = " ({$n})";
            $candidate = rtrim(mb_substr($base, 0, ExportSheet::MAX_TITLE_LENGTH - mb_strlen($suffix))) . $suffix;
            $n++;
        }
        $used[mb_strtolower($candidate)] = true;
        return $candidate;
    }

    /**
     * @param ExportDefinition $definition
     * @param ExportContext $context
     * @param string $path
     * @return int Filas de datos escritas
     * @throws \RuntimeException si no se puede escribir el archivo
     */
    public function toCsv(ExportDefinition $definition, ExportContext $context, string $path): int
    {
        $handle = @fopen($path, 'w');
        if ($handle === false) {
            throw new \RuntimeException("No se puede escribir «{$path}».");
        }
        $written = 0;
        try {
            //La hoja principal, con la elección de columnas ya aplicada.
            $main = $definition->mainSheet($context);
            $columns = $main->columns();
            //BOM: sin él, Excel abre el UTF-8 como si fuera Windows-1252.
            if (fwrite($handle, "\xEF\xBB\xBF") === false) {
                throw new \RuntimeException("No se puede escribir «{$path}».");
            }
            if (fputcsv($handle, array_map(fn(ExportColumn $c) => self::neutralize($c->label()), $columns), ',', '"', '') === false) {
                throw new \RuntimeException("No se puede escribir «{$path}».");
            }
            foreach ($main->rows() as $row) {
                $written++;
                $line = [];
                foreach ($columns as $column) {
                    //Una columna con fórmula va vacía: no hay hoja que la calcule.
                    [$value, $isTyped] = $column->formulaTemplate() !== null ? ['', false] : self::csvValue($column, $column->value($row, $context));
                    //Un valor tipado no se neutraliza: es un número o una fecha formateada por el escritor.
                    $line[] = $isTyped ? $value : self::neutralize($value);
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
        return $written;
    }

    /**
     * Las primeras filas de la hoja principal, convertidas como en CSV pero sin neutralizar: es para pintar, y quien pinta escapa.
     *
     * Deja de leer rows() en cuanto sabe que hay más de $limit.
     *
     * @param ExportDefinition $definition
     * @param ExportContext $context
     * @param int $limit
     * @return array{columns:string[],rows:string[][],truncated:bool}
     */
    public function firstRows(ExportDefinition $definition, ExportContext $context, int $limit = ExportDefinition::PREVIEW_ROWS): array
    {
        $main = $definition->mainSheet($context);
        $columns = $main->columns();
        $rows = [];
        $truncated = false;
        foreach ($main->rows() as $row) {
            if (count($rows) >= $limit) {
                $truncated = true;
                break;
            }
            $line = [];
            foreach ($columns as $column) {
                //Una columna con fórmula no tiene valor hasta que la hoja la calcula.
                $line[] = $column->formulaTemplate() !== null ? '' : self::csvValue($column, $column->value($row, $context))[0];
            }
            $rows[] = $line;
        }
        return [
            'columns' => array_map(fn(ExportColumn $c) => $c->label(), $columns),
            'rows' => $rows,
            'truncated' => $truncated,
        ];
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
     * XLSX: [número o serie de Excel, true] si encaja en el tipo; [texto, false] si no; [null, false] si es null.
     *
     * @param ExportColumn $column
     * @param mixed $value
     * @return array{0:int|float|string|null,1:bool}
     */
    private static function xlsxValue(ExportColumn $column, $value): array
    {
        if ($value === null) {
            return [null, false];
        }
        switch ($column->type()) {
            case ExportColumn::TYPE_INTEGER:
            case ExportColumn::TYPE_DECIMAL:
            case ExportColumn::TYPE_MONEY:
            case ExportColumn::TYPE_PERCENT:
                $number = self::toNumber($value);
                return $number !== null ? [$number, true] : [self::toText($value), false];
            case ExportColumn::TYPE_DATE:
            case ExportColumn::TYPE_DATETIME:
                $date = self::toDate($value, $column->type());
                $serial = $date !== null ? Date::PHPToExcel($date) : false;
                return $serial !== false ? [$serial, true] : [self::toText($value), false];
            case ExportColumn::TYPE_BOOLEAN:
                return [self::toBooleanLabel($column, $value), false];
            default:
                return [self::toText($value), false];
        }
    }

    /**
     * CSV: [texto, true] si encaja en el tipo (no se neutraliza); [texto, false] si no.
     *
     * @param ExportColumn $column
     * @param mixed $value
     * @return array{0:string,1:bool}
     */
    private static function csvValue(ExportColumn $column, $value): array
    {
        if ($value === null) {
            return ['', false];
        }
        switch ($column->type()) {
            case ExportColumn::TYPE_INTEGER:
            case ExportColumn::TYPE_DECIMAL:
            case ExportColumn::TYPE_MONEY:
            case ExportColumn::TYPE_PERCENT:
                $number = self::toNumber($value);
                //Punto decimal y sin separador de miles, sea cual sea el locale.
                return $number !== null ? [is_int($number) ? (string) $number : self::floatToText($number), true] : [self::toText($value), false];
            case ExportColumn::TYPE_DATE:
            case ExportColumn::TYPE_DATETIME:
                $date = self::toDate($value, $column->type());
                return $date !== null ? [$date->format($column->type() === ExportColumn::TYPE_DATE ? 'Y-m-d' : 'Y-m-d H:i:s'), true] : [self::toText($value), false];
            case ExportColumn::TYPE_BOOLEAN:
                return [self::toBooleanLabel($column, $value), false];
            default:
                return [self::toText($value), false];
        }
    }

    /**
     * @param mixed $value
     * @return int|float|null
     */
    private static function toNumber($value)
    {
        if (is_int($value) || is_float($value)) {
            return is_float($value) && !is_finite($value) ? null : $value;
        }
        if (is_string($value) && is_numeric(trim($value))) {
            return trim($value) + 0;
        }
        return null;
    }

    /**
     * @param float $number
     * @return string
     */
    private static function floatToText(float $number): string
    {
        //var_export da la representación más corta que hace ida y vuelta, siempre con punto.
        $text = var_export($number, true);
        return str_ends_with($text, '.0') ? substr($text, 0, -2) : $text;
    }

    /**
     * Estricto: 2026-02-30 se desborda a marzo y el formato de vuelta no coincide.
     *
     * @param mixed $value
     * @param string $type
     * @return \DateTimeInterface|null
     */
    private static function toDate($value, string $type): ?\DateTimeInterface
    {
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }
        if (!is_string($value)) {
            return null;
        }
        $text = trim($value);
        foreach (['!Y-m-d', '!Y-m-d H:i:s'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $text);
            if ($date !== false && $date->format(ltrim($format, '!')) === $text) {
                return $date;
            }
        }
        return null;
    }

    /**
     * @param ExportColumn $column
     * @param mixed $value
     * @return string
     */
    private static function toBooleanLabel(ExportColumn $column, $value): string
    {
        if ($value === true || $value === 1 || $value === '1') {
            return $column->yesLabel();
        }
        if ($value === false || $value === 0 || $value === '0') {
            return $column->noLabel();
        }
        return self::toText($value);
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
