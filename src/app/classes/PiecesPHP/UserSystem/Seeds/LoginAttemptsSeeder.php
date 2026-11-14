<?php

/**
 * LoginAttemptsSeeder.php
 */

namespace PiecesPHP\UserSystem\Seeds;

use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * LoginAttemptsSeeder.
 *
 * Intentos de ingreso para Registros › Accesos: de los usuarios sembrados, unos entran y otros fallan la contraseña, y
 * algunos son de un usuario que no existe. Repartidos en los últimos 30 días, ninguno de hoy.
 *
 * Los fallos no pueden bloquear a nadie: `OTPRateLimiter` cuenta los fallos de la última ventana (minutos) por usuario
 * o IP, y aquí el más reciente es de ayer y las IP son de documentación (203.0.113.0/24, RFC 5737).
 *
 * @package     PiecesPHP\UserSystem\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class LoginAttemptsSeeder extends ModuleSeeder
{

    const USER_AGENTS = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Safari/605.1.15',
        'Mozilla/5.0 (X11; Linux x86_64; rv:143.0) Gecko/20100101 Firefox/143.0',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148',
    ];

    public function mapper(): string
    {
        return LoginAttemptsModel::class;
    }

    /**
     * El usuario del intento: el de un usuario sembrado ya lleva el prefijo, y el de uno que no existe se escribe con él.
     *
     * @return string
     */
    public function markerColumn(): string
    {
        return 'usernameAttempt';
    }

    public function dependsOn(): array
    {
        return [UsersModel::class];
    }

    public function defaultCount(): int
    {
        return 20;
    }

    /**
     * Tres de cada cuatro, de un usuario sembrado (dos entran, uno falla la contraseña); el cuarto, de uno que no existe.
     *
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed>
     */
    public function row(int $index, SeedContext $context): array
    {
        $users = $context->seeded(UsersModel::class);
        $user = $users[$index % max(1, count($users))] ?? [];
        $kind = $index % 4;
        $exists = $kind !== 3;
        $success = $kind === 0 || $kind === 2;
        $date = (new \DateTime('today'))->modify('-' . (1 + ($index * 7) % 29) . ' days')->setTime(7 + ($index * 5) % 12, ($index * 13) % 60);
        return [
            'userID' => $exists ? $context->reference(UsersModel::class, $index) : null,
            'usernameAttempt' => $exists ? (string) ($user['username'] ?? $this->text('usuario', $index)) : $this->text('intento', $index),
            'success' => $success ? LoginAttemptsModel::SUCCESS_ATTEMPT : LoginAttemptsModel::FAIL_ATTEMPT,
            'ip' => '203.0.113.' . (10 + $index % 40),
            'ipGroup' => OTPRateLimiter::ipGroup('203.0.113.' . (10 + $index % 40)),
            'message' => $success ? '' : ($exists ? 'La contraseña es incorrecta.' : 'El usuario no existe.'),
            'date' => $date,
            'extraData' => [
                'dimensions' => ['w' => $index % 3 === 0 ? 390 : 1920, 'h' => $index % 3 === 0 ? 844 : 1080],
                'userAgent' => self::USER_AGENTS[$index % count(self::USER_AGENTS)],
            ],
        ];
    }

}
