<?php

/**
 * ExportColumn.php
 */

namespace PiecesPHP\Core\DataTransfer\Export;

/**
 * ExportColumn - Una columna de la exportación: la key que se lee de cada fila y la etiqueta de la cabecera.
 *
 * @package     PiecesPHP\Core\DataTransfer\Export
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ExportColumn
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $label;

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
}
