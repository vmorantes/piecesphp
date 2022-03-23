<?php

/**
 * EntityMapperExtensible.php
 */
namespace PiecesPHP\Core\Database;

use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\Database\Meta\MetaProperty;

/**
 * EntityMapperExtensible - Implementación de EntityMapper con meta campos autogestionados
 *
 * Constituye una abstracción de meta propiedades que se guardan en formato JSON
 *
 * @package     PiecesPHP\Core\Database
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class EntityMapperExtensible extends BaseEntityMapper
{

    /**
     * @var string $metaColumnName
     */
    protected $metaColumnName = 'meta';

    /**
     * @var MetaProperty[] $metaProperties
     */
    private $metaProperties = [];

    /**
     * ID insertado al hacer el último save
     *
     * @var int|null
     */
    protected $insertIdOnSave = null;

    /**
     * @param int $value
     * @param string $field_compare
     * @return static
     */
    public function __construct(int $value = null, string $field_compare = 'primary_key')
    {
        parent::__construct($value, $field_compare);

        $metaColumnName = $this->metaColumnName;
        $metaColumnValue = is_array($this->$metaColumnName) || $this->$metaColumnName instanceof \stdClass ? (object) $this->$metaColumnName : null;

        if (!is_null($metaColumnValue)) {

            foreach ($this->metaProperties as $name => $property) {

                if (isset($metaColumnValue->$name)) {

                    $this->getMetaProperty($name)->setValue($metaColumnValue->$name);

                }

            }

        }

    }

    /**
     * @param MetaProperty $property
     * @param string $name
     * @return static
     * @throws \Exception
     */
    public function addMetaProperty(MetaProperty $property, string $name)
    {

        if ($this->hasMetaProperty($name)) {
            throw new \Exception('Ya existe una propiedad con ese nombre: ' . $name);
        }

        $this->metaProperties[$name] = $property;

        return $this;

    }

    /**
     * @param string $name
     * @return MetaProperty
     * @throws \Exception
     */
    public function getMetaProperty(string $name)
    {

        if (!$this->hasMetaProperty($name)) {
            throw new \Exception('No existe una propiedad con ese nombre: ' . $name);
        }

        return $this->metaProperties[$name];

    }

    /**
     * @return MetaProperty[]
     */
    public function getMetaProperties()
    {

        return $this->metaProperties;

    }

    /**
     * @return bool
     */
    public function hasMetaProperty(string $name)
    {

        return array_key_exists($name, $this->metaProperties);

    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {

        if (is_string($name) && $this->hasMetaProperty($name) && !array_key_exists($name, $this->fields)) {

            $this->getMetaProperty($name)->setValue($value);

        } else {

            parent::__set($name, $value);

        }

    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {

        if (is_string($name) && $this->hasMetaProperty($name) && !array_key_exists($name, $this->fields)) {

            return $this->getMetaProperty($name)->getValue();

        } else {

            return parent::__get($name);

        }

    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        $metaColumnName = $this->metaColumnName;
        $metaColumnValue = is_array($this->$metaColumnName) || $this->$metaColumnName instanceof \stdClass ? (object) $this->$metaColumnName : new \stdClass();
        $metaIsArray = is_array($metaColumnValue);

        foreach ($this->metaProperties as $name => $property) {

            $value = $property->getValueToSQL();

            if ($metaIsArray) {

                $metaColumnValue[$name] = $value;

                if ($value !== 0 && $value !== '0' && $value !== 0.0 && $value !== '0.0') {
                    if ($value == null) {
                        unset($metaColumnValue[$name]);
                    }
                }

            } else {

                $metaColumnValue->$name = $value;

                if ($value !== 0 && $value !== '0' && $value !== 0.0 && $value !== '0.0') {
                    if ($value == null) {
                        unset($metaColumnValue->$name);
                    }
                }

            }

        }

        $this->$metaColumnName = $metaColumnValue;

        $saveResult = parent::save();

        if ($saveResult) {
            $this->insertIdOnSave = $this->getLastInsertID();
        }

        return $saveResult;

    }

    /**
     * @inheritDoc
     */
    public function update()
    {
        $metaColumnName = $this->metaColumnName;
        $metaColumnValue = is_array($this->$metaColumnName) || $this->$metaColumnName instanceof \stdClass ? (object) $this->$metaColumnName : new \stdClass();
        $metaIsArray = is_array($metaColumnValue);

        foreach ($this->metaProperties as $name => $property) {

            $value = $property->getValueToSQL();

            if ($metaIsArray) {

                $metaColumnValue[$name] = $value;

                if ($value !== 0 && $value !== '0' && $value !== 0.0 && $value !== '0.0') {
                    if ($value == null) {
                        unset($metaColumnValue[$name]);
                    }
                }

            } else {

                $metaColumnValue->$name = $value;

                if ($value !== 0 && $value !== '0' && $value !== 0.0 && $value !== '0.0') {
                    if ($value == null) {
                        unset($metaColumnValue->$name);
                    }
                }

            }

        }

        $this->$metaColumnName = $metaColumnValue;

        return parent::update();

    }

    /**
     * @return int|null Devuelve el ID insertado al hacer el último save o NULL si no ha sido guardado nada aún
     */
    public function getInsertIDOnSave()
    {
        return $this->insertIdOnSave;
    }

    /**
     * @inheritDoc
     */
    public function humanReadable()
    {
        $data = parent::humanReadable();

        foreach ($this->metaProperties as $name => $property) {
            $data['META:' . $name] = $property->getValueHuman();
        }

        $fields = array_keys($this->getFields());

        foreach ($fields as $name) {

            $value = $this->$name;

            if (is_subclass_of($value, EntityMapper::class)) {

                $data[$name] = $this->recursiveHumanization($value);

            }

        }

        return $data;
    }

    /**
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

    /**
     * @return BaseModel
     */
    public static function model()
    {
        return (new EntityMapperExtensible)->getModel();
    }
}
