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

    const MODE_UPDATE = 1;
    const MODE_INSERT = 2;
    const MODE_DEFINE_BY_IMPORTER = 3;

    const MODES = [
        self::MODE_UPDATE,
        self::MODE_INSERT,
        self::MODE_DEFINE_BY_IMPORTER,
    ];

    /**
     * @var callable|null
     */
    protected $before = null;
    /**
     * @var callable|null
     */
    protected $insertMethod = null;
    /**
     * @var callable|null
     */
    protected $updateMethod = null;
    /**
     * @var callable|null
     */
    protected $beforeExecuteUpdate = null;
    /**
     * @var string
     */
    protected $table = '';
    /**
     * @var string
     */
    protected $primaryKey = 'id';
    /**
     * @var bool
     */
    protected $primaryKeyIsSubField = false;
    /**
     * @var string
     */
    protected $parentFieldPrimaryKey = '';
    /**
     * @var FieldCollection
     */
    protected $fields = null;
    /**
     * @var string[]
     */
    protected $fieldsNames = [];
    /**
     * @var string[]
     */
    protected $requiredFields = [];
    /**
     * @var boolean
     */
    protected $templateWithHumanReadable = false;
    /**
     * @var BaseModel
     */
    protected $model = null;
    /**
     * @var int
     */
    protected $mode = self::MODE_DEFINE_BY_IMPORTER;

    const LANG_GROUP = 'importerModule';

    /**
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
     * @return bool
     * @throws Exception
     */
    public function insert(): bool
    {

        $data = [];
        $fields = $this->fields;

        foreach ($fields as $field) {

            /**
             * @var \PiecesPHP\Core\Importer\Field $field
             */

            $data[$field->getName()] = $field->getValue();

        }

        if (is_callable($this->insertMethod)) {

            return ($this->insertMethod)($this, $data);

        } else {

            $this->model->insert($data);
            $inserted = $this->model->execute();

            return $inserted;

        }

    }

    /**
     * @return bool
     * @throws Exception
     */
    public function update(): bool
    {

        $data = [];
        $fields = $this->fields;

        foreach ($fields as $field) {

            /**
             * @var \PiecesPHP\Core\Importer\Field $field
             */

            $data[$field->getName()] = $field->getValue();

        }

        $primaryKeyValue = $this->getPrimaryKeyValue();
        $where = [];

        if ($this->primaryKeyIsSubField) {

            $where = [
                "JSON_EXTRACT({$this->parentFieldPrimaryKey}, '$.{$this->primaryKey}')" => $primaryKeyValue,
            ];

        } else {

            $where = [
                $this->primaryKey => $primaryKeyValue,
            ];

        }

        if (is_callable($this->updateMethod)) {
            return ($this->updateMethod)($this, $data, $where);
        } else {

            $this->model->update($data)->where($where);

            /**
             * @var BaseModel $model
             */

            if (is_callable($this->beforeExecuteUpdate)) {
                $model = ($this->beforeExecuteUpdate)($this->model, $where);
            } else {
                $model = $this->model;
                $model->where($where);
            }

            $updated = $model->execute();

            return $updated;

        }

    }

    /**
     * Define si el modo del importador será definido por el Schema
     *
     * @param int $value Pude ser alguna de las constantes de clase MODE_*
     * @return static|int
     */
    public function mode(int $value = null)
    {

        if ($value !== null) {
            $this->mode = in_array($value, self::MODES) ? $value : self::MODE_DEFINE_BY_IMPORTER;
        } else {
            return $this->mode;
        }

        return $this;

    }

    public function setPrimaryKey(string $name, bool $isSubField = false, string $parentField = '')
    {
        $this->primaryKey = $name;
        $this->primaryKeyIsSubField = $isSubField;
        $this->parentFieldPrimaryKey = $parentField;
        return $this;
    }

    /**
     * @param callable $insertMethod
     * @return void
     */
    public function setAlternativeInsert(callable $insertMethod)
    {
        $this->insertMethod = $insertMethod;
    }

    /**
     * @param callable $updateMethod
     * @return void
     */
    public function setAlternativeUpdate(callable $updateMethod)
    {
        $this->updateMethod = $updateMethod;
    }

    /**
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
     * @param bool $set
     * @return static
     */
    public function setTemplateWithHumanReadable(bool $set)
    {
        $this->templateWithHumanReadable = $set;
        return $this;
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @return string|null
     */
    public function getPrimaryKeyValue()
    {

        if (!$this->primaryKeyIsSubField) {

            return $this->fields->getByName($this->primaryKey)->getValue();

        } else {

            $parentField = $this->fields->getByName($this->parentFieldPrimaryKey);
            $metaPropertiesName = $parentField->getMetaPropertiesNames();

            $existsMetaProperty = in_array($this->primaryKey, $metaPropertiesName);

            if ($existsMetaProperty) {
                return $parentField->getMetaPropertyByName($this->primaryKey)->getValue();
            } else {
                return null;
            }

        }

    }

    /**
     * @param string $name
     * @return Field|null
     */
    public function getFieldByName(string $name)
    {
        return $this->fields->getByName($name);
    }

    /**
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
     * @return BaseModel
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return FieldCollection
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @return string[]
     */
    public function getFieldNames()
    {
        return $this->fieldsNames;
    }

    /**
     * @return string[]
     */
    public function getRequiredNames()
    {
        return $this->requiredFields;
    }

    /**
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
     * @return bool
     */
    public function getTemplateWithHumanReadable()
    {
        return $this->templateWithHumanReadable;
    }

    /**
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
     * @param callable $callable
     * @return static
     */
    public function setBeforeExecuteUpdate(callable $callable)
    {
        $this->beforeExecuteUpdate = $callable;
        return $this;
    }

    /**
     * @return void
     */
    public function runBefore()
    {
        if ($this->isBefore()) {
            ($this->before)($this);
        }
    }

    /**
     * @return bool
     */
    public function isBefore()
    {
        return is_callable($this->before);
    }

    /**
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
            ->setCreator(__(self::LANG_GROUP, 'Plantilla'));

        $columnIndex = 1;
        foreach ($columns as $name => $field) {

            $displayName = $name;
            if ($this->getTemplateWithHumanReadable()) {
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
