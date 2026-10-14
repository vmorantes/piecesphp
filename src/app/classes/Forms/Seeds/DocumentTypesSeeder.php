<?php

/**
 * DocumentTypesSeeder.php
 */

namespace Forms\Seeds;

use Forms\DocumentTypes\Mappers\DocumentTypesMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * DocumentTypesSeeder.
 *
 * Tipos de documento de prueba. Su autor es el usuario principal que ya existe, leído de la base,
 * como en `OrganizationsSeeder`.
 *
 * @package     Forms\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class DocumentTypesSeeder extends ModuleSeeder
{

    /**
     * @var int|null El usuario principal, leído una sola vez.
     */
    protected ?int $author = null;

    public function mapper(): string
    {
        return DocumentTypesMapper::class;
    }

    public function markerColumn(): string
    {
        return 'documentTypeName';
    }

    public function defaultCount(): int
    {
        return 3;
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
                throw new \RuntimeException('PARA: no hay un usuario principal en la base para ponerlo de autor de los tipos de documento');
            }
        }
        return [
            'documentTypeName' => $this->text('tipo-documento', $index),
            'folder' => $this->text('tipo-documento', $index),
            'status' => DocumentTypesMapper::STATUS_ACTIVE,
            'createdBy' => $this->author,
        ];
    }

}
