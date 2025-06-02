<?php

/**
 * PublicationsApprovalHandler.php
 */

namespace SystemApprovals\Util\Packages;

use Publications\Mappers\PublicationMapper;

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
        return false;
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
}
