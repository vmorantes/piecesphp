<?php

/**
 * ExportDefinition.php
 */

namespace PiecesPHP\Core\DataTransfer\Export;

/**
 * ExportDefinition - Lo que un módulo implementa para exportar un tipo de dato.
 *
 * @package     PiecesPHP\Core\DataTransfer\Export
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
abstract class ExportDefinition
{
    /**
     * @return string
     */
    abstract public function key(): string;

    /**
     * @return string
     */
    abstract public function title(): string;

    /**
     * @return int[]
     */
    abstract public function allowedUserTypes(): array;

    /**
     * @return ExportColumn[]
     */
    abstract public function columns(): array;

    /**
     * @return iterable<array<string,scalar|null>>
     */
    abstract public function rows(): iterable;
}
