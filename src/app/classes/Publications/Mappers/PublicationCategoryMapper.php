<?php

/**
 * PublicationCategoryMapper.php
 */

namespace Publications\Mappers;

use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use PiecesPHP\Core\Database\Meta\MetaProperty;
use PiecesPHP\Core\StringManipulate;
use PiecesPHP\Core\Validation\Validator;
use Publications\Exceptions\DuplicateException;
use Publications\PublicationsLang;

/**
 * PublicationCategoryMapper.
 *
 * @package     Publications\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 * @property int|null $id
 * @property string|null $preferSlug Es un token usado para acceso individual sin exponer el ID
 * @property string $name
 * @property \stdClass|string|null $meta
 * @property string $baseLang
 * @property \stdClass|null $langData
 */
class PublicationCategoryMapper extends EntityMapperExtensible
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
        'name' => [
            'type' => 'text',
        ],
        'meta' => [
            'type' => 'json',
            'null' => true,
            'dafault' => null,
        ],
    ];

    const UNCATEGORIZED_ID = -10;

    const TABLE = 'publications_categories';
    const LANG_GROUP = PublicationsLang::LANG_GROUP;
    const ORDER_BY_PREFERENCE = [
        '`name` ASC',
        '`id` ASC',
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
        'name',
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
        $this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_TEXT, Config::get_default_lang(), false), 'baseLang');
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
     * Devuelve el slug
     *
     * @param string $lang
     * @return string
     */
    public function getSlug(string $lang = null)
    {
        return self::elementFriendlySlug($this, $lang);
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        if (self::existsByName($this->name, -1)) {
            throw new DuplicateException(__(self::LANG_GROUP, 'Ya existe la categoría.'));
        }

        $saveResult = parent::save();

        if ($saveResult) {
            $idInserted = $this->getInsertIDOnSave();
            if ($idInserted !== null) {
                $this->id = $idInserted;
                $this->preferSlug = self::getEncryptIDForSlug($idInserted);
                $this->update();
            }
        }

        return $saveResult;

    }

    /**
     * @inheritDoc
     */
    public function update()
    {
        if (self::existsByName($this->name, $this->id)) {
            throw new DuplicateException(__(self::LANG_GROUP, 'Ya existe la categoría.'));
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
     * @return string[]
     */
    protected static function fieldsToSelect()
    {

        $mapper = new PublicationCategoryMapper;
        $model = $mapper->getModel();
        $table = $model->getTable();

        $currentLang = Config::get_lang();

        $fields = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
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
     * Devuelve el nombre amigable del elemento
     *
     * @param \stdClass|PublicationCategoryMapper|int $elementOrID
     * @param string $lang
     * @return string
     */
    public static function elementFriendlySlug($elementOrID, string $lang = null)
    {
        $slug = '';

        if ($elementOrID instanceof \stdClass) {
            if (isset($elementOrID->id) && Validator::isInteger($elementOrID->id)) {
                $elementOrID = (int) $elementOrID->id;
            }
        }

        if (is_int($elementOrID)) {

            $elementOrID = self::getBy($elementOrID, 'id', true);

        }

        if ($elementOrID instanceof PublicationCategoryMapper && $elementOrID->id !== null) {

            $uniqid = $elementOrID->preferSlug !== null ? $elementOrID->preferSlug : self::getEncryptIDForSlug($elementOrID->id);
            $name = StringManipulate::friendlyURLString($lang === null ? $elementOrID->currentLangData('name') : $elementOrID->getLangData($lang, 'name'));

            $slug = "{$name}-{$uniqid}";

        }

        return $slug;
    }

    /**
     * Devuelve el ID desde el Slug válido, de lo contrario devuelve null
     *
     * @param string $slug
     * @return int|null
     */
    public static function extractIDFromSlug(string $slug)
    {
        $isUncategorized = false;
        $slug = explode('-', $slug);
        $slug = is_array($slug) && count($slug) > 1 ? $slug[count($slug) - 1] : null;
        $slug = $slug !== null ? BaseHashEncryption::decrypt(strtr($slug, '._', '-_'), self::TABLE) : null;

        if ($slug !== null) {
            $uncategorizedIDLen = mb_strlen(self::UNCATEGORIZED_ID);
            $isUncategorized = substr($slug, 0, $uncategorizedIDLen) == self::UNCATEGORIZED_ID;
        }

        if (!$isUncategorized) {
            $slug = $slug !== null ? explode('-', $slug) : null;
            $slugID = is_array($slug) && count($slug) === 2 ? $slug[0] : null;
            $slugID = (Validator::isInteger($slugID)) || $slugID == self::UNCATEGORIZED_ID ? (int) $slugID : null;
        } else {
            $slugID = self::UNCATEGORIZED_ID;
        }

        return $slugID;
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
     * Un array listo para ser usado en array_to_html_options
     * @param string $defaultLabel
     * @param string $defaultValue
     * @return array
     */
    public static function allForSelect(string $defaultLabel = '', string $defaultValue = '')
    {
        $defaultLabel = strlen($defaultLabel) > 0 ? $defaultLabel : __(self::LANG_GROUP, 'Categorías');
        $options = [];
        $options[$defaultValue] = $defaultLabel;

        /**
         * @param PublicationCategoryMapper $e
         */
        array_map(function ($e) use (&$options) {

            $value = $e->currentLangData('name');
            $options[$e->id] = $value;

        }, self::all(true));

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
     * @param string $name
     * @param integer $ignoreID
     * @return bool
     */
    public static function existsByName(string $name, int $ignoreID = null)
    {

        $ignoreID = $ignoreID !== null ? $ignoreID : -1;
        $model = self::model();

        $name = escapeString($name);

        $where = [
            "name = '{$name}' AND",
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
     * @return PublicationCategoryMapper|null
     */
    public static function objectToMapper(\stdClass $element)
    {

        $element = (array) $element;
        $mapper = new PublicationCategoryMapper;
        $fieldsFilleds = [];
        $fields = array_merge(array_keys($mapper->fields), array_keys($mapper->getMetaProperties()));

        $defaultPropertiesValues = [];

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
                if ($mapper->preferSlug === null && $mapper->name !== null) {
                    $mapper->preferSlug = self::getEncryptIDForSlug($mapper->id);
                    $mapper->update();
                }
            }

        }

        return $allFilled ? $mapper : null;

    }

    /**
     * Crea u obtiene la categoría genérica para las publicaciones huérfanas por la eliminación
     *
     * @return PublicationCategoryMapper Devuelve la categoría
     */
    public static function uncategorizedCategory()
    {

        $category = new PublicationCategoryMapper(self::UNCATEGORIZED_ID);

        if ($category->id == null) {
            $category->setLangData(Config::get_default_lang(), 'name', 'Sin categoría');
            $category->setLangData('es', 'name', 'Sin categoría');
            $category->setLangData('en', 'name', 'Uncategorized');
            $category->meta = $category->metaValueToSave();
            //$category->id = self::UNCATEGORIZED_ID;
            $dataToInsert = $category->dataToInsert()->getData();
            $dataToInsert['id'] = self::UNCATEGORIZED_ID;
            PublicationCategoryMapper::model()->insert($dataToInsert)->execute();
        }

        return $category;

    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new PublicationCategoryMapper)->getModel();
    }
}
