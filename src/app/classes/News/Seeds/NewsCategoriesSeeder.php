<?php

/**
 * NewsCategoriesSeeder.php
 */

namespace News\Seeds;

use News\Mappers\NewsCategoryMapper;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * NewsCategoriesSeeder.
 *
 * Categorías de noticias de prueba. El icono es el que el módulo trae por defecto: una imagen
 * sembrada propia tendría que limpiarse aparte.
 *
 * @package     News\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class NewsCategoriesSeeder extends ModuleSeeder
{

    public function mapper(): string
    {
        return NewsCategoryMapper::class;
    }

    /**
     * Un color por categoría sembrada, para distinguirlas en el panel.
     */
    const COLORS = ['#3558A2', '#2E8B57', '#C0392B', '#D68910', '#7D3C98'];

    public function markerColumn(): string
    {
        return 'name';
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
            'name' => $this->text('categoria-noticia', $index),
            'iconImage' => NewsCategoryMapper::DEFAULT_ICON,
            'color' => self::COLORS[$index % count(self::COLORS)],
        ];
    }

}
