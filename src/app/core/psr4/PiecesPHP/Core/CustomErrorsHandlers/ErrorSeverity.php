<?php

/**
 * ErrorSeverity.php
 */
namespace PiecesPHP\Core\CustomErrorsHandlers;

/**
 * ErrorSeverity - Errores graves y suaves (ADR 0060): qué nivel de PHP aborta y dónde.
 *
 * Grave: rompe en todas partes. Suave: en local rompe como antes del ADR (las suites dependen de ello, LEY 13); fuera
 * de local se anota con su referencia y la ejecución sigue.
 *
 * @package     PiecesPHP\Core\CustomErrorsHandlers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ErrorSeverity
{
    const LEVEL_GRAVE = 'grave';
    const LEVEL_SOFT = 'soft';

    const GRAVE_TYPES = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR,
        E_RECOVERABLE_ERROR,
        E_USER_ERROR,
    ];

    /**
     * Los suaves que abortan en local. E_USER_WARNING y E_USER_NOTICE no estaban y siguen sin estar: no abortaban en
     * ninguna parte, y ahora fuera de local quedan anotados.
     */
    const SOFT_TYPES_ABORTING_IN_LOCAL = [
        E_WARNING,
        E_NOTICE,
        E_DEPRECATED,
        E_USER_DEPRECATED,
    ];

    const TYPE_NAMES = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    ];

    /**
     * @param int $type Un nivel E_*
     * @return string LEVEL_GRAVE o LEVEL_SOFT
     */
    public static function levelOf(int $type): string
    {
        return in_array($type, self::GRAVE_TYPES, true) ? self::LEVEL_GRAVE : self::LEVEL_SOFT;
    }

    /**
     * @param int $type Un nivel E_*
     * @param bool $isLocal
     * @return bool
     */
    public static function aborts(int $type, bool $isLocal): bool
    {
        if (self::levelOf($type) === self::LEVEL_GRAVE) {
            return true;
        }
        return $isLocal && in_array($type, self::SOFT_TYPES_ABORTING_IN_LOCAL, true);
    }

    /**
     * @param int $type
     * @return string
     */
    public static function typeName(int $type): string
    {
        return self::TYPE_NAMES[$type] ?? 'E_UNKNOWN(' . $type . ')';
    }
}
