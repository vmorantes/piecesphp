<?php

/**
 * GenericHandler.php
 */
namespace PiecesPHP\Core\CustomErrorsHandlers;

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
     * $exception
     *
     * @var \Exception|\Error
     */
    protected $exception;
    /**
     * $maxSizeMB
     *
     * @var int
     */
    protected $maxSizeMB = 1;
    /**
     * $date
     *
     * @var \DateTime
     */
    protected $date = null;
    /**
     * $fileLocation
     *
     * @var string
     */
    protected $fileLocation = '';
    /**
     * $oldFileLocation
     *
     * @var string
     */
    protected $oldFileLocation = '';
    /**
     * $directory
     *
     * @var string
     */
    protected $directory = '';
    /**
     * $directoryBackup
     *
     * @var string
     */
    protected $directoryBackup = '';

    /**
     * __construct
     *
     * @param \Exception|\Error $e
     * @return static
     * @throws \TypeError
     */
    public function __construct($e)
    {
        if (!$e instanceof \Exception && !$e instanceof \Error) {
            throw new \TypeError('Error type unexpected.');
        }

        $this->exception = $e;
        $this->date = new \DateTime();

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
    }

    /**
     * logging
     *
     * @return void
     */
    public function logging()
    {
        $exists = file_exists($this->fileLocation);

        $fileLogSizeMB = $exists ? filesize($this->fileLocation) / 1024 / 1024 : 0;

        $fileLogJSON = $exists ? json_decode(file_get_contents($this->fileLocation), true) : [];
        $oldFileLogJSON = [];
        $backupOld = false;

        $classException = get_class($this->exception);
        $message = $classException . "\r\n\t" . $this->exception->getMessage();

        if (json_last_error() == \JSON_ERROR_NONE) {

            uksort($fileLogJSON, function ($a, $b) {
                $aDate = new \DateTime(explode(';', $a)[0]);
                $bDate = new \DateTime(explode(';', $b)[0]);
                if ($aDate == $bDate) {
                    return 0;
                } else {
                    return $aDate < $bDate ? 1 : 0; //Ordenación descendente
                }
            });

            if ($fileLogSizeMB > $this->maxSizeMB) {
                $oldFileLogJSON = $fileLogJSON;
                $fileLogJSON = [];
                $backupOld = true;
            }

        } else {
            $fileLogJSON = [];
        }

        $fileLogJSON[$this->date->format('Y-m-d h:i:s') . ';' . uniqid()] = [
            'type' => $classException,
            'message' => $this->exception->getMessage(),
            'code' => $this->exception->getCode(),
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
            'trace' => $this->exception->getTrace(),
        ];

        $fileLogJSON = json_encode($fileLogJSON, \JSON_PRETTY_PRINT);

        file_put_contents($this->fileLocation, $fileLogJSON);

        if ($backupOld) {
            file_put_contents(
                str_replace(
                    '{{DATE}}',
                    $this->date->format('Y_m_d_h-i-s'),
                    $this->oldFileLocation
                ),
                json_encode($oldFileLogJSON)
            );
        }

    }

}
