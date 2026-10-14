<?php

/**
 * StatesSeeder.php
 */

namespace PiecesPHP\App\Locations\Seeds;

use PiecesPHP\App\Locations\Mappers\CountryMapper;
use PiecesPHP\App\Locations\Mappers\StateMapper;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * StatesSeeder.
 *
 * Regiones de prueba, repartidas entre los países sembrados.
 *
 * @package     PiecesPHP\App\Locations\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class StatesSeeder extends ModuleSeeder
{

    public function mapper(): string
    {
        return StateMapper::class;
    }

    public function markerColumn(): string
    {
        return 'name';
    }

    public function dependsOn(): array
    {
        return [CountryMapper::class];
    }

    public function defaultCount(): int
    {
        return 3;
    }

    /**
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed>
     */
    public function row(int $index, SeedContext $context): array
    {
        return [
            'country' => $context->reference(CountryMapper::class, $index),
            'name' => $this->text('region', $index),
            'active' => StateMapper::ACTIVE,
        ];
    }

}
