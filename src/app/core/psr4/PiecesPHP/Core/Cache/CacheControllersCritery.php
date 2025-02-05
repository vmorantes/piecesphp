<?php

/**
 * CacheControllersCritery.php
 */
namespace PiecesPHP\Core\Cache;

use JsonSerializable;

/**
 * CacheControllersCritery
 *
 * @category    Cache
 * @package     PiecesPHP\Core\Cache
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class CacheControllersCritery implements JsonSerializable
{

    const LANG_GROUP = 'cache_manager_messages';

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var mixed
     */
    protected $value = null;

    /**
     * @var callable
     */
    protected $valueValidation = null;

    /**
     * @var callable
     */
    protected $valueParsePostValidation = null;

    /**
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
     * @param callable $callable
     * @return static
     */
    public function setValueValidation(callable $callable)
    {
        $this->valueValidation = $callable;
        return $this;
    }

    /**
     * @param callable $callable
     * @return static
     */
    public function setValueParsePostValidation(callable $callable)
    {
        $this->valueParsePostValidation = $callable;
        return $this;
    }

    /**
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
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = [];

        $data['name'] = $this->name;
        $data['value'] = $this->value;

        return $data;
    }

}
