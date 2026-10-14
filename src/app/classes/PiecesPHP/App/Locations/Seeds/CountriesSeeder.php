<?php

/**
 * CountriesSeeder.php
 */

namespace PiecesPHP\App\Locations\Seeds;

use PiecesPHP\App\Locations\Mappers\CountryMapper;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * CountriesSeeder.
 *
 * Países de prueba: la raíz de la cadena país, región, ciudad y punto.
 *
 * @package     PiecesPHP\App\Locations\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class CountriesSeeder extends ModuleSeeder
{

    /** Regiones con las que se reparte la columna `region`. */
    const REGIONS = [
        'América del Sur',
        'Centroamérica y el Caribe',
    ];

    public function mapper(): string
    {
        return CountryMapper::class;
    }

    public function markerColumn(): string
    {
        return 'name';
    }

    public function defaultCount(): int
    {
        return 2;
    }

    /**
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed>
     */
    public function row(int $index, SeedContext $context): array
    {
        return [
            'name' => $this->text('pais', $index),
            'region' => self::REGIONS[$index % count(self::REGIONS)],
            'active' => CountryMapper::ACTIVE,
        ];
    }

}
