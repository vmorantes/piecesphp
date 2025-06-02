<?php

/**
 * InterestResearchAreasMapper.php
 */

namespace PiecesPHP\UserSystem\Profile\SubMappers;

use App\Model\UsersModel;
use MySpace\Exceptions\SafeException;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use PiecesPHP\Core\Database\Meta\MetaProperty;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * InterestResearchAreasMapper.
 *
 * @package     PiecesPHP\UserSystem\Profile\SubMappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 * @property int|null $id
 * @property string|null $preferSlug Es un token usado para acceso individual sin exponer el ID
 * @property string|null $areaName
 * @property string|\DateTime $createdAt
 * @property string|\DateTime $updatedAt
 * @property int|UsersModel|null $createdBy
 * @property int|UsersModel $modifiedBy
 * @property \stdClass|string|null $meta
 * @property string $color
 * @property string $baseLang
 * @property \stdClass|null $langData
 */
class InterestResearchAreasMapper extends EntityMapperExtensible
{

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'preferSlug' => [
            'type' => 'text',
            'null' => true,
        ],
        'areaName' => [
            'type' => 'text',
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
        'meta' => [
            'type' => 'json',
            'null' => true,
            'dafault' => null,
        ],
    ];

    const TABLE = 'interest_research_area';
    const LANG_GROUP = UserDataPackage::LANG_GROUP;
    const ORDER_BY_PREFERENCE = [
        '`id` DESC',
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
        'areaName',
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
        $this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_TEXT, Config::get_default_lang(), true), 'baseLang');
        $this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_TEXT, 'rgba(122, 177, 232, 0.20)', true), 'color');
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
     * @inheritDoc
     */
    public function save()
    {
        if (self::existsByAreaName($this->areaName, -1)) {
            throw new SafeException(__(self::LANG_GROUP, 'Ya existe el área de interés.'));
        }
        $currentUser = UserDataPackage::getConfigCurrentUser(); //Importante para no generar recursividad con UserDataPackage
        $this->createdAt = new \DateTime();
        $this->createdBy = $currentUser !== null ? $currentUser->id : 1;
        $saveResult = parent::save();

        if ($saveResult) {
            $idInserted = $this->getInsertIDOnSave();
            if ($idInserted !== null) {
                $this->id = $idInserted;
                $this->preferSlug = self::getEncryptIDForSlug($idInserted);
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
        if (self::existsByAreaName($this->areaName, $this->id)) {
            throw new SafeException(__(self::LANG_GROUP, 'Ya existe el área de interés.'));
        }
        if (!$noDateUpdate) {
            $currentUser = UserDataPackage::getConfigCurrentUser(); //Importante para no generar recursividad con UserDataPackage
            $this->modifiedBy = $currentUser !== null ? $currentUser->id : 1;
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
        $baseLang = $this->baseLang;

        if (in_array($property, $translatables)) {

            if ($lang !== $baseLang) {

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

        $baseLang = $this->baseLang;

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

        } elseif ($defaultOnEmpty || $lang === $baseLang) {

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
     * Verifica si existe un idioma en los datos del elemento
     * @param string $lang
     * @return bool
     */
    public function hasLang(string $lang)
    {
        $baseLang = $this->baseLang;
        $langData = $this->langData;
        $hasLangData = isset($langData->$lang);
        return $baseLang === $lang || $hasLangData;
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
     *  - username
     *  - fullname
     *  - names
     *  - lastNames
     *  - baseLang
     * @return string[]
     */
    protected static function fieldsToSelect(string $formatDate = null)
    {

        $formatDate = $formatDate ?? get_default_format_date(null, true);
        $mapper = (new InterestResearchAreasMapper);
        $model = $mapper->getModel();
        $table = $model->getTable();

        $tableUser = UsersModel::TABLE;
        $firstnameSegment = "TRIM({$tableUser}.firstname)";
        $secondNameSegment = "IF({$tableUser}.secondname IS NOT NULL, CONCAT(' ', {$tableUser}.secondname), '')";
        $firstLastNameSegment = "TRIM({$tableUser}.first_lastname)";
        $secondLastNameSegment = "IF({$tableUser}.second_lastname IS NOT NULL, CONCAT(' ', {$tableUser}.second_lastname), '')";

        $currentLang = Config::get_lang();

        $fields = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
            "(SELECT {$tableUser}.username FROM {$tableUser} WHERE {$tableUser}.id = {$table}.belongsTo) AS username",
            "(SELECT TRIM(CONCAT($firstnameSegment, {$secondNameSegment}, ' ', {$firstLastNameSegment}, {$secondLastNameSegment})) FROM {$tableUser} WHERE {$tableUser}.id = {$table}.belongsTo) AS fullname",
            "(SELECT TRIM(CONCAT({$firstnameSegment}, {$secondNameSegment})) FROM {$tableUser} WHERE {$tableUser}.id = {$table}.belongsTo) AS names",
            "(SELECT TRIM(CONCAT({$firstLastNameSegment}, {$secondLastNameSegment})) FROM {$tableUser} WHERE {$tableUser}.id = {$table}.belongsTo) AS lastNames",
            "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.baseLang')) AS baseLang",
            "{$table}.meta",
        ];

        //Multi-idioma
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
            if (!array_key_exists($fieldToLang, $specialBehaviour)) {
                $normalField = "{$table}.{$fieldToLang}";
                $baseLang = "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.baseLang'))";
                $langField = "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.langData.{$currentLang}.{$fieldToLang}'))";
                $langFieldCondition = "IF({$langField} IS NOT NULL, {$langField}, {$normalField})";
                $fields[] = "IF('{$currentLang}' = {$baseLang}, {$normalField}, {$langFieldCondition}) AS `{$fieldToLang}`";
            } else {
                $fields[] = ($specialBehaviour[$fieldToLang])($fieldToLang);
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

        $currentLang = Config::get_lang();

        $fieldSQL = '';

        $jsonExtractFieldConditional = "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.langData.{$currentLang}.{$fieldName}'))";
        $baseLangField = "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.baseLang'))";
        $fieldSQL = "IF({$baseLangField} = '{$currentLang}', {$table}.{$fieldName}, {$jsonExtractFieldConditional})";

        return $fieldSQL;

    }

    /**
     * Devuelve el ID encriptado para generar un Slug
     *
     * @return string
     */
    public static function getEncryptIDForSlug(int $id)
    {
        $uniqid = mb_strtolower(str_replace(['.', '-'], '', uniqid()));
        $uniqid = strtr(BaseHashEncryption::encrypt("{$id}-{$uniqid}", self::TABLE), '-_', '._');
        return $uniqid;
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
     * Verifica si existe algún registro igual
     *
     * @param string $areaName
     * @param int $ignoreID
     * @return bool
     */
    public static function existsByAreaName(string $areaName, int $ignoreID = null)
    {

        $ignoreID = $ignoreID !== null ? $ignoreID : -1;
        $model = self::model();

        $areaName = escapeString($areaName);

        $where = [
            "areaName = '{$areaName}' AND",
            "id != {$ignoreID}",
        ];

        $model->select()->where(implode(' ', $where));

        $model->execute();

        $result = $model->result();

        return !empty($result);

    }

    /**
     * Devuelve el mapeador desde un objeto
     *
     * @param \stdClass $element
     * @return InterestResearchAreasMapper|null
     */
    public static function objectToMapper(\stdClass $element)
    {

        $element = (array) $element;
        $mapper = new InterestResearchAreasMapper;
        $fieldsFilleds = [];
        $fields = array_merge(array_keys($mapper->fields), array_keys($mapper->getMetaProperties()));

        $defaultPropertiesValues = [
        ];

        foreach ($defaultPropertiesValues as $defaultProperty => $defaultPropertyValue) {
            if (!array_key_exists($defaultProperty, $element)) {
                $element[$defaultProperty] = $defaultPropertyValue;
            }
        }

        $defaultMetaPropertiesValues = [
            'baseLang' => Config::get_default_lang(),
        ];

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

        $fieldsFilleds = array_unique($fieldsFilleds);
        $fields = array_unique($fields);
        sort($fieldsFilleds);
        sort($fields);
        $allFilled = count($fieldsFilleds) === count($fields);

        if ($allFilled) {

            if ($mapper->id !== null) {
                if ($mapper->preferSlug === null && $mapper->title !== null) {
                    $mapper->preferSlug = self::getEncryptIDForSlug($mapper->id);
                    $mapper->update();
                }
            }

        }

        return $allFilled ? $mapper : null;

    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new InterestResearchAreasMapper)->getModel();
    }
}
