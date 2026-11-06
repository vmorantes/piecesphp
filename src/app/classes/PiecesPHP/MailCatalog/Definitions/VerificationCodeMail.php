<?php

/**
 * VerificationCodeMail.php
 */

namespace PiecesPHP\MailCatalog\Definitions;

use PiecesPHP\MailCatalog\MailCatalogLang;
use PiecesPHP\MailCatalog\MailDefinition;
use PiecesPHP\UserSystem\Controllers\UserProblemsController;

/**
 * VerificationCodeMail - El código de los problemas de acceso: «olvidé mi usuario» o «mi cuenta está bloqueada».
 *
 * Datos: `code` y, opcional, `renderer`.
 *
 * @package     PiecesPHP\MailCatalog\Definitions
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class VerificationCodeMail extends MailDefinition
{
    const FORGET = UserProblemsController::TYPE_USER_FORGET;
    const BLOCKED = UserProblemsController::TYPE_USER_BLOCKED;

    /**
     * @var string
     */
    protected $type = self::FORGET;

    /**
     * @param string $type self::FORGET o self::BLOCKED
     */
    public function __construct(string $type)
    {
        if (!in_array($type, [self::FORGET, self::BLOCKED], true)) {
            throw new \InvalidArgumentException("VerificationCodeMail: tipo no soportado '{$type}'.");
        }
        $this->type = $type;
    }

    public function key(): string
    {
        return $this->type === self::FORGET ? 'user-forget-code' : 'user-blocked-code';
    }

    public function name(): string
    {
        return $this->type === self::FORGET
            ? __(MailCatalogLang::LANG_GROUP, 'Código para recuperar el usuario')
            : __(MailCatalogLang::LANG_GROUP, 'Código para desbloquear la cuenta');
    }

    public function trigger(): string
    {
        return $this->type === self::FORGET
            ? __(MailCatalogLang::LANG_GROUP, 'Al pedir ayuda porque se olvidó el usuario, desde la pantalla de acceso.')
            : __(MailCatalogLang::LANG_GROUP, 'Al pedir ayuda porque la cuenta quedó bloqueada, desde la pantalla de acceso.');
    }

    public function recipients(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'La persona que lo pide.');
    }

    public function subject(array $data): string
    {
        return (string) __(UserProblemsController::LANG_GROUP, 'Código de verificación');
    }

    public function sampleData(): array
    {
        return ['code' => 'DEMO42'];
    }

    public function render(array $data): string
    {
        $code = self::text($data, 'code');
        $renderer = self::renderer($data, fn() => self::viewRenderer(self::userSystemViews()));
        $forget = $this->type === self::FORGET;
        return (string) $renderer->render($forget ? 'usuarios/mail/user_forget_code' : 'usuarios/mail/user_blocked_code', [
            'code' => $code,
            'url' => UserProblemsController::routeName($forget ? 'forget-form' : 'blocked-form') . '?code=' . $code,
        ], false);
    }
}
