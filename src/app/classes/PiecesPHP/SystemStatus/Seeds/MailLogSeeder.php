<?php

/**
 * MailLogSeeder.php
 */

namespace PiecesPHP\SystemStatus\Seeds;

use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * MailLogSeeder.
 *
 * Correos para Registros › Correos enviados: entregados, en la bandeja local y fallidos, a direcciones `.invalid`.
 * No se envía nada: es solo el registro.
 *
 * - Ninguno es de las últimas 24 horas: un fallo dentro de `MailLogMapper::ALERT_WINDOW_HOURS` enciende el aviso de
 *   «Entrega del correo» a todo el panel.
 * - Sin cuerpo (la columna admite nulo): el cuerpo va cifrado y el mapper no expone cómo cifrarlo.
 *
 * @package     PiecesPHP\SystemStatus\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class MailLogSeeder extends ModuleSeeder
{

    const MAILS = [
        ['Recupera tu contraseña', 'RecoveryPasswordController::sendCode'],
        ['Tu código de acceso', 'UserSystemFeaturesController::generateOTP'],
        ['Bienvenida a la plataforma', 'UsersController::register'],
        ['Tienes una publicación por aprobar', 'SystemApprovalsController::notify'],
        ['Tu organización fue aprobada', 'OrganizationApprovalHandler::onApproved'],
    ];

    public function mapper(): string
    {
        return MailLogMapper::class;
    }

    /**
     * El destinatario: una dirección sembrada, con el prefijo.
     *
     * @return string
     */
    public function markerColumn(): string
    {
        return 'recipients';
    }

    public function defaultCount(): int
    {
        return 16;
    }

    /**
     * Uno de cada cuatro falla; de los demás, uno de cada tres se queda en la bandeja local.
     *
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed>
     */
    public function row(int $index, SeedContext $context): array
    {
        [$subject, $origin] = self::MAILS[$index % count(self::MAILS)];
        $failed = $index % 4 === 3;
        $outbox = !$failed && $index % 3 === 1;
        return [
            'sentAt' => (new \DateTime('today'))->modify('-' . (2 + ($index * 5) % 28) . ' days')->setTime(6 + ($index * 3) % 15, ($index * 19) % 60),
            'recipients' => $this->text('destinatario', $index) . '@example.invalid',
            'subject' => $subject,
            'origin' => $origin,
            'delivery' => $outbox ? MailDelivery::SINK : MailDelivery::REAL,
            'result' => $failed ? MailLogMapper::RESULT_FAILED : ($outbox ? MailLogMapper::RESULT_OUTBOX : MailLogMapper::RESULT_DELIVERED),
            'reason' => $failed ? 'SMTP Error: Could not connect to SMTP host.' : null,
            'body' => null,
        ];
    }

}
