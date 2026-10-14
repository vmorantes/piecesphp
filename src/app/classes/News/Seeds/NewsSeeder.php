<?php

/**
 * NewsSeeder.php
 */

namespace News\Seeds;

use News\Mappers\NewsCategoryMapper;
use News\Mappers\NewsMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * NewsSeeder.
 *
 * Noticias de prueba, bastantes para que el listado pagine. El cuerpo tiene un número de párrafos
 * distinto en cada fila, porque lo que se mide es cómo llena la pantalla. Su autor es el usuario
 * principal que ya existe, leído de la base, como en `OrganizationsSeeder`.
 *
 * @package     News\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class NewsSeeder extends ModuleSeeder
{

    /** Párrafos con los que se arma el cuerpo, de largo distinto. */
    const PARAGRAPHS = [
        'La jornada de puertas abiertas reunió a más de doscientas personas, que recorrieron las instalaciones y conversaron con los equipos de cada área.',
        'El nuevo horario de atención empieza el lunes. Las consultas presenciales se atenderán de ocho de la mañana a cuatro de la tarde, y las demás seguirán llegando por el formulario de contacto.',
        'Se abrió la convocatoria para el programa de formación del segundo semestre.',
        'El informe anual recoge los avances en los tres frentes de trabajo: la atención a los usuarios, la mejora de los procesos internos y la ampliación de la red de aliados. Cada capítulo incluye las cifras del periodo y los retos para el próximo año.',
        'Agradecemos a quienes participaron en la encuesta de satisfacción. Sus respuestas ya están orientando los cambios que verán en las próximas semanas.',
    ];

    /**
     * @var int|null El usuario principal, leído una sola vez.
     */
    protected ?int $author = null;

    public function mapper(): string
    {
        return NewsMapper::class;
    }

    public function markerColumn(): string
    {
        return 'newsTitle';
    }

    public function dependsOn(): array
    {
        return [NewsCategoryMapper::class];
    }

    public function defaultCount(): int
    {
        return 12;
    }

    /**
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed>
     * @throws \RuntimeException Si no hay un usuario principal que poner de autor.
     */
    public function row(int $index, SeedContext $context): array
    {
        if ($this->author === null) {
            $this->author = $context->existingUserId(UsersModel::TYPE_USER_ROOT);
            if ($this->author === null) {
                throw new \RuntimeException('PARA: no hay un usuario principal en la base para ponerlo de autor de las noticias');
            }
        }
        $paragraphs = [];
        for ($i = 0; $i <= $index % 4; $i++) {
            $paragraphs[] = '<p>' . self::PARAGRAPHS[($index + $i) % count(self::PARAGRAPHS)] . '</p>';
        }
        return [
            'newsTitle' => $this->text('noticia', $index),
            'content' => implode("\n", $paragraphs),
            'category' => $context->reference(NewsCategoryMapper::class, $index),
            'folder' => $this->text('noticia', $index),
            'createdBy' => $this->author,
            'status' => NewsMapper::ACTIVE,
        ];
    }

}
