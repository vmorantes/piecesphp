<?php

/**
 * GeoFilter.php
 */

namespace PiecesPHP\Geo;

/**
 * GeoFilter - Un filtro que declara una fuente: el panel de filtros se construye con los de las fuentes visibles.
 *
 * @package     PiecesPHP\Geo
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class GeoFilter
{
    const TYPE_TEXT = 'text';
    const TYPE_OPTIONS = 'options';
    /**
     * Opciones que se buscan al escribir, sin ninguna en la página: las da GeoSource::filterOptions() por la ruta
     * geo-admin-filter-options. Para listas que pueden ser largas.
     */
    const TYPE_REMOTE_OPTIONS = 'remoteOptions';
    const TYPE_DATE_RANGE = 'dateRange';

    const TYPES = [
        self::TYPE_TEXT,
        self::TYPE_OPTIONS,
        self::TYPE_REMOTE_OPTIONS,
        self::TYPE_DATE_RANGE,
    ];

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array<string|int,string>
     */
    private $options;

    /**
     * @param string $key
     * @param string $label Ya traducida
     * @param string $type GeoFilter::TYPE_*
     * @param array<string|int,string> $options Clave → etiqueta; solo con TYPE_OPTIONS
     * @throws \InvalidArgumentException si el tipo no existe, o si trae opciones sin ser de opciones
     */
    public function __construct(string $key, string $label, string $type, array $options = [])
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException("El filtro «{$key}» tiene un tipo que no existe: «{$type}».");
        }
        if ($type !== self::TYPE_OPTIONS && count($options) > 0) {
            throw new \InvalidArgumentException("El filtro «{$key}» no es de opciones y trae opciones.");
        }
        $this->key = $key;
        $this->label = $label;
        $this->type = $type;
        $this->options = $options;
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
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return array<string|int,string>
     */
    public function options(): array
    {
        return $this->options;
    }
}
