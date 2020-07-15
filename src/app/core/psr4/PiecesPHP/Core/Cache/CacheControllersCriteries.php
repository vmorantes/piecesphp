<?php

/**
 * CacheControllersCriteries.php
 */
namespace PiecesPHP\Core\Cache;

use JsonSerializable;
use Serializable;
use \ArrayObject;

/**
 * CacheControllersCriteries
 *
 * @category    Cache
 * @package     PiecesPHP\Core\Cache
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class CacheControllersCriteries implements Serializable, JsonSerializable
{

    const LANG_GROUP = 'cache_manager_messages';

    /**
     * $criteries
     *
     * @var ArrayObject
     */
    protected $criteries = null;

    /**
     * __construct
     *
     * @param CacheControllersCritery[] $criteries
     * @return static
     */
    public function __construct(array $criteries = [])
    {

        $this->criteries = new ArrayObject();
        $this->criteries($criteries);

    }

    /**
     * addCritery
     *
     * @param CacheControllersCritery $critery
     * @param bool $silentOnDuplicate
     * @return static
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
     * criteries
     *
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

                $arrayObject = new ArrayObject(is_array($value) ? $value : []);
                $this->$propertyName = $arrayObject;

            } else {

                $this->$propertyName = $value;

            }

        }

    }

    /**
     * jsonSerialize
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $data = [];

        $data['criteries'] = $this->criteries->getArrayCopy();

        return $data;
    }

}
