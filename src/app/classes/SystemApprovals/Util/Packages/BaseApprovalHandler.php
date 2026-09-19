<?php

/**
 * BaseApprovalHandler.php
 */

namespace SystemApprovals\Util\Packages;

use App\Model\UsersModel;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Database\EntityMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\Util\ApprovalElementHandlerInterface;

/**
 * BaseApprovalHandler.
 *
 * @package     SystemApprovals\Util\Packages
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class BaseApprovalHandler implements ApprovalElementHandlerInterface
{

    protected static $APPROVALS_ALLOW = true;
    protected static $MAPPER_NAME = null;
    protected static $REFERENCE_TABLE = OrganizationMapper::TABLE;
    protected static $REFERENCE_COLUMN = 'id';
    protected static $CREATION_DATE_COLUMN = 'createdAt';
    protected static $CREATOR_ID = 'createdBy';
    protected static $BASE_TEXT = 'Elemento';
    public static string $STATUS_ACTIVATION_COLUMN = 'status';
    public static array $STATUS_ACTIVATION_POSITIVES_VALUES = [
        OrganizationMapper::ACTIVE,
        OrganizationMapper::INACTIVE,
        OrganizationMapper::PENDING_APPROVAL,
    ];

    public static function isEnabled(): bool
    {
        return class_exists(static::$MAPPER_NAME) ? static::$APPROVALS_ALLOW : false;
    }

    public static function getMapperClass(): string
    {
        return static::$MAPPER_NAME;
    }

    public static function getContentType(int | EntityMapper $reference): string
    {
        if (method_exists(static::class, 'getContentTypeSpecificMapper')) {
            return call_user_func([static::class, 'getContentTypeSpecificMapper'], $reference);
        }
        $text = static::$BASE_TEXT;
        return $text;
    }

    public static function getReferenceTable(): string
    {
        return static::$REFERENCE_TABLE;
    }

    public static function getReferenceColumn(): string
    {
        return static::$REFERENCE_COLUMN;
    }

    public static function getFields(): array
    {
        return array_keys(static::$MAPPER_NAME::getFields());
    }

    public static function getCreationDateColumn(): string
    {
        return static::$CREATION_DATE_COLUMN;
    }

    public static function isAutoApproval(int | EntityMapper $reference): bool
    {
        if (method_exists(static::class, 'isAutoApprovalSpecificMapper')) {
            return call_user_func([static::class, 'isAutoApprovalSpecificMapper'], $reference);
        }
        return false;
    }

    public static function onApproved(EntityMapper $reference): void
    {
        if (method_exists(static::class, 'onApprovedSpecificMapper')) {
            call_user_func([static::class, 'onApprovedSpecificMapper'], $reference);
        }
    }

    public static function onRejected(EntityMapper $reference): void
    {
        if (method_exists(static::class, 'onRejectedSpecificMapper')) {
            call_user_func([static::class, 'onRejectedSpecificMapper'], $reference);
        }
    }

    public static function onUpdatedRecord(EntityMapper $reference, ?SystemApprovalsMapper $approvalMapper = null): void
    {
        if (method_exists(static::class, 'onUpdatedRecordSpecificMapper')) {
            call_user_func([static::class, 'onUpdatedRecordSpecificMapper'], $reference, $approvalMapper);
        }
    }

    public static function getContactUser(EntityMapper $reference): ?UsersModel
    {
        if (method_exists(static::class, 'getContactUserSpecificMapper')) {
            return call_user_func([static::class, 'getContactUserSpecificMapper'], $reference);
        }
        $fields = static::getFields();
        $creatorID = static::$CREATOR_ID;
        $creator = -1;
        if (in_array($creatorID, $fields)) {
            $creator = $reference->$creatorID;
        } else if (mb_strtolower($creatorID) == 'same') {
            $creator = $reference;
        }
        $userMapper = is_int($creator) ? new UsersModel($creator) : $creator;
        return $userMapper instanceof UsersModel && $userMapper->id !== null ? $userMapper : null;
    }

}
