<?php

/**
 * FormUploadHandler.php
 */

namespace PiecesPHP\Core\Forms;

use PiecesPHP\Core\Statics\ProtectedUploads;

/**
 * FormUploadHandler - La subida de un campo de formulario a la carpeta de su registro (ADR 0068).
 *
 * Valida el archivo, lo guarda en `<subidas del módulo>/<carpeta del registro>` y borra el anterior, pero solo si está
 * dentro de las subidas del módulo. Devuelve la ruta relativa que se guarda, o cadena vacía si no llegó archivo.
 * Cada módulo la usa desde su `handlerUpload()`, que pone su política: el modo, los tipos por omisión y la conversión
 * de FormUploadRejectedException en su SafeException.
 *
 * @package     PiecesPHP\Core\Forms
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class FormUploadHandler
{
    /**
     * Un archivo por campo, que nace privado (ProtectedUploads::moveUploadedToPrivate).
     */
    const MODE_PRIVATE = 'private';
    /**
     * Un archivo por campo, público (FileUpload::moveTo).
     */
    const MODE_PUBLIC = 'public';
    /**
     * Un archivo por campo, privado, leído con UploadedFileAdapter (admite el campo anidado, `campo[0]`).
     */
    const MODE_PRIVATE_MULTIPLE = 'private-multiple';

    /**
     * @var string
     */
    private $uploadDir;
    /**
     * @var string
     */
    private $uploadDirURL;
    /**
     * @var string
     */
    private $mode;

    /**
     * @param string $uploadDir Ruta en disco de las subidas del módulo
     * @param string $uploadDirURL Su URL relativa a la base
     * @param string $mode Una de las constantes MODE_*
     * @throws \InvalidArgumentException
     */
    public function __construct(string $uploadDir, string $uploadDirURL, string $mode)
    {
        if (!in_array($mode, [self::MODE_PRIVATE, self::MODE_PUBLIC, self::MODE_PRIVATE_MULTIPLE], true)) {
            throw new \InvalidArgumentException("Modo de subida desconocido: {$mode}");
        }
        $this->uploadDir = $uploadDir;
        $this->uploadDirURL = $uploadDirURL;
        $this->mode = $mode;
    }

    /**
     * Las subidas de un módulo, con su carpeta dentro de `upload_dir`: las mismas rutas que calcula su controlador.
     *
     * @param string $moduleDir La carpeta del módulo (su UPLOAD_DIR)
     * @param string $mode Una de las constantes MODE_*
     * @return static
     * @throws \InvalidArgumentException
     */
    public static function forModule(string $moduleDir, string $mode): self
    {
        return new self(
            append_to_path_system(get_config('upload_dir'), $moduleDir),
            str_replace(base_url(), '', append_to_url(get_config('upload_dir_url'), $moduleDir)),
            $mode
        );
    }

    /**
     * @param string|null $nameOnFiles El campo en $_FILES
     * @param string $folder La carpeta del registro; vacía, se deduce de $currentRoute
     * @param string|null $currentRoute La ruta guardada del archivo anterior, o null si no hay
     * @param array $allowedTypes Tipos de FileValidator
     * @param bool $setNameByInput El nombre sale del archivo subido; si no, de $name
     * @param string|null $name Sin él, `file_<uniqid>`
     * @param string $suffixName Solo en MODE_PRIVATE_MULTIPLE: se añade al nombre como `_<sufijo>`
     * @return string
     * @throws FormUploadRejectedException Si el archivo no pasa la validación
     * @throws \Exception
     */
    public function upload(?string $nameOnFiles, string $folder, ?string $currentRoute, array $allowedTypes, bool $setNameByInput = true, ?string $name = null, string $suffixName = ''): string
    {
        $relativeURL = '';
        $name ??= 'file_' . uniqid();

        if ($this->mode === self::MODE_PRIVATE_MULTIPLE) {
            //Sin nombre en $_FILES no hay rutas, y ninguna vuelta: devuelve vacía.
            $associativePaths = UploadedFileAdapter::findAssociativePathsByName($nameOnFiles);
            //Con varios, la fila apuntaba al último y los demás quedaban en disco sin dueño (DEF-2).
            if (count($associativePaths) > 1) {
                throw new FormUploadRejectedException(__(FileValidator::LANG_GROUP, 'Solo se admite un archivo por campo.'));
            }
            foreach ($associativePaths as $associativePath) {
                $handler = new UploadedFileAdapter($associativePath, $allowedTypes);
                //Un campo sin elegir llega con UPLOAD_ERR_NO_FILE: es «sin archivo», como en FileUpload (DEF-3).
                if ($handler->hasInput() && $handler->getFileInformation()['error'] !== \UPLOAD_ERR_NO_FILE) {
                    try {
                        $valid = $handler->validate();
                        if ($setNameByInput && $valid) {
                            $name = self::nameFromInput($nameOnFiles);
                        }
                        [$uploadDirPath, $uploadDirRelativeURL, $oldFile] = $this->destination($folder, $currentRoute);
                        if (mb_strlen($suffixName) > 0) {
                            $name .= "_{$suffixName}";
                        }
                        if (!$valid) {
                            throw new FormUploadRejectedException(implode('<br>', $handler->getErrorMessages()));
                        }
                        $relativeURL = $this->moveToPrivateOrFail($handler->getFileInformation(), $uploadDirPath, $uploadDirRelativeURL, $name, $oldFile);
                    } catch (FormUploadRejectedException $e) {
                        throw $e;
                    } catch (\Exception $e) {
                        throw new \Exception($e->getMessage());
                    }
                }
            }
            return $relativeURL;
        }

        $handler = new FileUpload($nameOnFiles, $allowedTypes);
        if ($handler->hasInput()) {
            try {
                $valid = $handler->validate();
                if ($setNameByInput && $valid) {
                    $name = self::nameFromInput($nameOnFiles);
                }
                [$uploadDirPath, $uploadDirRelativeURL, $oldFile] = $this->destination($folder, $currentRoute);
                if (!$valid) {
                    throw new FormUploadRejectedException(implode('<br>', $handler->getErrorMessages()));
                }
                $relativeURL = $this->mode === self::MODE_PRIVATE
                    ? $this->moveToPrivateOrFail($handler->getFileInformation(), $uploadDirPath, $uploadDirRelativeURL, $name, $oldFile)
                    : $this->moveToPublic($handler, $uploadDirPath, $uploadDirRelativeURL, $name, $oldFile);
            } catch (FormUploadRejectedException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }

        return $relativeURL;
    }

    /**
     * El nombre del archivo subido, sin extensión y apto para disco.
     *
     * @param string|null $nameOnFiles
     * @return string
     */
    private static function nameFromInput(?string $nameOnFiles): string
    {
        $name = $_FILES[$nameOnFiles]['name'];
        $lastPointIndex = mb_strrpos($name, '.');
        if ($lastPointIndex !== false) {
            $name = mb_substr($name, 0, $lastPointIndex);
        }
        $name = safe_upload_name($name);
        return $name;
    }

    /**
     * La carpeta de destino (en disco y su URL) y el archivo anterior en disco, si existe.
     * Con $folder vacía y un anterior, la carpeta es la del anterior: se queda en $folder para las vueltas siguientes.
     *
     * @param string $folder
     * @param string|null $currentRoute
     * @return array{0: string, 1: string, 2: string|null}
     */
    private function destination(string &$folder, ?string $currentRoute): array
    {
        $oldFile = null;
        if ($currentRoute !== null) {
            if ($this->mode === self::MODE_PUBLIC) {
                $oldFile = append_to_url(basepath(), $currentRoute);
                $oldFile = file_exists($oldFile) ? $oldFile : null;
            } else {
                //En disco puede llevar el sufijo de lo privado: resolve() lo encuentra con o sin él.
                [$oldFile] = ProtectedUploads::resolve(append_to_url(basepath(), $currentRoute));
            }
            if (mb_strlen(trim($folder)) < 1) {
                $folder = str_replace($this->uploadDirURL, '', $currentRoute);
                $folder = str_replace(basename($currentRoute), '', $folder);
                $folder = trim($folder, '/');
            }
        }
        return [
            append_to_path_system($this->uploadDir, $folder),
            append_to_url($this->uploadDirURL, $folder),
            $oldFile,
        ];
    }

    /**
     * NACE PRIVADO, y directamente: va a su nombre de disco sin pasar por el público; la ruta que se guarda no lleva el
     * sufijo. Si no se puede mover, devuelve null y no se borra nada.
     *
     * @param array $information La de FileUpload o UploadedFileAdapter
     * @param string $uploadDirPath
     * @param string $uploadDirRelativeURL
     * @param string $name
     * @param string|null $oldFile
     * @return string|null
     */
    private function moveToPrivate(array $information, string $uploadDirPath, string $uploadDirRelativeURL, string $name, ?string $oldFile): ?string
    {
        $url = ProtectedUploads::moveUploadedToPrivate((string) $information['tmp_name'], $uploadDirPath, $name, pathinfo((string) $information['name'], \PATHINFO_EXTENSION));
        if ($url === '') {
            return null;
        }
        //Si tenía el mismo nombre, el movimiento ya lo sustituyó.
        if ($oldFile !== null && is_file($oldFile) && $oldFile !== ProtectedUploads::privatePath($url)) {
            $this->deletePrevious($oldFile);
        }
        return trim(append_to_url($uploadDirRelativeURL, basename($url)), '/');
    }

    /**
     * Los dos modos privados: si no se puede mover, es un error, no «sin archivo» (H6 y G1, como DEF-1 en el público).
     *
     * @param array $information
     * @param string $uploadDirPath
     * @param string $uploadDirRelativeURL
     * @param string $name
     * @param string|null $oldFile
     * @return string
     * @throws \Exception
     */
    private function moveToPrivateOrFail(array $information, string $uploadDirPath, string $uploadDirRelativeURL, string $name, ?string $oldFile): string
    {
        return $this->moveToPrivate($information, $uploadDirPath, $uploadDirRelativeURL, $name, $oldFile)
            ?? throw new \Exception("No se pudo guardar el archivo subido en {$uploadDirPath}");
    }

    /**
     * Si mover falla, FileUpload devuelve una ruta vacía: es un error, y no se guarda la URL de la carpeta (DEF-1).
     *
     * @param FileUpload $handler
     * @param string $uploadDirPath
     * @param string $uploadDirRelativeURL
     * @param string $name
     * @param string|null $oldFile
     * @return string
     */
    private function moveToPublic(FileUpload $handler, string $uploadDirPath, string $uploadDirRelativeURL, string $name, ?string $oldFile): string
    {
        $locations = $handler->moveTo($uploadDirPath, $name, null, false, true);
        if (empty($locations)) {
            return '';
        }
        $url = $locations[0];
        if (!is_string($url) || $url === '') {
            throw new \Exception("No se pudo guardar el archivo subido en {$uploadDirPath}");
        }
        $nameCurrent = basename($url);
        if ($oldFile !== null && basename($oldFile) != $nameCurrent) {
            $this->deletePrevious($oldFile);
        }
        foreach ($locations as $file) {
            if ($url != $file && is_string($file) && file_exists($file)) {
                //RETORNO-IGNORADO: con un archivo por campo, moveTo() da una sola ruta y esto no corre; si sobrara uno, solo ocupa disco.
                unlink($file);
            }
        }
        return trim(append_to_url($uploadDirRelativeURL, $nameCurrent), '/');
    }

    /**
     * Solo se borra lo que está dentro de las subidas del módulo: el anterior puede ser una ruta ajena (el logo por
     * omisión de la carga del geovisor es un archivo versionado). Si no se puede borrar, se registra y el guardado sigue.
     *
     * @param string $oldFile
     * @return void
     */
    private function deletePrevious(string $oldFile): void
    {
        $moduleUploadDir = realpath($this->uploadDir);
        $oldFileReal = realpath($oldFile);
        if ($moduleUploadDir === false || $oldFileReal === false || !str_starts_with($oldFileReal, $moduleUploadDir . \DIRECTORY_SEPARATOR)) {
            return;
        }
        if (is_file($oldFile) && (!is_writable(dirname($oldFile)) || !unlink($oldFile))) {
            log_exception(new \RuntimeException('No se pudo borrar el archivo anterior: ' . $oldFile));
        }
    }

}
