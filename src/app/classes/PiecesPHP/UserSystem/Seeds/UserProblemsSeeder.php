<?php

/**
 * UserProblemsSeeder.php
 */

namespace PiecesPHP\UserSystem\Seeds;

use PiecesPHP\UserSystem\Controllers\UserProblemsController;
use PiecesPHP\UserSystem\ORM\UserProblemsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * UserProblemsSeeder.
 *
 * Códigos VIGENTES de «olvidé mi usuario» y «usuario bloqueado», alternados, de los usuarios sembrados, para Registros ›
 * Tokens y códigos. Como los de recuperación: vencen en uno o dos días y solo valen para esos usuarios.
 *
 * @package     PiecesPHP\UserSystem\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class UserProblemsSeeder extends ModuleSeeder
{

    public function mapper(): string
    {
        return UserProblemsModel::class;
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
     * Desde el final de los usuarios sembrados, para no repetir los de RecoveryPasswordSeeder.
     *
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed>
     */
    public function row(int $index, SeedContext $context): array
    {
        $users = array_reverse($context->seeded(UsersModel::class));
        $user = $users[$index % max(1, count($users))] ?? [];
        return [
            'email' => (string) ($user['email'] ?? $this->text('usuario', $index) . '@example.invalid'),
            'code' => str_pad((string) ((314159 + $index * 6007) % 1000000), 6, '0', STR_PAD_LEFT),
            'created' => (new \DateTime())->modify('-' . (2 + $index * 2) . ' hours'),
            'expired' => (new \DateTime())->modify('+' . (1 + $index % 2) . ' days'),
            'type' => $index % 2 === 0 ? UserProblemsController::TYPE_USER_FORGET : UserProblemsController::TYPE_USER_BLOCKED,
        ];
    }

}
