<?php

/**
 * ExportSheet.php
 */

namespace PiecesPHP\Core\DataTransfer\Export;

use PiecesPHP\Core\DataTransfer\Import\ImportRunner;

/**
 * ExportSheet - Una hoja del libro XLSX: su tabla y lo que va encima (títulos, filtros, fecha, imágenes) y debajo (totales).
 *
 * Solo el XLSX la usa; el CSV sigue siendo la tabla de columns()/rows() de la definición.
 *
 * @package     PiecesPHP\Core\DataTransfer\Export
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ExportSheet
{
    const AGGREGATE_SUM = 'SUM';
    const AGGREGATE_AVERAGE = 'AVERAGE';
    const AGGREGATE_COUNT = 'COUNT';
    const AGGREGATE_MIN = 'MIN';
    const AGGREGATE_MAX = 'MAX';

    const MAX_TITLE_LENGTH = 31;

    const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg'];

    /**
     * @var string
     */
    private $title;

    /**
     * @var ExportColumn[]
     */
    private $columns;

    /**
     * @var iterable<array<string,mixed>>
     */
    private $rows;

    /**
     * @var string|null
     */
    private $heading = null;

    /**
     * @var string[]
     */
    private $subheadings = [];

    /**
     * @var string|null
     */
    private $filters = null;

    /**
     * @var string|null
     */
    private $generated = null;

    /**
     * @var array<int,array{path:string,cell:string,height:int|null}>
     */
    private $images = [];

    /**
     * @var array<string,string>
     */
    private $totals = [];

    /**
     * @var string|null
     */
    private $totalsLabel = null;

    /**
     * @var string[]
     */
    private $merges = [];

    /**
     * @var ExportStyle|null
     */
    private $style = null;

    /**
     * @var string[]
     */
    private $droppedColumns = [];

    /**
     * @param string $title Nombre de la pestaña: se quitan : \ / ? * [ ] y se recorta a 31; vacío → «Hoja N»
     * @param ExportColumn[] $columns
     * @param iterable<array<string,mixed>> $rows
     */
    public function __construct(string $title, array $columns, iterable $rows)
    {
        $this->title = self::cleanTitle($title);
        $this->columns = array_values($columns);
        $this->rows = $rows;
    }

    /**
     * @param string $text
     * @return static
     */
    public function heading(string $text): static
    {
        $this->heading = $text;
        return $this;
    }

    /**
     * @param string $text
     * @return static
     */
    public function subheading(string $text): static
    {
        $this->subheadings[] = $text;
        return $this;
    }

    /**
     * «Filtros: <etiqueta>: <valor>; …» con los parámetros que llevan valor; sin ninguno, no añade fila.
     *
     * @param ExportContext $context
     * @return static
     */
    public function appliedFilters(ExportContext $context): static
    {
        $parts = [];
        foreach ($context->parameters() as $key => $parameter) {
            $text = self::describe($parameter, $context->all()[$key] ?? null);
            if ($text !== null) {
                $parts[] = "{$parameter->label()}: {$text}";
            }
        }
        $this->filters = count($parts) > 0 ? __(ImportRunner::LANG_GROUP, 'Filtros') . ': ' . implode('; ', $parts) : null;
        return $this;
    }

    /**
     * @param bool $on
     * @return static
     */
    public function generatedAt(bool $on = true): static
    {
        $this->generated = $on ? sprintf(__(ImportRunner::LANG_GROUP, 'Generado el %s'), date('d/m/Y H:i')) : null;
        return $this;
    }

    /**
     * @param string $path Dentro del proyecto; .png, .jpg o .jpeg
     * @param string $cell
     * @param int|null $heightPx
     * @return static
     * @throws \InvalidArgumentException
     */
    public function image(string $path, string $cell = 'A1', ?int $heightPx = null): static
    {
        $real = ProjectFile::resolve($path, self::IMAGE_EXTENSIONS);
        if (preg_match('/^[A-Z]{1,3}[1-9][0-9]*$/', $cell) !== 1) {
            throw new \InvalidArgumentException("Celda «{$cell}» no válida.");
        }
        if ($heightPx !== null && $heightPx <= 0) {
            throw new \InvalidArgumentException('La altura de la imagen debe ser positiva.');
        }
        $this->images[] = ['path' => $real, 'cell' => $cell, 'height' => $heightPx];
        return $this;
    }

    /**
     * Se valida al construir el libro: columna y agregado existentes; SUM, AVERAGE, MIN y MAX solo en columnas numéricas.
     *
     * @param string $columnKey
     * @param string $aggregate SUM|AVERAGE|COUNT|MIN|MAX
     * @return static
     */
    public function total(string $columnKey, string $aggregate): static
    {
        $this->totals[$columnKey] = $aggregate;
        return $this;
    }

    /**
     * @param string $label
     * @return static
     */
    public function totalsLabel(string $label): static
    {
        $this->totalsLabel = $label;
        return $this;
    }

    /**
     * @param string $range p. ej. «A1:D1»
     * @return static
     * @throws \InvalidArgumentException
     */
    public function merge(string $range): static
    {
        if (preg_match('/^[A-Z]{1,3}[1-9][0-9]*:[A-Z]{1,3}[1-9][0-9]*$/', $range) !== 1) {
            throw new \InvalidArgumentException("Rango «{$range}» no válido.");
        }
        $this->merges[] = $range;
        return $this;
    }

    /**
     * Sustituye el estilo de la definición para esta hoja.
     *
     * @param ExportStyle $style
     * @return static
     */
    public function style(ExportStyle $style): static
    {
        $this->style = $style;
        return $this;
    }

    /**
     * Columnas declaradas que no se eligieron: sus totales se omiten sin error.
     *
     * @param string[] $keys
     * @return static
     */
    public function dropColumns(array $keys): static
    {
        $this->droppedColumns = array_values($keys);
        return $this;
    }

    /**
     * El título ya limpio; puede ser vacío (el escritor pone «Hoja N» y desambigua repetidos).
     *
     * @return string
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * @return ExportColumn[]
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * @return iterable<array<string,mixed>>
     */
    public function rows(): iterable
    {
        return $this->rows;
    }

    /**
     * Las filas de encima de la tabla, en orden: título, subtítulos, filtros y fecha.
     *
     * @return array<int,array{kind:string,text:string}>
     */
    public function topLines(): array
    {
        $lines = [];
        if ($this->heading !== null) {
            $lines[] = ['kind' => 'heading', 'text' => $this->heading];
        }
        foreach ($this->subheadings as $text) {
            $lines[] = ['kind' => 'subheading', 'text' => $text];
        }
        if ($this->filters !== null) {
            $lines[] = ['kind' => 'filters', 'text' => $this->filters];
        }
        if ($this->generated !== null) {
            $lines[] = ['kind' => 'generated', 'text' => $this->generated];
        }
        return $lines;
    }

    /**
     * @return array<int,array{path:string,cell:string,height:int|null}>
     */
    public function images(): array
    {
        return $this->images;
    }

    /**
     * @return array<string,string> columna => agregado, sin los de columnas no elegidas
     */
    public function totals(): array
    {
        return array_diff_key($this->totals, array_flip($this->droppedColumns));
    }

    /**
     * @return string
     */
    public function totalsLabelText(): string
    {
        return $this->totalsLabel ?? __(ImportRunner::LANG_GROUP, 'Total');
    }

    /**
     * @return string[]
     */
    public function merges(): array
    {
        return $this->merges;
    }

    /**
     * @return ExportStyle|null
     */
    public function styleValue(): ?ExportStyle
    {
        return $this->style;
    }

    /**
     * @param string $title
     * @return string
     */
    private static function cleanTitle(string $title): string
    {
        $clean = trim(str_replace([':', '\\', '/', '?', '*', '[', ']'], '', $title));
        return trim(mb_substr($clean, 0, self::MAX_TITLE_LENGTH));
    }

    /**
     * El valor de un filtro, legible; null si no lleva valor.
     *
     * @param ExportParameter $parameter
     * @param mixed $value
     * @return string|null
     */
    private static function describe(ExportParameter $parameter, $value): ?string
    {
        if ($value === null || $value === [] || ($value instanceof DateRange && $value->isEmpty())) {
            return null;
        }
        $options = $parameter->options();
        switch ($parameter->type()) {
            case ExportParameter::TYPE_BOOLEAN:
                return $value ? __(ImportRunner::LANG_GROUP, 'Sí') : __(ImportRunner::LANG_GROUP, 'No');
            case ExportParameter::TYPE_CHOICE:
                return is_int($value) || is_string($value) ? ($options[$value] ?? (string) $value) : null;
            case ExportParameter::TYPE_MULTI_CHOICE:
                return is_array($value) ? implode(', ', array_map(fn($k) => (string) ($options[$k] ?? $k), $value)) : null;
            case ExportParameter::TYPE_DATE:
                return $value instanceof \DateTimeInterface ? $value->format('d/m/Y') : null;
            case ExportParameter::TYPE_DATE_RANGE:
                if (!$value instanceof DateRange) {
                    return null;
                }
                $from = $value->from();
                $to = $value->to();
                if ($from !== null && $to !== null) {
                    return sprintf(__(ImportRunner::LANG_GROUP, 'del %s al %s'), $from->format('d/m/Y'), $to->format('d/m/Y'));
                }
                return $from !== null
                    ? sprintf(__(ImportRunner::LANG_GROUP, 'desde el %s'), $from->format('d/m/Y'))
                    : sprintf(__(ImportRunner::LANG_GROUP, 'hasta el %s'), $to !== null ? $to->format('d/m/Y') : '');
            default:
                return is_scalar($value) ? (string) $value : null;
        }
    }
}
