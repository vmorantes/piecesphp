<?php

/**
 * CitiesSeeder.php
 */

namespace PiecesPHP\App\Locations\Seeds;

use PiecesPHP\App\Locations\Mappers\CityMapper;
use PiecesPHP\App\Locations\Mappers\StateMapper;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * CitiesSeeder.
 *
 * Ciudades de prueba, repartidas entre las regiones sembradas.
 *
 * @package     PiecesPHP\App\Locations\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class CitiesSeeder extends ModuleSeeder
{

    public function mapper(): string
    {
        return CityMapper::class;
    }

    public function markerColumn(): string
    {
        return 'name';
    }

    public function dependsOn(): array
    {
        return [StateMapper::class];
    }

    public function defaultCount(): int
    {
        return 4;
    }

    /**
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed>
     */
    public function row(int $index, SeedContext $context): array
    {
        return [
            'state' => $context->reference(StateMapper::class, $index),
            'name' => $this->text('ciudad', $index),
            'active' => CityMapper::ACTIVE,
        ];
    }

}
