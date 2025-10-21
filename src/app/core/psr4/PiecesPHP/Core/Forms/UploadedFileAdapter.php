<?php

/**
 * UploadedFileAdapter.php
 */
namespace PiecesPHP\Core\Forms;

/**
 * UploadedFileAdapter.
 *
 * Representa un archivo subido por formulario
 *
 * @package     PiecesPHP\Core\Forms
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class UploadedFileAdapter
{
    /**
     * @var FileValidator
     */
    protected $validator;
    /**
     * @var array
     */
    protected $fileInformation;
    /**
     * @var string[]
     */
    protected $associativePath = [];
    /**
     * @var string[]
     */
    protected $errorMessages = [];
    /**
     * @var string
     */
    protected $directoryMove = null;
    /**
     * @var string
     */
    protected $nameOnMove = null;
    /**
     * @var string
     */
    protected $extensionOnMove = null;

    const NOT_UPLOAD_FAKE_NAME = 'NOT_FILE';
    const NOT_UPLOAD_FAKE_TYPE = 'mimetype/unexists';
    const NOT_UPLOAD_FAKE_SIZE = 100 * 100 * 100 * 100;
    const NOT_UPLOAD_FAKE_TMP_NAME = 'NOT_FILE';
    const NOT_UPLOAD_FAKE_ERROR = 'FAKE_ERROR';

    const LANG_GROUP = 'UploadedFileAdapter';

    /**
     * @param string[] $associativePath Arreglo que representa el camino deseado, por ejemplo, para files['groupOne']['name']['fileOne'] sería
     * ['groupOne', 'fileOne'] (name se omite porque forma parte del contenido estándar de $_FILES)
     * @param array $types
     * @param int $maxSizeMB
     * @return static
     * @throws \Exception
     */
    public function __construct(array $associativePath, array $types = [], int $maxSizeMB = null)
    {

        $associativePath = array_filter($associativePath, function ($e) {
            return is_scalar($e);
        });
        $this->associativePath = $associativePath;
        $this->validator = new FileValidator($types, $maxSizeMB);
        $defaultInformation = [
            'name' => self::NOT_UPLOAD_FAKE_NAME,
            'type' => self::NOT_UPLOAD_FAKE_TYPE,
            'size' => self::NOT_UPLOAD_FAKE_SIZE,
            'tmp_name' => self::NOT_UPLOAD_FAKE_TMP_NAME,
            'error' => self::NOT_UPLOAD_FAKE_ERROR,
            'full_path' => null,
        ];
        $this->fileInformation = $defaultInformation;

        $files = $_FILES;
        $notFoundUploadedFileMessage = __(self::LANG_GROUP, "No se encontró ningún archivo cargado.");
        $generalErrorMessage = __(self::LANG_GROUP, "Los archivos no han sido cargados correctamente.");
        $shouldBeUniqueFileMessage = __(self::LANG_GROUP, "Debe ser un solo archivo por manejador.");

        if (!empty($associativePath)) {

            $nameOnFiles = $associativePath[0];
            $exists = array_key_exists($nameOnFiles, $files);
            if ($exists) {

                unset($associativePath[0]);
                $filesByName = $files[$nameOnFiles];
                $fileData = $defaultInformation;
                $fileInformationNames = [
                    'name',
                    'type',
                    'size',
                    'tmp_name',
                    'error',
                    'full_path',
                ];
                $optionalsInformation = [
                    'full_path',
                ];

                if (empty($associativePath)) {

                    foreach ($fileInformationNames as $informationName) {

                        $isOptionalInformation = in_array($informationName, $optionalsInformation);

                        if (array_key_exists($informationName, $filesByName)) {

                            $informationValue = $filesByName[$informationName];
                            $isNotArray = !is_array($informationValue);

                            if ($isNotArray) {
                                $fileData[$informationName] = $informationValue;
                            } else {
                                throw new \Exception($generalErrorMessage);
                            }

                        } else if (!$isOptionalInformation) {
                            throw new \Exception($generalErrorMessage);
                        }

                    }

                } else {

                    $getScalar = function ($element, $callback, $path) {
                        $elementIsArray = is_array($element);
                        $path = array_values($path);
                        $pathNext = $path;
                        $finalValue = null;
                        if (!empty($pathNext)) {
                            unset($pathNext[0]);
                        }
                        foreach ($path as $pathElement) {
                            if ($elementIsArray && array_key_exists($pathElement, $element)) {
                                $value = $element[$pathElement];
                                if (!is_scalar($value)) {
                                    $finalValue = ($callback)($value, $callback, $pathNext);
                                    if (empty($pathNext)) {
                                        if ($finalValue === null) {
                                            $finalValue = $value;
                                        }
                                    }
                                } else {
                                    $finalValue = $value;
                                }
                            }
                        }
                        return $finalValue;
                    };
                    if (is_array($filesByName['name'])) {

                        foreach ($fileInformationNames as $informationName) {

                            $isOptionalInformation = in_array($informationName, $optionalsInformation);

                            if (array_key_exists($informationName, $filesByName)) {

                                $informationValue = ($getScalar)($filesByName[$informationName], $getScalar, $associativePath);
                                $isNotArray = !is_array($informationValue);

                                if ($isNotArray) {
                                    $fileData[$informationName] = $informationValue;
                                } else {
                                    throw new \Exception($shouldBeUniqueFileMessage);
                                }

                            } else if (!$isOptionalInformation) {
                                throw new \Exception($generalErrorMessage);
                            }

                        }
                    }
                }

                $this->fileInformation = $fileData['name'] !== null ? $fileData : $defaultInformation;

            }

        } else {
            throw new \Exception($notFoundUploadedFileMessage);
        }

    }

    /**
     * @return bool
     * @throws \Exception en caso de no ser un archivo subido mediante formulario
     */
    public function validate()
    {
        $this->errorMessages = [];
        $valid = true;
        $file = $this->fileInformation;
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
                    throw new \Exception(__(self::LANG_GROUP, "Los archivos deben ser subidos mediante POST."));
                } else {
                    $this->errorMessages[] = __(self::LANG_GROUP, 'No se ha subido ningún archivo.');
                    $valid = false;
                }

            }

        } elseif ($error == \UPLOAD_ERR_INI_SIZE) {

            $max_upload = min(ini_get('post_max_size'), ini_get('upload_max_filesize'));
            $max_upload = str_replace('M', 'MB', $max_upload);

            $this->errorMessages[] = __(self::LANG_GROUP, 'El archivo excede el peso máximo permitido por el servidor. (' . "$max_upload" . ')');
            $valid = false;

        } elseif ($error == \UPLOAD_ERR_FORM_SIZE) {

            $message_error_size = __(self::LANG_GROUP, 'El archivo excede el peso máximo permitido.');

            if (isset($_POST['MAX_FILE_SIZE']) && ctype_digit($_POST['MAX_FILE_SIZE'])) {
                $max_upload = $_POST['MAX_FILE_SIZE'] / 1000 / 1000;
                $max_upload = (int) floor($max_upload);
                $message_error_size .= " ({$max_upload}MB)";
            }

            $this->errorMessages[] = __(self::LANG_GROUP, $message_error_size);

            $valid = false;

        } elseif ($error == \UPLOAD_ERR_PARTIAL) {

            $this->errorMessages[] = __(self::LANG_GROUP, 'El archivo no se subió completamente.');
            $valid = false;

        } elseif ($error == \UPLOAD_ERR_NO_FILE) {

            $this->errorMessages[] = __(self::LANG_GROUP, 'No ha subido ningún archivo.');
            $valid = false;

        } elseif ($error == \UPLOAD_ERR_NO_TMP_DIR) {

            $this->errorMessages[] = __(self::LANG_GROUP, 'No se ha subido el archivo. Problema con el directorio temporal.');
            $valid = false;

        } elseif ($error == \UPLOAD_ERR_CANT_WRITE) {

            $this->errorMessages[] = __(self::LANG_GROUP, 'No se ha subido ningún archivo. Problema al escribir en disco.');
            $valid = false;

        } elseif ($error == \UPLOAD_ERR_EXTENSION) {

            $this->errorMessages[] = __(self::LANG_GROUP, 'No se ha subido ningún archivo. Problema con alguna extensión.');
            $valid = false;

        }

        return $valid;
    }

    /**
     * @return bool
     */
    public function hasInput()
    {
        $has = true;
        if ($this->fileInformation['error'] === self::NOT_UPLOAD_FAKE_ERROR) {
            $has = false;
        }
        return $has;
    }

    /**
     * @param string $name
     * @return static
     */
    public function setNameOnMove(string $name)
    {
        $this->nameOnMove = $name;
        return $this;
    }

    /**
     * @param string $extension
     * @return static
     */
    public function setExtensionOnMove(string $extension)
    {
        $this->extensionOnMove = $extension;
        return $this;
    }

    /**
     * @param string $directory
     * @return static
     */
    public function setDirectoryMove(string $directory)
    {
        $this->directoryMove = $directory;
        return $this;
    }

    /**
     * @return array
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    /**
     * @return array{name:string,type:string,size:int,tmp_name:string,error:int,full_path:string,associativePath:string[]|null>}
     */
    public function getFileInformation()
    {
        $fileInformation = $this->fileInformation;
        $fileInformation['associativePath'] = $this->associativePath;
        return $fileInformation;
    }

    /**
     * @param string $directory
     * @param string $name
     * @param string $extension
     * @param bool $validate
     * @param bool $overwrite
     * @return string Ruta del fichero movido, si no fue movido quedará vacía
     * @throws Exception Si no hay directorio de destino definido
     */
    public function moveTo(string $directory = null, string $name = null, string $extension = null, bool $validate = true, bool $overwrite = true)
    {

        $file = $this->fileInformation;
        $output = '';

        $move = $validate ? $this->validate() : true;

        if (is_null($directory)) {
            if (!is_null($this->directoryMove)) {
                $directory = $this->directoryMove;
            } else {
                throw new \Exception(__(self::LANG_GROUP, "No hay ningún directorio de destino definido"));
            }
        }

        $directory = is_null($directory) ? $this->directoryMove : $directory;
        $name = is_null($name) ? $this->nameOnMove : $name;
        $extension = is_null($extension) ? $this->extensionOnMove : $extension;

        if ($move) {
            $newName = $name;
            $newExtension = $extension;
            if (is_null($extension)) {
                $newExtension = pathinfo($file['name'], \PATHINFO_EXTENSION);
            }
            $tmp = $file['tmp_name'];
            $output = self::moveFileTo($directory, $tmp, $newName, $newExtension, $overwrite);
        }

        return $output;

    }

    /**
     * @param string $directory
     * @param string $name
     * @param string $extension
     * @param bool $validate
     * @param bool $overwrite
     * @param bool $removeOriginal
     * @return string Ruta del fichero copiado, si no fue copiado quedará vacía
     * @throws Exception Si no hay directorio de destino definido
     */
    public function copyTo(string $directory = null, string $name = null, string $extension = null, bool $validate = true, bool $overwrite = true, bool $removeOriginal = false)
    {
        $file = $this->fileInformation;
        $output = '';

        $move = $validate ? $this->validate() : true;

        if (is_null($directory)) {
            if (!is_null($this->directoryMove)) {
                $directory = $this->directoryMove;
            } else {
                throw new \Exception(__(self::LANG_GROUP, "No hay ningún directorio de destino definido"));
            }
        }

        $directory = is_null($directory) ? $this->directoryMove : $directory;
        $name = is_null($name) ? $this->nameOnMove : $name;
        $extension = is_null($extension) ? $this->extensionOnMove : $extension;

        if ($move) {
            $newName = $name;
            $newExtension = $extension;
            if (is_null($extension)) {
                $newExtension = pathinfo($file['name'], \PATHINFO_EXTENSION);
            }
            $tmp = $file['tmp_name'];
            $output = self::copyFileTo($directory, $tmp, $newName, $newExtension, $overwrite, $removeOriginal);
        }

        return $output;

    }

    /**
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

    /**
     * @param string $directory
     * @param string $file
     * @param string $basename
     * @param string $extension
     * @param bool $overwrite
     * @param bool $removeOriginal
     * @return string La ruta a la que fue movido (string vacío si no fue movido)
     */
    public static function copyFileTo(string $directory, string $file, string $basename = null, string $extension = null, bool $overwrite = true, bool $removeOriginal = false)
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
        $copied = false;

        if ($exists) {
            if ($overwrite) {
                unlink($filepath);
                $copied = copy($file, $filepath);
            }
        } else {
            $copied = copy($file, $filepath);
        }

        if ($copied) {
            if ($removeOriginal) {
                @unlink($file);
            }
        }

        return $copied ? $filepath : '';
    }

    /**
     * Devuelve todos los conjuntos de índices que corresponden a un archivo único
     * @param string $name
     * @return array<string[]>
     */
    public static function findAssociativePathsByName(string $name)
    {
        $filesByName = array_key_exists($name, $_FILES) ? $_FILES[$name] : null;
        $associativePaths = [];
        if ($filesByName !== null) {
            $fileTmpNames = array_key_exists('name', $filesByName) ? $filesByName['tmp_name'] : null;
            if ($fileTmpNames !== null) {
                $associativePaths = self::getFilePathsOnUpload($fileTmpNames, [$name]);
            }
        }
        return $associativePaths;
    }

    /**
     * @param array<string|int|array> $filesDataOnTmpName
     * @param array<string|int> $currentPath
     * @return array<string[]>
     */
    public static function getFilePathsOnUpload(array $filesDataOnTmpName, array $currentPath = [])
    {
        $paths = [];
        foreach ($filesDataOnTmpName as $key => $value) {
            $newPath = array_merge($currentPath, [$key]);
            if (is_array($value)) {
                $paths = array_merge($paths, self::getFilePathsOnUpload($value, $newPath));
            } else {
                if (!empty($value)) {
                    $paths[] = $newPath;
                }
            }
        }
        return $paths;
    }

}
