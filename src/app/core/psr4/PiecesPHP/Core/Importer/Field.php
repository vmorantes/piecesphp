<?php
/**
 * Field.php
 */
namespace PiecesPHP\Core\Importer;

use PiecesPHP\Core\Importer\Collections\FieldCollection;

/**
 * Field.
 *
 * Campo de un esquema de importación
 *
 * @package     PiecesPHP\Core\Importer
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class Field
{
    /**
     * $name
     *
     * @var string
     */
    protected $name = null;
    /**
     * $humanReadableName
     *
     * @var string
     */
    protected $humanReadableName = null;
    /**
     * $defaultValue
     *
     * @var mixed
     */
    protected $defaultValue = null;
    /**
     * $sampleValue
     *
     * @var mixed
     */
    protected $sampleValue = null;
    /**
     * $value
     *
     * @var mixed
     */
    protected $value = null;
    /**
     * $optional
     *
     * @var bool
     */
    protected $optional = true;
    /**
     * $validator
     *
     * @var callable
     */
    protected $validator = null;
    /**
     * $parser
     *
     * @var callable
     */
    protected $parser = null;
    /**
     * $encodeJson
     *
     * @var bool
     */
    protected $encodeJson = false;
    /**
     * $metaPropertiesAllowed
     *
     * @var FieldCollection
     */
    protected $metaPropertiesAllowed = null;
    /**
     * $parent
     *
     * @var string
     */
    protected $parent = null;
    /**
     * $showInTemplate
     *
     * @var bool
     */
    protected $showInTemplate = true;

    /**
     * __construct
     *
     * @param string $name
     * @param string $humanReadableName
     * @param mixed $defaultValue
     * @param bool $optional
     * @param mixed $sampleValue
     * @param bool $inTemplate
     * @return static
     */
    public function __construct(string $name, string $humanReadableName, $defaultValue = '', bool $optional = false, $sampleValue = '', bool $inTemplate = true)
    {
        $this->name = trim($name);
        $this->defaultValue = $defaultValue;
        $this->sampleValue = $sampleValue;
        $this->humanReadableName = $humanReadableName === null ? $this->name : mb_strtolower(trim($humanReadableName));
        $this->humanReadableName = str_split($this->humanReadableName);
        $this->humanReadableName[0] = mb_strtoupper($this->humanReadableName[0]);
        $this->humanReadableName = trim(implode('', $this->humanReadableName));
        $this->optional = $optional;
        $this->metaPropertiesAllowed = new FieldCollection();
        $this->showInTemplate = $inTemplate;
    }

    /**
     * setMetaProperty
     *
     * @param Field $property
     * @return static
     */
    public function setMetaProperty(Field $property)
    {
        $property->setParentName($this->name);
        $this->metaPropertiesAllowed->append($property);
        return $this;
    }

    /**
     * setMetaProperties
     *
     * @param FieldCollection $properties
     * @return static
     */
    public function setMetaProperties(FieldCollection $properties)
    {
        $subFields = new FieldCollection();
        foreach ($properties as $property) {
            $subFields->append($property->setParentName($this->name));
        }
        $this->metaPropertiesAllowed = $subFields;
        return $this;
    }

    /**
     * setValueMetaProperty
     *
     * @param string $name
     * @param mixed $value
     * @return static
     * @throws \Exception
     */
    public function setValueMetaProperty(string $name, $value)
    {
        $position = $this->metaPropertiesAllowed->getPositionField($name);

        if ($position !== null) {
            $field = $this->metaPropertiesAllowed->getByName($name);
            $field->setValue($value);
            $this->metaPropertiesAllowed->offsetSet($position, $field);
        }

        return $this;
    }

    /**
     * setValue
     *
     * @param mixed $value
     * @param string $metaName
     * @return static
     * @throws \Exception
     */
    public function setValue($value, string $metaName = null)
    {
        if (is_string($metaName)) {

            $this->setValueMetaProperty($metaName, $value);

        } else {
            if ($this->encodeJson) {

                $this->value = json_encode($value);

            } else {
                if ($this->validate($value)) {
                    $this->value = $this->parse($value);
                } else {
                    throw new \Exception(
						sprintf(
							__('importerModule', 'El valor ingresado en %s no es válido.'),
							"$this->name/$this->humanReadableName"
						)
					);
                }
            }
        }

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
        $this->validator = $validator;
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
        $this->parser = $parser;
        return $this;
    }

    /**
     * setEncodeToJson
     *
     * @param bool $encodeJson
     * @return static
     */
    public function setEncodeToJson(bool $encodeJson)
    {
        $this->encodeJson = $encodeJson;

        return $this;
    }

    /**
     * setSampleValue
     *
     * @param mixed $value
     * @return static
     * @throws TypeError
     */
    public function setSampleValue($value)
    {
        if (is_scalar($value)) {
            $this->sampleValue = $value;
        } else {
            throw new \TypeError('$value debe ser un valor escalar.');
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
        if ($this->hasMetaProperties()) {
            if (is_null($this->value)) {

                $value = [];

                foreach ($this->metaPropertiesAllowed as $property) {
                    $value[$property->getName()] = $property->getValue();
                }

                return json_encode($value);

            } else {
                return $this->value;
            }
        } else {
            return $this->value;
        }
    }

    /**
     * getSampleValue
     *
     * @return mixed
     */
    public function getSampleValue()
    {
        return $this->sampleValue;
    }

    /**
     * getDefaultValue
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * getHumanReadable
     *
     * @return string
     */
    public function getHumanReadable()
    {
        return $this->humanReadableName;
    }

    /**
     * getMetaPropertyByName
     *
     * @param string $name
     * @return Field|null
     */
    public function getMetaPropertyByName(string $name)
    {
        return $this->metaPropertiesAllowed->getByName($name);
    }

    /**
     * getMetaProperties
     *
     * @return FieldCollection
     */
    public function getMetaProperties()
    {
        return $this->metaPropertiesAllowed;
    }

    /**
     * getMetaPropertiesNames
     *
     * @return string[]
     */
    public function getMetaPropertiesNames()
    {
        $names = [];
        foreach ($this->metaPropertiesAllowed as $property) {
            $names[] = $property->getName();
        }
        return $names;
    }

    /**
     * validate
     *
     * @param mixed $value
     * @return bool
     */
    public function validate($value)
    {
        if (is_callable($this->validator)) {
            return ($this->validator)($value);
        } else {
            return true;
        }
    }

    /**
     * parse
     *
     * @param mixed $value
     * @return mixed
     */
    public function parse($value)
    {
        if (is_callable($this->parser)) {
            return ($this->parser)($value);
        } else {
            return $value;
        }
    }

    /**
     * hasMetaProperties
     *
     * @return bool
     */
    public function hasMetaProperties()
    {
        return count($this->metaPropertiesAllowed->getArrayCopy()) > 0;
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
     * getName
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * setParentName
     *
     * @param string $name
     * @return static
     */
    private function setParentName(string $name)
    {
        $this->parent = $name;
        return $this;
    }

    /**
     * getParentName
     *
     * @return string|null
     */
    public function getParentName()
    {
        return $this->parent;
    }

    /**
     * hasParent
     *
     * @return bool
     */
    public function hasParent()
    {
        return $this->parent !== null;
    }

    /**
     * getShowInTemplate
     *
     * @return bool
     */
    public function getShowInTemplate()
    {
        return $this->showInTemplate;
    }
}
