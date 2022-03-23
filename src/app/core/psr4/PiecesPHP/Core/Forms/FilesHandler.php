<?php

/**
 * FilesHandler.php
 */
namespace PiecesPHP\Core\Forms;

/**
 * FilesHandler.
 *
 * Manejador de subida de ficheros
 *
 * @package     PiecesPHP\Core\Forms
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class FilesHandler
{
    /**
     * @var FileUpload[]
     */
    protected $files = [];
    /**
     * @var array
     */
    protected $errorMessages = [];

    /**
     * @param FileUpload[] $files
     * @return static
     */
    public function __construct(array $files)
    {
        if (!self::isArrayFileUpload($files)) {
            throw new \TypeError('El parámetro $files debe ser de tipo FileUpload[]');
        }
        foreach ($files as $file) {
            $this->files[$file->getName()] = $file;
        }
    }

    /**
     * @param string $directory
     * @param bool $validate
     * @param bool $overwrite
     * @return array Las rutas de los archivos movidos dentro de los índices con su nombre
     */
    public function moveFilesTo(string $directory, bool $validate = true, bool $overwrite = true)
    {
        $moved = [];
        foreach ($this->files as $file) {
            $moved[$file->getName()] = $file->moveTo($directory, null, null, $validate, $overwrite);
        }
        return $moved;
    }

    /**
     * @return bool
     */
    public function validate()
    {
        $valid = true;
        $this->errorMessages = [];
        foreach ($this->files as $file) {
            if (!$file->validate()) {
                $this->errorMessages[$file->getName()] = $file->getErrorMessages();
                $valid = false;
            } else {
                $this->errorMessages[$file->getName()] = [];
            }
        }
        return $valid;
    }

    /**
     * @return array
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    /**
     * @param string $name
     * @return FileUpload|null
     */
    public function getFile(string $name)
    {
        return isset($this->files[$name]) ? $this->files[$name] : null;
    }

    /**
     * @return FileUpload[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param array $files
     * @return bool
     */
    public static function isArrayFileUpload(array $files)
    {
        foreach ($files as $file) {
            if (!($file instanceof FileUpload)) {
                return false;
            }
        }
        return true;
    }
}
