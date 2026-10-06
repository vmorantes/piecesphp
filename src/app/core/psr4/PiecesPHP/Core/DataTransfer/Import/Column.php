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
     * @var string|null
     */
    private $help = null;

    /**
     * @var string|null
     */
    private $example = null;

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
     * Ayuda para quien rellena el archivo: sale en el formulario y como comentario en la plantilla.
     *
     * @param string $text
     * @return static
     */
    public function help(string $text): static
    {
        $this->help = $text;
        return $this;
    }

    /**
     * Un valor de ejemplo: sale en el formulario y en el comentario de la plantilla, nunca como fila.
     *
     * @param string $value
     * @return static
     */
    public function example(string $value): static
    {
        $this->example = $value;
        return $this;
    }

    /**
     * @return string|null
     */
    public function helpText(): ?string
    {
        return $this->help;
    }

    /**
     * @return string|null
     */
    public function exampleValue(): ?string
    {
        return $this->example;
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
