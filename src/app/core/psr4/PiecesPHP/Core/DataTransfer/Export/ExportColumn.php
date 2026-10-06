<?php

/**
 * ExportColumn.php
 */

namespace PiecesPHP\Core\DataTransfer\Export;

use PiecesPHP\Core\DataTransfer\Import\ImportRunner;

/**
 * ExportColumn - Una columna de la exportación: la key que se lee de cada fila, la etiqueta de la cabecera,
 * y opcionalmente su tipo, formato, ancho, alineación y transformación.
 *
 * Sin configurar es texto, como `new ExportColumn($key, $label)` siempre fue.
 *
 * @package     PiecesPHP\Core\DataTransfer\Export
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ExportColumn
{
    const TYPE_TEXT = 'text';
    const TYPE_INTEGER = 'integer';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_MONEY = 'money';
    const TYPE_PERCENT = 'percent';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_BOOLEAN = 'boolean';

    const ALIGN_LEFT = 'left';
    const ALIGN_CENTER = 'center';
    const ALIGN_RIGHT = 'right';

    const MAX_DECIMALS = 10;

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $type = self::TYPE_TEXT;

    /**
     * @var string|null
     */
    private $numberFormat = null;

    /**
     * @var float|null
     */
    private $width = null;

    /**
     * @var string|null
     */
    private $alignment = null;

    /**
     * @var callable|null
     */
    private $transform = null;

    /**
     * @var string|null
     */
    private $formula = null;

    /**
     * @var string|null
     */
    private $yesLabel = null;

    /**
     * @var string|null
     */
    private $noLabel = null;

    /**
     * @param string $key
     * @param string $label
     */
    public function __construct(string $key, string $label)
    {
        $this->key = $key;
        $this->label = $label;
    }

    /**
     * @return static
     */
    public function asText(): static
    {
        return $this->setType(self::TYPE_TEXT, null);
    }

    /**
     * @return static
     */
    public function asInteger(): static
    {
        return $this->setType(self::TYPE_INTEGER, '#,##0');
    }

    /**
     * @param int $decimals
     * @return static
     */
    public function asDecimal(int $decimals = 2): static
    {
        return $this->setType(self::TYPE_DECIMAL, self::decimalFormat('#,##0', $decimals));
    }

    /**
     * @param string $currency Código de la moneda, p. ej. «COP»
     * @param int $decimals
     * @return static
     */
    public function asMoney(string $currency, int $decimals = 2): static
    {
        //Entre comillas dobles es literal para Excel; una comilla dentro se dobla.
        $literal = '"' . str_replace('"', '""', $currency) . '"';
        return $this->setType(self::TYPE_MONEY, self::decimalFormat('#,##0', $decimals) . ' ' . $literal);
    }

    /**
     * El valor 0.25 se muestra como 25 %.
     *
     * @param int $decimals
     * @return static
     */
    public function asPercent(int $decimals = 0): static
    {
        return $this->setType(self::TYPE_PERCENT, self::decimalFormat('0', $decimals) . '%');
    }

    /**
     * @param string $excelFormat
     * @return static
     */
    public function asDate(string $excelFormat = 'dd/mm/yyyy'): static
    {
        return $this->setType(self::TYPE_DATE, $excelFormat);
    }

    /**
     * @param string $excelFormat
     * @return static
     */
    public function asDateTime(string $excelFormat = 'dd/mm/yyyy hh:mm'): static
    {
        return $this->setType(self::TYPE_DATETIME, $excelFormat);
    }

    /**
     * @param string|null $yes null: «Sí» traducido
     * @param string|null $no null: «No» traducido
     * @return static
     */
    public function asBoolean(?string $yes = null, ?string $no = null): static
    {
        $this->yesLabel = $yes;
        $this->noLabel = $no;
        return $this->setType(self::TYPE_BOOLEAN, null);
    }

    /**
     * Sustituye el formato por defecto del tipo.
     *
     * @param string $excelFormat
     * @return static
     */
    public function format(string $excelFormat): static
    {
        $this->numberFormat = $excelFormat;
        return $this;
    }

    /**
     * @param float|null $width null: automático
     * @return static
     */
    public function width(?float $width): static
    {
        $this->width = $width;
        return $this;
    }

    /**
     * @param string $align left|center|right
     * @return static
     * @throws \InvalidArgumentException
     */
    public function align(string $align): static
    {
        if (!in_array($align, [self::ALIGN_LEFT, self::ALIGN_CENTER, self::ALIGN_RIGHT], true)) {
            throw new \InvalidArgumentException("Alineación «{$align}» no admitida: left, center o right.");
        }
        $this->alignment = $align;
        return $this;
    }

    /**
     * @param callable(array<string,mixed>,ExportContext):mixed $fn
     * @return static
     */
    public function transform(callable $fn): static
    {
        $this->transform = $fn;
        return $this;
    }

    /**
     * Fórmula de Excel por fila: «={amount}*{rate}»; cada {clave} es la celda de esa columna en la misma fila.
     *
     * Sale del código, nunca de la fila: con fórmula, transform() y el valor de la fila se ignoran.
     * En CSV la columna va vacía (no hay hoja que la calcule).
     *
     * @param string $template
     * @return static
     * @throws \InvalidArgumentException si no empieza por «=»
     */
    public function formula(string $template): static
    {
        if (!str_starts_with($template, '=')) {
            throw new \InvalidArgumentException("La fórmula de «{$this->key}» debe empezar por «=».");
        }
        $this->formula = $template;
        return $this;
    }

    /**
     * @return string|null
     */
    public function formulaTemplate(): ?string
    {
        return $this->formula;
    }

    /**
     * Las {clave} que la fórmula referencia.
     *
     * @return string[]
     */
    public function formulaKeys(): array
    {
        if ($this->formula === null || preg_match_all('/\{([^{}]+)\}/', $this->formula, $matches) === false) {
            return [];
        }
        return array_values(array_unique($matches[1]));
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function label(): string
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function numberFormat(): ?string
    {
        return $this->numberFormat;
    }

    /**
     * @return float|null
     */
    public function widthValue(): ?float
    {
        return $this->width;
    }

    /**
     * @return string|null
     */
    public function alignment(): ?string
    {
        return $this->alignment;
    }

    /**
     * @return string
     */
    public function yesLabel(): string
    {
        return $this->yesLabel ?? __(ImportRunner::LANG_GROUP, 'Sí');
    }

    /**
     * @return string
     */
    public function noLabel(): string
    {
        return $this->noLabel ?? __(ImportRunner::LANG_GROUP, 'No');
    }

    /**
     * @param array<string,mixed> $row
     * @param ExportContext $context
     * @return mixed
     */
    public function value(array $row, ExportContext $context)
    {
        if ($this->transform !== null) {
            return ($this->transform)($row, $context);
        }
        return $row[$this->key] ?? null;
    }

    /**
     * @param string $type
     * @param string|null $numberFormat
     * @return static
     */
    private function setType(string $type, ?string $numberFormat): static
    {
        $this->type = $type;
        $this->numberFormat = $numberFormat;
        return $this;
    }

    /**
     * @param string $integerPart
     * @param int $decimals
     * @return string
     * @throws \InvalidArgumentException
     */
    private static function decimalFormat(string $integerPart, int $decimals): string
    {
        if ($decimals < 0 || $decimals > self::MAX_DECIMALS) {
            throw new \InvalidArgumentException('Los decimales van de 0 a ' . self::MAX_DECIMALS . ".");
        }
        return $decimals === 0 ? $integerPart : $integerPart . '.' . str_repeat('0', $decimals);
    }
}
