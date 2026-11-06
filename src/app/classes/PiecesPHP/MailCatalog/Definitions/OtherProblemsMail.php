<?php

/**
 * OtherProblemsMail.php
 */

namespace PiecesPHP\MailCatalog\Definitions;

use PiecesPHP\Core\Config;
use PiecesPHP\MailCatalog\MailCatalogLang;
use PiecesPHP\MailCatalog\MailDefinition;
use PiecesPHP\UserSystem\Controllers\UserProblemsController;

/**
 * OtherProblemsMail - «Otros problemas» de acceso, cuando osTicket no lo recibe.
 *
 * Datos: `email`, `name`, `message`, `extra` (array|null; su `subject`, si lo trae, sustituye al genérico) y, opcional,
 * `renderer`.
 *
 * @package     PiecesPHP\MailCatalog\Definitions
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class OtherProblemsMail extends MailDefinition
{

    public function key(): string
    {
        return 'user-other-problems';
    }

    public function name(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'Otros problemas de acceso');
    }

    public function trigger(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'Al enviar «Otros problemas» desde la ayuda para ingresar, si osTicket no lo recibe.');
    }

    public function recipients(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'Las direcciones de «other_problems_recipients»; la respuesta va a quien escribe.');
    }

    public function subject(array $data): string
    {
        $extra = $data['extra'] ?? null;
        $customSubject = is_array($extra) && isset($extra['subject']) && is_string($extra['subject']) && mb_strlen(trim($extra['subject'])) > 0 ? trim($extra['subject']) : null;
        return ($customSubject ?? __(UserProblemsController::LANG_GROUP, 'Ticket genérico')) . ' - ' . Config::app_title();
    }

    public function sampleData(): array
    {
        return [
            'email' => 'laura.gomez@example.com',
            'name' => 'Laura Gómez',
            'message' => 'No consigo entrar desde el móvil: la página se queda en blanco después de escribir la contraseña.',
            'extra' => [['display' => 'Navegador', 'text' => 'Firefox 140 en Android']],
        ];
    }

    public function render(array $data): string
    {
        $extra = $data['extra'] ?? null;
        return (string) self::renderer($data, fn() => self::viewRenderer(self::userSystemViews()))->render('usuarios/mail/other-problems', [
            'originURL' => baseurl(),
            'subject' => $this->subject($data),
            'mail' => self::text($data, 'email'),
            'name' => self::text($data, 'name'),
            'message' => self::text($data, 'message'),
            'extra' => is_array($extra) ? $extra : null,
        ], false);
    }
}
