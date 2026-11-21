<?php

/**
 * ContactFormMail.php
 */

namespace PiecesPHP\MailCatalog\Definitions;

use App\Controller\ContactFormsController;
use PiecesPHP\Core\Config;
use PiecesPHP\MailCatalog\MailCatalogLang;
use PiecesPHP\MailCatalog\MailDefinition;

/**
 * ContactFormMail - El formulario de contacto de la zona pública.
 *
 * Datos: `name`, `email`, `subject` (el que escribe la persona; vacío o ausente, el asunto por defecto), `message`, `updates` (bool), `titleSuffix` (lo que
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
        $subject = trim(self::text($data, 'subject'));
        if ($subject === '') {
            $subject = (string) __(LANG_GROUP, 'Mensaje desde el sitio web');
        }
        return mb_convert_encoding((string) __(LANG_GROUP, 'Contacto') . ': ' . $subject, 'UTF-8') . ' - ' . self::text($data, 'titleSuffix');
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
     * Las entradas bien formadas, con los topes del controlador aunque `extraFields` no venga de él.
     *
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
            if (count($valid) >= ContactFormsController::EXTRA_FIELDS_LIMIT) {
                break;
            }
            if (is_array($field) && is_string($field['label'] ?? null) && is_string($field['value'] ?? null)) {
                $value = $field['value'];
                if (mb_strlen($value) > ContactFormsController::EXTRA_FIELD_LENGTH_LIMIT) {
                    $value = mb_substr($value, 0, ContactFormsController::EXTRA_FIELD_LENGTH_LIMIT) . '…';
                }
                $valid[] = ['label' => $field['label'], 'value' => $value];
            }
        }
        return $valid;
    }
}
