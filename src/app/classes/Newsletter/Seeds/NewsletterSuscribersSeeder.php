<?php

/**
 * NewsletterSuscribersSeeder.php
 */

namespace Newsletter\Seeds;

use Newsletter\Mappers\NewsletterSuscriberMapper;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * NewsletterSuscribersSeeder.
 *
 * Suscriptores de prueba. El correo usa el dominio reservado `.invalid`, que no se entrega nunca;
 * la mitad acepta recibir novedades, para que el listado muestre los dos estados.
 *
 * @package     Newsletter\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class NewsletterSuscribersSeeder extends ModuleSeeder
{

    public function mapper(): string
    {
        return NewsletterSuscriberMapper::class;
    }

    public function markerColumn(): string
    {
        return 'name';
    }

    public function defaultCount(): int
    {
        return 8;
    }

    /**
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed>
     */
    public function row(int $index, SeedContext $context): array
    {
        return [
            'name' => $this->text('suscriptor', $index),
            'email' => $this->text('suscriptor', $index) . '@example.invalid',
            'acceptUpdates' => $index % 2 === 0 ? NewsletterSuscriberMapper::ACCEPT_UPDATES_YES : NewsletterSuscriberMapper::ACCEPT_UPDATES_NO,
        ];
    }

}
