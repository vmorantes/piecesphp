<?php

/**
 * ApiUserCreatedMail.php
 */

namespace PiecesPHP\MailCatalog\Definitions;

use API\Controllers\APIController;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\MailCatalog\MailCatalogLang;
use PiecesPHP\MailCatalog\MailDefinition;

/**
 * ApiUserCreatedMail - El aviso de usuario creado y a la espera de aprobación, al darse de alta por la API.
 *
 * Datos: `name` (el nombre completo, sin escapar) y, opcional, `renderer`.
 *
 * @package     PiecesPHP\MailCatalog\Definitions
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class ApiUserCreatedMail extends MailDefinition
{

    public function key(): string
    {
        return 'api-user-created';
    }

    public function name(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'Usuario creado por la API');
    }

    public function trigger(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'Al registrarse un usuario por la API: queda a la espera de aprobación.');
    }

    public function recipients(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'El usuario que se registra.');
    }

    public function subject(array $data): string
    {
        return self::titledSubject((string) __(APIController::LANG_GROUP, 'Aprobaciones'));
    }

    public function sampleData(): array
    {
        return ['name' => 'Laura Gómez'];
    }

    public function render(array $data): string
    {
        $message = strReplaceTemplate(__(APIController::LANG_GROUP, "Sr(a). {NAME}, le informamos que su usuario ha sido creado y está a la espera de aprobación. De momento puede iniciar sesión y completar su perfil para agilizar el proceso de aprobación."), [
            '{NAME}' => htmlspecialchars(self::text($data, 'name'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ]);
        return (string) self::renderer($data, fn() => new \API\Controllers\HelperController(null, []))->render('mailing/template_base_no_style', [
            'text' => mb_convert_encoding($message, 'UTF-8'),
            'url' => AdminPanelController::routeName(''),
            'text_button' => __(APIController::LANG_GROUP, 'Iniciar sesión'),
        ], false, false);
    }
}
