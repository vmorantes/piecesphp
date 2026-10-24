<?php

/**
 * PhpErrorHandler.php
 */
namespace PiecesPHP\Core\CustomErrorsHandlers;

/**
 * PhpErrorHandler - El manejador de set_error_handler() de bootstrap.php: graves y suaves (ADR 0060).
 *
 * @package     PiecesPHP\Core\CustomErrorsHandlers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class PhpErrorHandler
{
    const LEVEL_LABELS = [
        E_ERROR => 'Fatal error',
        E_WARNING => 'Warning',
        E_PARSE => 'Compile-time',
        E_NOTICE => 'Notice -possible false positive-',
        E_DEPRECATED => 'Deprecated',
        //Sin estos niveles, todo trigger_error() de una librería se descarta en silencio.
        E_RECOVERABLE_ERROR => 'Recoverable error',
        E_USER_ERROR => 'Fatal error (trigger_error)',
        E_USER_WARNING => 'Warning (trigger_error)',
        E_USER_NOTICE => 'Notice (trigger_error)',
        E_USER_DEPRECATED => 'Deprecated (trigger_error)',
    ];

    /**
     * Lanza ErrorException si el nivel aborta (ErrorSeverity::aborts()); si no, lo anota como suave y devuelve true
     * para que la ejecución siga.
     *
     * @param int $type
     * @param string $message
     * @param string $file
     * @param int $line
     * @param bool $isLocal
     * @return bool
     * @throws \ErrorException
     */
    public static function handle(int $type, string $message, string $file, int $line, bool $isLocal): bool
    {
        //Se respeta la supresión con @: el autoload de bin/tools se carga así a propósito.
        if (!(error_reporting() & $type)) {
            return true;
        }

        if (isset(self::LEVEL_LABELS[$type])) {
            $message = '(Level: ' . self::LEVEL_LABELS[$type] . ") {$message}";
        }

        //Un suave solo aborta en local: ojo, un cronjob sin --local cae en producción.
        if (ErrorSeverity::aborts($type, $isLocal)) {
            throw new \ErrorException($message, 0, $type, $file, $line);
        }

        //Las deprecaciones, además, a su archivo propio: vaciarlo fue la puerta de la migración.
        $logLine = '[' . date('c') . "] {$message} en {$file}:{$line}" . PHP_EOL;
        if (in_array($type, [E_DEPRECATED, E_USER_DEPRECATED], true) && defined('LOG_ERRORS_PATH')) {
            $deprecationsLog = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\') . DIRECTORY_SEPARATOR . 'deprecations.log';
            //RETORNO-IGNORADO: este archivo es un extra; el suave queda en el registro de errores justo debajo.
            @file_put_contents($deprecationsLog, $logLine, FILE_APPEND | LOCK_EX);
        }

        if (GenericHandler::logSoft(new \ErrorException($message, 0, $type, $file, $line), ErrorSeverity::typeName($type)) === '') {
            error_log(rtrim($logLine));
        }

        return true;
    }
}
