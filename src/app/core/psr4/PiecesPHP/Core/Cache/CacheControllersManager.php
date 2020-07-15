<?php

/**
 * CacheControllersManager.php
 */
namespace PiecesPHP\Core\Cache;

use PiecesPHP\Core\StringManipulate;
use \ArrayObject;
use \JsonSerializable;
use \Serializable;

/**
 * CacheControllersManager
 *
 * @category    Cache
 * @package     PiecesPHP\Core\Cache
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class CacheControllersManager implements Serializable, JsonSerializable
{

    const LANG_GROUP = 'cache_manager_messages';

    const CONTENT_TYPE_JSON = 'application/json';
    const CONTENT_TYPE_HTML = 'text/html';

    const EXTENSIONS_BY_CONTENT_TYPE = [
        self::CONTENT_TYPE_JSON => 'json',
        self::CONTENT_TYPE_HTML => 'html',
    ];

    /**
     * $hash
     *
     * @var string
     */
    private $hash = '';

    /**
     * $ownConfigurationFileName
     *
     * @var string
     */
    private $ownConfigurationFileName = '';

    /**
     * $folderCache
     *
     * @var string
     */
    private $folderCache = '';

    /**
     * $globalConfigurationFile
     *
     * @var string
     */
    private $globalConfigurationFile = '';

    /**
     * $className
     *
     * @var string
     */
    protected $className = '';

    /**
     * $methodName
     *
     * @var string
     */
    protected $methodName = '';

    /**
     * $contentType
     *
     * @var string
     */
    protected $contentType = '';

    /**
     * $duration
     *
     * @var int
     */
    protected $duration = 0;

    /**
     * $creationTime
     *
     * @var int
     */
    protected $creationTime = 0;

    /**
     * $criteries
     *
     * @var \ArrayObject
     */
    protected $criteries = null;

    /**
     * $globalOptions
     *
     * @var array
     */
    protected $globalOptions = [
        'shouldBeRecached' => false,
    ];

    /**
     * __construct
     *
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
     * process
     *
     * @return static
     */
    public function process()
    {

        $serialized = StringManipulate::urlSafeB64Encode(serialize($this));
        $this->hash = sha1($serialized);
        $this->ownConfigurationFileName = $this->hash . '.json';
        $this->creationTime = time();

        $fileConfig = append_to_url($this->folderCache, $this->ownConfigurationFileName);

        if (!file_exists($fileConfig)) {
            file_put_contents($fileConfig, json_encode($this));
            chmod($fileConfig, 0777);
        } else {
            $this->unserialize(file_get_contents($fileConfig));
        }

        if ($this->isExpired() || $this->globalOptions['shouldBeRecached'] === true) {
            if (file_exists($fileConfig)) {
                unlink($fileConfig);
            }
            if (file_exists($this->getCachedDataFileName(true))) {
                unlink($this->getCachedDataFileName(true));
            }
        }

        return $this;

    }

    /**
     * setOwnConfigurationProperty
     *
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
     * isExpired
     *
     * @return bool
     */
    public function isExpired()
    {
        return time() >= $this->duration + $this->creationTime;
    }

    /**
     * setDataCache
     *
     * @param string $data
     * @param string $contentType
     * @return static
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
     * getCachedData
     *
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
     * hasCachedData
     *
     * @return bool
     */
    public function hasCachedData()
    {
        return file_exists($this->getCachedDataFileName(true));
    }

    /**
     * getCachedDataFileName
     *
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
     * getContentType
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * setCriteries
     *
     * @param CacheControllersCriteries $criteries
     * @return static
     */
    public function setCriteries(CacheControllersCriteries $criteries)
    {
        $this->criteries = $criteries;
        return $this;
    }

    /**
     * getCriteries
     *
     * @return CacheControllersCriteries
     */
    public function getCriteries()
    {
        return $this->criteries;
    }

    /**
     * configFolderCache
     *
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
     * globalTouch
     *
     * @param string $className
     * @param string $methodName
     * @return void
     */
    public static function globalTouch(string $className, string $methodName)
    {
        self::setGlobalConfigurationProperty($className, $methodName, 'shouldBeRecached', true);
    }

    /**
     * setGlobalConfigurationProperty
     *
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
     * globalFolderName
     *
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
     * globalExists
     *
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
     * serialize
     *
     * @return string
     */
    public function serialize()
    {
        return json_encode($this);
    }

    /**
     * unserialize
     *
     * @param mixed $serialized
     * @return void
     */
    public function unserialize($serialized)
    {
        $data = json_decode($serialized);

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
     * jsonSerialize
     *
     * @return array
     */
    public function jsonSerialize()
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
