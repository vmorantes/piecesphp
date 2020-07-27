<?php

/**
 * MetaProperty.php
 */
namespace PiecesPHP\Core\Database\Meta;

use PiecesPHP\Core\Database\EntityMapper;

/**
 * MetaProperty - Implementación de un meta campo
 *
 * Constituye una abstracción de una propiedad de campo que se guarde en formato JSON
 *
 * @package     PiecesPHP\Core\Database\Meta
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class MetaProperty
{

    const TYPE_ARRAY = 'ARRAY';
    const TYPE_TEXT = 'TEXT';
    const TYPE_NUMBER = 'NUMBER';
    const TYPE_INT = 'INT';
    const TYPE_JSON = 'JSON';
    const TYPE_DOUBLE = 'DOUBLE';
    const TYPE_DATE = 'DATE';
    const TYPE_MAPPER = 'MAPPER';
    const TYPE_ARRAY_MAPPER = 'ARRAY_MAPPER';

    const TYPES = [
        self::TYPE_ARRAY,
        self::TYPE_TEXT,
        self::TYPE_NUMBER,
        self::TYPE_INT,
        self::TYPE_JSON,
        self::TYPE_DOUBLE,
        self::TYPE_DATE,
        self::TYPE_MAPPER,
        self::TYPE_ARRAY_MAPPER,
    ];

    /**
     * @var string $type
     */
    protected $type = self::TYPE_TEXT;

    /**
     * @var string $mapperName
     */
    protected $mapperName = null;

    /**
     * @var mixed $value
     */
    protected $value = null;

    /**
     * @var mixed $defaultValue
     */
    protected $defaultValue = null;

    /**
     * @var mixed $nullable
     */
    protected $nullable = true;

    /**
     * @var string $propertyMapper
     */
    protected $propertyMapper = 'id';

    /**
     * @var callable $customSetMapper
     */
    protected $customSetMapper = null;

    /**
     * @var callable $customValidMapper
     */
    protected $customValidMapper = null;

    /**
     * @var callable $customGetValueToSQL
     */
    protected $customGetValueToSQL = null;

    /**
     * @var bool $settedValue
     */
    protected $settedValue = false;

    /**
     * __construct
     *
     * @param string $type
     * @param mixed $defaultValue
     * @param bool $nullable
     * @return static
     * @throws \Exception
     */
    public function __construct(string $type = 'TEXT', $defaultValue = null, bool $nullable = true, string $mapperName = null, string $propertyMapper = 'id')
    {

        $this->nullable = $nullable;
        $this->mapperName = $mapperName;
        $this->propertyMapper = $propertyMapper;

        if (in_array($type, self::TYPES)) {

            $this->type = $type;

            if ($this->type == self::TYPE_MAPPER || $this->type == self::TYPE_ARRAY_MAPPER) {

                if (!is_string($mapperName) || strlen(trim($mapperName)) == 0) {

                    throw new \Exception('Debe proveer el nombre de la clase del mapper.');

                }

            }

        } else {

            throw new \Exception('No existe el tipo proporcionado: ' . $type);

        }

        $valid = $this->validateValue($defaultValue);

        if ($this->nullable) {

            if (is_null($defaultValue)) {
                $valid = true;
            }

        }

        if ($valid) {

            $this->defaultValue = $defaultValue;

        } else {

            throw new \Exception('El valor proporcionado no es compatible con el tipo definido: ' . $type);

        }

    }

    /**
     * setValue
     *
     * @param mixed $value
     * @return static
     * @throws \Exception
     */
    public function setValue($value)
    {

        $this->settedValue = true;

        $valid = true;
        $existsOnMapper = true;

        if ($this->type == self::TYPE_MAPPER) {

            $valid = $this->validateValue($value);
            $existsOnMapper = false;

            if (!$valid) {

                if (is_null($this->customSetMapper)) {

                    $mapper = $this->mapperName;
                    $value = new $mapper($value);

                } else {

                    $value = ($this->customSetMapper)($value);

                }

            }

            $valid = $this->validateValue($value);

            if ($valid) {

                if (is_null($this->customValidMapper)) {

                    $existsOnMapper = $value->id !== null;

                } else {

                    $existsOnMapper = ($this->customValidMapper)($value) === true;

                }

            }

        } else if ($this->type == self::TYPE_ARRAY_MAPPER) {

            $valid = $this->validateValue($value) && is_array($value);

            if (!$valid) {

                if (is_null($this->customSetMapper)) {

                    $mapper = $this->mapperName;

                    foreach ($value as $k => $v) {

                        $value[$k] = new $mapper($v);

                    }

                } else {

                    $mapper = $this->mapperName;

                    foreach ($value as $k => $v) {

                        $value[$k] = ($this->customSetMapper)($v);

                    }

                }

            }

            $valid = $this->validateValue($value);

            if ($valid) {

                foreach ($value as $k => $v) {

                    if (is_null($this->customValidMapper)) {

                        if ($v->id === null) {
                            $existsOnMapper = false;
                            break;
                        }

                    } else {

                        if (($this->customValidMapper)($value) !== true) {
                            $existsOnMapper = false;
                            break;
                        }

                    }

                }

            }

        } else {

            $valid = $this->validateValue($value);

        }

        if ($this->nullable) {

            if (is_null($value)) {
                $valid = true;
            }

        }

        if ($valid && $existsOnMapper) {

            $this->value = $value;

        } else {

            if (!$valid) {

                throw new \Exception('El valor proporcionado no es compatible con el campo.');

            } elseif (!$existsOnMapper) {

                if ($this->type == self::TYPE_MAPPER) {
                    throw new \Exception('El valor proporcionado no puede ser encontrado en el mapper.');
                } else {
                    throw new \Exception('Uno o varios de los valores proporcionados no puede ser encontrado en el mapper.');
                }

            }

        }

        return $this;

    }

    /**
     * getValue
     *
     * @return mixed
     */
    public function getValue()
    {

        if ($this->settedValue) {
            return $this->value;
        } else {
            return $this->defaultValue;
        }

    }

    /**
     * getValueHuman
     *
     * @return mixed
     */
    public function getValueHuman()
    {

        $value = $this->value;

        if ($this->type == self::TYPE_MAPPER) {

            if (is_subclass_of($value, EntityMapper::class)) {

                return $this->recursiveHumanization($value);

            } else {
                return $value;
            }

        } else if ($this->type == self::TYPE_ARRAY_MAPPER) {

            if (is_array($value)) {

                foreach ($value as $k => $v) {

                    if (is_subclass_of($v, EntityMapper::class)) {
                        $value[$k] = $this->recursiveHumanization($v);
                    }

                }

                return $value;

            } else {
                return $value;
            }

        } elseif (!is_scalar($value) && !is_null($value)) {
            return (array) $value;
        } else {
            return $value;
        }

    }

    /**
     * getValueToSQL
     *
     * @return mixed
     */
    public function getValueToSQL()
    {

        $propertyMapper = $this->propertyMapper;

        if ($this->type == self::TYPE_ARRAY_MAPPER) {

            if ($this->settedValue) {

                $value = $this->value;

                if (is_array($value)) {

                    foreach ($value as $k => $v) {

                        $mapperName = $this->mapperName;
                        $isMapper = $v instanceof $mapperName;

                        if ($isMapper) {

                            $value[$k] = is_null($this->customGetValueToSQL) ? $v->$propertyMapper : ($this->customGetValueToSQL)($v);

                        } else {

                            $value[$k] = $v;
                        }

                    }

                }

                return $value;

            } else {

                $value = $this->defaultValue;

                if (is_array($value)) {

                    foreach ($value as $k => $v) {

                        $mapperName = $this->mapperName;
                        $isMapper = $v instanceof $mapperName;

                        if ($isMapper) {

                            $value[$k] = is_null($this->customGetValueToSQL) ? $v->$propertyMapper : ($this->customGetValueToSQL)($v);

                        } else {

                            $value[$k] = $v;
                        }

                    }

                }

                return $value;

            }

        } elseif ($this->type == self::TYPE_MAPPER) {

            if ($this->settedValue) {

                $isMapper = $this->validateValue($this->value);

                if ($isMapper) {

                    return is_null($this->customGetValueToSQL) ? $this->value->$propertyMapper : ($this->customGetValueToSQL)($this->value);

                } else {

                    return $this->value;
                }

            } else {

                $isMapper = $this->validateValue($this->defaultValue);

                if ($isMapper) {

                    return is_null($this->customGetValueToSQL) ? $this->defaultValue->$propertyMapper : ($this->customGetValueToSQL)($this->defaultValue);

                } else {

                    return $this->defaultValue;
                }

            }

        } else {

            if ($this->settedValue) {
                return $this->value;
            } else {
                return $this->defaultValue;
            }

        }

    }

    /**
     * setCustomSetMapper
     *
     * @param callable $custom
     * @return static
     */
    public function setCustomSetMapper(callable $custom)
    {
        $this->customSetMapper = $custom;
        return $this;
    }

    /**
     * setCustomValidMapper
     *
     * @param callable $custom
     * @return static
     */
    public function setCustomValidMapper(callable $custom)
    {
        $this->customValidMapper = $custom;
        return $this;
    }

    /**
     * setCustomGetValueToSQL
     *
     * @param callable $custom
     * @return static
     */
    public function setCustomGetValueToSQL(callable $custom)
    {
        $this->customGetValueToSQL = $custom;
        return $this;
    }

    /**
     * validateData
     *
     * @param mixed $value
     * @return bool
     */
    public function validateValue($value)
    {

        if ($this->type == self::TYPE_ARRAY) {

            return EntityMapper::validateType('array', $value);

        } elseif ($this->type == self::TYPE_TEXT) {

            return EntityMapper::validateType('text', $value);

        } elseif ($this->type == self::TYPE_NUMBER) {

            return EntityMapper::validateType('number', $value);

        } elseif ($this->type == self::TYPE_INT) {

            return EntityMapper::validateType('int', $value);

        } elseif ($this->type == self::TYPE_JSON) {

            return EntityMapper::validateType('json', $value);

        } elseif ($this->type == self::TYPE_DOUBLE) {

            return EntityMapper::validateType('double', $value);

        } elseif ($this->type == self::TYPE_DATE) {

            return EntityMapper::validateType('date', $value);

        } elseif ($this->type == self::TYPE_MAPPER) {

            return $value instanceof $this->mapperName && is_subclass_of($value, EntityMapper::class);

        } elseif ($this->type == self::TYPE_ARRAY_MAPPER) {

            $valid = EntityMapper::validateType('array', $value);

            if ($valid) {

                foreach ($value as $v) {

                    $mapperName = $this->mapperName;

                    if (!$v instanceof $mapperName || !is_subclass_of($v, EntityMapper::class)) {

                        $valid = false;
                        break;

                    }

                }

            }

            return $valid;

        }

    }

    /**
     * recursiveHumanization
     *
     * @param EntityMapper $mapper
     * @return array
     */
    private function recursiveHumanization(EntityMapper $mapper)
    {

        $dataHuman = $mapper->humanReadable();

        $subFields = array_keys($mapper->getFields());

        foreach ($subFields as $nameSubField) {

            if (is_subclass_of($mapper->$nameSubField, EntityMapper::class)) {

                $dataHuman[$nameSubField] = $this->recursiveHumanization($mapper->$nameSubField);

            }

        }

        return $dataHuman;
    }

}
