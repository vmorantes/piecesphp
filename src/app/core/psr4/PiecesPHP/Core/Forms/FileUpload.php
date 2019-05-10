<?php

/**
 * FileUpload.php
 */
namespace PiecesPHP\Core\Forms;

/**
 * FileUpload.
 *
 * Representa un archivo subido por formulario
 *
 * @package     PiecesPHP\Core\Forms
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @version     v.1.0
 * @copyright   Copyright (c) 2019
 */
class FileUpload
{
    /**
     * $validator
     *
     * @var FileValidator
     */
    protected $validator;
    /**
     * $fileInformation
     *
     * @var array
     */
    protected $fileInformation;
    /**
     * $multiple
     *
     * @var bool
     */
    protected $multiple = false;
    /**
     * $errorMessages
     *
     * @var string[]
     */
    protected $errorMessages = [];
    /**
     * $name
     *
     * @var string
     */
    protected $name = '';
    /**
     * $directoryMove
     *
     * @var string
     */
    protected $directoryMove = null;
    /**
     * $nameOnMove
     *
     * @var string
     */
    protected $nameOnMove = null;
    /**
     * $extensionOnMove
     *
     * @var string
     */
    protected $extensionOnMove = null;

    const NOT_UPLOAD_FAKE_NAME = 'NOT_FILE';
    const NOT_UPLOAD_FAKE_TYPE = 'mimetype/unexists';
    const NOT_UPLOAD_FAKE_SIZE = 100 * 100 * 100 * 100;
    const NOT_UPLOAD_FAKE_TMP_NAME = 'NOT_FILE';
    const NOT_UPLOAD_FAKE_ERROR = 'FAKE_ERROR';

    /**
     * __construct
     *
     * @param string $name
     * @param array $types
     * @param int $max_size_mb
     * @param bool $multiple
     * @return static
     * @throws \Exception
     */
    public function __construct(string $name, array $types = [], int $max_size_mb = null, bool $multiple = false)
    {
        $this->validator = new FileValidator($types, $max_size_mb);
        $this->multiple = $multiple;
        $this->fileInformation = [];
        $this->name = $name;
        $files = $_FILES;

        if (!isset($files[$name]) || $files[$name]['error'] == \UPLOAD_ERR_NO_FILE) {
            $files[$name] = [
                'name' => !$multiple ? self::NOT_UPLOAD_FAKE_NAME : [self::NOT_UPLOAD_FAKE_NAME],
                'type' => !$multiple ? self::NOT_UPLOAD_FAKE_TYPE : [self::NOT_UPLOAD_FAKE_TYPE],
                'size' => !$multiple ? self::NOT_UPLOAD_FAKE_SIZE : [self::NOT_UPLOAD_FAKE_SIZE],
                'tmp_name' => !$multiple ? self::NOT_UPLOAD_FAKE_TMP_NAME : [self::NOT_UPLOAD_FAKE_TMP_NAME],
                'error' => !$multiple ? self::NOT_UPLOAD_FAKE_ERROR : [self::NOT_UPLOAD_FAKE_ERROR],
            ];
        }

        if ($this->multiple) {

            $files = $files[$name];
            $values_files_is_array = is_array($files['name']);
            $count_files = count($files['name']);
            $properties = array_keys($files);

            if ($values_files_is_array) {

                for ($i = 0; $i < $count_files; $i++) {
                    $file = [];
                    foreach ($properties as $property) {
                        $file[$property] = $files[$property][$i];
                    }
                    $this->fileInformation[] = $file;
                }
            } else {
                throw new \Exception("El archivo $name debe ser un archivo de subida múltiple.");
            }

        } else {

            $is_multiple_value = is_array($files[$name]['name']);
            if (!$is_multiple_value) {
                $this->fileInformation = $files[$name];
            } else {
                throw new \Exception("No se aceptan subidas múltiples en $name.");
            }

        }

    }

