<?php

/**
 * UploadedFileValidation.php
 */

namespace PiecesPHP\Core\Forms;

/**
 * UploadedFileValidation - La clasificación de un archivo subido, compartida por FileUpload y UploadedFileAdapter.
 *
 * @package     PiecesPHP\Core\Forms
 */
class UploadedFileValidation
{
    const NOT_UPLOAD_FAKE_TMP_NAME = 'NOT_FILE';
    const NOT_UPLOAD_FAKE_ERROR = 'FAKE_ERROR';

    /**
     * @param array $file Una entrada con la forma de $_FILES (name, tmp_name y error)
     * @param FileValidator $validator
     * @param bool $ignorePOSTUploaded
     * @param callable(string):string $message Recibe cada texto fijo en español y devuelve el que se muestra
     * @return string[] Los errores; vacío si el archivo es válido
     * @throws \Exception si el archivo existe pero no llegó por POST
     */
    public static function errors(array $file, FileValidator $validator, bool $ignorePOSTUploaded, callable $message): array
    {
        $tmp = $file['tmp_name'];
        $error = $file['error'];
        $errors = [];

        //NO compares $error con `==`: vale la CADENA FAKE_ERROR si la clave no venía. Ver T135.
        if ($error === self::NOT_UPLOAD_FAKE_ERROR) {

            $errors[] = $message('No se ha subido ningún archivo.');

        } elseif ($error == \UPLOAD_ERR_OK) {

            if (is_uploaded_file($tmp) || $ignorePOSTUploaded) {

                if (!$validator->validate($tmp, $file['name'])) {
                    $errors[] = $validator->getMessage();
                }

            } elseif ($tmp != self::NOT_UPLOAD_FAKE_TMP_NAME) {

                throw new \Exception($message('Los archivos deben ser subidos mediante POST.'));

            } else {

                $errors[] = $message('No se ha subido ningún archivo.');

            }

        } elseif ($error == \UPLOAD_ERR_INI_SIZE) {

            $maxUpload = min(ini_get('post_max_size'), ini_get('upload_max_filesize'));
            $maxUpload = str_replace('M', 'MB', $maxUpload);
            $errors[] = $message('El archivo excede el peso máximo permitido por el servidor.') . " ({$maxUpload})";

        } elseif ($error == \UPLOAD_ERR_FORM_SIZE) {

            $messageErrorSize = $message('El archivo excede el peso máximo permitido.');

            if (isset($_POST['MAX_FILE_SIZE']) && ctype_digit($_POST['MAX_FILE_SIZE'])) {
                $maxUpload = (int) floor($_POST['MAX_FILE_SIZE'] / 1000 / 1000);
                $messageErrorSize .= " ({$maxUpload}MB)";
            }

            $errors[] = $messageErrorSize;

        } elseif ($error == \UPLOAD_ERR_PARTIAL) {

            $errors[] = $message('El archivo no se subió completamente.');

        } elseif ($error == \UPLOAD_ERR_NO_FILE) {

            $errors[] = $message('No ha subido ningún archivo.');

        } elseif ($error == \UPLOAD_ERR_NO_TMP_DIR) {

            $errors[] = $message('No se ha subido el archivo. Problema con el directorio temporal.');

        } elseif ($error == \UPLOAD_ERR_CANT_WRITE) {

            $errors[] = $message('No se ha subido ningún archivo. Problema al escribir en disco.');

        } elseif ($error == \UPLOAD_ERR_EXTENSION) {

            $errors[] = $message('No se ha subido ningún archivo. Problema con alguna extensión.');

        } else {

            //Sin esta rama, un código de error desconocido dejaba el archivo como válido.
            $errors[] = $message('No se ha podido validar el archivo subido.');

        }

        return $errors;
    }
}
