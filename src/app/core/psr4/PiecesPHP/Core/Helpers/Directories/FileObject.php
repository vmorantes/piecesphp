<?php
/**
 * FileObject.php
 */

namespace PiecesPHP\Core\Helpers\Directories;

/**
 * FileObject - Representa un archivo dentro de un objeto DirectoryObject.
 *
 * Provee métodos para obtener información básica del archivo como su ruta,
 * extensión y directorio padre.
 *
 * @package     PiecesPHP\Core\Helpers\Directories
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class FileObject implements \JsonSerializable
{

    /**
     * @var string La ruta del archivo tal cual fue proporcionada.
     */
    protected $rawPath = '';

    /**
     * @var string La ruta real (absolute) del archivo.
     */
    protected $path = '';

    /**
     * @var string El nombre base del archivo (incluyendo extensión).
     */
    protected $basename = '';

    /**
     * @var string La ruta del directorio padre.
     */
    protected $parent = '';

    /**
     * @var bool Indica si el archivo existe físicamente.
     */
    protected $exists = false;

    /**
     * Constructor de FileObject.
     *
     * @param string $path Ruta del archivo.
     */
    public function __construct(string $path)
    {
        $path = self::normalizePath($path);
        $this->rawPath = $path;
        $this->exists = file_exists($path) || is_link($path);
        $this->path = $path;
        $this->basename = basename($path);
        $this->parent = str_replace($this->basename, '', $path);
    }

    /**
     * Obtiene la ruta original proporcionada.
     *
     * @return string
     */
    public function getRawPath()
    {
        return $this->rawPath;
    }

    /**
     * Verifica si el archivo existe.
     *
     * @return bool
     */
    public function getExists()
    {
        return $this->exists;
    }

    /**
     * Obtiene la ruta absoluta del archivo.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Obtiene el nombre base del archivo.
     *
     * @return string
     */
    public function getBasename()
    {
        return $this->basename;
    }

    /**
     * Obtiene la extensión del archivo.
     *
     * @return string
     */
    public function getExtension()
    {
        $basename = $this->getBasename();
        $extension = pathinfo($basename, \PATHINFO_EXTENSION);
        return $extension;
    }

    /**
     * Obtiene la ruta del directorio padre.
     *
     * @return string
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Especifica los datos que deben serializarse en JSON.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'basename' => $this->getBasename(),
            'path' => $this->getPath(),
            'parent' => $this->getParent(),
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
