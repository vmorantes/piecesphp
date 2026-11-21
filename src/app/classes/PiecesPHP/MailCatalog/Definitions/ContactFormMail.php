<?php

/**
 * ContactFormMail.php
 */

namespace PiecesPHP\MailCatalog\Definitions;

use PiecesPHP\Core\Config;
use PiecesPHP\MailCatalog\MailCatalogLang;
use PiecesPHP\MailCatalog\MailDefinition;

/**
 * ContactFormMail - El formulario de contacto de la zona pública.
 *
 * Datos: `name`, `email`, `subject` (el que escribe la persona), `message`, `updates` (bool), `titleSuffix` (lo que
 * get_title() daba en el envío) y, opcionales, `extraFields` (lista de `label`/`value` sin escapar, de
 * ContactFormsController::extraFields()) y `renderer`.
 *
 * @package     PiecesPHP\MailCatalog\Definitions
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class ContactFormMail extends MailDefinition
{

    public function key(): string
    {
        return 'contact-form';
    }

    public function name(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'Formulario de contacto');
    }

    public function trigger(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'Al enviar el formulario de contacto de la zona pública.');
    }

    public function recipients(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'Las direcciones de «contact_form_recipients»; la respuesta va a quien escribe.');
    }

    public function subject(array $data): string
    {
        return mb_convert_encoding((string) __(LANG_GROUP, 'Contacto') . ': ' . self::text($data, 'subject'), 'UTF-8') . ' - ' . self::text($data, 'titleSuffix');
    }

    public function sampleData(): array
    {
        return [
            'name' => 'Laura Gómez',
            'email' => 'laura.gomez@example.com',
            'subject' => 'Información sobre los cursos',
            'message' => '¿Hay plazas para el curso de otoño? Me interesa el horario de tarde.',
            'updates' => true,
            'titleSuffix' => Config::app_title(),
        ];
    }

    public function render(array $data): string
    {
        $title = vsprintf(__(LANG_GROUP, "Fue contactado desde: <a href='%s'>%s</a>"), [
            baseurl(),
            get_config('title_app'),
        ]);
        $body = (string) self::renderer($data, fn() => self::viewRenderer())->render('mailing/generic-contact-form', [
            'title' => $title,
            'name' => self::text($data, 'name'),
            'email' => self::text($data, 'email'),
            'subject' => $this->subject($data),
            'message' => self::text($data, 'message'),
            'updates' => ($data['updates'] ?? false) === true,
            'extraFields' => self::extraFields($data),
        ], false);
        return mb_convert_encoding($body, 'UTF-8');
    }

    /**
     * @param array<string,mixed> $data
     * @return array<int,array{label:string,value:string}>
     */
    private static function extraFields(array $data): array
    {
        $extraFields = $data['extraFields'] ?? null;
        if (!is_array($extraFields)) {
            return [];
        }
        $valid = [];
        foreach ($extraFields as $field) {
            if (is_array($field) && is_string($field['label'] ?? null) && is_string($field['value'] ?? null)) {
                $valid[] = ['label' => $field['label'], 'value' => $field['value']];
            }
        }
        return $valid;
    }
}
