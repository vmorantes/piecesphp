<?php

/**
 * GenericTokenMessageMail.php
 */

namespace PiecesPHP\MailCatalog\Definitions;

use PiecesPHP\MailCatalog\MailCatalogLang;
use PiecesPHP\MailCatalog\MailDefinition;
use PiecesPHP\Tokens\Controllers\HelperController;

/**
 * GenericTokenMessageMail - El mensaje que se escribe desde un enlace con token (GenericTokenController).
 *
 * Datos: `subject`, `message` y, opcional, `renderer`.
 *
 * @package     PiecesPHP\MailCatalog\Definitions
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class GenericTokenMessageMail extends MailDefinition
{

    public function key(): string
    {
        return 'generic-token-message';
    }

    public function name(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'Mensaje desde un enlace con token');
    }

    public function trigger(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'Al enviar el formulario de un enlace con token de un solo uso.');
    }

    public function recipients(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'El remitente configurado en Configuración › Sistema › Correo.');
    }

    public function subject(array $data): string
    {
        return mb_convert_encoding(self::text($data, 'subject'), 'UTF-8');
    }

    public function sampleData(): array
    {
        return ['subject' => 'Comentario sobre el enlace compartido', 'message' => 'El documento del enlace ya no está actualizado; ¿pueden enviar la versión nueva?'];
    }

    public function render(array $data): string
    {
        //Sin `url` ni `text_button`: la plantilla pinta el botón si `url` es una cadena, y con '' salía un botón vacío que
        //llevaba a ninguna parte también en el envío real (#2449).
        return (string) self::renderer($data, fn() => new HelperController(null, []))->render('mailing/template_base', [
            'text' => htmlspecialchars(mb_convert_encoding(self::text($data, 'message'), 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'note' => '',
        ], false, false);
    }
}
