<?php

/**
 * InterfaceLevel.php
 */

namespace PiecesPHP\Core\DataTransfer;

use PiecesPHP\Core\DataTransfer\Export\ProjectFile;

/**
 * InterfaceLevel - Los niveles de interfaz que comparten importadores y exportadores, y su comprobación al registrar.
 *
 * @package     PiecesPHP\Core\DataTransfer
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class InterfaceLevel
{
    /** Sin interfaz web propia. */
    const NONE = 0;
    /** Formulario generado. */
    const AUTO = 1;
    /** Lo del AUTO más los extras del nivel y el hueco formPartial(). */
    const EXTENDED = 2;
    /** La definición pone su vista (customView()) sobre las mismas rutas. */
    const CUSTOM = 3;

    /**
     * @param int $level
     * @param string|null $customView
     * @param string $key Para el mensaje
     * @return void
     * @throws \InvalidArgumentException nivel fuera de 0..3, o nivel 3 sin vista propia o con ella fuera del proyecto
     */
    public static function check(int $level, ?string $customView, string $key): void
    {
        if (!in_array($level, [self::NONE, self::AUTO, self::EXTENDED, self::CUSTOM], true)) {
            throw new \InvalidArgumentException("Nivel de interfaz «{$level}» no válido en «{$key}»: 0, 1, 2 o 3.");
        }
        if ($level === self::CUSTOM) {
            if ($customView === null) {
                throw new \InvalidArgumentException("«{$key}» es de nivel 3 y no declara customView().");
            }
            ProjectFile::resolve($customView, ['php']);
        }
    }
}
