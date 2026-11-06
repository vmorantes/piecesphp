<?php

/**
 * OneUseCodeMail.php
 */

namespace PiecesPHP\MailCatalog\Definitions;

use PiecesPHP\Core\Config;
use PiecesPHP\MailCatalog\MailCatalogLang;
use PiecesPHP\MailCatalog\MailDefinition;
use PiecesPHP\UserSystem\Authentication\OTPHandler;

/**
 * OneUseCodeMail - La contraseña de un uso (OTP por correo).
 *
 * Datos: `code`, `minutes` y, opcionales, `renderer` y `view` (OTPHandler::generateOTP() deja elegir los dos a quien llama).
 *
 * @package     PiecesPHP\MailCatalog\Definitions
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class OneUseCodeMail extends MailDefinition
{
    const DEFAULT_VIEW = 'mails/otp-code';

    public function key(): string
    {
        return 'user-one-use-code';
    }

    public function name(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'Contraseña de un uso');
    }

    public function trigger(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'Al pedir en la pantalla de acceso una contraseña de un uso, en vez de la de siempre.');
    }

    public function recipients(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'La persona que lo pide.');
    }

    public function subject(array $data): string
    {
        return mb_convert_encoding((string) __(OTPHandler::LANG_GROUP, 'Contraseña de un uso'), 'UTF-8') . ' - ' . Config::app_title();
    }

    public function sampleData(): array
    {
        return ['code' => '481516', 'minutes' => 10];
    }

    public function render(array $data): string
    {
        $view = self::text($data, 'view', self::DEFAULT_VIEW);
        $body = (string) self::renderer($data, fn() => self::viewRenderer(self::userSystemViews()))->render($view, [
            'text' => __(OTPHandler::LANG_GROUP, 'Contraseña de un solo uso'),
            'note' => vsprintf(__(OTPHandler::LANG_GROUP, 'Tiene una validez de %s minutos'), [self::text($data, 'minutes')]),
            'code' => self::text($data, 'code'),
        ], false);
        return mb_convert_encoding($body, 'UTF-8');
    }
}
