<?php

/**
 * Parameters.php
 */
namespace PiecesPHP\Core\Validation\Parameters;

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
     * $parameters
     *
     * @var Parameter[]
     */
    protected $parameters = [];

    /**
     * $inputValues
     *
     * @var array
     */
    protected $inputValues = [];

    /**
     * __construct
     *
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

    public function addParamater(Parameter $parameter)
    {
        $this->parameters[$parameter->getName()] = $parameter;
        return $this;
    }

    public function addParameters(array $parameters)
    {
        foreach ($parameters as $parameter) {
            $this->addParamater($parameter);
        }
        return $this;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = [];
        $this->addParameters($parameters);
        return $this;
    }

    /**
     * validate
     *
     * @return bool
     * @throws \Exception En caso de que falte algún parámetro obligatorio en la lista de parámetros
     * @throws \Exception en caso de que el resultado de parse no sea válido
     * @throws \TypeError en caso de que el valor no sea válido
     */
    public function validate()
    {
        $missing = [];
        $to_validate = [];
        foreach ($this->parameters as $name => $parameter) {
            if (array_key_exists($name, $this->inputValues)) {
                $to_validate[$name] = $this->inputValues[$name];
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

        if (count($parameters_errors) > 0) {
            throw new \Exception("Los parámetros " . implode(',', $parameters_errors) . "son obligatorios");
        }

        foreach ($to_validate as $name => $value) {
            $this->parameters[$name]->validate($value);
        }

        return true;
    }

    /**
     * getValues
     *
     * @return array
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
     * setInputValues
     *
     * @param array $inputValues Array con clave=>valor que correponde al nombre del parámetro y al valor
     * @return static
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
     * jsonSerialize
     *
     * @return mixed
     */
    public function jsonSerialize()
    {
        return [
            'parameters' => $this->parameters,
        ];
    }
}
