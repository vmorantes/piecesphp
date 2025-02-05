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
 * @copyright   Copyright (c) 2018
 */
class ResultOperations implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $name = '';
    /**
     * @var string
     */
    protected $message = '';
    /**
     * @var Operation[]
     */
    protected $operations = [];
    /**
     * @var array
     */
    protected $extras = [];
    /**
     * @var array
     */
    protected $values = [];
    /**
     * @var bool
     */
    protected $successOnSingleOperation = false;
    /**
     * @var bool
     */
    protected $singleOperation = false;

    /**
     * @param Operation[] $operations
     * @param string $name
     * @param string $message
     * @return static
     */
    public function __construct(array $operations = [], string $name = '', string $message = '', bool $singleOperation = false)
    {
        array_map(function ($el) use ($operations) {
            if (!$el instanceof Operation) {
                throw new \TypeError("El parametro $operations debe ser un array de objetos " . Operation::class);
            }
        }, $operations);

        $this->setOperations($operations);
        $this->name = mb_strlen(trim($name)) > 0 ? $name : time();
        $this->message = $message;

        if (!empty($this->operations)) {
            $singleOperation = false;
        }

        $this->singleOperation = $singleOperation;

    }

    /**
     * @param bool $onlyMinimumRequired
     * @return bool
     */
    public function success(bool $onlyMinimumRequired = false): bool
    {
        if ($this->singleOperation) {

            return $this->successOnSingleOperation;

        } else {

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
    }

    /**
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
     * @param bool $success
     * @return static
     */
    public function setSuccessOnSingleOperation(bool $success)
    {
        $this->successOnSingleOperation = $success;
        return $this;
    }

    /**
     * @param string $name
     * @return static
     */
    public function setSingleOperation(bool $singleOperation)
    {
        $this->singleOperation = $singleOperation;
        return $this;
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
     * @param string $message
     * @return static
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @param Operation &$operation
     * @return static
     */
    public function addOperation(Operation &$operation)
    {
        $this->singleOperation = false;
        $name = $operation->getName();
        $this->operations[$name] = $operation;
        return $this;
    }

    /**
     * @param string $name
     * @return Operation|null
     */
    public function operation(string $name)
    {
        if ($this->singleOperation) {

            return null;

        } else {
            if (isset($this->operations[$name])) {
                return $this->operations[$name];
            } else {
                return null;
            }
        }
    }

    /**
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
     * @param mixed &$extra
     * @return static
     */
    public function addExtra(&$extra)
    {
        $this->extras[] = $extra;
        return $this;
    }

    /**
     * @param array &$extras
     * @return static
     */
    public function setExtras(array &$extras)
    {
        $this->extras = $extras;
        return $this;
    }

    /**
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
     * @return bool
     */
    public function getSingleOperation()
    {
        return $this->singleOperation;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getValue(string $name)
    {
        return isset($this->values[$name]) ? $this->values[$name] : null;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param array $operations
     * @param string $message
     * @return ResultOperations
     */
    public function result(array $operations, string $message = '')
    {
        return self::resultInstance($operations, $message);
    }

    /**
     * @param array $operations
     * @param string $message
     * @return ResultOperations
     */
    public static function resultInstance(array $operations, string $message = '')
    {
        return (new ResultOperations($operations, $message));
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'message' => $this->message,
            'minimumRequiredSuccess' => $this->success(true),
            'success' => $this->success(),
            'successOperations' => $this->successOperations(),
            'fails' => $this->fails(),
            'operations' => $this->operations,
            'singleOperation' => $this->singleOperation,
            'extras' => $this->extras,
            'values' => $this->values,
        ];
    }
}
