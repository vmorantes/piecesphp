<?php

/**
 * Parameters.php
 */
namespace PiecesPHP\Core\Validation\Parameters;

use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParamaterNotExistsException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;

/**
 * Parameters
 *
 * Representa un conjunto de parámetros pasados a un método/función
 * para validarlos
 *
 * @category    Validation
 * @package     PiecesPHP\Core\Validation\Parameters
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class Parameters implements \JsonSerializable
{

    /**
     * @var Parameter[]
     */
    protected $parameters = [];

    /**
     * @var array
     */
    protected $inputValues = [];

    const LANG_GROUP = 'PiecesPHP\Core\Validation\Parameters';

    /**
     * @param string $name
     * @param mixed $default
     * @param callable $validate
     * @param bool $optional
     * @param callable $parse
     * @param string $typeName
     * @return static
     */
    public function __construct(array $parameters)
    {
        $this->setParameters($parameters);
    }

    /**
     * @param array $inputValues Array con clave=>valor que correponde al nombre del parámetro y al valor
     * @return Parameters
     */
    public function setInputValues(array $inputValues)
    {
        foreach ($inputValues as $name => $value) {

            if (is_string($name)) {
                $this->inputValues[$name] = $value;
            }
        }
    }

    /**
     * @param Parameter $parameter
     * @return static
     */
    public function addParamater(Parameter $parameter)
    {
        $this->parameters[$parameter->getName()] = $parameter;
        return $this;
    }

    /**
     * @param Parameter[] $parameters
     * @return static
     */
    public function addParameters(array $parameters)
    {
        foreach ($parameters as $parameter) {
            $this->addParamater($parameter);
        }
        return $this;
    }

    /**
     * @param Parameter[] $parameters
     * @return static
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = [];
        $this->addParameters($parameters);
        return $this;
    }

    /**
     * @param string $name
     * @return Parameter
     * @throws ParamaterNotExistsException
     */
    public function getParameter(string $name)
    {
        if (array_key_exists($name, $this->parameters)) {
            return $this->parameters[$name];
        } else {
            throw new ParamaterNotExistsException("El parámetro $name no existe.");
        }
    }

    /**
     * @return Parameter[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws ParsedValueException en caso de que el resultado de parse no sea válido
     * @throws InvalidParameterValueException en caso de que el valor no sea válido
     * @throws ParamaterNotExistsException
     */
    public function getValue(string $name)
    {
        $parameter = $this->getParameter($name);
        $value = null;

        if ($parameter->validate($parameter->getValue(true))) {
            $value = $parameter->getValue();
        }

        return $value;
    }

    /**
     * @return array
     * @throws MissingRequiredParamaterException En caso de que falte algún parámetro obligatorio en la lista de parámetros
     * @throws ParsedValueException en caso de que el resultado de parse no sea válido
     * @throws InvalidParameterValueException en caso de que el valor no sea válido
     */
    public function getValues()
    {
        $values = [];

        if ($this->validate()) {

            foreach ($this->parameters as $name => $parameter) {
                $values[$name] = $parameter->getValue();
            }

        }

        return $values;
    }

    /**
     * @return bool
     * @throws MissingRequiredParamaterException En caso de que falte algún parámetro obligatorio en la lista de parámetros
     * @throws ParsedValueException en caso de que el resultado de parse no sea válido
     * @throws InvalidParameterValueException en caso de que el valor no sea válido
     */
    public function validate()
    {
        $missing = [];
        $to_validate = [];
        foreach ($this->parameters as $name => $parameter) {
            if (array_key_exists($name, $this->inputValues)) {
                $nullable = $parameter->isNullable($this->inputValues[$name]);
                if (!$nullable) {
                    $to_validate[$name] = $this->inputValues[$name];
                } else {
                    $missing[] = $name;
                }
            } else {
                $missing[] = $name;
            }
        }

        $parameters_errors = [];
        foreach ($missing as $name) {
            $parameter = $this->parameters[$name];
            if (!$parameter->isOptional()) {
                $parameters_errors[] = $name;
            }
        }

        if (!empty($parameters_errors)) {

            $isMany = count($parameters_errors) > 1;
            if ($isMany) {
                $text = __(self::LANG_GROUP, 'Los parámetros %PARAMS% son obligatorios');
            } else {
                $text = __(self::LANG_GROUP, 'El parámetro %PARAMS% es obligatorio');
            }
            throw new MissingRequiredParamaterException(strReplaceTemplate($text, [
                '%PARAMS%' => implode(', ', $parameters_errors),
            ]));
        }

        foreach ($to_validate as $name => $value) {
            $this->parameters[$name]->validate($value);
        }

        return true;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'parameters' => $this->parameters,
        ];
    }
}
