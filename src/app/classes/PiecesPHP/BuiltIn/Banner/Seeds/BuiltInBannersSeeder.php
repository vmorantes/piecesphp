<?php

/**
 * BuiltInBannersSeeder.php
 */

namespace PiecesPHP\BuiltIn\Banner\Seeds;

use PiecesPHP\BuiltIn\Banner\Mappers\BuiltInBannerMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * BuiltInBannersSeeder.
 *
 * Banners de prueba, con su imagen copiada a una carpeta propia de cada uno. Nacen públicos, como
 * los del panel. Su autor es el usuario principal que ya existe, leído de la base.
 *
 * @package     PiecesPHP\BuiltIn\Banner\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class BuiltInBannersSeeder extends ModuleSeeder
{

    /** Dónde guarda sus archivos el módulo, relativo a src/. */
    const UPLOADS = 'statics/uploads/built-in-banner';

    /** Textos con los que se reparte la columna `content`, de largo distinto. */
    const CONTENTS = [
        'Inscripciones abiertas hasta fin de mes.',
        'Conoce los nuevos servicios en línea y haz tus trámites sin desplazarte.',
        'Consulta el calendario de actividades y reserva tu lugar.',
        'Este fin de semana, jornada de puertas abiertas para toda la familia, con talleres, visitas guiadas y música en vivo.',
    ];

    /**
     * @var int|null El usuario principal, leído una sola vez.
     */
    protected ?int $author = null;

    public function mapper(): string
    {
        return BuiltInBannerMapper::class;
    }

    public function markerColumn(): string
    {
        return 'title';
    }

    public function ownedFolder(): ?array
    {
        return ['base' => self::UPLOADS, 'column' => 'folder'];
    }

    public function defaultCount(): int
    {
        return 4;
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
                throw new \RuntimeException('PARA: no hay un usuario principal en la base para ponerlo de autor de los banners');
            }
        }
        $folder = $this->text('banner', $index);
        return [
            'title' => $this->text('banner', $index),
            'content' => self::CONTENTS[$index % count(self::CONTENTS)],
            'desktopImage' => $context->copyToUploads('statics/images/generic-image-placeholder.png', self::UPLOADS, $folder, 'escritorio.png', false),
            'orderPosition' => $index,
            'folder' => $folder,
            'createdBy' => $this->author,
            'status' => BuiltInBannerMapper::ACTIVE,
        ];
    }

}
