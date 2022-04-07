<?php
/**
 * DirectoryObject.php
 */

namespace PiecesPHP\Core\Helpers\Directories;

/**
 * DirectoryObject - Representa un directorio
 *
 * @package     PiecesPHP\Core\Helpers\Directories
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class DirectoryObject implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $root = '';

    /**
     * @var string
     */
    protected $rawPath = '';

    /**
     * @var string
     */
    protected $path = '';

    /**
     * @var string
     */
    protected $basename = '';

    /**
     * @var bool
     */
    protected $exists = false;

    /**
     * @var DirectoryObject[]
     */
    protected $directories = [];

    /**
     * @var FileObject[]
     */
    protected $files = [];

    /**
     * @var string[]
     */
    protected $ignoreDefault = [
        '.',
        '..',
    ];

    const CHMOD_LEVEL_ALL = -1;
    const CHMOD_LEVEL_CONTENT = 1;

    /**
     * @param string $path
     * @param string $root
     * @return static
     */
    public function __construct(string $path, string $root = null)
    {
        $this->rawPath = $path;
        $this->path = realpath($this->rawPath);
        $this->exists = file_exists($this->rawPath);
        $this->basename = basename($this->rawPath);
        if (is_null($root)) {
            $this->root = str_replace($this->basename, '', $this->path);
        } else {
            $this->root = $root;
        }
    }

    /**
     * @param FilesIgnore $filesIgnore
     * @param array $ignored_files
     * @return $this
     */
    public function process(FilesIgnore $filesIgnore = null, array &$ignored_files = [])
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
                    if (is_dir($path)) {
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
     * @param string $destination
     * @param bool $compress
     * @return array
     */
    public function copyTo(string $destination, bool $compress = true)
    {
        $result = [
            'files' => [],
            'folders' => [],
            'total_copies' => 0,
        ];

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

        return $result;
    }

    /**
     * @param string $destination
     * @return array
     */
    public function copyContentTo(string $destination)
    {
        $result = [
            'files' => [],
            'folders' => [],
            'total_copies' => 0,
        ];

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

        return $result;
    }

    /**
     * @param mixed bool
     * @return array
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
                $removed = unlink($file->getPath());
                $result['files'][] = [
                    'path' => $file->getPath(),
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
                $removed = rmdir($this->getPath());
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
     * @param int $mode
     * @param int $levels Profundidad del cambio (-1 para recursividad total)
     * @param bool $onlyDirectories
     * @return array
     */
    public function chmod(int $mode, int $levels = 0, bool $onlyDirectories = false)
    {
        /**
         * @var array $result
         * @var string[] $result['files']
         * @var string[] $result['folders']
         */
        $result = [
            'files' => [],
            'folders' => [],
        ];

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

        return $result;
    }

    /**
     * @return bool
     */
    public function directoryExists()
    {
        $this->exists = file_exists($this->rawPath);
        return $this->exists;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getBasename()
    {
        return $this->basename;
    }

    /**
     * @return DirectoryObject[]
     */
    public function getDirectories()
    {
        return $this->directories;
    }

    /**
     * @return FileObject[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Obtiene un directorio hijo inmediato según el nombre
     *
     * @param string $name
     * @return DirectoryObject
     */
    public function getChildrenDirectoryByName(string $name)
    {
        return isset($this->directories[$name]) ? $this->directories[$name] : null;
    }

    /**
     * Obtiene un archivo hijo inmediato según el nombre
     *
     * @param string $name
     * @return FileObject
     */
    public function getChildrenFileByName(string $name)
    {
        return isset($this->files[$name]) ? $this->files[$name] : null;
    }

    /**
     * Ordena los directorios
     *
     * @param callable $compareFunction
     * @return void
     */
    public function orderDirectories(callable $compareFunction = null)
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
     * Ordena los archivos
     *
     * @param callable $compareFunction
     * @return void
     */
    public function orderFiles(callable $compareFunction = null)
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
     * @param \ZipArchive &$zip
     * @param array &$logger
     * @param string $directoryBasename
     * @return void
     */
    protected function fillZip(\ZipArchive &$zip, array &$logger, string $directoryBasename = null)
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
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'basename' => $this->getBasename(),
            'path' => $this->getPath(),
            'files' => $this->getFiles(),
            'directories' => $this->getDirectories(),
        ];
    }
}
