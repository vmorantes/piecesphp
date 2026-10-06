<?php

/**
 * ExportStyle.php
 */

namespace PiecesPHP\Core\DataTransfer\Export;

/**
 * ExportStyle - El aspecto de una hoja XLSX: encabezado, bordes, panel congelado, autofiltro y anchos.
 *
 * Solo afecta al XLSX; el CSV no tiene estilo.
 *
 * @package     PiecesPHP\Core\DataTransfer\Export
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ExportStyle
{
    /**
     * @var string|null
     */
    private $headerFont = null;

    /**
     * @var string|null
     */
    private $headerFill = null;

    /**
     * @var bool
     */
    private $headerBold = false;

    /**
     * @var string|null
     */
    private $border = null;

    /**
     * @var bool
     */
    private $freeze = false;

    /**
     * @var bool
     */
    private $filter = false;

    /**
     * @var bool
     */
    private $wrap = false;

    /**
     * @var float|null
     */
    private $maxWidth = null;

    private function __construct()
    {
    }

    /**
     * Encabezado en negrita blanco sobre 263238, bordes D7DEE2, panel congelado, autofiltro,
     * ajuste de texto y ancho automático con tope 40.
     *
     * @return self
     */
    public static function report(): self
    {
        $style = new self();
        $style->headerBold = true;
        return $style->headerColors('FFFFFF', '263238')
            ->borderColor('D7DEE2')
            ->freezeHeader(true)
            ->autoFilter(true)
            ->wrapText(true)
            ->maxAutoWidth(40);
    }

    /**
     * Sin estilo: solo ancho automático sin tope.
     *
     * @return self
     */
    public static function plain(): self
    {
        return new self();
    }

    /**
     * @param string $fontHex
     * @param string $fillHex
     * @return static
     * @throws \InvalidArgumentException
     */
    public function headerColors(string $fontHex, string $fillHex): static
    {
        $this->headerFont = self::color($fontHex);
        $this->headerFill = self::color($fillHex);
        return $this;
    }

    /**
     * @param string|null $hex null: sin bordes
     * @return static
     * @throws \InvalidArgumentException
     */
    public function borderColor(?string $hex): static
    {
        $this->border = $hex !== null ? self::color($hex) : null;
        return $this;
    }

    /**
     * @param bool $on
     * @return static
     */
    public function freezeHeader(bool $on): static
    {
        $this->freeze = $on;
        return $this;
    }

    /**
     * @param bool $on
     * @return static
     */
    public function autoFilter(bool $on): static
    {
        $this->filter = $on;
        return $this;
    }

    /**
     * @param bool $on
     * @return static
     */
    public function wrapText(bool $on): static
    {
        $this->wrap = $on;
        return $this;
    }

    /**
     * @param float|null $width null: sin tope
     * @return static
     */
    public function maxAutoWidth(?float $width): static
    {
        $this->maxWidth = $width;
        return $this;
    }

    /**
     * @return string|null
     */
    public function headerFontColor(): ?string
    {
        return $this->headerFont;
    }

    /**
     * @return string|null
     */
    public function headerFillColor(): ?string
    {
        return $this->headerFill;
    }

    /**
     * @return bool
     */
    public function isHeaderBold(): bool
    {
        return $this->headerBold;
    }

    /**
     * @return string|null
     */
    public function borderColorValue(): ?string
    {
        return $this->border;
    }

    /**
     * @return bool
     */
    public function isFreezeHeader(): bool
    {
        return $this->freeze;
    }

    /**
     * @return bool
     */
    public function isAutoFilter(): bool
    {
        return $this->filter;
    }

    /**
     * @return bool
     */
    public function isWrapText(): bool
    {
        return $this->wrap;
    }

    /**
     * @return float|null
     */
    public function maxAutoWidthValue(): ?float
    {
        return $this->maxWidth;
    }

    /**
     * @param string $hex
     * @return string
     * @throws \InvalidArgumentException
     */
    private static function color(string $hex): string
    {
        if (preg_match('/^[0-9A-Fa-f]{6}$/', $hex) !== 1) {
            throw new \InvalidArgumentException("Color «{$hex}» no válido: seis dígitos hexadecimales, sin «#».");
        }
        return strtoupper($hex);
    }
}
