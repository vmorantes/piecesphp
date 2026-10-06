<?php

/**
 * ExportParameterException.php
 */

namespace PiecesPHP\Core\DataTransfer\Export;

/**
 * ExportParameterException - Filtros inválidos en una exportación: todos los errores juntos, ya traducidos.
 *
 * @package     PiecesPHP\Core\DataTransfer\Export
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class ExportParameterException extends \InvalidArgumentException
{
    /**
     * @var string[]
     */
    private $errors;

    /**
     * @param string[] $errors
     */
    public function __construct(array $errors)
    {
        $this->errors = array_values(array_filter($errors, 'is_string'));
        parent::__construct(implode("\n", $this->errors));
    }

    /**
     * @return string[]
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
