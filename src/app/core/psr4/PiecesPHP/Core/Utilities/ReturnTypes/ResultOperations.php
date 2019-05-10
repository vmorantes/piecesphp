<?php

/**
 * ResultOperations.php
 */
namespace PiecesPHP\Core\Utilities\ReturnTypes;

/**
 * ResultOperations
 *
 * Representa un tipo de retorno para brindar una interfaz uniforme de respuesta
 *
 * @category    Utilidades
 * @package     PiecesPHP\Core\Utilities\ReturnTypes
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @version     v.1
 * @copyright   Copyright (c) 2018
 * @info Funciona como módulo independiente
 */
class ResultOperations implements \JsonSerializable
{
    /**
     * $name
     *
     * @var string
     */
    protected $name = '';
    /**
     * $message
     *
     * @var string
     */
    protected $message = '';
    /**
     * $operations
     *
     * @var Operation[]
     */
    protected $operations = [];
    /**
     * $extras
     *
     * @var array
     */
    protected $extras = [];
    /**
     * $values
     *
     * @var array
     */
    protected $values = [];

    /**
     * __construct
     *
     * @param Operation[] $operations
     * @param string $message
     * @return static
     */
    public function __construct(array $operations = [], string $name = '', string $message = '')
    {
        array_map(function ($el) use ($operations) {
            if (!$el instanceof Operation) {
                throw new \TypeError("El parametro $operations debe ser un array de objetos " . Operation::class);
            }
        }, $operations);

        $this->setOperations($operations);
        $this->name = strlen(trim($name)) > 0 ? $name : time();
        $this->message = $message;

    }

    /**
     * success
     *
     * @param bool $onlyMinimumRequired
     * @return bool
     */
    public function success(bool $onlyMinimumRequired = false): bool
    {
        $success = true;
        foreach ($this->operations as $operation) {
            if ($onlyMinimumRequired) {
                if ($operation->getRequired() && !$operation->getSuccess()) {
                    $success = false;
                    break;
                }
            } else {
                if (!$operation->getSuccess()) {
                    $success = false;
                    break;
                }
            }
        }
        return $success;
    }

    /**
     * successOperations
     *
     * @return bool
     */
    public function successOperations(): array
    {
        $success = [];
        foreach ($this->operations as $operation) {
            if ($operation->getSuccess()) {
                $success[$operation->getName()] = $operation;
            }
        }
        return $success;
    }

    /**
     * fails
     *
     * @return Operation[]
     */
    public function fails(): array
    {
        $fails = [];
        foreach ($this->operations as $operation) {
            if (!$operation->getSuccess()) {
                $fails[$operation->getName()] = $operation;
            }
        }
        return $fails;
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
     * setMessage
     *
     * @param string $message
     * @return static
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * addOperation
     *
     * @param Operation &$operation
     * @return static
     */
    public function addOperation(Operation &$operation)
    {
        $name = $operation->getName();
        $this->operations[$name] = $operation;
        return $this;
    }

    /**
     * operation
     *
     * @param string $name
     * @return Operation|null
     */
    public function operation(string $name)
    {
        if (isset($this->operations[$name])) {
            return $this->operations[$name];
        } else {
            return null;
        }
    }

    /**
     * setOperations
     *
     * @param Operation[] &$operation
     * @return static
     */
    public function setOperations(array &$operations)
    {
        $this->operations = [];
        foreach ($operations as $operation) {
            $this->addOperation($operation);
        }
        return $this;
    }

    /**
     * addExtra
     *
     * @param mixed &$extra
     * @return static
     */
    public function addExtra(&$extra)
    {
        $this->extras[] = $extra;
        return $this;
    }

    /**
     * setdExtras
     *
     * @param array &$extras
     * @return static
     */
    public function setExtras(array &$extras)
    {
        $this->extras = $extras;
        return $this;
    }

    /**
     * setValue
     *
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function setValue(string $name, $value)
    {
        $this->values[$name] = $value;
        return $this;
    }

    /**
     * setValues
     *
     * @param array $values
     * @return static
     */
    public function setValues(array $values)
    {
        foreach ($values as $key => $value) {
            $this->setValue($key, $value);
        }
        return $this;
    }

    /**
     * getValue
     *
     * @param string $name
     * @return mixed
     */
    public function getValue(string $name)
    {
        return isset($this->values[$name]) ? $this->values[$name] : null;
    }

    /**
     * getValues
     *
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * result
     *
     * @param array $operations
     * @param string $message
     * @return static
     */
    public function result(array $operations, string $message = '')
    {
        return (new static($operations, $message));
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
            'message' => $this->message,
            'minimumRequiredSuccess' => $this->success(true),
            'success' => $this->success(),
            'successOperations' => $this->successOperations(),
            'fails' => $this->fails(),
            'operations' => $this->operations,
            'extras' => $this->extras,
            'values' => $this->values,
        ];
    }
}
