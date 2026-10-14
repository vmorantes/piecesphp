<?php

/**
 * UsersSeeder.php
 */

namespace PiecesPHP\UserSystem\Seeds;

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * UsersSeeder.
 *
 * Usuarios de prueba, uno por cada tipo salvo el principal, repartidos entre las organizaciones
 * sembradas. El correo usa el dominio reservado `.invalid`, que no se entrega nunca. Su clave es
 * aleatoria y no se guarda: nadie entra con ellos.
 *
 * @package     PiecesPHP\UserSystem\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class UsersSeeder extends ModuleSeeder
{

    public function mapper(): string
    {
        return UsersModel::class;
    }

    public function markerColumn(): string
    {
        return 'username';
    }

    public function dependsOn(): array
    {
        return [OrganizationMapper::class];
    }

    /**
     * Cada arranque de la aplicación crea el perfil que le falte a un usuario de
     * TYPES_USER_SHOULD_HAVE_PROFILE (`ProfileTasksUtilities::generateMissingProfiles()`), y su fila
     * de aprobación (`SystemApprovalManager::init()`), que apunta por número de id y por tabla.
     *
     * @return array<string,string|array{column: string, where: array<string,string|int>}>
     */
    public function ownedRows(): array
    {
        return [
            UserProfileMapper::TABLE => 'belongsTo',
            SystemApprovalsMapper::TABLE => ['column' => 'referenceValue', 'where' => ['referenceTable' => UsersModel::TABLE]],
        ];
    }

    public function defaultCount(): int
    {
        return count(self::types());
    }

    /**
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed>
     */
    public function row(int $index, SeedContext $context): array
    {
        $types = self::types();
        $now = date('Y-m-d H:i:s');
        return [
            'username' => $this->text('usuario', $index),
            'password' => $context->passwordHash(),
            'firstname' => $this->text('nombre', $index),
            'firstLastname' => $this->text('apellido', $index),
            'email' => $this->text('usuario', $index) . '@example.invalid',
            'type' => $types[$index % count($types)],
            'organization' => $context->reference(OrganizationMapper::class, $index),
            'status' => UsersModel::STATUS_USER_ACTIVE,
            'failedAttempts' => 0,
            'createdAt' => $now,
            'modifiedAt' => $now,
        ];
    }

    /**
     * Los tipos de usuario que se siembran: todos los de TYPES_USERS menos el principal.
     *
     * @return int[]
     */
    protected static function types(): array
    {
        return array_values(array_filter(array_map('intval', array_keys(UsersModel::TYPES_USERS)), static fn (int $type): bool => $type !== UsersModel::TYPE_USER_ROOT));
    }

}
