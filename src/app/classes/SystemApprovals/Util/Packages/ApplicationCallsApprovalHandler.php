<?php

/**
 * ApplicationCallsApprovalHandler.php
 */

namespace SystemApprovals\Util\Packages;

use ApplicationCalls\Mappers\ApplicationCallsMapper;
use App\Model\UsersModel;
use SystemApprovals\Mappers\SystemApprovalsMapper;

/**
 * ApplicationCallsApprovalHandler.
 *
 * @package     SystemApprovals\Util\Packages
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class ApplicationCallsApprovalHandler extends BaseApprovalHandler
{

    protected static $APPROVALS_ALLOW = true;
    protected static $MAPPER_NAME = ApplicationCallsMapper::class;
    protected static $REFERENCE_TABLE = ApplicationCallsMapper::TABLE;
    protected static $REFERENCE_COLUMN = 'id';
    protected static $CREATION_DATE_COLUMN = 'createdAt';
    protected static $BASE_TEXT = 'Contenido';
    public static string $STATUS_ACTIVATION_COLUMN = 'status';
    public static array $STATUS_ACTIVATION_POSITIVES_VALUES = [
        ApplicationCallsMapper::ACTIVE,
    ];

    /**
     * Obtiene el tipo de contenido específico del mapper.
     *
     * @param int|ApplicationCallsMapper $reference Referencia al mapper o su ID.
     * @return string Texto base del tipo de contenido.
     */
    public static function getContentTypeSpecificMapper(int | ApplicationCallsMapper $reference): string
    {
        $text = self::$BASE_TEXT;
        $mapper = $reference instanceof ApplicationCallsMapper ? $reference : new ApplicationCallsMapper($reference);
        $id = $mapper->id;
        if ($id !== null && $mapper == null) {
            $mapper = ApplicationCallsMapper::getBy($id, 'id', true);
        }
        if ($mapper !== null && $mapper->id !== null) {
            $text = $mapper->contentTypeText(false);
        }
        return $text;
    }

    /**
     * Verifica si el mapper específico debe ser aprobado automáticamente.
     *
     * @param int|ApplicationCallsMapper $reference Referencia al mapper o su ID.
     * @return bool True si el mapper debe ser aprobado automáticamente, false en caso contrario.
     */
    public static function isAutoApprovalSpecificMapper(int | ApplicationCallsMapper $reference): bool
    {
        $approved = false;
        $mapper = $reference instanceof ApplicationCallsMapper ? $reference : new ApplicationCallsMapper($reference);
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
     * @param ApplicationCallsMapper $element El elemento que ha sido aprobado.
     */
    public static function onApprovedSpecificMapper(ApplicationCallsMapper $element)
    {}

    /**
     * Método que se ejecuta cuando un elemento es rechazado.
     *
     * @param ApplicationCallsMapper $element El elemento que ha sido rechazado.
     */
    public static function onRejectedSpecificMapper(ApplicationCallsMapper $element): void
    {}

    /**
     * Método cuando el elemento es actualizado
     *
     * @param ApplicationCallsMapper $element El elemento que ha sido actualizado.
     * @param ?SystemApprovalsMapper $approvalMapper El elemento que gestiona la aprobación
     */
    public static function onUpdatedRecordSpecificMapper(ApplicationCallsMapper $element, ?SystemApprovalsMapper $approvalMapper = null): void
    {
    }
}
