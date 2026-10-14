<?php

/**
 * OrganizationsSeeder.php
 */

namespace Organizations\Seeds;

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * OrganizationsSeeder.
 *
 * Organizaciones de prueba. Su autor (`createdBy`, obligatorio) es el usuario principal que ya
 * existe, LEÍDO de la base: los usuarios sembrados dependen de las organizaciones, así que
 * declararlos como autores cerraría un ciclo.
 *
 * @package     Organizations\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class OrganizationsSeeder extends ModuleSeeder
{

    /**
     * @var int|null El usuario principal, leído una sola vez.
     */
    protected ?int $author = null;

    public function mapper(): string
    {
        return OrganizationMapper::class;
    }

    public function markerColumn(): string
    {
        return 'name';
    }

    /**
     * Cada arranque crea su fila de aprobación (`SystemApprovalManager::init()`), que apunta por
     * número de id y por tabla.
     *
     * @return array<string,string|array{column: string, where: array<string,string|int>}>
     */
    public function ownedRows(): array
    {
        return [SystemApprovalsMapper::TABLE => ['column' => 'referenceValue', 'where' => ['referenceTable' => OrganizationMapper::TABLE]]];
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
                throw new \RuntimeException('PARA: no hay un usuario principal en la base para ponerlo de autor de las organizaciones');
            }
        }
        return [
            'name' => $this->text('organizacion', $index),
            'nit' => $this->text('nit', $index),
            'folder' => $this->text('organizacion', $index),
            'createdBy' => $this->author,
            'createdAt' => date('Y-m-d H:i:s'),
            'status' => OrganizationMapper::ACTIVE,
        ];
    }

}
