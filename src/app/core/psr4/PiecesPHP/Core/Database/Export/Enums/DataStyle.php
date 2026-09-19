<?php

namespace PiecesPHP\Core\Database\Export\Enums;

/**
 * Enum DataStyle
 * 
 * Estilo de comandos para la inserción de datos.
 */
enum DataStyle: string
{
    /** Uso de sentencias INSERT estándar */
    case INSERT = 'INSERT';
    /** Uso de sentencias REPLACE para sobrescribir registros existentes */
    case REPLACE = 'REPLACE';
    /** Limpiar la tabla antes de insertar (TRUNCATE + INSERT) */
    case TRUNCATE_INSERT = 'TRUNCATE+INSERT';
}
