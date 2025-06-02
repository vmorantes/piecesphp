<?php

/**
 * UsersApprovalHandler.php
 */

namespace SystemApprovals\Util\Packages;

use App\Model\UsersModel;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * UsersApprovalHandler.
 *
 * @package     SystemApprovals\Util\Packages
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class UsersApprovalHandler extends BaseApprovalHandler
{

    protected static $APPROVALS_ALLOW = true;
    protected static $MAPPER_NAME = UsersModel::class;
    protected static $REFERENCE_TABLE = UsersModel::TABLE;
    protected static $REFERENCE_COLUMN = 'id';
    protected static $CREATION_DATE_COLUMN = 'created_at';
    protected static $CREATOR_ID = 'SAME';
    protected static $BASE_TEXT = 'Perfil';

    /**
     * Obtiene el tipo de contenido específico del mapper.
     *
     * @param int|UsersModel $reference Referencia al mapper o su ID.
     * @return string Texto base del tipo de contenido.
     */
    public static function getContentTypeSpecificMapper(int | UsersModel $reference): string
    {
        $text = self::$BASE_TEXT;
        $mapper = $reference instanceof UsersModel ? $reference : new UsersModel($reference);
        $id = $mapper->id;
        if ($id !== null && $mapper == null) {
            $mapper = UsersModel::getBy($id, 'id', [], new UserDataPackage(1), true);
        }
        $organization = $mapper->organization;
        $organization = $organization !== null ? new OrganizationMapper($organization) : new OrganizationMapper();
        $isBaseOrg = $organization->id !== null && $organization->id == OrganizationMapper::INITIAL_ID_GLOBAL;
        if ($isBaseOrg) {
            if ($mapper->type == UsersModel::TYPE_USER_GENERAL) {
                $text = 'Investigador independiente';
            }
        }
        return $text;
    }

    /**
     * Verifica si el mapper específico debe ser aprobado automáticamente.
     *
     * @param int|UsersModel $reference Referencia al mapper o su ID.
     * @return bool True si el mapper debe ser aprobado automáticamente, false en caso contrario.
     */
    public static function isAutoApprovalSpecificMapper(int | UsersModel $reference): bool
    {
        $approved = false;
        $userMapper = $reference instanceof UsersModel ? $reference : new UsersModel($reference);
        $organization = $userMapper->organization;
        $organization = $organization !== null ? new OrganizationMapper($organization) : new OrganizationMapper();
        $isBaseOrg = $organization->id !== null && $organization->id == OrganizationMapper::INITIAL_ID_GLOBAL;
        $autoApprovalUserTypes = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
        ];
        if (in_array($userMapper->type, $autoApprovalUserTypes) || $userMapper->status == UsersModel::STATUS_USER_ACTIVE || in_array($userMapper->type, UsersModel::ARE_AUTO_APPROVAL)) {
            $approved = true;
        }
        return $approved;
    }

    /**
     * Método que se ejecuta cuando un elemento es aprobado.
     *
     * @param UsersModel $element El elemento que ha sido aprobado.
     */
    public static function onApprovedSpecificMapper(UsersModel $element)
    {
        $element->status = UsersModel::STATUS_USER_ACTIVE;
        $element->update();
    }

    /**
     * Método que se ejecuta cuando un elemento es rechazado.
     *
     * @param UsersModel $element El elemento que ha sido rechazado.
     */
    public static function onRejectedSpecificMapper(UsersModel $element): void
    {
        $element->status = UsersModel::STATUS_USER_REJECTED;
        $element->update();
    }
}
