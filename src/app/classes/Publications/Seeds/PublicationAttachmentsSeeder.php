<?php

/**
 * PublicationAttachmentsSeeder.php
 */

namespace Publications\Seeds;

use PiecesPHP\Core\Config;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Publications\Mappers\AttachmentPublicationMapper;
use Publications\Mappers\PublicationMapper;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * PublicationAttachmentsSeeder.
 *
 * Adjuntos de prueba, repartidos entre las publicaciones sembradas. Su archivo va dentro de la
 * carpeta de SU publicación, en `attachments/`, como en un alta del panel: no tiene carpeta propia,
 * y la borra el limpiado de la publicación, que va después.
 *
 * @package     Publications\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class PublicationAttachmentsSeeder extends ModuleSeeder
{

    /** El archivo que se copia. */
    const FILE = 'statics/images/attachment-placeholder.png';

    /**
     * @var int|null El usuario principal, leído una sola vez.
     */
    protected ?int $author = null;

    public function mapper(): string
    {
        return AttachmentPublicationMapper::class;
    }

    public function markerColumn(): string
    {
        return 'attachmentName';
    }

    public function dependsOn(): array
    {
        return [PublicationMapper::class];
    }

    public function defaultCount(): int
    {
        return 6;
    }

    /**
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed>
     * @throws \RuntimeException Si no hay publicaciones sembradas o usuario principal.
     */
    public function row(int $index, SeedContext $context): array
    {
        if ($this->author === null) {
            $this->author = $context->existingUserId(UsersModel::TYPE_USER_ROOT);
            if ($this->author === null) {
                throw new \RuntimeException('PARA: no hay un usuario principal en la base para ponerlo de autor de los adjuntos');
            }
        }
        $publications = $context->seeded(PublicationMapper::class);
        if (count($publications) === 0) {
            throw new \RuntimeException('PARA: no hay publicaciones sembradas a las que adjuntar');
        }
        //reference() reparte con el mismo índice: el id y la carpeta son de la MISMA publicación.
        $publication = $publications[$index % count($publications)];
        $folder = (is_string($publication['folder'] ?? null) ? $publication['folder'] : '') . '/attachments';
        return [
            'publication' => $context->reference(PublicationMapper::class, $index),
            'attachmentName' => $this->text('adjunto', $index),
            'fileLocation' => $context->copyToUploads(self::FILE, PublicationsSeeder::UPLOADS, $folder, $this->text('adjunto', $index) . '.png', true),
            'lang' => Config::get_default_lang(),
            'folder' => $folder,
            'createdBy' => $this->author,
        ];
    }

}
