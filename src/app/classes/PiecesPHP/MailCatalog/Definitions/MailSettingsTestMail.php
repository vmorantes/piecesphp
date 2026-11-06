<?php

/**
 * MailSettingsTestMail.php
 */

namespace PiecesPHP\MailCatalog\Definitions;

use PiecesPHP\MailCatalog\MailCatalogLang;
use PiecesPHP\MailCatalog\MailDefinition;
use PiecesPHP\Settings\Mail\MailTester;

/**
 * MailSettingsTestMail - El correo de prueba de Configuración › Sistema › Correo (MailTester::send()).
 *
 * Datos: `app` (el título de la aplicación) y `date` (AAAA-MM-DD HH:MM:SS).
 *
 * @package     PiecesPHP\MailCatalog\Definitions
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class MailSettingsTestMail extends MailDefinition
{

    public function key(): string
    {
        return 'mail-settings-test';
    }

    public function name(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'Correo de prueba de la configuración');
    }

    public function trigger(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'Al pulsar «Enviar correo de prueba» en Configuración › Sistema › Correo.');
    }

    public function recipients(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'La dirección que se escribe en esa prueba.');
    }

    public function subject(array $data): string
    {
        return sprintf(__(MailTester::LANG_GROUP, 'Correo de prueba de %s'), self::text($data, 'app'));
    }

    public function sampleData(): array
    {
        return ['app' => (string) get_config('title_app'), 'date' => date('Y-m-d H:i:s')];
    }

    public function render(array $data): string
    {
        return '<p>' . escape_html(sprintf(__(MailTester::LANG_GROUP, 'Si lees esto, el correo sale de %s. Lo envió la prueba de Configuración › Sistema › Correo el %s.'), self::text($data, 'app'), self::text($data, 'date'))) . '</p>';
    }
}
