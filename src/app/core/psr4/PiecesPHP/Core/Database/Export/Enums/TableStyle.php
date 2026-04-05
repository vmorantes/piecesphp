<?php

namespace PiecesPHP\Core\Database\Export\Enums;

/**
 * Enum TableStyle
 * 
 * Estilo de comandos para la creación de tablas.
 */
enum TableStyle: string
{
    /** Borrar tabla antes de crearla (DROP + CREATE) */
    case DROP_CREATE = 'DROP+CREATE';
    /** Crear únicamente la tabla sin borrarla previamente */
    case CREATE = 'CREATE';
}
