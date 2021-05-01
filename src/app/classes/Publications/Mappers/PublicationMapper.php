<?php

/**
 * PublicationMapper.php
 */

namespace Publications\Mappers;

use App\Model\UsersModel;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use PiecesPHP\Core\Database\Meta\MetaProperty;
use PiecesPHP\Core\StringManipulate;
use Publications\Exceptions\DuplicateException;
use Publications\PublicationsLang;

/**
 * PublicationMapper.
 *
 * @package     Publications\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 * @property int $id
 * @property string|null $preferSlug Es un token usado para acceso individual sin exponer el ID
 * @property string $title
 * @property string $content
 * @property string|null $seoDescription
 * @property int|UsersModel $author
 * @property int|PublicationCategoryMapper $category
 * @property string $mainImage
 * @property string $thumbImage
 * @property string $ogImage
 * @property string $folder
 * @property int $visits
 * @property string|\DateTime|null $startDate
 * @property string|\DateTime|null $endDate
 * @property string|\DateTime $createdAt
 * @property string|\DateTime $updatedAt
 * @property \stdClass|string|null $meta
 * @property \stdClass|null $langData
 */
class PublicationMapper extends EntityMapperExtensible
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
        'title' => [
            'type' => 'varchar',
            'length' => 255,
        ],
        'content' => [
            'type' => 'text',
        ],
        'seoDescription' => [
            'type' => 'text',
            'null' => true,
            'default' => '',
        ],
        'author' => [
            'type' => 'int',
            'reference_table' => UsersModel::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'username',
            'mapper' => UsersModel::class,
        ],
        'category' => [
            'type' => 'int',
            'reference_table' => PublicationCategoryMapper::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'id',
            'mapper' => PublicationCategoryMapper::class,
        ],
        'mainImage' => [
            'type' => 'text',
        ],
        'thumbImage' => [
            'type' => 'text',
        ],
        'ogImage' => [
            'type' => 'text',
        ],
        'folder' => [
            'type' => 'text',
        ],
        'visits' => [
            'type' => 'int',
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
        'meta' => [
            'type' => 'json',
            'null' => true,
            'dafault' => null,
        ],
    ];

    const TYPE_PREFER_DATE_DATETIME = '{DATETIME}';
    const TYPE_PREFER_DATE_DAY_NUMBER = '{DAY_NUMBER}';
    const TYPE_PREFER_DATE_DAY_NAME = '{DAY_NAME}';
    const TYPE_PREFER_DATE_MONTH_NUMBER = '{MONTH_NUMBER}';
    const TYPE_PREFER_DATE_MONTH_NAME = '{MONTH_NAME}';
    const TYPE_PREFER_DATE_YEAR = '{YEAR}';

    const TABLE = 'publications_elements';
    const LANG_GROUP = PublicationsLang::LANG_GROUP;
    const ORDER_BY_PREFERENCE = [
        '`title` ASC',
        '`category` ASC',
        '`startDate` ASC',
        '`createdAt` ASC',
        '`endDate` ASC',
    ];

    /**
     * Propiedades que no necesitan multi-idioma
     *
     * @var string[]
     */
    protected $noTranslatableProperties = [
        'id',
        'preferSlug',
        'author',
        'category',
        'folder',
        'startDate',
        'endDate',
        'createdAt',
        'updatedAt',
    ];

    /**
     * Propiedades necesitan multi-idioma
     *
     * @var string[]
     */
    protected $translatableProperties = [
        'title',
        'content',
        'seoDescription',
        'mainImage',
        'thumbImage',
        'ogImage',
        'visits',
    ];

    /**
     * $table
     *
     * @var string
     */
    protected $table = self::TABLE;

    //id
    //preferSlug
    //title
    //content
    //seoDescription
    //author
    //category
    //mainImage
    //thumbImage
    //ogImage
    //folder
    //visits
    //startDate
    //endDate
    //createdAt
    //updatedAt

    /**
     * @param int $value
     * @param string $fieldCompare
     * @return static
     */
    public function __construct(int $value = null, string $fieldCompare = 'primary_key')
    {

        $this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_JSON, new \stdClass, true), 'langData');
        parent::__construct($value, $fieldCompare);

    }

    /**
     * Devuelve el slug
     *
     * @return string
     */
    public function getSlug()
    {
        return self::elementFriendlySlug($this);
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        if (self::existsByName($this->name, is_object($this->category) ? $this->category->id : $this->category, -1)) {
            throw new DuplicateException(__(self::LANG_GROUP, 'Ya existe la publicación.'));
            return false;
        }

        $saveResult = parent::save();

        if ($saveResult) {
            $idInserted = $this->getInsertIDOnSave();
            $this->id = $idInserted;
            $this->preferSlug = self::getEncryptIDForSlug($idInserted);
            $this->update();
        }

        return $saveResult;

    }

    /**
     * @inheritDoc
     */
    public function update()
    {
        if (self::existsByName($this->name, is_object($this->category) ? $this->category->id : $this->category, $this->id)) {
            throw new DuplicateException(__(self::LANG_GROUP, 'Ya existe la publicación.'));
            return false;
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
     * @return string[]
     */
    public static function fieldsToSelect()
    {

        $mapper = (new static );
        $model = $mapper->getModel();
        $table = $model->getTable();

        $defaultLang = Config::get_default_lang();
        $currentLang = Config::get_lang();

        $fields = [];

        if ($defaultLang == $currentLang || !self::jsonExtractExistsMySQL()) {

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
                    $langDataIsNull = "JSON_EXTRACT({$table}.meta, '$.langData') = 'null'";
                    $langDataIsNull2 = "JSON_EXTRACT({$table}.meta, '$.langData') IS NULL";

                    $langIsNull = "JSON_EXTRACT({$table}.meta, '$.langData.{$currentLang}') = 'null'";
                    $langIsNull2 = "JSON_EXTRACT({$table}.meta, '$.langData.{$currentLang}') IS NULL";

                    $fieldIsNull = "JSON_EXTRACT({$table}.meta, '$.langData.{$currentLang}.{$fieldToLang}') = 'null'";
                    $fieldIsNull2 = "JSON_EXTRACT({$table}.meta, '$.langData.{$currentLang}.{$fieldToLang}') IS NULL";

                    $normalField = "{$table}.{$fieldToLang}";
                    $langField = "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.langData.{$currentLang}.{$fieldToLang}'))";

                    $if6 = "IF($fieldIsNull2, $normalField, $langField)";
                    $if5 = "IF($fieldIsNull, $normalField, $if6)";
                    $if4 = "IF($langIsNull2, $normalField, $if5)";
                    $if3 = "IF($langIsNull, $normalField, $if4)";
                    $if2 = "IF($langDataIsNull2, $normalField, $if3)";
                    $if1 = "IF($langDataIsNull, $normalField, $if2)";
                    $fields[] = "({$if1}) AS `{$fieldToLang}`";
                }

            }

        }

        return $fields;

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
     * Devuelve el nombre amigable del elemento
     *
     * @param \stdClass|PublicationMapper|int $elementOrID
     * @return string
     */
    public static function elementFriendlySlug($elementOrID)
    {
        $slug = '';

        if ($elementOrID instanceof \stdClass) {
            if (isset($elementOrID->id) && is_string($elementOrID->id) && ctype_digit($elementOrID->id)) {
                $elementOrID = (int) $elementOrID->id;
            }
        }

        if (is_int($elementOrID)) {

            $elementOrID = self::getBy($elementOrID, 'id', true);

        }

        if ($elementOrID instanceof PublicationMapper && $elementOrID->id !== null) {

            $uniqid = $elementOrID->preferSlug !== null ? $elementOrID->preferSlug : self::getEncryptIDForSlug($elementOrID->id);
            $name = StringManipulate::friendlyURLString($elementOrID->currentLangData('name'));

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
        $slug = is_string($slug) ? explode('-', $slug) : null;
        $slug = is_array($slug) && count($slug) > 1 ? $slug[count($slug) - 1] : null;
        $slug = $slug !== null ? BaseHashEncryption::decrypt(strtr($slug, '._', '-_'), self::TABLE) : null;
        $slug = $slug !== null ? explode('-', $slug) : null;
        $slugID = is_array($slug) && count($slug) === 2 ? $slug[0] : null;
        $slugID = is_string($slugID) && ctype_digit($slugID) ? (int) $slugID : null;
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

        $result = count($result) > 0 ? $result[0] : null;

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

        return count($result) > 0;
    }

    /**
     * Verifica si existe algún registro igual
     *
     * @param string $name
     * @param integer $categoryID
     * @param integer $ignoreID
     * @return bool
     */
    public static function existsByName(string $name, int $categoryID, int $ignoreID = -1)
    {

        $model = self::model();

        $name = escapeString($name);

        $where = [
            "name = '{$name}' AND",
            "category = {$categoryID} AND",
            "id != {$ignoreID}",
        ];

        $model->select()->where(implode(' ', $where));

        $model->execute();

        $result = $model->result();

        return count($result) > 0;

    }

    /**
     * Devuelve el mapeador desde un objeto
     *
     * @param \stdClass $element
     * @return static|null
     */
    public static function objectToMapper(\stdClass $element)
    {

        $element = (array) $element;
        $mapper = new static;
        $fieldsFilleds = [];
        $fields = array_merge(array_keys($mapper->fields), array_keys($mapper->getMetaProperties()));

        foreach ($element as $property => $value) {

            if (in_array($property, $fields)) {

                if ($property == 'meta') {

                    $value = $value instanceof \stdClass ? $value : @json_decode($value);

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
     * @return bool
     */
    public static function jsonExtractExistsMySQL()
    {

        try {

            $json = [
                'ok' => true,
            ];
            $json = json_encode($json);
            $sql = "SELECT JSON_EXTRACT('{$json}'" . ', \'$.test\')';
            $prepared = self::model()->prepare($sql);
            $prepared->execute();
            return true;

        } catch (\PDOException $e) {

            if ($e->getCode() == 1305 || $e->getCode() == 42000) {
                return false;
            } else {
                throw $e;
            }

        }

    }

    /**
     * @return BaseModel
     */
    public static function model()
    {
        return (new static )->getModel();
    }
}
