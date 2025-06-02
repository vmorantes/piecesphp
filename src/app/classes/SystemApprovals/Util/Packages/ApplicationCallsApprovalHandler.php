<?php

/**
 * ApplicationCallsApprovalHandler.php
 */

namespace SystemApprovals\Util\Packages;

use ApplicationCalls\Mappers\ApplicationCallsMapper;

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
        return false;
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
}
