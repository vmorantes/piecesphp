<?php

/**
 * RecoveryPasswordMail.php
 */

namespace PiecesPHP\MailCatalog\Definitions;

use PiecesPHP\MailCatalog\MailCatalogLang;
use PiecesPHP\MailCatalog\MailDefinition;
use PiecesPHP\UserSystem\Controllers\RecoveryPasswordController;

/**
 * RecoveryPasswordMail - El código para recuperar la contraseña, con enlace o solo el código.
 *
 * Datos: `code`, `email` (para el enlace) y, opcional, `renderer`.
 *
 * @package     PiecesPHP\MailCatalog\Definitions
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class RecoveryPasswordMail extends MailDefinition
{

    /**
     * @var bool
     */
    protected $onlyCode = false;

    /**
     * @param bool $onlyCode Sin enlace: solo el código
     */
    public function __construct(bool $onlyCode)
    {
        $this->onlyCode = $onlyCode;
    }

    public function key(): string
    {
        return $this->onlyCode ? 'user-recovery-password-code' : 'user-recovery-password';
    }

    public function name(): string
    {
        return $this->onlyCode
            ? __(MailCatalogLang::LANG_GROUP, 'Recuperación de contraseña (solo el código)')
            : __(MailCatalogLang::LANG_GROUP, 'Recuperación de contraseña');
    }

    public function trigger(): string
    {
        return $this->onlyCode
            ? __(MailCatalogLang::LANG_GROUP, 'Al pedir un código para recuperar la contraseña por la API.')
            : __(MailCatalogLang::LANG_GROUP, 'Al pedir un código para recuperar la contraseña desde la pantalla de acceso.');
    }

    public function recipients(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'La persona que lo pide.');
    }

    public function subject(array $data): string
    {
        return (string) __(RecoveryPasswordController::LANG_GROUP, 'Recuperación de contraseña');
    }

    public function sampleData(): array
    {
        return ['code' => 'DEMO42', 'email' => 'laura.gomez@example.com'];
    }

    public function render(array $data): string
    {
        $code = self::text($data, 'code');
        $renderer = self::renderer($data, fn() => self::viewRenderer(self::userSystemViews()));
        if (!$this->onlyCode) {
            return (string) $renderer->render('usuarios/mail/recovery_password_code', [
                'code' => $code,
                'url' => RecoveryPasswordController::routeName('recovery-form') . '?code=' . rawurlencode($code) . '&email=' . rawurlencode(self::text($data, 'email')),
            ], false);
        }
        return (string) $renderer->render('usuarios/mail/recovery_password_code_only', [
            'code' => $code,
            'text' => __(MAIL_TEMPLATES_LANG_GROUP, 'Recuperación de contraseña') . ':',
            'text_button' => __(MAIL_TEMPLATES_LANG_GROUP, 'Ingresar el código'),
            'note' => __(MAIL_TEMPLATES_LANG_GROUP, 'MENSAJE_DE_VALIDEZ'),
        ], false);
    }
}
