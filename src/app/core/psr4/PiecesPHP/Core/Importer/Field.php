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
     * @var string
     */
    protected $name = null;
    /**
     * @var string
     */
    protected $humanReadableName = null;
    /**
     * @var mixed
     */
    protected $defaultValue = null;
    /**
     * @var mixed
     */
    protected $sampleValue = null;
    /**
     * @var mixed
     */
    protected $value = null;
    /**
     * @var bool
     */
    protected $optional = true;
    /**
     * @var callable|null
     */
    protected $validator = null;
    /**
     * @var callable|null
     */
    protected $parser = null;
    /**
     * @var bool
     */
    protected $encodeJson = false;
    /**
     * @var FieldCollection
     */
    protected $metaPropertiesAllowed = null;
    /**
     * @var string
     */
    protected $parent = null;
    /**
     * @var bool
     */
    protected $showInTemplate = true;
    /**
     * @var string
     */
    protected $currentErrorMessage = '';

    const LANG_GROUP = 'importerModule';

    /**
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
        $humanReadableName = is_string($humanReadableName) && mb_strlen($humanReadableName) > 0 ? mb_strtolower(trim($humanReadableName)) : $this->name;
        $humanReadableName = mb_str_split($humanReadableName);
        if ($humanReadableName !== false) {
            $humanReadableName[0] = mb_strtoupper($humanReadableName[0]);
            $this->humanReadableName = trim(implode('', $humanReadableName));
        } else {
            $this->humanReadableName = $this->name;
        }
        $this->optional = $optional;
        $this->metaPropertiesAllowed = new FieldCollection();
        $this->showInTemplate = $inTemplate;
    }

    /**
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
                            is_string($this->currentErrorMessage) && mb_strlen($this->currentErrorMessage) > 0 ? $this->currentErrorMessage : __(self::LANG_GROUP, 'El valor ingresado en %s no es válido.'),
                            "$this->name/$this->humanReadableName"
                        )
                    );
                }
            }
        }

        return $this;
    }

    /**
     * @param callable $validator
     * @return static
     */
    public function setValidator(callable $validator)
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * @param callable $parser
     * @return static
     */
    public function setParser(callable $parser)
    {
        $this->parser = $parser;
        return $this;
    }

    /**
     * @param bool $encodeJson
     * @return static
     */
    public function setEncodeToJson(bool $encodeJson)
    {
        $this->encodeJson = $encodeJson;

        return $this;
    }

    /**
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
     * @param string $value
     * @return static
     */
    public function setCurrentErrorMessage(string $value)
    {
        $this->currentErrorMessage = $value;
        return $this;
    }

    /**
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
     * @return mixed
     */
    public function getSampleValue()
    {
        return $this->sampleValue;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @return string
     */
    public function getHumanReadable()
    {
        return $this->humanReadableName;
    }

    /**
     * @param string $name
     * @return Field|null
     */
    public function getMetaPropertyByName(string $name)
    {
        return $this->metaPropertiesAllowed->getByName($name);
    }

    /**
     * @return FieldCollection
     */
    public function getMetaProperties()
    {
        return $this->metaPropertiesAllowed;
    }

    /**
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
     * @return bool
     */
    public function hasMetaProperties()
    {
        return !empty($this->metaPropertiesAllowed->getArrayCopy());
    }

    /**
     * @return bool
     */
    public function isOptional()
    {
        return $this->optional;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return static
     */
    private function setParentName(string $name)
    {
        $this->parent = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getParentName()
    {
        return $this->parent;
    }

    /**
     * @return bool
     */
    public function hasParent()
    {
        return $this->parent !== null;
    }

    /**
     * @return bool
     */
    public function getShowInTemplate()
    {
        return $this->showInTemplate;
    }
}
