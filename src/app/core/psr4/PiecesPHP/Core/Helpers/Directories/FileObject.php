<?php
/**
 * FileObject.php
 */

namespace PiecesPHP\Core\Helpers\Directories;

/**
 * FileObject - Representa un archivo de un objeto DirectoryObject
 *
 * @package     PiecesPHP\Core\Helpers\Directories
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class FileObject implements \JsonSerializable
{

    /**
     * $rawPath
     *
     * @var string
     */
    protected $rawPath = '';

    /**
     * $path
     *
     * @var string
     */
    protected $path = '';

    /**
     * $basename
     *
     * @var string
     */
    protected $basename = '';

    /**
     * $parent
     *
     * @var string
     */
    protected $parent = '';

    /**
     * $exists
     *
     * @var bool
     */
    protected $exists = false;

    /**
     * __construct
     *
     * @param string $path
     * @return static
     */
    public function __construct(string $path)
    {
        $this->rawPath = $path;
        $this->exists = file_exists($path);
        $this->path = realpath($path);
        $this->basename = basename($path);
        $this->parent = str_replace($this->basename, '', $path);
    }

    /**
     * getRawPath
     *
     * @return string
     */
    public function getRawPath()
    {
        return $this->rawPath;
    }

    /**
     * getExists
     *
     * @return bool
     */
    public function getExists()
    {
        return $this->exists;
    }

    /**
     * getPath
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * getBasename
     *
     * @return string
     */
    public function getBasename()
    {
        return $this->basename;
    }

    /**
     * getParent
     *
     * @return string
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * jsonSerialize
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'basename' => $this->getBasename(),
            'path' => $this->getPath(),
            'files' => $this->getParent(),
        ];
    }

}
