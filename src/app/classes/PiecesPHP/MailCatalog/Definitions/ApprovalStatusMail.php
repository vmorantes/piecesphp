<?php

/**
 * ApprovalStatusMail.php
 */

namespace PiecesPHP\MailCatalog\Definitions;

use PiecesPHP\MailCatalog\MailCatalogLang;
use PiecesPHP\MailCatalog\MailDefinition;
use SystemApprovals\Controllers\SystemApprovalsController;
use SystemApprovals\Mappers\SystemApprovalsMapper;

/**
 * ApprovalStatusMail - El aviso de que un contenido cambió de estado en Aprobaciones: aprobado o rechazado. Con otro
 * estado el texto va vacío, como antes.
 *
 * Datos: `name` (sin escapar), `contentName` (ya traducido), `reason` (sin escapar) y, opcional, `renderer`.
 *
 * @package     PiecesPHP\MailCatalog\Definitions
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class ApprovalStatusMail extends MailDefinition
{

    /**
     * @var string
     */
    protected $status = SystemApprovalsMapper::STATUS_APPROVED;

    /**
     * @param string $status Uno de SystemApprovalsMapper::STATUS_*
     */
    public function __construct(string $status)
    {
        $this->status = $status;
    }

    public function key(): string
    {
        return match ($this->status) {
            SystemApprovalsMapper::STATUS_APPROVED => 'approval-approved',
            SystemApprovalsMapper::STATUS_REJECTED => 'approval-rejected',
            default => 'approval-status-' . mb_strtolower($this->status),
        };
    }

    public function name(): string
    {
        return $this->status === SystemApprovalsMapper::STATUS_REJECTED
            ? __(MailCatalogLang::LANG_GROUP, 'Contenido rechazado')
            : __(MailCatalogLang::LANG_GROUP, 'Contenido aprobado');
    }

    public function trigger(): string
    {
        return $this->status === SystemApprovalsMapper::STATUS_REJECTED
            ? __(MailCatalogLang::LANG_GROUP, 'Al rechazar un contenido en Aprobaciones (un perfil, una organización o una publicación).')
            : __(MailCatalogLang::LANG_GROUP, 'Al aprobar un contenido en Aprobaciones (un perfil, una organización o una publicación).');
    }

    public function recipients(): string
    {
        return __(MailCatalogLang::LANG_GROUP, 'La persona de contacto del contenido.');
    }

    public function subject(array $data): string
    {
        return self::titledSubject((string) __(SystemApprovalsController::LANG_GROUP, 'Aprobaciones'));
    }

    public function sampleData(): array
    {
        return [
            'name' => 'Laura Gómez',
            'contentName' => 'Informe anual 2026',
            'reason' => $this->status === SystemApprovalsMapper::STATUS_REJECTED ? 'Falta la fuente de las cifras del segundo apartado.' : '',
        ];
    }

    public function render(array $data): string
    {
        $replacements = [
            '{NAME}' => htmlspecialchars(self::text($data, 'name'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            '{CONTENT_NAME}' => self::text($data, 'contentName'),
        ];
        $message = '';
        if ($this->status == SystemApprovalsMapper::STATUS_APPROVED) {
            $message = strReplaceTemplate(__(SystemApprovalsController::LANG_GROUP, "Sr(a). {NAME}, le informamos que su contenido \"{CONTENT_NAME}\" ha sido aprobado"), $replacements);
        } elseif ($this->status == SystemApprovalsMapper::STATUS_REJECTED) {
            $message = strReplaceTemplate(__(SystemApprovalsController::LANG_GROUP, "Sr(a). {NAME}, le informamos que su contenido \"{CONTENT_NAME}\" ha sido rechazado"), $replacements);
        }
        return (string) self::renderer($data, fn() => new \SystemApprovals\Controllers\HelperController(null, []))->render('mailing/template_base_no_style', [
            'text' => mb_convert_encoding($message, 'UTF-8'),
            'reason' => htmlspecialchars(mb_convert_encoding(self::text($data, 'reason'), 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ], false, false);
    }
}
