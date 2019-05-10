<?php
/**
 * Schema.php
 */
namespace PiecesPHP\Core\Importer;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Importer\Collections\FieldCollection;

/**
 * Schema.
 *
 * Esquema de importación
 *
 * @package     PiecesPHP\Core\Importer
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class Schema
{
    /**
     * $before
     *
     * @var callable
     */
    protected $before = null;
    /**
     * $insertMethod
     *
     * @var callable
     */
    protected $insertMethod = null;
    /**
     * $table
     *
     * @var string
     */
    protected $table = '';
    /**
     * $fields
     *
     * @var FieldCollection
     */
    protected $fields = null;
    /**
     * $fieldsNames
     *
     * @var string[]
     */
    protected $fieldsNames = [];
    /**
     * $requiredFields
     *
     * @var string[]
     */
	protected $requiredFields = [];
	/**
	 * $templateWithHumanReadable
	 *
	 * @var boolean
	 */
	protected $templateWithHumanReadable = false;
    /**
     * $model
     *
     * @var BaseModel
     */
    protected $model = null;

    /**
     * __construct
     *
     * @param FieldCollection $fields
     * @param string $table
     * @param callable $before
     * @param bool $templateWithHumanReadable
     * @return static
     */
    public function __construct(FieldCollection $fields, string $table, callable $before = null, bool $templateWithHumanReadable = false)
    {

        $this->fields = $fields;
        $this->fieldsNames = $fields->getNames();
        $this->table = $table;
		$this->before = $before;
		$this->templateWithHumanReadable = $templateWithHumanReadable;

        $this->model = new BaseModel();
        $this->model->setTable($this->table);
        $this->model->setFields($this->fieldsNames);

        foreach ($this->fields as $field) {
            if (!$field->isOptional()) {
                $this->requiredFields[] = $field->getName();
            }
        }

    }

    /**
     * insert
     *
     * @return bool
     * @throws Exception
     */
    public function insert(): bool
    {
        $this->runBefore();

        if (is_callable($this->insertMethod)) {
            return ($this->insertMethod)($this);
        } else {

            $data = [];
            $fields = $this->fields;

            foreach ($fields as $field) {
                $data[$field->getName()] = $field->getValue();
            }

            $this->model->insert($data);
            $inserted = $this->model->execute();

            return $inserted;

        }

    }

    /**
     * setAlternativeInsert
     *
     * @param callable $insertMethod
     * @return void
     */
    public function setAlternativeInsert(callable $insertMethod)
    {
        $this->insertMethod = $insertMethod;
    }

    /**
     * setFieldValue
     *
     * @param string $name
     * @param mixed $value
     * @return void
     * @throws Exception
     */
    public function setFieldValue(string $name, $value)
    {
        $position = $this->fields->getPositionField($name);

        if ($position !== null) {
            $field = $this->fields->getByName($name);
            $field->setValue($value);
            $this->fields->offsetSet($position, $field);
        }
    }

    /**
     * setSubFieldValue
     *
     * @param string $fieldName
     * @param string $subFieldName
     * @param mixed $value
     * @return void
     * @throws Exception
     */
    public function setSubFieldValue(string $fieldName, string $subFieldName, $value)
    {
        $position = $this->fields->getPositionField($fieldName);
        $field = $this->fields->getByName($fieldName);

        if (!is_null($field)) {

            $field->setValue($value, $subFieldName);

        }

        if ($position !== null) {
            $this->fields->offsetSet($position, $field);
        }
	}
	
	/**
	 * setTemplateWithHumanReadable
	 *
	 * @param bool $set
	 * @return static
	 */
	public function setTemplateWithHumanReadable(bool $set){
		$this->templateWithHumanReadable = $set;
		return $this;
	}

    /**
     * getFieldByName
     *
     * @param string $name
     * @return Field|null
     */
    public function getFieldByName(string $name)
    {
        return $this->fields->getByName($name);
    }

    /**
     * getSubFieldByName
     *
     * @param string $fieldName
     * @param string $subFieldName
     * @return Field|null
     */
    public function getSubFieldByName(string $fieldName, string $subFieldName)
    {
        $field = $this->fields->getByName($fieldName);

        if (!is_null($field)) {
            return $field->getMetaPropertyByName($subFieldName);
        } else {
            return null;
        }

    }

    /**
     * getFields
     *
     * @return FieldCollection
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * getFieldNames
     *
     * @return string[]
     */
    public function getFieldNames()
    {
        return $this->fieldsNames;
    }

    /**
     * getRequiredNames
     *
     * @return string[]
     */
    public function getRequiredNames()
    {
        return $this->requiredFields;
    }

    /**
     * getSubFieldsNames
     *
     * @return array
     */
    public function getSubFieldsNames()
    {
        $subFields = [];
        foreach ($this->fields as $field) {
            if ($field->hasMetaProperties()) {
                $subFields[$field->getName()] = $field->getMetaProperties();
            }
        }
        return $subFields;
	}
	
	/**
	 * getTemplateWithHumanReadable
	 *
	 * @return bool
	 */
	public function getTemplateWithHumanReadable(){
		return $this->templateWithHumanReadable;
	}

    /**
     * hasSubFields
     *
     * @return bool
     */
    public function hasSubFields()
    {
        foreach ($this->fields as $field) {
            if ($field->hasMetaProperties()) {
                return true;
            }
        }
        return false;
    }

    /**
     * runBefore
     *
     * @return void
     */
    public function runBefore()
    {
        if ($this->isBefore()) {
            ($this->before)($this);
        }
    }

    /**
     * isBefore
     *
     * @return bool
     */
    public function isBefore()
    {
        return is_callable($this->before);
    }

    /**
     * template
     *
     * @return XlsxWriter
     */
    public function template(): XlsxWriter
    {
        $columns = [];

        foreach ($this->getFields() as $field) {
            if ($field->getShowInTemplate()) {
                $columns[$field->getName()] = $field;
            }
        }

        foreach ($this->getSubFieldsNames() as $subFields) {
            foreach ($subFields as $subField) {
				if ($subField->getShowInTemplate()) {
					$name = $subField->getName();
					if (!array_key_exists($name, $columns)) {
						$columns[$name] = $subField;
					}
				}
            }
        }

        $spreadSheet = new Spreadsheet();
        $spreadSheet->getProperties()
            ->setCreator('Plantilla');

        $columnIndex = 1;
        foreach ($columns as $name => $field) {

			$displayName = $name;
			if($this->getTemplateWithHumanReadable()){
				$displayName = $field->getHumanReadable();
			}
			$value = $field->getSampleValue();
			
            $spreadSheet->setActiveSheetIndex(0)
                ->setCellValueByColumnAndRow($columnIndex, 1, $displayName)
                ->setCellValueByColumnAndRow($columnIndex, 2, $value);
            $columnIndex++;
        }
        $writer = new XlsxWriter($spreadSheet);
        return $writer;
    }
}
