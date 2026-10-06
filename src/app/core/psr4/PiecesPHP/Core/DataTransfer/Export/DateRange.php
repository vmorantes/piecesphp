<?php

/**
 * DateRange.php
 */

namespace PiecesPHP\Core\DataTransfer\Export;

/**
 * DateRange - Un rango de fechas de un filtro, abierto por cualquiera de los dos lados.
 *
 * @package     PiecesPHP\Core\DataTransfer\Export
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class DateRange
{
    /**
     * @var \DateTimeImmutable|null
     */
    private $from;

    /**
     * @var \DateTimeImmutable|null
     */
    private $to;

    /**
     * @param \DateTimeImmutable|null $from
     * @param \DateTimeImmutable|null $to
     */
    public function __construct(?\DateTimeImmutable $from, ?\DateTimeImmutable $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function from(): ?\DateTimeImmutable
    {
        return $this->from;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function to(): ?\DateTimeImmutable
    {
        return $this->to;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->from === null && $this->to === null;
    }
}
