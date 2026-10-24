<?php

/**
 * RecoveryPasswordSeeder.php
 */

namespace PiecesPHP\UserSystem\Seeds;

use PiecesPHP\UserSystem\ORM\RecoveryPasswordModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * RecoveryPasswordSeeder.
 *
 * Códigos de recuperación de contraseña VIGENTES de los usuarios sembrados, para Registros › Tokens y códigos (solo
 * enseña los vigentes): vencen en uno o dos días. Valen de verdad, pero solo para esos usuarios, cuyo correo es de
 * `.invalid` y nunca recibe nada.
 *
 * @package     PiecesPHP\UserSystem\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class RecoveryPasswordSeeder extends ModuleSeeder
{

    public function mapper(): string
    {
        return RecoveryPasswordModel::class;
    }

    /**
     * El correo del usuario sembrado, que ya empieza por el prefijo.
     *
     * @return string
     */
    public function markerColumn(): string
    {
        return 'email';
    }

    public function dependsOn(): array
    {
        return [UsersModel::class];
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
        $users = $context->seeded(UsersModel::class);
        $user = $users[$index % max(1, count($users))] ?? [];
        $created = (new \DateTime())->modify('-' . (1 + $index * 3) . ' hours');
        return [
            'email' => (string) ($user['email'] ?? $this->text('usuario', $index) . '@example.invalid'),
            'code' => str_pad((string) ((271828 + $index * 7919) % 1000000), 6, '0', STR_PAD_LEFT),
            'created' => $created,
            'expired' => (new \DateTime())->modify('+' . (1 + $index % 2) . ' days'),
        ];
    }

}
