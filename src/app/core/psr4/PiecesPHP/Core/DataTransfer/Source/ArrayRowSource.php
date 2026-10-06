<?php

/**
 * ArrayRowSource.php
 */

namespace PiecesPHP\Core\DataTransfer\Source;

/**
 * ArrayRowSource - Filas en memoria, para pruebas o para orígenes que no son un archivo.
 *
 * @package     PiecesPHP\Core\DataTransfer\Source
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ArrayRowSource implements RowSource
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
    public function __construct(array $headers, array $rows)
    {
        $this->headers = array_values($headers);
        $this->rows = array_values($rows);
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
}
