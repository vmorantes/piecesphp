<?php
/**
 * DirectoryObject.php
 */

namespace PiecesPHP\Core\Helpers\Directories;

/**
 * DirectoryObject - Representa y gestiona un directorio en el sistema de archivos.
 *
 * Esta clase permite manipular directorios, escanear su contenido, copiarlos, eliminarlos
 * y cambiar sus permisos de forma recursiva o simple.
 *
 * @package     PiecesPHP\Core\Helpers\Directories
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class DirectoryObject implements \JsonSerializable
{
    /**
     * @var string La ruta base utilizada para calcular rutas relativas.
     */
    protected $root = '';

    /**
     * @var string La ruta tal cual fue proporcionada al constructor.
     */
    protected $rawPath = '';

    /**
     * @var string La ruta real (absolute) resuelta en el sistema de archivos.
     */
    protected $path = '';

    /**
     * @var string El nombre del directorio (sin la ruta completa).
     */
    protected $basename = '';

    /**
     * @var bool Indica si el directorio existe físicamente.
     */
    protected $exists = false;

    /**
     * @var DirectoryObject[] Listado de subdirectorios (indexado por basename).
     */
    protected $directories = [];

    /**
     * @var FileObject[] Listado de archivos contenidos (indexado por basename).
     */
    protected $files = [];

    /**
     * @var string[] Elementos a ignorar por defecto durante el escaneo.
     */
    protected $ignoreDefault = [
        '.',
        '..',
    ];

    const CHMOD_LEVEL_ALL = -1;
    const CHMOD_LEVEL_CONTENT = 1;

    /**
     * Constructor de DirectoryObject.
     *
     * @param string $path Ruta del directorio.
     * @param string|null $root Ruta base opcional. Si es null, se calcula automáticamente.
     */
    public function __construct(string $path, ?string $root = null)
    {
        $path = self::normalizePath($path);
        $this->rawPath = $path;
        $this->path = $path;
        $this->exists = file_exists($this->rawPath) || is_link($this->rawPath);
        $this->basename = basename($this->rawPath);
        if (is_null($root)) {
            $this->root = str_replace($this->basename, '', $this->path);
        } else {
            $this->root = self::normalizePath($root);
        }
    }

    /**
     * Procesa el contenido del directorio de forma recursiva.
     *
     * Llena las propiedades $files y $directories.
     *
     * @param FilesIgnore|null $filesIgnore Objeto para definir reglas de exclusión.
     * @param array $ignored_files Referencia a un arreglo donde se guardarán las rutas ignoradas.
     * @return $this
     */
    public function process(?FilesIgnore $filesIgnore = null, array &$ignored_files = [])
    {

        if ($this->directoryExists()) {

            $content = scandir($this->path);

            foreach ($content as $basename) {

                $path = $this->path . DIRECTORY_SEPARATOR . $basename;
                $read = !in_array($basename, $this->ignoreDefault);
                $relative_path = str_replace($this->root, '', $path);

                if ($read) {
                    $ignore = !is_null($filesIgnore) ? $filesIgnore->ignore($relative_path) : false;
                    if ($ignore) {
                        $ignored_files[] = $relative_path;
                        $read = false;
                    }
                }

                if ($read) {
                    if (is_dir($path) && !is_link($path)) {
                        $subDir = new DirectoryObject($path, $this->root);
                        $subDir->process($filesIgnore, $ignored_files);
                        $this->directories[$subDir->getBasename()] = $subDir;
                    } else {
                        $this->files[basename($path)] = new FileObject($path);
                    }
                }
            }
        }

        sort($ignored_files);

        return $this;
    }

    /**
     * Copia el directorio a un destino.
     *
     * @param string $destination Ruta de destino.
     * @param bool $compress Si es true, genera un archivo 'output.zip' en el destino. Si es false, copia la estructura de carpetas.
     * @return array Resumen de la operación con archivos y carpetas copiadas.
     */
    public function copyTo(string $destination, bool $compress = true)
    {
        $oldUmask = umask(0);
        $result = [
            'files' => [],
            'folders' => [],
            'total_copies' => 0,
        ];
        try {
            if ($compress) {

                if (!file_exists($destination)) {
                    mkdir($destination, 0777, true);
                }

                if ($this->directoryExists()) {

                    $zip = new \ZipArchive();
                    $zip->open($destination . DIRECTORY_SEPARATOR . 'output.zip', \ZIPARCHIVE::CREATE);

                    $this->fillZip($zip, $result);

                    $zip->close();
                }
            } else {

                $destination = $destination . DIRECTORY_SEPARATOR . $this->basename;

                if (!file_exists($destination)) {
                    mkdir($destination, 0777, true);
                }

                $result['folders'][] = [
                    'from' => $this->getPath(),
                    'to' => $destination,
                ];

                if ($this->directoryExists()) {

                    foreach ($this->files as $file) {

                        $newPath = $destination . DIRECTORY_SEPARATOR . $file->getBasename();
                        copy($file->getPath(), $newPath);
                        chmod($newPath, 0777);

                        $result['files'][] = [
                            'from' => $file->getPath(),
                            'to' => $newPath,
                        ];
                    }

                    foreach ($this->directories as $directory) {
                        $sub_result = $directory->copyTo($destination, false);
                        $result['files'] = array_merge($result['files'], $sub_result['files']);
                        $result['folders'] = array_merge($result['folders'], $sub_result['folders']);
                    }
                }
            }

            $result['total_copies'] = count($result['files']) + count($result['folders']);

        } finally {
            umask($oldUmask);
        }
        return $result;
    }

    /**
     * Copia únicamente el contenido del directorio al destino especificado.
     *
     * @param string $destination Ruta de destino donde se pegará el contenido.
     * @return array Resumen de la operación.
     */
    public function copyContentTo(string $destination)
    {
        $oldUmask = umask(0);
        $result = [
            'files' => [],
            'folders' => [],
            'total_copies' => 0,
        ];

        try {

            if (!file_exists($destination)) {
                mkdir($destination, 0777, true);
            }

            $result['folders'][] = [
                'from' => $this->getPath(),
                'to' => $destination,
            ];

            if ($this->directoryExists()) {

                foreach ($this->files as $file) {

                    $newPath = $destination . DIRECTORY_SEPARATOR . $file->getBasename();
                    copy($file->getPath(), $newPath);
                    chmod($newPath, 0777);

                    $result['files'][] = [
                        'from' => $file->getPath(),
                        'to' => $newPath,
                    ];
                }

                foreach ($this->directories as $directory) {
                    $sub_result = $directory->copyTo($destination, false);
                    $result['files'] = array_merge($result['files'], $sub_result['files']);
                    $result['folders'] = array_merge($result['folders'], $sub_result['folders']);
                }
            }

            $result['total_copies'] = count($result['files']) + count($result['folders']);
        } finally {
            umask($oldUmask);
        }

        return $result;
    }

    /**
     * Elimina el contenido del directorio y opcionalmente el directorio mismo.
     *
     * @param bool $deleteSelf Si es true, también intenta eliminar el directorio actual una vez vaciado.
     * @return array Resumen de elementos eliminados.
     */
    public function delete(bool $deleteSelf = false)
    {
        $result = [
            'root' => $this->getPath(),
            'self_deleted' => false,
            'files' => [],
            'folders' => [],
            'total_deleted' => 0,
        ];

        $all_content_deleted = true;

        if ($this->directoryExists()) {

            foreach ($this->files as $file) {
                $filePath = $file->getPath();
                $removed = (file_exists($filePath) || is_link($filePath)) ? unlink($filePath) : false;
                $result['files'][] = [
                    'path' => $filePath,
                    'deleted' => $removed,
                ];
                $all_content_deleted = $all_content_deleted && $removed;
            }

            foreach ($this->directories as $directory) {
                $sub_result = $directory->delete(true);
                $removed = $sub_result['self_deleted'];
                $all_content_deleted = $all_content_deleted && $removed;
                if ($removed) {
                    $result['folders'][] = [
                        'path' => $directory->getPath(),
                        'deleted' => $removed,
                    ];
                }
                $result['files'] = array_merge($result['files'], $sub_result['files']);
                $result['folders'] = array_merge($result['folders'], $sub_result['folders']);
            }
        }

        if ($deleteSelf) {
            if ($all_content_deleted) {
                $dirPath = $this->getPath();
                $removed = (file_exists($dirPath) || is_link($dirPath)) ? (is_link($dirPath) ? unlink($dirPath) : rmdir($dirPath)) : false;
                $result['self_deleted'] = $removed;
            }
        }

        $total_files_deleted = count(array_filter($result['files'], function ($e) {
            return $e['deleted'];
        }));
        $total_folders_deleted = count(array_filter($result['folders'], function ($e) {
            return $e['deleted'];
        }));

        $result['total_deleted'] = $total_files_deleted + $total_folders_deleted;

        return $result;
    }

    /**
     * Cambia los permisos (chmod) de forma jerárquica.
     *
     * @param int $mode Modo de permisos (ej. 0777).
     * @param int $levels Niveles de profundidad (-1 para recursivo total, 0 para solo el directorio actual).
     * @param bool $onlyDirectories Si es true, solo afecta a carpetas.
     * @return array Registro de cambios realizados.
     */
    public function chmod(int $mode, int $levels = 0, bool $onlyDirectories = false)
    {
        $oldUmask = umask(0);
        /**
         * @var array{
         *  files:array<string,array{
         *      path:string,
         *      from:string,
         *      to:string,
         *  }>,
         *  folders:array<string, array{
         *      path:string,
         *      from:string,
         *      to:string,
         *  }>,
         * } $result
         */
        $result = [
            'files' => [],
            'folders' => [],
        ];
        try {

            if ($this->directoryExists()) {

                $result['folders'][] = [
                    'path' => $this->path,
                    'from' => mb_substr(sprintf('%o', fileperms($this->path)), -4),
                    'to' => '0' . decoct($mode),
                ];

                chmod($this->path, $mode);

                if ($levels > 0 || $levels == -1) {

                    if (!$onlyDirectories) {

                        foreach ($this->files as $file) {

                            $filepath = $file->getPath();
                            if (file_exists($filepath)) {
                                $result['files'][] = [
                                    'path' => $filepath,
                                    'from' => mb_substr(sprintf('%o', fileperms($filepath)), -4),
                                    'to' => '0' . decoct($mode),
                                ];
                                chmod($filepath, $mode);
                            }
                        }
                    }

                    foreach ($this->directories as $directory) {

                        if ($directory->directoryExists()) {
                            $levels = $levels > 0 ? $levels - 1 : $levels;
                            $sub_result = $directory->chmod($mode, $levels, $onlyDirectories);
                            $result['files'] = array_merge($result['files'], $sub_result['files']);
                            $result['folders'] = array_merge($result['folders'], $sub_result['folders']);
                        }
                    }
                }
            }
        } finally {
            umask($oldUmask);
        }
        return $result;
    }

    /**
     * Verifica si el directorio existe físicamente y actualiza el estado interno.
     *
     * @return bool
     */
    public function directoryExists()
    {
        $this->exists = file_exists($this->rawPath);
        return $this->exists;
    }

    /**
     * Obtiene la ruta absoluta del directorio.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Obtiene el nombre base (basename) del directorio.
     *
     * @return string
     */
    public function getBasename()
    {
        return $this->basename;
    }

    /**
     * Obtiene el listado de subdirectorios procesados.
     *
     * @return DirectoryObject[]
     */
    public function getDirectories()
    {
        return $this->directories;
    }

    /**
     * Obtiene el listado de archivos procesados.
     *
     * @return FileObject[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Obtiene un directorio hijo inmediato por su nombre.
     *
     * @param string $name Nombre del directorio.
     * @return DirectoryObject|null Devuelve null si no existe.
     */
    public function getChildrenDirectoryByName(string $name)
    {
        return isset($this->directories[$name]) ? $this->directories[$name] : null;
    }

    /**
     * Obtiene un archivo hijo inmediato por su nombre.
     *
     * @param string $name Nombre del archivo.
     * @return FileObject|null Devuelve null si no existe.
     */
    public function getChildrenFileByName(string $name)
    {
        return isset($this->files[$name]) ? $this->files[$name] : null;
    }

    /**
     * Ordena el listado interno de directorios.
     *
     * @param callable|null $compareFunction Función de comparación opcional. Por defecto usa orden natural por nombre.
     * @return void
     */
    public function orderDirectories( ? callable $compareFunction = null)
    {

        if ($compareFunction === null) {

            /**
             * @param DirectoryObject $a
             * @param DirectoryObject $b
             */
            $compareFunction = function ($a, $b) {

                $aBasename = $a->getBasename();
                $bBasename = $b->getBasename();

                $regexpRemoveExtension = "|^(.*)\.(.*)$|m";

                $aNameWhitoutExtension = preg_replace($regexpRemoveExtension, '$1', $aBasename);
                $bNameWhitoutExtension = preg_replace($regexpRemoveExtension, '$1', $bBasename);

                return strnatcmp($aNameWhitoutExtension, $bNameWhitoutExtension);

            };
        }

        usort($this->directories, $compareFunction);
    }

    /**
     * Ordena el listado interno de archivos.
     *
     * @param callable|null $compareFunction Función de comparación opcional. Por defecto usa orden natural por nombre.
     * @return void
     */
    public function orderFiles( ? callable $compareFunction = null)
    {

        if ($compareFunction === null) {

            /**
             * @param FileObject $a
             * @param FileObject $b
             */
            $compareFunction = function ($a, $b) {

                $aBasename = $a->getBasename();
                $bBasename = $b->getBasename();

                $regexpRemoveExtension = "|^(.*)\.(.*)$|m";

                $aNameWhitoutExtension = preg_replace($regexpRemoveExtension, '$1', $aBasename);
                $bNameWhitoutExtension = preg_replace($regexpRemoveExtension, '$1', $bBasename);

                return strnatcmp($aNameWhitoutExtension, $bNameWhitoutExtension);

            };
        }

        usort($this->files, $compareFunction);
    }

    /**
     * Agrega el contenido del directorio a un archivo ZIP de forma recursiva.
     *
     * @param \ZipArchive $zip Instancia del objeto ZIP.
     * @param array $logger Referencia a un arreglo para registrar lo que se va agregando.
     * @param string|null $directoryBasename Ruta relativa dentro del ZIP.
     * @return void
     */
    protected function fillZip(\ZipArchive  &$zip, array &$logger, ?string $directoryBasename = null)
    {
        foreach ($this->files as $file) {
            $basename = $file->getBasename();
            $localPath = is_null($directoryBasename) ? $basename : $directoryBasename . DIRECTORY_SEPARATOR . $basename;
            $zip->addFile($file->getPath(), $localPath);
            $logger['files'][] = [
                'from' => $file->getPath(),
                'to' => $localPath,
            ];
        }
        foreach ($this->directories as $directory) {
            $basename = $directory->getBasename();
            $localPath = is_null($directoryBasename) ? $basename : $directoryBasename . DIRECTORY_SEPARATOR . $basename;
            $directory->fillZip($zip, $logger, $localPath);
            $logger['folders'][] = [
                'from' => $directory->getPath(),
                'to' => $localPath,
            ];
        }
    }

    /**
     * Especifica los datos que deben serializarse en JSON.
     *
     * @return array
     */
    public function jsonSerialize() : array
    {
        return [
            'basename' => $this->getBasename(),
            'path' => $this->getPath(),
            'files' => $this->getFiles(),
            'directories' => $this->getDirectories(),
        ];
    }

    /**
     * Normaliza una ruta eliminando puntos (.) y subidas de nivel (..) sin resolver enlaces simbólicos.
     *
     * @param string $path
     * @return string
     */
    protected static function normalizePath(string $path)
    {
        $path = str_replace(['\\', '//'], '/', $path);
        $parts = array_filter(explode('/', $path), function ($part) {
            return strlen($part) > 0;
        });
        $isAbsolute = (mb_strpos($path, '/') === 0);
        $result = [];
        foreach ($parts as $part) {
            if ($part == '.') {
                continue;
            }
            if ($part == '..') {
                array_pop($result);
            } else {
                $result[] = $part;
            }
        }
        $normalized = ($isAbsolute ? '/' : '') . implode('/', $result);
        return $normalized === '' ? ($isAbsolute ? '/' : '.') : $normalized;
    }
}
