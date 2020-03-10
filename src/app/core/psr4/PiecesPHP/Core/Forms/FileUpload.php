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
     * $quantity
     *
     * @var int
     */
    protected $quantity = 0;
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

        if ($this->multiple) {

            if (isset($files[$name])) {

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

                        if (!isset($file) || $file['error'] == \UPLOAD_ERR_NO_FILE) {
                            $file = [
                                'name' => self::NOT_UPLOAD_FAKE_NAME,
                                'type' => self::NOT_UPLOAD_FAKE_TYPE,
                                'size' => self::NOT_UPLOAD_FAKE_SIZE,
                                'tmp_name' => self::NOT_UPLOAD_FAKE_TMP_NAME,
                                'error' => self::NOT_UPLOAD_FAKE_ERROR,
                            ];
                        } else {
                            $this->quantity++;
                        }

                        $this->fileInformation[] = $file;
                    }

                } else {
                    throw new \Exception("El archivo $name debe ser un archivo de subida múltiple.");
                }

            } else {

                $this->fileInformation[] = [
                    'name' => self::NOT_UPLOAD_FAKE_NAME,
                    'type' => self::NOT_UPLOAD_FAKE_TYPE,
                    'size' => self::NOT_UPLOAD_FAKE_SIZE,
                    'tmp_name' => self::NOT_UPLOAD_FAKE_TMP_NAME,
                    'error' => self::NOT_UPLOAD_FAKE_ERROR,
                ];

            }

        } else {

            if (isset($files[$name])) {

                $is_multiple_value = is_array($files[$name]['name']);

                if (!$is_multiple_value) {

                    if (!isset($files[$name]) || $files[$name]['error'] == \UPLOAD_ERR_NO_FILE) {
                        $files[$name] = [
                            'name' => self::NOT_UPLOAD_FAKE_NAME,
                            'type' => self::NOT_UPLOAD_FAKE_TYPE,
                            'size' => self::NOT_UPLOAD_FAKE_SIZE,
                            'tmp_name' => self::NOT_UPLOAD_FAKE_TMP_NAME,
                            'error' => self::NOT_UPLOAD_FAKE_ERROR,
                        ];
                    } else {

                        $this->quantity++;

                    }

                    $this->fileInformation = $files[$name];

                } else {
                    throw new \Exception("No se aceptan subidas múltiples en $name.");
                }

            } else {

                $this->fileInformation = [
                    'name' => self::NOT_UPLOAD_FAKE_NAME,
                    'type' => self::NOT_UPLOAD_FAKE_TYPE,
                    'size' => self::NOT_UPLOAD_FAKE_SIZE,
                    'tmp_name' => self::NOT_UPLOAD_FAKE_TMP_NAME,
                    'error' => self::NOT_UPLOAD_FAKE_ERROR,
                ];

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
            $error = $file['error'];

            if ($error == \UPLOAD_ERR_OK) {

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

            } elseif ($error == \UPLOAD_ERR_INI_SIZE) {

                $max_upload = min(ini_get('post_max_size'), ini_get('upload_max_filesize'));
                $max_upload = str_replace('M', 'MB', $max_upload);

                $this->errorMessages[] = 'El archivo excede el peso máximo permitido por el servidor. (' . "$max_upload" . ')';
                $valid = false;

            } elseif ($error == \UPLOAD_ERR_FORM_SIZE) {

                $message_error_size = 'El archivo excede el peso máximo permitido.';

                if (isset($_POST['MAX_FILE_SIZE']) && ctype_digit($_POST['MAX_FILE_SIZE'])) {
                    $max_upload = $_POST['MAX_FILE_SIZE'] / 1000 / 1000;
                    $max_upload = (int) floor($max_upload);
                    $message_error_size .= " ({$max_upload}MB)";
                }

                $this->errorMessages[] = $message_error_size;

                $valid = false;

            } elseif ($error == \UPLOAD_ERR_PARTIAL) {

                $this->errorMessages[] = 'El archivo no se subió completamente.';
                $valid = false;

            } elseif ($error == \UPLOAD_ERR_NO_FILE) {

                $this->errorMessages[] = 'No ha subido ningún archivo.';
                $valid = false;

            } elseif ($error == \UPLOAD_ERR_NO_TMP_DIR) {

                $this->errorMessages[] = 'No se ha subido el archivo. Problema con el directorio temporal.';
                $valid = false;

            } elseif ($error == \UPLOAD_ERR_CANT_WRITE) {

                $this->errorMessages[] = 'No se ha subido ningún archivo. Problema al escribir en disco.';
                $valid = false;

            } elseif ($error == \UPLOAD_ERR_EXTENSION) {

                $this->errorMessages[] = 'No se ha subido ningún archivo. Problema con alguna extensión.';
                $valid = false;

            }

        }

        return $valid;
    }

    /**
     * getQuantity
     *
     * @return int
     */
    public function getQuantity()
    {

        return $this->quantity;

    }

    /**
     * hasInput
     *
     * @return bool
     */
    public function hasInput()
    {
        $has = true;

        $files = $this->fileInformation;

        if ($this->multiple) {
            foreach ($files as $value) {
                if ($value['error'] === self::NOT_UPLOAD_FAKE_ERROR) {
                    $has = false;
                    break;
                }
            }
        } else {
            if ($files['error'] === self::NOT_UPLOAD_FAKE_ERROR) {
                $has = false;
            }
        }

        return $has;
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

        $counterFiles = 0;

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

                $counterFiles++;
                $newName = $name;
                $newExtension = $extension;

                if (!is_null($name) && $multiple) {
                    $newName = pathinfo($newName, \PATHINFO_FILENAME);
                    $newName = "{$newName}_{$counterFiles}";
                }

                if (is_null($extension)) {
                    $newExtension = pathinfo($file['name'], \PATHINFO_EXTENSION);
                }
                $tmp = $file['tmp_name'];
                $moved_files[] = self::moveFileTo($directory, $tmp, $newName, $newExtension, $overwrite);
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