    /**
     * validate
     *
     * @return bool
     * @throws \Exception en caso de no ser un archivo subido mediante formulario
     */
    public function validate()
    {
        $this->errorMessages = [];
        $valid = true;
        $files = $this->isMultiple() ? $this->fileInformation : [$this->fileInformation];

        foreach ($files as $file) {

            $tmp = $file['tmp_name'];

            if (is_uploaded_file($tmp)) {

                if (!$this->validator->validate($tmp, $file['name'])) {
                    $this->errorMessages[] = $this->validator->getMessage();
                    $valid = false;
                }

            } else {

                if ($tmp != self::NOT_UPLOAD_FAKE_TMP_NAME) {
                    throw new \Exception("Los archivos deben ser subidos mediante POST.");
                } else {
                    $this->errorMessages[] = 'No se ha subido ningún archivo.';
                    $valid = false;
                }

            }

        }

        return $valid;
    }

    /**
     * setNameOnMove
     *
     * @param string $name
     * @return static
     */
    public function setNameOnMove(string $name)
    {
        $this->nameOnMove = $name;
        return $this;
    }

    /**
     * setExtensionOnMove
     *
     * @param string $extension
     * @return static
     */
    public function setExtensionOnMove(string $extension)
    {
        $this->extensionOnMove = $extension;
        return $this;
    }

    /**
     * setDirectoryMove
     *
     * @param string $directory
     * @return static
     */
    public function setDirectoryMove(string $directory)
    {
        $this->directoryMove = $directory;
        return $this;
    }

    /**
     * getErrorMessages
     *
     * @return array
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    /**
     * getFileInformation
     *
     * @return array
     */
    public function getFileInformation()
    {
        return $this->fileInformation;
    }

    /**
     * getName
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * isMultiple
     *
     * @return bool
     */
    public function isMultiple()
    {
        return $this->multiple;
    }

    /**
     * moveTo
     *
     * @param string $directory
     * @param string $name
     * @param string $extension
     * @param bool $validate
     * @param bool $overwrite
     * @return string[] Rutas de los ficheros movidos (las rutas que no fueron movidas se mostrarán vacías)
     * @throws Exception Si no hay directorio de destino definido
     */
    public function moveTo(string $directory = null, string $name = null, string $extension = null, bool $validate = true, bool $overwrite = true)
    {
        $multiple = $this->isMultiple();

        $files = $multiple ? $this->fileInformation : [$this->fileInformation];

        $counter_files = 0;

        $moved_files = [];

        $move = $validate ? $this->validate() : true;

        if (is_null($directory)) {
            if (!is_null($this->directoryMove)) {
                $directory = $this->directoryMove;
            } else {
                throw new \Exception("No hay ningún directorio de destino definido");
            }
        }

        $directory = is_null($directory) ? $this->directoryMove : $directory;
        $name = is_null($name) ? $this->nameOnMove : $name;
        $extension = is_null($extension) ? $this->extensionOnMove : $extension;

        if ($move) {
            foreach ($files as $file) {

                $counter_files++;
                $new_name = $name;

                if (!is_null($name) && $multiple) {
                    $name = pathinfo($file['name'], \PATHINFO_BASENAME);
                    $new_name = "{$name}_{$counter_files}";
                }

                if (is_null($extension)) {
                    $extension = pathinfo($file['name'], \PATHINFO_EXTENSION);
                }
                $tmp = $file['tmp_name'];
                $moved_files[] = self::moveFileTo($directory, $tmp, $new_name, $extension, $overwrite);
            }
        }

        return $moved_files;

    }

    /**
     * moveFileTo
     *
     * @param string $directory
     * @param string $file
     * @param string $basename
     * @param string $extension
     * @param bool $overwrite
     * @return string La ruta a la que fue movido (string vacío si no fue movido)
     */
    public static function moveFileTo(string $directory, string $file, string $basename = null, string $extension = null, bool $overwrite = true)
    {
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        if (is_null($extension)) {
            $extension = pathinfo($file, \PATHINFO_EXTENSION);
        }

        if (is_null($basename)) {
            $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        }

        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $filepath = $directory . DIRECTORY_SEPARATOR . $filename;
        $filepath = str_replace(['//', '\\\\'], ['/', '\\'], $filepath);

        $exists = file_exists($filepath);
        $uploaded = false;

        if ($exists) {
            if ($overwrite) {
                unlink($filepath);
                $uploaded = move_uploaded_file($file, $filepath);
            }
        } else {
            $uploaded = move_uploaded_file($file, $filepath);
        }

        return $uploaded ? $filepath : '';
    }
}
