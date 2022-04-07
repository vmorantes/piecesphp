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
     * @var string
     */
    protected $parent = '';

    /**
     * @var bool
     */
    protected $exists = false;

    /**
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
     * @return string
     */
    public function getRawPath()
    {
        return $this->rawPath;
    }

    /**
     * @return bool
     */
    public function getExists()
    {
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
     * @return string
     */
    public function getParent()
    {
        return $this->parent;
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
            'files' => $this->getParent(),
        ];
    }

}
