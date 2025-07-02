<?php

/**
 * OrganizationApprovalHandler.php
 */

namespace SystemApprovals\Util\Packages;

use App\Model\UsersModel;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\UserSystem\UserDataPackage;
use SystemApprovals\Mappers\SystemApprovalsMapper;

/**
 * OrganizationApprovalHandler.
 *
 * @package     SystemApprovals\Util\Packages
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class OrganizationApprovalHandler extends BaseApprovalHandler
{

    protected static $APPROVALS_ALLOW = true;
    protected static $MAPPER_NAME = OrganizationMapper::class;
    protected static $REFERENCE_TABLE = OrganizationMapper::TABLE;
    protected static $REFERENCE_COLUMN = 'id';
    protected static $CREATION_DATE_COLUMN = 'createdAt';
    protected static $BASE_TEXT = 'Organización';
    public static string $STATUS_ACTIVATION_COLUMN = 'status';
    public static array $STATUS_ACTIVATION_POSITIVES_VALUES = [
        OrganizationMapper::ACTIVE,
        OrganizationMapper::INACTIVE,
        OrganizationMapper::PENDING_APPROVAL,
    ];

    /**
     * Obtiene el tipo de contenido específico del mapper.
     *
     * @param int|OrganizationMapper $reference Referencia al mapper o su ID.
     * @return string Texto base del tipo de contenido.
     */
    public static function getContentTypeSpecificMapper(int | OrganizationMapper $reference): string
    {
        $text = static::$BASE_TEXT;
        return $text;
    }

    /**
     * Verifica si el mapper específico debe ser aprobado automáticamente.
     *
     * @param int|OrganizationMapper $reference Referencia al mapper o su ID.
     * @return bool True si el mapper debe ser aprobado automáticamente, false en caso contrario.
     */
    public static function isAutoApprovalSpecificMapper(int | OrganizationMapper $reference): bool
    {
        $approved = false;
        $mapper = $reference instanceof OrganizationMapper ? $reference : new OrganizationMapper($reference);
        $approved = $mapper->id == OrganizationMapper::INITIAL_ID_GLOBAL;
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
     * @param OrganizationMapper $element El elemento que ha sido aprobado.
     */
    public static function onApprovedSpecificMapper(OrganizationMapper $element)
    {
        $users = UsersModel::allByMultipleCriteries([
            [
                'column' => 'organization',
                'value' => $element->id,
            ],
        ], [], new UserDataPackage(1), true);
        $users = !empty($users) ? $users : [];
        $element->status = OrganizationMapper::ACTIVE;
        $element->update();
        foreach ($users as $user) {
            $approvalMapperUser = SystemApprovalsMapper::getByMultipleCriteries([
                [
                    'column' => 'referenceTable',
                    'value' => UsersModel::TABLE,
                ],
                [
                    'column' => 'referenceValue',
                    'value' => $user->id,
                ],
            ], [], false, true);
            if ($approvalMapperUser !== null) {
                $approvalMapperUser->status = SystemApprovalsMapper::STATUS_APPROVED;
                $approvalMapperUser->update();
            }
            $user->status = UsersModel::STATUS_USER_ACTIVE;
            $user->update();
        }
    }

    /**
     * Método que se ejecuta cuando un elemento es rechazado.
     *
     * @param OrganizationMapper $element El elemento que ha sido rechazado.
     */
    public static function onRejectedSpecificMapper(OrganizationMapper $element): void
    {
        $users = UsersModel::allByMultipleCriteries([
            [
                'column' => 'organization',
                'value' => $element->id,
            ],
        ], [], new UserDataPackage(1), true);
        $users = !empty($users) ? $users : [];
        foreach ($users as $user) {
            $user->status = UsersModel::STATUS_USER_APPROVED_PENDING;
            $user->update();
        }
    }

    /**
     * Método cuando el elemento es actualizado
     *
     * @param OrganizationMapper $element El elemento que ha sido actualizado.
     * @param ?SystemApprovalsMapper $approvalMapper El elemento que gestiona la aprobación
     */
    public static function onUpdatedRecordSpecificMapper(OrganizationMapper $element, ?SystemApprovalsMapper $approvalMapper = null): void
    {
    }
}
