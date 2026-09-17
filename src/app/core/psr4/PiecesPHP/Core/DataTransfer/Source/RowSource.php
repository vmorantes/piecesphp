<?php

/**
 * RowSource.php
 */

namespace PiecesPHP\Core\DataTransfer\Source;

/**
 * RowSource - De dónde salen las cabeceras y las filas de una importación.
 *
 * @package     PiecesPHP\Core\DataTransfer\Source
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
interface RowSource
{
    /**
     * @return string[]
     */
    public function headers(): array;

    /**
     * @return iterable<int,array<int,string|null>> por índice numérico de columna
     */
    public function rows(): iterable;
}
