<?php

/**
 * PublicationsApprovalHandler.php
 */

namespace SystemApprovals\Util\Packages;

use App\Model\UsersModel;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

/**
 * PublicationsApprovalHandler.
 *
 * @package     SystemApprovals\Util\Packages
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class PublicationsApprovalHandler extends BaseApprovalHandler
{

    protected static $APPROVALS_ALLOW = true;
    protected static $MAPPER_NAME = PublicationMapper::class;
    protected static $REFERENCE_TABLE = PublicationMapper::TABLE;
    protected static $REFERENCE_COLUMN = 'id';
    protected static $CREATION_DATE_COLUMN = 'createdAt';
    protected static $BASE_TEXT = 'Publicación';
    public static string $STATUS_ACTIVATION_COLUMN = 'status';
    public static array $STATUS_ACTIVATION_POSITIVES_VALUES = [
        PublicationMapper::ACTIVE,
        PublicationMapper::DRAFT,
    ];

    /**
     * Obtiene el tipo de contenido específico del mapper.
     *
     * @param int|PublicationMapper $reference Referencia al mapper o su ID.
     * @return string Texto base del tipo de contenido.
     */
    public static function getContentTypeSpecificMapper(int | PublicationMapper $reference): string
    {
        $text = static::$BASE_TEXT;
        return $text;
    }

    /**
     * Verifica si el mapper específico debe ser aprobado automáticamente.
     *
     * @param int|PublicationMapper $reference Referencia al mapper o su ID.
     * @return bool True si el mapper debe ser aprobado automáticamente, false en caso contrario.
     */
    public static function isAutoApprovalSpecificMapper(int | PublicationMapper $reference): bool
    {
        $approved = false;
        $mapper = $reference instanceof PublicationMapper ? $reference : new PublicationMapper($reference);
        //Auto aprobación cuando lo crea ciertos tipos de usuarios
        $createdBy = $mapper->createdBy;
        $createdByType = $createdBy->type;
        $autoApprovalUserTypes = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
        ];
        if (in_array($createdByType, $autoApprovalUserTypes)) {
            $approved = true;
        }
        return $approved;
    }

    /**
     * Método que se ejecuta cuando un elemento es aprobado.
     *
     * @param PublicationMapper $element El elemento que ha sido aprobado.
     */
    public static function onApprovedSpecificMapper(PublicationMapper $element)
    {
    }

    /**
     * Método que se ejecuta cuando un elemento es rechazado.
     *
     * @param PublicationMapper $element El elemento que ha sido rechazado.
     */
    public static function onRejectedSpecificMapper(PublicationMapper $element): void
    {
    }

    /**
     * Método cuando el elemento es actualizado
     *
     * @param PublicationMapper $element El elemento que ha sido actualizado.
     * @param ?SystemApprovalsMapper $approvalMapper El elemento que gestiona la aprobación
     */
    public static function onUpdatedRecordSpecificMapper(PublicationMapper $element, ?SystemApprovalsMapper $approvalMapper = null): void
    {}
}
