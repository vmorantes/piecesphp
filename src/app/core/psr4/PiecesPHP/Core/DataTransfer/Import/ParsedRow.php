<?php

/**
 * ParsedRow.php
 */

namespace PiecesPHP\Core\DataTransfer\Import;

/**
 * ParsedRow - Una fila ya leída, con los valores por key de columna. null es «ausente».
 *
 * @package     PiecesPHP\Core\DataTransfer\Import
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ParsedRow
{
    /**
     * @var int
     */
    private $position;

    /**
     * @var array<string,string|null>
     */
    private $values;

    /**
     * @param int $position 1 = primera fila de datos
     * @param array<string,string|null> $values
     */
    public function __construct(int $position, array $values)
    {
        $this->position = $position;
        $this->values = $values;
    }

    /**
     * @return int
     */
    public function position(): int
    {
        return $this->position;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        return $this->values[$key] ?? null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return ($this->values[$key] ?? null) !== null;
    }

    /**
     * @return array<string,string|null>
     */
    public function values(): array
    {
        return $this->values;
    }
}
