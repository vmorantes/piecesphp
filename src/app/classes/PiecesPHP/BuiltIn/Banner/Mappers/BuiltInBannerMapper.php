<?php

/**
 * BuiltInBannerMapper.php
 */

namespace PiecesPHP\BuiltIn\Banner\Mappers;

use App\Model\UsersModel;
use PiecesPHP\BuiltIn\Banner\BuiltInBannerLang;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use PiecesPHP\Core\Database\Meta\MetaProperty;
use PiecesPHP\Core\Validation\Validator;

/**
 * BuiltInBannerMapper.
 *
 * @package     PiecesPHP\BuiltIn\Banner\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 * @property int|null $id
 * @property string|null $title
 * @property string|null $content
 * @property string|null $link
 * @property string $desktopImage
 * @property string|null $mobileImage
 * @property int $orderPosition
 * @property string $folder
 * @property string|\DateTime|null $startDate
 * @property string|\DateTime|null $endDate
 * @property string|\DateTime $createdAt
 * @property string|\DateTime $updatedAt
 * @property int|UsersModel $createdBy
 * @property int|UsersModel|null $modifiedBy
 * @property int $status
 * @property \stdClass|string|null $meta
 * @property \stdClass|null $langData
 */
class BuiltInBannerMapper extends EntityMapperExtensible
{

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'title' => [
            'type' => 'text',
            'null' => true,
        ],
        'content' => [
            'type' => 'text',
            'null' => true,
        ],
        'link' => [
            'type' => 'text',
            'null' => true,
        ],
        'desktopImage' => [
            'type' => 'text',
        ],
        'mobileImage' => [
            'type' => 'text',
            'null' => true,
        ],
        'orderPosition' => [
            'type' => 'int',
            'default' => 0,
        ],
        'folder' => [
            'type' => 'text',
        ],
        'startDate' => [
            'type' => 'datetime',
            'null' => true,
        ],
        'endDate' => [
            'type' => 'datetime',
            'null' => true,
        ],
        'createdAt' => [
            'type' => 'datetime',
            'default' => 'timestamp',
        ],
        'updatedAt' => [
            'type' => 'datetime',
            'null' => true,
        ],
        'createdBy' => [
            'type' => 'int',
            'reference_table' => UsersModel::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'username',
            'mapper' => UsersModel::class,
        ],
        'modifiedBy' => [
            'type' => 'int',
            'reference_table' => UsersModel::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'username',
            'mapper' => UsersModel::class,
            'null' => true,
        ],
        'status' => [
            'type' => 'int',
            'default' => self::ACTIVE,
        ],
        'meta' => [
            'type' => 'json',
            'null' => true,
            'dafault' => null,
        ],
    ];

    const ACTIVE = 1;
    const INACTIVE = 0;

    const STATUSES = [
        self::ACTIVE => 'Activo',
        self::INACTIVE => 'Inactivo',
    ];

    const CAN_DELETE_ALL = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN_GRAL,
    ];

    const TABLE = 'built_in_banner_elements';
    const VIEW_ACTIVE_DATE = 'built_in_banner_active_date_elements';
    const LANG_GROUP = BuiltInBannerLang::LANG_GROUP;
    const ORDER_BY_PREFERENCE = [
        '`orderPosition` ASC',
        '`title` ASC',
        '`startDate` ASC',
        '`endDate` ASC',
    ];

    /**
     * Propiedades que no necesitan multi-idioma
     * Si está vacía se llenará automáticamente con los campos no incluidos en $translatableProperties
     * @var string[]
     */
    protected $noTranslatableProperties = [];

    /**
     * Propiedades necesitan multi-idioma
     *
     * @var string[]
     */
    protected $translatableProperties = [
        'title',
        'content',
        'desktopImage',
        'mobileImage',
    ];

    /**
     * @var string
     */
    protected $table = self::TABLE;

    /**
     * @param int $value
     * @param string $fieldCompare
     * @return static
     */
    public function __construct(int $value = null, string $fieldCompare = 'primary_key')
    {

        $this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_JSON, new \stdClass, true), 'langData');
        parent::__construct($value, $fieldCompare);

        //Definición de campos no traducibles en caso de que estén vacíos
        $fields = array_keys($this->fields);
        if (count($this->noTranslatableProperties) == 0) {
            foreach ($fields as $fieldName) {
                if (!in_array($fieldName, $this->translatableProperties) && $this->metaColumnName !== $fieldName) {
                    $this->noTranslatableProperties[] = $fieldName;
                }
            }
        }
    }

    /**
     * @param int $maxLength
     * @return string
     */
    public function excerpt(int $maxLength = 300)
    {
        $content = is_string($this->currentLangData('content')) ? strip_tags($this->currentLangData('content')) : '';
        $contentLength = mb_strlen($content);
        return $contentLength <= $maxLength ? $content : substr($content, 0, ($maxLength >= 6 ? $maxLength - 3 : $maxLength)) . '...';
    }

    /**
     * @return string
     */
    public function createdByFullName()
    {
        $createdBy = $this->createdBy;

        if (!is_object($createdBy)) {
            $this->createdBy = new UsersModel($createdBy);
            $createdBy = $this->createdBy;
        }

        return $createdBy->getFullName();
    }

    /**
     * @return string|null
     */
    public function modifiedByFullName()
    {
        $modifiedBy = $this->modifiedBy;

        if (!is_object($modifiedBy) && $modifiedBy !== null) {
            $this->modifiedBy = new UsersModel($modifiedBy);
            $modifiedBy = $this->modifiedBy;
        }

        return $modifiedBy !== null ? $modifiedBy->getFullName() : null;
    }

    /**
     * Verifica si ya está disponible según startDate y endDate
     * @return string
     */
    public function isActiveByDates()
    {
        $active = true;
        $now = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:00'));

        if ($this->startDate !== null) {
            $active = $active && $this->startDate <= $now;
        }

        if ($this->endDate !== null) {
            $active = $active && $this->endDate > $now;
        }

        return $active;
    }

    /**
     * @param string $format
     * @param array $replaceTemplate Para remplazar contenido dentro del formato, el array debe ser ['VALOR_A_REEMPLAZAR' => 'VALOR_DE_REEMPLAZO']
     * @return string
     */
    public function createdAtFormat(string $format = null, array $replaceTemplate = [])
    {
        $format = is_string($format) ? $format : get_default_format_date();
        $formated = localeDateFormat($format, $this->createdAt, $replaceTemplate);
        return $formated;
    }

    /**
     * @param string $format
     * @param array $replaceTemplate Para remplazar contenido dentro del formato, el array debe ser ['VALOR_A_REEMPLAZAR' => 'VALOR_DE_REEMPLAZO']
     * @return string|null
     */
    public function updatedAtFormat(string $format = null, array $replaceTemplate = [])
    {
        $format = is_string($format) ? $format : get_default_format_date();
        $formated = $this->updatedAt instanceof \DateTime  ? localeDateFormat($format, $this->updatedAt, $replaceTemplate) : null;
        return $formated;
    }

    /**
     * @param string $format
     * @param array $replaceTemplate Para remplazar contenido dentro del formato, el array debe ser ['VALOR_A_REEMPLAZAR' => 'VALOR_DE_REEMPLAZO']
     * @return string|null
     */
    public function startDateFormat(string $format = null, array $replaceTemplate = [])
    {
        $format = is_string($format) ? $format : get_default_format_date();
        $formated = $this->startDate instanceof \DateTime  ? localeDateFormat($format, $this->startDate, $replaceTemplate) : null;
        return $formated;
    }

    /**
     * @param string $format
     * @param array $replaceTemplate Para remplazar contenido dentro del formato, el array debe ser ['VALOR_A_REEMPLAZAR' => 'VALOR_DE_REEMPLAZO']
     * @return string|null
     */
    public function endDateFormat(string $format = null, array $replaceTemplate = [])
    {
        $format = is_string($format) ? $format : get_default_format_date();
        $formated = $this->endDate instanceof \DateTime  ? localeDateFormat($format, $this->endDate, $replaceTemplate) : null;
        return $formated;
    }

    /**
     * @inheritDoc
     */
    public function save()
    {

        $this->createdAt = new \DateTime();
        $this->createdBy = getLoggedFrameworkUser()->id;
        $saveResult = parent::save();

        if ($saveResult) {
            $idInserted = $this->getInsertIDOnSave();
            if ($idInserted !== null) {
                $this->id = $idInserted;
                $this->update(true);
            }
        }

        return $saveResult;

    }

    /**
     * @param bool $noDateUpdate
     * @inheritDoc
     */
    public function update(bool $noDateUpdate = false)
    {
        if (!$noDateUpdate) {
            $this->modifiedBy = getLoggedFrameworkUser()->id;
            $this->updatedAt = new \DateTime();
        }
        return parent::update();
    }

    /**
     * Define una propiedad que está habilitada en multi-idioma en un idioma en específico
     * @param string $lang
     * @param string $property
     * @param mixed $data
     * @return static
     */
    public function setLangData(string $lang, string $property, $data)
    {

        $translatables = $this->translatableProperties;
        $noTranslatables = $this->noTranslatableProperties;

        if (in_array($property, $translatables)) {

            if ($lang !== Config::get_default_lang()) {

                if (!isset($this->langData->$lang)) {
                    $this->langData->$lang = new \stdClass;
                }

                $this->langData->$lang->$property = $data;

            } else {
                $this->$property = $data;
            }

        } elseif (in_array($property, $noTranslatables)) {
            $this->$property = $data;
        }

        return $this;
    }

    /**
     * Obtiene una propiedad que está habilitada en multi-idioma en un idioma en específico o
     * el valor de la propiedad por defecto
     * @param string $lang
     * @param string $property
     * @param bool $defaultOnEmpty
     * @param mixed $returnOnEmpty
     * @return mixed
     * Si la propiedad no existe se devolerá null
     * Si $defaultOnEmpty está en true, cuando no exista en el idioma seleccionado se tomará de las propiedades principales
     * Si $defaultOnEmpty está en false, cuando no exista en el idioma seleccionado se devolverá $returnOnEmpty
     */
    public function getLangData(string $lang, string $property, bool $defaultOnEmpty = true, $returnOnEmpty = '')
    {
        if (isset($this->langData->$lang) && isset($this->langData->$lang->$property)) {

            $value = $this->langData->$lang->$property;

            //Si para una propiedad se quiere un compartamiento particular en su obtención
            //puede definírsele aquí
            $specialBehaviour = [
                'SAMPLE_FIELD' => function ($value) {
                    return $value;
                },
            ];

            return array_key_exists($property, $specialBehaviour) ? ($specialBehaviour[$property])($value) : $value;

        } elseif ($defaultOnEmpty || $lang === Config::get_default_lang()) {

            $propertiesExpected = array_merge($this->noTranslatableProperties, $this->translatableProperties);

            if (in_array($property, $propertiesExpected)) {

                $value = $this->$property;

                //Si para una propiedad se quiere un compartamiento particular en su obtención
                //puede definírsele aquí
                $specialBehaviour = [
                    'SAMPLE_FIELD' => function ($value) {
                        return $value;
                    },
                ];

                return array_key_exists($property, $specialBehaviour) ? ($specialBehaviour[$property])($value) : $value;

            }

            return null;

        } else {

            return $returnOnEmpty;

        }

    }

    /**
     * @param string $property
     * @return mixed
     */
    public function currentLangData(string $property)
    {
        $lang = Config::get_lang();
        return $this->getLangData($lang, $property);
    }

    /**
     * Devuelve los campos que no son traducibles
     *
     * @return string[]
     */
    public function getNoTranslatableProperties()
    {
        return $this->noTranslatableProperties;
    }

    /**
     * Devuelve los campos que son traducibles
     *
     * @return string[]
     */
    public function getTranslatableProperties()
    {
        return $this->translatableProperties;
    }

    /**
     * Campos extra:
     *  - idPadding
     *  - statusText
     *  - isActiveByDate
     *  - startDateFormat
     *  - endDateFormat
     * @return string[]
     */
    protected static function fieldsToSelect(string $formatDate = '%d-%m-%Y')
    {

        $mapper = (new BuiltInBannerMapper);
        $model = $mapper->getModel();
        $table = $model->getTable();

        $tableView = self::VIEW_ACTIVE_DATE;

        $defaultLang = Config::get_default_lang();
        $currentLang = Config::get_lang();

        $statusesJSON = json_encode((object) self::statuses(), \JSON_UNESCAPED_UNICODE);
        $isActiveByDate = "(SELECT COUNT({$tableView}.id) > 0 FROM {$tableView} WHERE {$tableView}.id = {$table}.id)";

        $fields = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
            "JSON_UNQUOTE(JSON_EXTRACT('{$statusesJSON}', CONCAT('$.', {$table}.status))) AS statusText",
            "{$isActiveByDate} AS isActiveByDate",
            "IF({$table}.startDate IS NOT NULL, DATE_FORMAT({$table}.startDate, '{$formatDate}'), '-') AS startDateFormat",
            "IF({$table}.endDate IS NOT NULL, DATE_FORMAT({$table}.endDate, '{$formatDate}'), '-') AS endDateFormat",
            "{$table}.meta",
        ];

        if ($defaultLang == $currentLang) {

            //En caso de que las funciones JSON_* no estén disponibles en el motor SQL
            $allFields = array_merge($mapper->getNoTranslatableProperties(), $mapper->getTranslatableProperties());

            foreach ($allFields as $field) {
                $fields[] = "{$table}.{$field}";
            }

        } else {

            $noTranslatables = $mapper->getNoTranslatableProperties();

            foreach ($noTranslatables as $field) {
                $fields[] = "{$table}.{$field}";
            }

            $translatables = $mapper->getTranslatableProperties();

            $specialBehaviour = [
                'FIELD_NAME' => function ($fieldName) {
                    return $fieldName;
                },
            ];

            foreach ($translatables as $fieldToLang) {

                if (array_key_exists($fieldToLang, $specialBehaviour)) {
                    $fields[] = ($specialBehaviour[$fieldToLang])($fieldToLang);
                } else {
                    $normalField = "{$table}.{$fieldToLang}";
                    $langField = "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.langData.{$currentLang}.{$fieldToLang}'))";
                    $fields[] = "IF({$langField} IS NOT NULL, {$langField}, {$normalField}) AS `{$fieldToLang}`";
                }

            }

        }

        return $fields;

    }

    /**
     * @param string $fieldName
     * @return string
     */
    public static function fieldCurrentLangForSQL(string $fieldName)
    {

        $table = self::TABLE;

        $defaultLang = Config::get_default_lang();
        $currentLang = Config::get_lang();

        $fieldSQL = '';

        if ($defaultLang == $currentLang) {
            $fieldSQL = "{$table}.{$fieldName}";
        } else {
            $jsonExtractField = "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.langData.{$currentLang}.{$fieldName}'))";
            $fieldSQL = "IF({$jsonExtractField} IS NOT NULL, {$jsonExtractField}, {$table}.{$fieldName})";
        }

        return $fieldSQL;

    }

    /**
     * Configura las versiones de las propiedades según el idioma actual
     *
     * @param \stdClass $element
     * @return \stdClass
     */
    public static function translateEntityObject(\stdClass $element)
    {

        $mapper = self::objectToMapper($element);

        $defaultLang = get_config('default_lang');
        $currentLang = Config::get_lang();

        if ($defaultLang != $currentLang && $mapper !== null) {

            $translatables = $mapper->getTranslatableProperties();

            foreach ($translatables as $property) {
                $element->$property = $mapper->getLangData($currentLang, $property);
            }

        }

        return $element;

    }

    /**
     * Devuelve el ID desde el Slug válido, de lo contrario devuelve null
     *
     * @param string $slug
     * @return int|null
     */
    public static function extractIDFromSlug(string $slug)
    {
        $slug = explode('-', $slug);
        $slug = is_array($slug) && count($slug) > 1 ? $slug[count($slug) - 1] : null;
        $slug = $slug !== null ? BaseHashEncryption::decrypt(strtr($slug, '._', '-_'), self::TABLE) : null;
        $slug = $slug !== null ? explode('-', $slug) : null;
        $slugID = is_array($slug) && count($slug) === 2 ? $slug[0] : null;
        $slugID = Validator::isInteger($slugID) ? (int) $slugID : null;
        return $slugID;
    }

    /**
     * @return array
     */
    public static function statuses()
    {
        $options = [];
        foreach (self::STATUSES as $value => $text) {
            $options[$value] = __(self::LANG_GROUP, $text);
        }
        return $options;
    }

    /**
     * @param bool $asMapper
     *
     * @return static[]|array
     */
    public static function all(bool $asMapper = false)
    {
        $model = self::model();

        $selectFields = [];

        $model->select($selectFields);

        $model->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        if ($asMapper) {
            foreach ($result as $key => $value) {
                $result[$key] = self::objectToMapper($value);
            }
        }

        return $result;
    }

    /**
     * @param string $column
     * @param int $value
     * @param bool $asMapper
     *
     * @return static[]|array
     */
    public static function allBy(string $column, $value, bool $asMapper = false)
    {
        $model = self::model();

        $model->select()->where([
            $column => $value,
        ])->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        if ($asMapper) {
            foreach ($result as $key => $value) {
                $result[$key] = self::objectToMapper($value);
            }
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @param string $column
     * @param boolean $as_mapper
     * @return static|object|null
     */
    public static function getBy($value, string $column = 'id', bool $as_mapper = false)
    {
        $model = self::model();

        $where = [
            $column => $value,
        ];

        $model->select()->where($where);

        $model->execute();

        $result = $model->result();

        $result = !empty($result) ? $result[0] : null;

        if (!is_null($result) && $as_mapper) {
            $result = self::objectToMapper($result);
        }

        return $result;
    }

    /**
     * @param bool $asMapper
     * @param bool $onlyActives
     * @return \stdClass|static|null
     */
    public static function lastModifiedElement(bool $asMapper = false, bool $onlyActives = false)
    {
        $table = self::TABLE;
        $model = self::model();

        $selectFields = [];

        $model->select($selectFields);

        $model->orderBy("{$table}.updatedAt DESC, {$table}.createdAt DESC");

        $whereString = null;
        $where = [];
        $and = 'AND';

        if ($onlyActives) {

            $now = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:00'));
            $now = $now->getTimestamp();
            $unixNowDate = "FROM_UNIXTIME({$now})";
            $startDateSQL = "{$table}.startDate";
            $endDateSQL = "{$table}.endDate";

            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$startDateSQL} <= {$unixNowDate} OR {$table}.startDate IS NULL";
            $where[] = "{$beforeOperator} ({$critery})";

            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$endDateSQL} > {$unixNowDate} OR {$table}.endDate IS NULL";
            $where[] = "{$beforeOperator} ({$critery})";

            $statusActive = self::ACTIVE;
            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$table}.status = {$statusActive}";
            $where[] = "{$beforeOperator} ({$critery})";

        }

        if (!empty($where)) {
            $whereString = implode(' ', $where);
        }

        if ($whereString !== null) {
            $model->where($whereString);
        }

        $model->execute(false, 1, 1);

        $result = $model->result();
        $result = !empty($result) ? $result[0] : null;

        if ($asMapper && $result !== null) {
            $result = self::objectToMapper($result);
        }

        return $result;
    }

    /**
     * @return string|null Los ID separados por comas, i.e.: 1,2,3,4 O null si no hay resultados
     */
    public static function activesByDateIDs()
    {
        $table = self::VIEW_ACTIVE_DATE;
        $model = clone self::model();
        $model->setTable($table);

        $selectFields = [
            "GROUP_CONCAT({$table}.id) AS ids",
        ];

        $model->select($selectFields);
        $model->having("ids IS NOT NULL");
        $model->execute();

        $result = $model->result();
        $result = !empty($result) ? $result[0]->ids : null;

        return $result;
    }

    /**
     * @param int $id
     * @return bool
     */
    public static function existsByID(int $id)
    {
        $model = self::model();

        $where = [
            "id = $id",
        ];
        $where = trim(implode(' ', $where));

        $model->select()->where($where);

        $model->execute();

        $result = $model->result();

        return !empty($result);
    }

    /**
     * Devuelve el mapeador desde un objeto
     *
     * @param \stdClass $element
     * @return BuiltInBannerMapper|null
     */
    public static function objectToMapper(\stdClass $element)
    {

        $element = (array) $element;
        $mapper = new BuiltInBannerMapper;
        $fieldsFilleds = [];
        $fields = array_merge(array_keys($mapper->fields), array_keys($mapper->getMetaProperties()));

        $defaultPropertiesValues = [];

        foreach ($defaultPropertiesValues as $defaultProperty => $defaultPropertyValue) {
            if (!array_key_exists($defaultProperty, $element)) {
                $element[$defaultProperty] = $defaultPropertyValue;
            }
        }

        $defaultMetaPropertiesValues = [];

        foreach ($element as $property => $value) {

            if (in_array($property, $fields)) {

                if ($property == 'meta') {

                    $value = $value instanceof \stdClass  ? $value : @json_decode($value);

                    foreach ($defaultMetaPropertiesValues as $defaultMetaProperty => $defaultMetaPropertyValue) {
                        foreach ($defaultMetaPropertiesValues as $defaultMetaProperty => $defaultMetaPropertyValue) {
                            if (!property_exists($value, $defaultMetaProperty)) {
                                $value->$defaultMetaProperty = $defaultMetaPropertyValue;
                            }
                        }
                    }

                    if ($value instanceof \stdClass) {
                        foreach ($value as $metaPropertyName => $metaPropertyValue) {

                            if ($mapper->hasMetaProperty($metaPropertyName)) {
                                $mapper->$metaPropertyName = $metaPropertyValue;
                                $fieldsFilleds[] = $metaPropertyName;
                            }

                        }
                    }

                } else {
                    $mapper->$property = $value;
                }

                $fieldsFilleds[] = $property;

            }

        }

        $allFilled = count($fieldsFilleds) === count($fields);

        return $allFilled ? $mapper : null;

    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new BuiltInBannerMapper)->getModel();
    }
}
