<?php

/**
 * CacheControllersCritery.php
 */
namespace PiecesPHP\Core\Cache;

use JsonSerializable;
use Serializable;

/**
 * CacheControllersCritery
 *
 * @category    Cache
 * @package     PiecesPHP\Core\Cache
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class CacheControllersCritery implements Serializable, JsonSerializable
{

    const LANG_GROUP = 'cache_manager_messages';

    /**
     * $name
     *
     * @var string
     */
    protected $name = '';

    /**
     * $value
     *
     * @var mixed
     */
    protected $value = null;

    /**
     * $valueValidation
     *
     * @var callable
     */
    protected $valueValidation = null;

    /**
     * $valueParsePostValidation
     *
     * @var callable
     */
    protected $valueParsePostValidation = null;

    /**
     * __construct
     *
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function __construct(string $name, $value = null)
    {
        $this->name = $name;
        $this->value = $value;
        $this->valueValidation = function ($value) {
            return true;
        };
        $this->valueParsePostValidation = function ($value) {
            return $value;
        };
    }

    /**
     * name
     *
     * @param string $criteries
     * @return string|static
     */
    public function name(string $name = null)
    {

        if ($name !== null) {

            $this->name = $name;

            return $this;

        } else {

            return $this->name;

        }

    }

    /**
     * value
     *
     * @param string $criteries
     * @param bool $modeSet
     * @return mixed|static
     */
    public function value($value = null, bool $modeSet = false)
    {

        if ($value !== null || $modeSet) {

            if ($this->validateValue($value)) {
                $this->value = ($this->valueParsePostValidation)($value);
            } else {
                throw new \Exception(self::__('Ya existe este CacheControllersCritery'));
            }

            return $this;

        } else {

            return $this->value;

        }

    }

    /**
     * setValueValidation
     *
     * @param callable $callable
     * @return static
     */
    public function setValueValidation(callable $callable)
    {
        $this->valueValidation = $callable;
        return $this;
    }

    /**
     * setValueParsePostValidation
     *
     * @param callable $callable
     * @return static
     */
    public function setValueParsePostValidation(callable $callable)
    {
        $this->valueParsePostValidation = $callable;
        return $this;
    }

    /**
     * validateValue
     *
     * @param mixed $value
     * @return bool
     */
    public function validateValue($value)
    {
        $result = ($this->valueValidation)($value);
        return is_bool($result) ? $result : false;
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

            if ($propertyName == 'OTHER_STRATEGY') {

                $this->$propertyName = $value;

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

        $data['name'] = $this->name;
        $data['value'] = $this->value;

        return $data;
    }

}
