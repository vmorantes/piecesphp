<?php

/**
 * UserSessionsSeeder.php
 */

namespace PiecesPHP\UserSystem\Seeds;

use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UserSessionsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * UserSessionsSeeder.
 *
 * Sesiones para Registros › Sesiones abiertas: de los usuarios sembrados, dos de cada tres abiertas y una cerrada (por
 * el usuario, por un administrador o por salir), con su IP de documentación (RFC 5737) y su navegador.
 *
 * Ninguna se puede usar: una sesión se busca por el hash de su `sid`, y `zz-siembra-sesion-N` no es el hash de nada.
 *
 * @package     PiecesPHP\UserSystem\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class UserSessionsSeeder extends ModuleSeeder
{

    const CLOSE_REASONS = [
        UserSessions::REASON_LOGOUT,
        UserSessions::REASON_CLOSED_BY_USER,
        UserSessions::REASON_CLOSED_BY_ADMIN,
    ];

    /**
     * @var int|null El usuario principal, para las cerradas por un administrador.
     */
    protected ?int $admin = null;

    public function mapper(): string
    {
        return UserSessionsModel::class;
    }

    /**
     * El hash de la sesión: no se pinta, y con el prefijo no puede coincidir con el de una sesión de verdad.
     *
     * @return string
     */
    public function markerColumn(): string
    {
        return 'sessionHash';
    }

    public function dependsOn(): array
    {
        return [UsersModel::class];
    }

    public function defaultCount(): int
    {
        return 12;
    }

    /**
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed>
     */
    public function row(int $index, SeedContext $context): array
    {
        if ($this->admin === null) {
            $this->admin = $context->existingUserId(UsersModel::TYPE_USER_ROOT);
        }
        $userID = $context->reference(UsersModel::class, $index);
        $open = $index % 3 !== 2;
        $createdAt = (new \DateTime('today'))->modify('-' . ($open ? $index % 5 : 3 + ($index * 4) % 25) . ' days')->setTime(8 + $index % 10, ($index * 17) % 60);
        $lastActivityAt = (clone $createdAt)->modify('+' . (1 + $index % 6) . ' hours');
        $reason = self::CLOSE_REASONS[intdiv($index, 3) % count(self::CLOSE_REASONS)];
        return [
            'userID' => $userID,
            'sessionHash' => $this->text('sesion', $index),
            'createdAt' => $createdAt,
            'lastActivityAt' => $lastActivityAt,
            'expiresAt' => $open ? (new \DateTime())->modify('+' . (2 + $index % 6) . ' days') : (clone $createdAt)->modify('+7 days'),
            'ip' => '203.0.113.' . (60 + $index % 40),
            'userAgent' => LoginAttemptsSeeder::USER_AGENTS[$index % count(LoginAttemptsSeeder::USER_AGENTS)],
            'closedAt' => $open ? null : $lastActivityAt,
            'closedBy' => $open ? null : ($reason === UserSessions::REASON_CLOSED_BY_ADMIN ? $this->admin : $userID),
            'closeReason' => $open ? null : $reason,
        ];
    }

}
