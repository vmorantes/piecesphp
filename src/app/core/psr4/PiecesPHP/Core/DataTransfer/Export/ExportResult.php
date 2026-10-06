<?php

/**
 * ExportResult.php
 */

namespace PiecesPHP\Core\DataTransfer\Export;

/**
 * ExportResult - Lo que se generó: formato, nombre final, tamaño y filas de datos de la hoja principal.
 *
 * @package     PiecesPHP\Core\DataTransfer\Export
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ExportResult
{
    /**
     * @var string
     */
    private $format;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var int
     */
    private $bytes;

    /**
     * @var int
     */
    private $rowCount;

    /**
     * @param string $format xlsx|csv
     * @param string $fileName El nombre final, con extensión y ya limpio
     * @param int $bytes
     * @param int $rowCount Filas de datos de la hoja principal (sin títulos, encabezado, relleno ni totales)
     */
    public function __construct(string $format, string $fileName, int $bytes, int $rowCount)
    {
        $this->format = $format;
        $this->fileName = $fileName;
        $this->bytes = $bytes;
        $this->rowCount = $rowCount;
    }

    /**
     * @return string
     */
    public function format(): string
    {
        return $this->format;
    }

    /**
     * @return string
     */
    public function fileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return int
     */
    public function bytes(): int
    {
        return $this->bytes;
    }

    /**
     * @return int
     */
    public function rowCount(): int
    {
        return $this->rowCount;
    }
}
