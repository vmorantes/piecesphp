<?php

/**
 * SupportTicketMail.php
 */

namespace PiecesPHP\MailCatalog\Definitions;

use PiecesPHP\Core\Config;
use PiecesPHP\MailCatalog\MailCatalogLang;
use PiecesPHP\MailCatalog\MailDefinition;

/**
 * SupportTicketMail - El ticket de soporte que se escribe desde el panel.
 *
 * Datos: `subject` y `comments`, tal como los escribe el usuario.
 *
 * @package     PiecesPHP\MailCatalog\Definitions
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SupportTicketMail extends MailDefinition
{

    public function key(): string
    {
        return 'support-ticket';
    }

    public function name(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'Ticket de soporte');
    }

    public function trigger(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'Al enviar el formulario de soporte del panel.');
    }

    public function recipients(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'Las direcciones de «support_form_recipients»; la respuesta va a quien escribe.');
    }

    public function subject(array $data): string
    {
        return self::text($data, 'subject') . ' - ' . Config::app_title();
    }

    public function sampleData(): array
    {
        return ['subject' => 'No puedo subir un documento', 'comments' => "Al subir un PDF de 3 MB la pantalla se queda cargando.\nLo probé dos veces."];
    }

    public function render(array $data): string
    {
        //Lo que escribe el usuario va escapado: el HTML del correo no ejecuta nada suyo.
        return nl2br(escape_html(self::text($data, 'comments')));
    }
}
