<?php

/**
 * Column.php
 */

namespace PiecesPHP\Core\DataTransfer\Import;

/**
 * Column - Una columna que la importación declara. Lo que el archivo traiga y no esté declarado se ignora.
 *
 * @package     PiecesPHP\Core\DataTransfer\Import
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class Column
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
     * @var bool
     */
    private $required;

    /**
     * @var string[]
     */
    private $aliases;

    /**
     * @var callable|null
     */
    private $validator;

    /**
     * @param string $key
     * @param string $label
     * @param bool $required
     * @param string[] $aliases
     * @param callable|null $validator fn(?string $value, ParsedRow $row): ?string — null si es válido; si no, el mensaje en texto plano
     */
    public function __construct(string $key, string $label, bool $required = false, array $aliases = [], ?callable $validator = null)
    {
        $this->key = $key;
        $this->label = $label;
        $this->required = $required;
        $this->aliases = array_values(array_filter($aliases, 'is_string'));
        $this->validator = $validator;
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

    /**
     * @return bool
     */
    public function required(): bool
    {
        return $this->required;
    }

    /**
     * @return string[]
     */
    public function aliases(): array
    {
        return $this->aliases;
    }

    /**
     * @param string|null $value
     * @param ParsedRow $row
     * @return string|null
     */
    public function validate(?string $value, ParsedRow $row): ?string
    {
        if ($this->validator === null) {
            return null;
        }
        $result = ($this->validator)($value, $row);
        return is_string($result) ? $result : null;
    }
}
