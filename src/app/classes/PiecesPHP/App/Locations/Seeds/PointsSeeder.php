<?php

/**
 * PointsSeeder.php
 */

namespace PiecesPHP\App\Locations\Seeds;

use PiecesPHP\App\Locations\Mappers\CityMapper;
use PiecesPHP\App\Locations\Mappers\PointMapper;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * PointsSeeder.
 *
 * Puntos de prueba, repartidos entre las ciudades sembradas. Las coordenadas caen en tierra
 * firme alrededor de un mismo centro y son distintas en cada fila, para que un mapa las separe.
 *
 * @package     PiecesPHP\App\Locations\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class PointsSeeder extends ModuleSeeder
{

    /** Direcciones con las que se reparte la columna `address`, de largo distinto. */
    const ADDRESSES = [
        'Calle 12 # 4-56',
        'Carrera 7 # 32-10, local 2',
        'Avenida Jiménez # 9-43, edificio Monserrate, piso 3',
        'Calle 45 # 13-21',
        'Transversal 18 # 100-05, oficina 401, frente al parque principal',
        'Diagonal 22 # 8-30, segundo piso',
    ];

    public function mapper(): string
    {
        return PointMapper::class;
    }

    public function markerColumn(): string
    {
        return 'name';
    }

    public function dependsOn(): array
    {
        return [CityMapper::class];
    }

    public function defaultCount(): int
    {
        return 6;
    }

    /**
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed>
     */
    public function row(int $index, SeedContext $context): array
    {
        return [
            'city' => $context->reference(CityMapper::class, $index),
            'name' => $this->text('punto', $index),
            'address' => self::ADDRESSES[$index % count(self::ADDRESSES)],
            'latitude' => round(4.6097 + $index * 0.0137, 6),
            'longitude' => round(-74.0817 - $index * 0.0109, 6),
            'active' => PointMapper::ACTIVE,
        ];
    }

}
