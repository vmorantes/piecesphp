<?php

/**
 * ExportContext.php
 */

namespace PiecesPHP\Core\DataTransfer\Export;

/**
 * ExportContext - Los filtros ya validados y el usuario que exporta, para columns() y rows().
 *
 * @package     PiecesPHP\Core\DataTransfer\Export
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ExportContext
{
    /**
     * @var array<string,mixed>
     */
    private $values;

    /**
     * @var object|null
     */
    private $user;

    /**
     * @var array<string,ExportParameter>
     */
    private $parameters;

    /**
     * @var string[]|null
     */
    private $selectedColumns;

    /**
     * @param array<string,mixed> $values Por key de parámetro declarado
     * @param object|null $user El usuario del framework que exporta; null en terminal o pruebas
     * @param array<string,ExportParameter> $parameters Los declarados, por key y en su orden
     * @param string[]|null $selectedColumns Keys de la hoja principal en el orden pedido; null: todas
     */
    public function __construct(array $values, ?object $user, array $parameters = [], ?array $selectedColumns = null)
    {
        $this->parameters = $parameters;
        $this->selectedColumns = $selectedColumns;
        $this->values = $values;
        $this->user = $user;
    }

    /**
     * @param string $key
     * @return mixed
     * @throws \InvalidArgumentException si la key no es de un parámetro declarado
     */
    public function get(string $key)
    {
        if (!array_key_exists($key, $this->values)) {
            throw new \InvalidArgumentException("No hay ningún parámetro declarado con la key «{$key}».");
        }
        return $this->values[$key];
    }

    /**
     * @return array<string,mixed>
     */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * @return object|null
     */
    public function user(): ?object
    {
        return $this->user;
    }

    /**
     * Los parámetros declarados, en su orden; vacío si el contexto se construyó sin ellos.
     *
     * @return array<string,ExportParameter>
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    /**
     * Las columnas de la hoja principal que se pidieron, en su orden; null si son todas.
     *
     * @return string[]|null
     */
    public function selectedColumns(): ?array
    {
        return $this->selectedColumns;
    }

    /**
     * @param string $key
     * @return ExportParameter
     * @throws \InvalidArgumentException si no está declarado
     */
    public function parameter(string $key): ExportParameter
    {
        if (!array_key_exists($key, $this->parameters)) {
            throw new \InvalidArgumentException("No hay ningún parámetro declarado con la key «{$key}».");
        }
        return $this->parameters[$key];
    }
}
