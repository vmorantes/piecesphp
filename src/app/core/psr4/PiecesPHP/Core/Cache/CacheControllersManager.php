<?php

/**
 * CacheControllersManager.php
 */
namespace PiecesPHP\Core\Cache;

use PiecesPHP\Core\StringManipulate;
use \ArrayObject;
use \JsonSerializable;

/**
 * CacheControllersManager
 *
 * @category    Cache
 * @package     PiecesPHP\Core\Cache
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class CacheControllersManager implements JsonSerializable
{

    const LANG_GROUP = 'cache_manager_messages';

    const CONTENT_TYPE_JSON = 'application/json';
    const CONTENT_TYPE_HTML = 'text/html';

    const EXTENSIONS_BY_CONTENT_TYPE = [
        self::CONTENT_TYPE_JSON => 'json',
        self::CONTENT_TYPE_HTML => 'html',
    ];

    /**
     * @var string
     */
    private $hash = '';

    /**
     * @var string
     */
    private $ownConfigurationFileName = '';

    /**
     * @var string
     */
    private $folderCache = '';

    /**
     * @var string
     */
    private $globalConfigurationFile = '';

    /**
     * @var string
     */
    protected $className = '';

    /**
     * @var string
     */
    protected $methodName = '';

    /**
     * @var string
     */
    protected $contentType = '';

    /**
     * @var int
     */
    protected $duration = 0;

    /**
     * @var int
     */
    protected $creationTime = 0;

    /**
     * @var \ArrayObject
     */
    protected $criteries = null;

    /**
     * @var array
     */
    protected $globalOptions = [
        'shouldBeRecached' => false,
    ];

    /**
     * @param string $className
     * @param string $methodName
     * @param int $validTimeOnSeconds
     * @return static
     */
    public function __construct(string $className, string $methodName, int $validTimeOnSeconds = 3600)
    {

        if (!class_exists($className)) {
            throw new \Exception(vsprintf(self::__("No existe la clase '%s'."), [
                $className,
            ]));

        }

        if (!method_exists($className, $methodName)) {

            throw new \Exception(vsprintf(self::__("No existe el método '%s' para la clase '%s'."), [
                $methodName,
                $className,
            ]));

        }

        $this->className = $className;
        $this->methodName = $methodName;
        $this->criteries = new \ArrayObject();
        $this->duration = $validTimeOnSeconds;

        $this->init();

    }

    /**
     * @return static
     */
    public function process()
    {

        $serialized = StringManipulate::urlSafeB64Encode(json_encode($this));
        $this->hash = sha1($serialized);
        $this->ownConfigurationFileName = $this->hash . '.json';
        $this->creationTime = time();

        if ($this->isExpired() || $this->globalOptions['shouldBeRecached'] === true) {

            if (file_exists($this->folderCache) && is_dir($this->folderCache)) {

                $removedElements = [];

                $removeDir = function ($path, &$removedElements) use (&$removeDir) {

                    $dirResource = opendir($path);

                    if ($dirResource !== false) {

                        $dirEntry = readdir($dirResource);

                        $removedDirectories = [];
                        $removedElements[basename($path)] = [
                            'files' => [],
                            'directories' => [],
                        ];

                        while ($dirEntry !== false) {

                            if (!in_array($dirEntry, ['.', '..', 'configuration.json'])) {

                                $fullPathEntry = $path . '/' . $dirEntry;
                                $fullPathEntry = realpath($fullPathEntry);

                                if (file_exists($fullPathEntry)) {

                                    if (is_dir($fullPathEntry)) {
                                        ($removeDir)($fullPathEntry, $removedDirectories);
                                        rmdir($fullPathEntry);
                                    } else {
                                        $removedElements[basename($path)]['files'][] = basename($fullPathEntry);
                                        unlink($fullPathEntry);
                                    }

                                }

                            }

                            $dirEntry = readdir($dirResource);

                        }

                        $removedElements[basename($path)]['directories'] = $removedDirectories;

                        closedir($dirResource);

                    }

                };

                ($removeDir)($this->folderCache, $removedElements);

            }

        }

        $fileConfig = append_to_url($this->folderCache, $this->ownConfigurationFileName);

        if (!file_exists($fileConfig)) {
            file_put_contents($fileConfig, json_encode($this));
            chmod($fileConfig, 0777);
        } else {
            $this->jsonUnserialize(json_decode(file_get_contents($fileConfig), true));
        }

        return $this;

    }

    /**
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function setOwnConfigurationProperty(string $name, $value)
    {

        $fileConfig = append_to_url($this->folderCache, $this->ownConfigurationFileName);

        if (file_exists($fileConfig)) {

            $data = json_decode(file_get_contents($fileConfig));

            $data->$name = $value;

            file_put_contents($fileConfig, json_encode($data));

        }

        return $this;

    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return time() >= $this->duration + $this->creationTime;
    }

    /**
     * @param string $data
     * @param string $contentType
     * @return CacheControllersManager
     */
    public function setDataCache(string $data, string $contentType)
    {
        $this->contentType = $contentType;

        $fileConfig = $this->getCachedDataFileName(true);
        file_put_contents($fileConfig, $data);
        chmod($fileConfig, 0777);

        self::setGlobalConfigurationProperty($this->className, $this->methodName, 'shouldBeRecached', false);
        $this->setOwnConfigurationProperty('contentType', $this->contentType);
    }

    /**
     * @param bool $parse
     * @return string|\stdClass|array
     */
    public function getCachedData(bool $parse = true)
    {

        $data = '';

        if ($this->hasCachedData()) {
            $data = file_get_contents($this->getCachedDataFileName(true));
        }

        if ($parse) {

            if ($this->contentType == self::CONTENT_TYPE_JSON) {
                $data = json_decode($data);
            }

        }

        return $data;
    }

    /**
     * @return bool
     */
    public function hasCachedData()
    {
        return file_exists($this->getCachedDataFileName(true));
    }

    /**
     * @param bool $fullPath
     * @return string
     */
    public function getCachedDataFileName(bool $fullPath = false)
    {
        $ext = array_key_exists($this->contentType, self::EXTENSIONS_BY_CONTENT_TYPE) ? self::EXTENSIONS_BY_CONTENT_TYPE[$this->contentType] : 'html';
        $filename = $this->hash . '-' . 'data' . '.' . $ext;
        return $fullPath ? append_to_url($this->folderCache, $filename) : $filename;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param CacheControllersCriteries $criteries
     * @return static
     */
    public function setCriteries(CacheControllersCriteries $criteries)
    {
        $this->criteries = $criteries;
        return $this;
    }

    /**
     * @return CacheControllersCriteries
     */
    public function getCriteries()
    {
        return $this->criteries;
    }

    /**
     * @return void
     */
    public function init()
    {
        $this->folderCache = app_basepath('cache') . '/' . trim(implode('/', explode("\\", $this->className)), '') . '/' . $this->methodName;

        if (!file_exists($this->folderCache)) {
            mkdir($this->folderCache, 0777, true);
        }

        $this->globalConfigurationFile = append_to_url($this->folderCache, 'configuration.json');

        if (!file_exists($this->globalConfigurationFile)) {
            file_put_contents($this->globalConfigurationFile, json_encode($this->globalOptions));
            chmod($this->globalConfigurationFile, 0777);
        } else {
            $this->globalOptions = json_decode(file_get_contents($this->globalConfigurationFile), true);
        }
    }

    /**
     * @param string $className
     * @param string $methodName
     * @return void
     */
    public static function globalTouch(string $className, string $methodName)
    {
        self::setGlobalConfigurationProperty($className, $methodName, 'shouldBeRecached', true);
    }

    /**
     * @param string $className
     * @param string $methodName
     * @param string $property
     * @param mixed $value
     * @return void
     */
    public static function setGlobalConfigurationProperty(string $className, string $methodName, string $property, $value)
    {
        if (self::globalExists($className, $methodName)) {
            $folderCache = self::globalFolderName($className, $methodName);
            $file = append_to_url($folderCache, 'configuration.json');
            $options = json_decode(file_get_contents($file));
            $options->$property = $value;
            file_put_contents($file, json_encode($options));
        }
    }

    /**
     * @param string $className
     * @param string $methodName
     * @return string
     */
    public static function globalFolderName(string $className, string $methodName)
    {
        $folderCache = app_basepath('cache') . '/' . trim(implode('/', explode("\\", $className)), '') . '/' . $methodName;
        return $folderCache;
    }

    /**
     * @param string $className
     * @param string $methodName
     * @return bool
     */
    public static function globalExists(string $className, string $methodName)
    {
        return file_exists(self::globalFolderName($className, $methodName));
    }

    /**
     * __
     *
     * @param string $message
     * @return string
     */
    public static function __(string $message)
    {
        return __(self::LANG_GROUP, $message);
    }

    /**
     * @param array $data
     * @return void
     */
    public function jsonUnserialize(array $data)
    {

        foreach ($data as $propertyName => $value) {

            if ($propertyName == 'criteries') {

                $this->$propertyName = $value;

            } else {

                $this->$propertyName = $value;

            }

        }

        $this->init();

    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = [];

        $data['ownConfigurationFileName'] = $this->ownConfigurationFileName;
        $data['globalConfigurationFile'] = $this->globalConfigurationFile;
        $data['className'] = $this->className;
        $data['methodName'] = $this->methodName;
        $data['contentType'] = $this->contentType;
        $data['duration'] = $this->duration;
        $data['creationTime'] = $this->creationTime;
        $data['criteries'] = $this->criteries;
        return $data;
    }

}
