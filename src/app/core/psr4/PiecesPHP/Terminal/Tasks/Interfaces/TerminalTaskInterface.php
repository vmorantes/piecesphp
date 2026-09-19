<?php

/**
 * TerminalTaskInterface.php
 */

namespace PiecesPHP\Terminal\Tasks\Interfaces;

use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;

/**
 * TerminalTaskInterface.
 *
 * @package     PiecesPHP\Terminal\Tasks\Interfaces
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
interface TerminalTaskInterface
{
    /**
     * Ejecuta la tarea
     */
    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []);
    /**
     * Devuelve la ruta de la tarea
     */
    public static function route(string $startRoute = '', ?string $namePrefix = null): Route;
}
