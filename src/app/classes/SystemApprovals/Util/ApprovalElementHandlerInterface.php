<?php

/**
 * ApprovalElementHandlerInterface.php
 */

namespace SystemApprovals\Util;

use App\Model\UsersModel;
use PiecesPHP\Core\Database\EntityMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

/**
 * ApprovalElementHandlerInterface.
 *
 * @package     SystemApprovals\Util
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
interface ApprovalElementHandlerInterface
{
    /**
     * Verifica si el manejador de elementos de aprobación está habilitado.
     *
     * @return bool
     */
    public static function isEnabled(): bool;

    /**
     * Obtiene la clase del mapeador de entidades.
     *
     * @return string
     */
    public static function getMapperClass(): string;

    /**
     * Obtiene el tipo de contenido para una referencia dada.
     *
     * @param int|EntityMapper $reference
     * @return string
     */
    public static function getContentType(int | EntityMapper $reference): string;

    /**
     * Obtiene la tabla de referencia.
     *
     * @return string
     */
    public static function getReferenceTable(): string;

    /**
     * Obtiene la columna de referencia.
     *
     * @return string
     */
    public static function getReferenceColumn(): string;

    /**
     * Obtiene los campos del manejador de elementos de aprobación.
     *
     * @return array
     */
    public static function getFields(): array;

    /**
     * Obtiene la columna de fecha de creación.
     *
     * @return string
     */
    public static function getCreationDateColumn(): string;

    /**
     * Verifica si la aprobación es automática para una referencia dada.
     *
     * @param int|EntityMapper $reference
     * @return bool
     */
    public static function isAutoApproval(int | EntityMapper $reference): bool;

    /**
     * Método que se ejecuta cuando se aprueba una referencia.
     *
     * @param EntityMapper $reference
     * @return void
     */
    public static function onApproved(EntityMapper $reference): void;

    /**
     * Método que se ejecuta cuando se rechaza una referencia.
     *
     * @param EntityMapper $reference
     * @return void
     */
    public static function onRejected(EntityMapper $reference): void;

    /**
     * Método que se ejecuta el evento de actualización es disparado.
     *
     * @param EntityMapper $reference
     * @param ?SystemApprovalsMapper $approvalMapper
     * @return void
     */
    public static function onUpdatedRecord(EntityMapper $reference, ?SystemApprovalsMapper $approvalMapper = null): void;

    /**
     * Obtiene el usuario asociado para notificaciones
     *
     * @param EntityMapper $reference
     * @return UsersModel|null
     */
    public static function getContactUser(EntityMapper $reference): ?UsersModel;
}
