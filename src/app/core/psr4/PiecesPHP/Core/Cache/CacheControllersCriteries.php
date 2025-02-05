<?php

/**
 * CacheControllersCriteries.php
 */
namespace PiecesPHP\Core\Cache;

use JsonSerializable;
use \ArrayObject;

/**
 * CacheControllersCriteries
 *
 * @category    Cache
 * @package     PiecesPHP\Core\Cache
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class CacheControllersCriteries implements JsonSerializable
{

    const LANG_GROUP = 'cache_manager_messages';

    /**
     * @var ArrayObject
     */
    protected $criteries = null;

    /**
     * @param CacheControllersCritery[] $criteries
     * @return static
     */
    public function __construct(array $criteries = [])
    {

        $this->criteries = new ArrayObject();
        $this->criteries($criteries);

    }

    /**
     * @param CacheControllersCritery $critery
     * @param bool $silentOnDuplicate
     * @return CacheControllersCriteries
     */
    public function addCritery(CacheControllersCritery $critery, bool $silentOnDuplicate = false)
    {

        $name = $critery->name();

        if (!$this->criteries->offsetExists($name)) {
            $this->criteries->offsetSet($name, $critery);
        } else {
            if (!$silentOnDuplicate) {
                throw new \Exception(self::__('Ya existe este CacheControllersCritery'));
            }
        }

    }

    /**
     * @param CacheControllersCritery[] $criteries
     * @return CacheControllersCritery[|static
     */
    public function criteries(array $criteries = null)
    {

        if (is_array($criteries)) {

            array_map(function ($e) {

                if (!$e instanceof CacheControllersCritery) {
                    throw new \TypeError(self::__('El parámetro $criteries debe ser CacheControllersCritery[]'));
                }

            }, $criteries);

        }

        if ($criteries !== null) {

            $this->criteries = new ArrayObject();

            foreach ($criteries as $critery) {
                $this->addCritery($critery);
            }

            return $this;

        } else {

            return $this->criteries;

        }

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
     * @return array
     */
    public function __serialize()
    {
        return $this->jsonSerialize();
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data)
    {
        foreach ($data as $propertyName => $value) {

            if ($propertyName == 'criteries') {

                $arrayObject = new ArrayObject(is_array($value) ? $value : []);
                $this->$propertyName = $arrayObject;

            } else {

                $this->$propertyName = $value;

            }

        }

    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = [];

        $data['criteries'] = $this->criteries->getArrayCopy();

        return $data;
    }

}
