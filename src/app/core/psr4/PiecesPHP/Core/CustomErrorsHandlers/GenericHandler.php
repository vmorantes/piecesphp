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
     * @var \Exception|\Error
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

        $date = \DateTime::createFromFormat('U.u', microtime(true));
        $dateTimeZone = new \DateTimeZone(date_default_timezone_get());
        $date->setTimezone($dateTimeZone);

        $this->date = $date;

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

        if (json_last_error() == \JSON_ERROR_NONE) {

            uksort($fileLogJSON, function ($a, $b) {
                $aDate = \DateTime::createFromFormat('d-m-Y', $a);
                $bDate = \DateTime::createFromFormat('d-m-Y', $b);
                $result = 0;
                $asc = false;

                if ($aDate == $bDate) {
                    $result = 0;
                } else {
                    $result = $aDate < $bDate ? -1 : 1;
                }

                if ($asc) {
                    return $result;
                } elseif ($result != 0) {
                    return $result > 0 ? -1 : 1;
                } else {
                    return 0;
                }
            });

            foreach ($fileLogJSON as $time => $value) {
                uksort($value, function ($a, $b) {
                    $aDate = \DateTime::createFromFormat('d-m-Y H:i:s.u', "01-01-1999 $a");
                    $bDate = \DateTime::createFromFormat('d-m-Y H:i:s.u', "01-01-1999 $b");
                    $result = 0;
                    $asc = false;

                    if ($aDate == $bDate) {
                        $result = 0;
                    } else {
                        $result = $aDate < $bDate ? -1 : 1;
                    }

                    if ($asc) {
                        return $result;
                    } elseif ($result != 0) {
                        return $result > 0 ? -1 : 1;
                    } else {
                        return 0;
                    }
                });
                $fileLogJSON[$time] = $value;
            }

            if ($fileLogSizeMB > $this->maxSizeMB) {
                $oldFileLogJSON = $fileLogJSON;
                $fileLogJSON = [];
                $backupOld = true;
            }
        } else {
            $fileLogJSON = [];
        }

        //Crear grupo por fecha
        $date_current = $this->date->format('d-m-Y');
        $time = $this->date->format('H:i:s');

        if (!array_key_exists($date_current, $fileLogJSON)) {
            $fileLogJSON[$date_current] = [];
        }
        if (!array_key_exists($time, $fileLogJSON[$date_current])) {
            $fileLogJSON[$date_current][$time] = [];
        }

        $fileLogJSON[$date_current][$time][] = [
            'type' => $classException,
            'message' => $this->exception->getMessage(),
            'code' => $this->exception->getCode(),
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
            'trace' => $this->exception->getTrace(),
        ];

        $fileLogJSON = json_encode($fileLogJSON);

        file_put_contents($this->fileLocation, $fileLogJSON);
        @chmod($this->fileLocation, 0777);

        if ($backupOld) {
            $file_old_output = str_replace(
                '{{DATE}}',
                $this->date->format('d-m-Y h-i-s.u'),
                $this->oldFileLocation
            );
            $fp = fopen($file_old_output, 'w+');
            fwrite($fp, json_encode($oldFileLogJSON));
            fclose($fp);
            @chmod($file_old_output, 0777);
        }
    }
}
