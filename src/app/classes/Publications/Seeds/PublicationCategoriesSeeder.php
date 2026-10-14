<?php

/**
 * PublicationCategoriesSeeder.php
 */

namespace Publications\Seeds;

use Publications\Mappers\PublicationCategoryMapper;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * PublicationCategoriesSeeder.
 *
 * Categorías de publicaciones de prueba. Las publicaciones no se siembran: exigen imágenes, y
 * editar una fila que apunte a una imagen versionada la borraría del repositorio.
 *
 * @package     Publications\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class PublicationCategoriesSeeder extends ModuleSeeder
{

    public function mapper(): string
    {
        return PublicationCategoryMapper::class;
    }

    public function markerColumn(): string
    {
        return 'name';
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
            'name' => $this->text('categoria-publicacion', $index),
        ];
    }

}
