<?php

/**
 * Parameter.php
 */
namespace PiecesPHP\Core\Validation\Parameters;

use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;

/**
 * Parameter
 *
 * Representa un parámetro pasado a un método/función
 *
 * @category    Validation
 * @package     PiecesPHP\Core\Validation\Parameters
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class Parameter implements \JsonSerializable
{

    /**
     * $name
     *
     * @var string
     */
    protected $name = '';
    /**
     * $optional
     *
     * @var bool
     */
    protected $optional = false;
    /**
     * $default
     *
     * @var mixed
     */
    protected $default = null;
    /**
     * $value
     *
     * @var mixed
     */
    protected $value = null;
    /**
     * $validate
     *
     * @var callable
     */
    protected $validate = null;
    /**
     * $parse
     *
     * @var callable
     */
    protected $parse = null;
    /**
     * $onError
     *
     * @var callable
     */
    protected $onError = null;

    /**
     * __construct
     *
     * @param string $name
     * @param mixed $default
     * @param callable $validate
     * @param bool $optional
     * @param callable $parse
     * @return static
     */
    public function __construct(string $name = null, $default = null, callable $validate = null, bool $optional = false, callable $parse = null)
    {
        $this->name = !is_null($name) ? $name : uniqid();
        $this->default = $default;
        $this->validate = is_callable($validate) ? $validate : true;
        $this->optional = $optional;
        $this->parse = is_callable($parse) ? $parse : function ($value) {return $value;};
        $this->value = null;

    }

    /**
     * validate
     *
     * Valida la entrada y establece el valor si es válido
     *
     * @param mixed $value
     * @return bool
     * @throws ParsedValueException en caso de que el resultado de parse no sea válido
     * @throws InvalidParameterValueException en caso de que el valor no sea válido
     */
    public function validate($value)
    {
        $success = true;

        if ($value != $this->getDefaultValue() && $this->isValid($value)) {

            $this->value = $value;

        } else {

            if ($this->isOptional()) {

                $this->value = $this->getDefaultValue();

            } else {

                $success = false;
                $this->onError(gettype($value));

            }

        }

        return $success;
    }

    /**
     * getName
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * getDefaultValue
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->default;
    }
    /**
     * isOptional
     *
     * @return bool
     */
    public function isOptional()
    {
        return $this->optional;
    }
    /**
     * getValue
     *
     * @param bool $raw
     * @return mixed
     * @throws InvalidParameterValueException en caso de que el valor que está almacenado no sea válido
     */
    public function getValue(bool $raw = false)
    {
        if ($this->validate($this->value)) {
            $parsed = $raw ? $this->value : $this->parse($this->value);
            return $parsed;
        }
    }

    /**
     * setName
     *
     * @param string $name
     * @return static
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }
    /**
     * setDefaultValue
     *
     * @param mixed $default
     * @return static
     */
    public function setDefaultValue($default)
    {
        $this->default = $default;
        return $this;
    }
    /**
     * setValue
     *
     * @param mixed $value
     * @return static
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
    /**
     * setOptional
     *
     * @param bool $optional
     * @return static
     */
    public function setOptional(bool $optional)
    {
        $this->optional = $optional;
        return $this;
    }
    /**
     * setValidator
     *
     * @param callable $validator
     * @return static
     */
    public function setValidator(callable $validator)
    {
        $this->validate = $validator;
        return $this;
    }
    /**
     * setParser
     *
     * @param callable $parser
     * @return static
     */
    public function setParser(callable $parser)
    {
        $this->parse = $parser;
        return $this;
    }

    /**
     * isValid
     *
     * @param mixed $value
     * @return bool
     */
    protected function isValid($value)
    {
        $valid = true;
        if (is_callable($this->validate)) {
            $valid = ($this->validate)($value) === true;
        }

        if ($this->isOptional()) {
            $valid = $valid || $value == $this->getDefaultValue();
        }

        return $valid;
    }

    /**
     * parse
     *
     * @param mixed $value
     * @return mixed
     * @throws ParsedValueException en caso de que el resultado no sea válido
     */
    protected function parse($value)
    {

        if (is_callable($this->parse) && $value != $this->getDefaultValue()) {
            $parsed_value = ($this->parse)($value);
            if ($this->isValid($parsed_value)) {
                return $parsed_value;
            } else {
                throw new ParsedValueException("El valor del parámetro $this->name devuelto por parse() no es válido.");
            }

        }

        return $value;
    }

    /**
     * onError
     *
     * @param string $typeReceived
     * @return void
     * @throws InvalidParameterValueException
     */
    protected function onError(string $typeReceived)
    {
        throw new InvalidParameterValueException("El parámetro {$this->name} ha recibido un tipo no permitido ($typeReceived)");
    }

    /**
     * jsonSerialize
     *
     * @return mixed
     */
    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'optional' => $this->optional,
            'default' => $this->default,
            'value' => $this->value,
            'valid' => $this->isValid($this->value),
        ];
    }
}
