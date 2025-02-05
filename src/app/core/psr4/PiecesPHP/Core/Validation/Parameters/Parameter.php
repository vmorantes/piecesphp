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
     * @var string
     */
    protected $name = '';
    /**
     * @var bool
     */
    protected $optional = false;
    /**
     * @var mixed
     */
    protected $default = null;
    /**
     * @var mixed
     */
    protected $value = null;
    /**
     * @var callable
     */
    protected $validate = null;
    /**
     * @var callable
     */
    protected $parse = null;
    /**
     * @var callable
     */
    protected $onError = null;

    /**
     * @var string
     */
    protected static $NOT_SETTED_VALUE = null;

    /**
     * @param string $name
     * @param mixed $default
     * @param callable $validate
     * @param bool $optional
     * @param callable $parse
     * @return static
     */
    public function __construct(string $name = null, $default = null, callable $validate = null, bool $optional = false, callable $parse = null)
    {
        if (self::$NOT_SETTED_VALUE === null) {
            self::$NOT_SETTED_VALUE = uniqid('NOT_SETTED_VALUE_', true);
        }
        $this->name = !is_null($name) ? $name : uniqid();
        $this->default = $default;
        $this->validate = is_callable($validate) ? $validate : true;
        $this->optional = $optional;
        $this->parse = is_callable($parse) ? $parse : function ($value) {return $value;};
        $this->value = self::$NOT_SETTED_VALUE;

    }

    /**
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
        $value = $this->isNullable($value) ? null : $value;

        if ($value !== $this->getDefaultValue() && $this->isValid($value)) {

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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->default;
    }

    /**
     * @return bool
     */
    public function isOptional()
    {
        return $this->optional;
    }

    /**
     * @param bool $raw
     * @return mixed
     * @throws InvalidParameterValueException en caso de que el valor que está almacenado no sea válido
     */
    public function getValue(bool $raw = false)
    {
        $isNotSettedValue = $this->value === self::$NOT_SETTED_VALUE;
        $valueToAnalyze = !$isNotSettedValue ? $this->value : $this->getDefaultValue();
        $valueRawOnNotSetted = $isNotSettedValue ? null : $this->value;
        if ($this->validate($valueToAnalyze)) {
            $parsed = $raw ? $valueRawOnNotSetted : $this->parse($valueToAnalyze);
            return $parsed;
        }
    }

    /**
     * @param string $name
     * @return static
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param mixed $default
     * @return static
     */
    public function setDefaultValue($default)
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @param mixed $value
     * @return static
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param bool $optional
     * @return static
     */
    public function setOptional(bool $optional)
    {
        $this->optional = $optional;
        return $this;
    }

    /**
     * @param callable $validator
     * @return static
     */
    public function setValidator(callable $validator)
    {
        $this->validate = $validator;
        return $this;
    }

    /**
     * @param callable $parser
     * @return static
     */
    public function setParser(callable $parser)
    {
        $this->parse = $parser;
        return $this;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isNullable($value)
    {
        $nullable = self::nullable($value);
        $defaulValue = $this->getDefaultValue();
        return $nullable && !($value === $defaulValue);
    }

    /**
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
     * @param mixed $value
     * @return mixed
     * @throws ParsedValueException en caso de que el resultado no sea válido
     */
    protected function parse($value)
    {

        if (is_callable($this->parse) && $value !== $this->getDefaultValue()) {
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
     * @param string $typeReceived
     * @return void
     * @throws InvalidParameterValueException
     */
    protected function onError(string $typeReceived)
    {
        throw new InvalidParameterValueException("El parámetro {$this->name} ha recibido un tipo no permitido ($typeReceived)");
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected static function nullable($value)
    {
        $nullableValues = [
            '',
        ];
        foreach ($nullableValues as $i) {
            if ($value === $i) {
                return true;
            }
        }
        return $value === null;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
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
