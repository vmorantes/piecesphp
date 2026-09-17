<?php

/**
 * RowResult.php
 */

namespace PiecesPHP\Core\DataTransfer\Import;

/**
 * RowResult - Los errores de una fila. Sin errores, la fila es válida.
 *
 * @package     PiecesPHP\Core\DataTransfer\Import
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class RowResult
{
    /**
     * @var int
     */
    private $position;

    /**
     * @var string[]
     */
    private $errors;

    /**
     * @param int $position
     * @param string[] $errors
     */
    public function __construct(int $position, array $errors = [])
    {
        $this->position = $position;
        $this->errors = array_values($errors);
    }

    /**
     * @return int
     */
    public function position(): int
    {
        return $this->position;
    }

    /**
     * @return string[]
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return count($this->errors) === 0;
    }
}
