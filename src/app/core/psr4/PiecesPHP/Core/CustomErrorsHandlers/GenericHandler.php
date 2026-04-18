<?php

/**
 * GenericHandler.php
 */
namespace PiecesPHP\Core\CustomErrorsHandlers;

use Throwable;

/**
 * GenericHandler - ....
 *
 * @category     ErrorsHandlers
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class GenericHandler
{
    /**
     * @var Throwable
     */
    protected $exception;
    /**
     * @var int
     */
    protected $maxSizeMB = 1;
    /**
     * @var \DateTime
     */
    protected $date = null;
    /**
     * @var string
     */
    protected $fileLocation = '';
    /**
     * @var string
     */
    protected $oldFileLocation = '';
    /**
     * @var string
     */
    protected $directory = '';
    /**
     * @var string
     */
    protected $directoryBackup = '';
    /**
     * @var string
     */
    protected $fileLocationPlain = '';
    /**
     * @var string
     */
    protected $oldFileLocationPlain = '';
    /**
     * @var string
     */
    protected $fileLocationUniqueMessage = '';

    /**
     * @param Throwable $e
     * @return static
     * @throws \TypeError
     */
    public function __construct(Throwable $e)
    {

        $this->exception = $e;

        $date = \DateTime::createFromFormat('U.u', microtime(true));
        $date = $date !== false ? $date : new \DateTime();
        $dateTimeZone = new \DateTimeZone(date_default_timezone_get());
        $date->setTimezone($dateTimeZone);

        $this->date = $date;

        if (!defined('LOG_ERRORS_PATH')) {
            define('LOG_ERRORS_PATH', realpath(__DIR__ . '/../../../../../logs'));
        }
        if (!defined('LOG_ERRORS_BACKUP_PATH')) {
            define('LOG_ERRORS_BACKUP_PATH', realpath(__DIR__ . '/../../../../../logs/olds'));
        }
        $this->directory = LOG_ERRORS_PATH;
        $this->directoryBackup = LOG_ERRORS_BACKUP_PATH;

        if (!file_exists($this->directory)) {
            make_directory($this->directory);
        }
        if (!file_exists($this->directoryBackup)) {
            make_directory($this->directoryBackup);
        }

        $this->fileLocation = $this->directory . '/error.log.json';
        $this->oldFileLocation = $this->directoryBackup . '/error.log.{{DATE}}.json';

        $this->fileLocationPlain = $this->directory . '/error.plain.log';
        $this->oldFileLocationPlain = $this->directoryBackup . '/error.plain.{{DATE}}.log';

        $this->fileLocationUniqueMessage = $this->directory . '/error.unique.message.log';
    }

    /**
     * @return Throwable
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param bool $plainLog
     * @return void
     */
    public function logging(bool $plainLog = true)
    {
        $exists = file_exists($this->fileLocation);
        $fileLogSizeMB = $exists ? filesize($this->fileLocation) / 1024 / 1024 : 0;
        $fileLogJSON = [];
        $oldFileLogJSON = [];
        $backupOld = false;

        if ($exists) {
            $content = file_get_contents($this->fileLocation);
            $fileLogJSON = json_decode($content, true);
            if (json_last_error() !== \JSON_ERROR_NONE) {
                $fileLogJSON = [];
            }
        }

        if ($fileLogSizeMB > $this->maxSizeMB) {
            $oldFileLogJSON = $fileLogJSON;
            $fileLogJSON = [];
            $backupOld = true;
        }

        $classException = get_class($this->exception);
        $dateCurrent = $this->date->format('d-m-Y');
        $timeCurrent = $this->date->format('H:i:s.u');

        // Limpiar traza (eliminar argumentos para reducir tamaño y redundancia)
        $cleanTrace = [];
        foreach ($this->exception->getTrace() as $frame) {
            $cleanFrame = [
                'file' => $frame['file'] ?? 'unknown',
                'line' => $frame['line'] ?? '?',
                'function' => $frame['function'] ?? 'unknown',
            ];
            if (isset($frame['class'])) {
                $cleanFrame['class'] = $frame['class'];
            }
            $cleanTrace[] = $cleanFrame;
        }

        $codeException = '-';
        try {
            $codeException = $this->exception->getCode();
        } catch (\Throwable $e) {}

        // Preparar entrada
        $logEntry = [
            'time' => $timeCurrent,
            'type' => $classException,
            'message' => $this->exception->getMessage(),
            'code' => $codeException,
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
            'extraData' => method_exists($this->exception, 'extraData') ? call_user_func([$this->exception, 'extraData']) : [],
            'trace' => $cleanTrace,
        ];

        // Agrupar solo por fecha (evita el nivel extra de 'time' como clave)
        if (!isset($fileLogJSON[$dateCurrent])) {
            // Si es un día nuevo, lo ponemos al principio (orden cronológico inverso)
            $fileLogJSON = array_merge([$dateCurrent => []], $fileLogJSON);
        }

        // Añadir al principio del array del día (más reciente primero)
        array_unshift($fileLogJSON[$dateCurrent], $logEntry);

        // Guardar JSON
        file_put_contents($this->fileLocation, json_encode($fileLogJSON, \JSON_PRETTY_PRINT  | \JSON_UNESCAPED_UNICODE  | \JSON_UNESCAPED_SLASHES));
        @chmod($this->fileLocation, 0644);

        if ($backupOld) {
            $file_old_output = str_replace(
                '{{DATE}}',
                $this->date->format('d-m-Y h-i-s.u'),
                $this->oldFileLocation
            );
            $fp = fopen($file_old_output, 'w+');
            fwrite($fp, json_encode($oldFileLogJSON));
            fclose($fp);
            @chmod($file_old_output, 0644);
        }

        // Plain Log
        if ($plainLog) {

            $trace = $this->exception->getTrace();
            $traceSummary = [];
            foreach ($trace as $i => $frame) {
                if ($i > 2) {
                    break;
                }
                $file = isset($frame['file']) ? $frame['file'] : 'unknown';
                $line = isset($frame['line']) ? $frame['line'] : '?';
                $function = isset($frame['function']) ? $frame['function'] : 'unknown';
                $traceSummary[] = "{$file}:{$line} ({$function})";
            }
            $traceString = count($traceSummary) > 0 ? " | Trace: " . implode(" <- ", $traceSummary) : "";

            $plainLogEntry = sprintf(
                "[%s] [%s] [%s] %s in %s:%s%s\n",
                $this->date->format('Y-m-d H:i:s.u'),
                $classException,
                $codeException,
                $this->exception->getMessage(),
                $this->exception->getFile(),
                $this->exception->getLine(),
                $traceString
            );

            $plainLogExists = file_exists($this->fileLocationPlain);
            $plainLogSizeMB = $plainLogExists ? filesize($this->fileLocationPlain) / 1024 / 1024 : 0;

            if ($plainLogSizeMB > $this->maxSizeMB) {
                $file_old_output_plain = str_replace(
                    '{{DATE}}',
                    $this->date->format('d-m-Y h-i-s.u'),
                    $this->oldFileLocationPlain
                );
                rename($this->fileLocationPlain, $file_old_output_plain);
                @chmod($file_old_output_plain, 0644);
            }

            file_put_contents($this->fileLocationPlain, $plainLogEntry, \FILE_APPEND);
            @chmod($this->fileLocationPlain, 0644);
        }
    }

    /**
     * Registra un mensaje de error único.
     *
     * Si el mensaje completo (incluyendo cabecera y traza de 4 niveles) ya existe en el log, no se vuelve a añadir.
     * La firma de unicidad se basa en las 5 líneas del reporte (sin el timestamp).
     *
     * @return void
     */
    public function loggingUniqueMessage()
    {
        $classException = get_class($this->exception);
        $message = $this->exception->getMessage();
        $file = $this->exception->getFile();
        $line = $this->exception->getLine();

        // Código de excepción
        $codeException = '-';
        try {
            $codeException = $this->exception->getCode();
        } catch (\Throwable $e) {}

        // Construir la firma de 5 líneas (sin el timestamp inicial)
        // Línea 1: Cabecera
        $headerSignature = sprintf("[%s] [%s] %s in %s:%s\n", $classException, $codeException, $message, $file, $line);

        // Líneas 2 a 5: Traza
        $traceLines = "";
        $trace = $this->exception->getTrace();
        for ($i = 0; $i < 4; $i++) {
            if (isset($trace[$i])) {
                $traceFile = $trace[$i]['file'] ?? 'unknown';
                $traceLine = $trace[$i]['line'] ?? '?';
                $traceLines .= sprintf("     in %s:%s\n", $traceFile, $traceLine);
            } else {
                $traceLines .= "     in unknown:?\n";
            }
        }

        $fullSignature = $headerSignature . $traceLines;

        $existsInFile = false;
        if (file_exists($this->fileLocationUniqueMessage)) {
            // Buscamos la firma completa de 5 líneas para evitar duplicados exactos de flujo
            $content = file_get_contents($this->fileLocationUniqueMessage);
            if (mb_strpos($content, $fullSignature) !== false) {
                $existsInFile = true;
            }
        }

        if (!$existsInFile) {
            // Si no existe el flujo exacto, lo añadimos con el timestamp
            $logEntry = sprintf("[%s] %s", $this->date->format('Y-m-d H:i:s.u'), $fullSignature);
            file_put_contents($this->fileLocationUniqueMessage, $logEntry, \FILE_APPEND);
            @chmod($this->fileLocationUniqueMessage, 0644);
        }
    }
}
