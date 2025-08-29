<?php

/**
 * NewsMapper.php
 */

namespace News\Mappers;

use App\Model\UsersModel;
use News\NewsLang;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use PiecesPHP\Core\Database\Meta\MetaProperty;
use PiecesPHP\Core\StringManipulate;
use PiecesPHP\Core\Validation\Validator;

/**
 * NewsMapper.
 *
 * @package     News\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 * @property int|null $id
 * @property string|null $preferSlug Es un token usado para acceso individual sin exponer el ID
 * @property string $newsTitle
 * @property array $profilesTarget
 * @property string $content
 * @property int|NewsCategoryMapper $category
 * @property string $folder
 * @property string|\DateTime|null $startDate
 * @property string|\DateTime|null $endDate
 * @property string|\DateTime $createdAt
 * @property string|\DateTime $updatedAt
 * @property int|UsersModel $createdBy
 * @property int|UsersModel|null $modifiedBy
 * @property int $status
 * @property \stdClass|string|null $meta
 * @property int $draft 1|0
 * @property string $baseLang
 * @property \stdClass|null $langData
 */
class NewsMapper extends EntityMapperExtensible
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
        'newsTitle' => [
            'type' => 'text',
        ],
        'profilesTarget' => [
            'type' => 'json',
            'default' => [],
        ],
        'content' => [
            'type' => 'text',
        ],
        'category' => [
            'type' => 'int',
            'reference_table' => NewsCategoryMapper::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'id',
            'mapper' => NewsCategoryMapper::class,
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
    const PSEUDO_STATUS_DRAFT = 'DRAFT';

    const STATUSES = [
        self::ACTIVE => 'Activo',
        self::INACTIVE => 'Inactiva',
    ];

    const STATUSES_COLORS = [
        self::ACTIVE => 'brand-color',
        self::INACTIVE => 'brand-color alt2',
        self::PSEUDO_STATUS_DRAFT => 'orange',
    ];

    const CAN_DELETE_ALL = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN_GRAL,
        UsersModel::TYPE_USER_ADMIN_ORG,
    ];

    const CAN_VIEW_TARGET_ALL = [
        UsersModel::TYPE_USER_ROOT,
    ];

    const TABLE = 'news_elements';
    const VIEW_ACTIVE_DATE = 'news_active_date_elements';
    const LANG_GROUP = NewsLang::LANG_GROUP;
    const ORDER_BY_PREFERENCE = [
        '`idPadding` DESC',
        '`startDate` DESC',
        '`newsTitle` ASC',
        '`categoryName` ASC',
    ];

    const LANGS_ON_CREATION = [
        'es',
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
        'newsTitle',
        'content',
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

        $this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_INT, 0, false), 'draft');
        $this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_JSON, new \stdClass, true), 'langData');
        $this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_TEXT, Config::get_default_lang(), true), 'baseLang');
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
    public function excerptTitle(int $maxLength = 45)
    {
        $title = $this->currentLangData('newsTitle');
        $titleLength = mb_strlen($title);
        return $titleLength <= $maxLength ? $title : substr($title, 0, ($maxLength >= 6 ? $maxLength - 3 : $maxLength)) . '...';
    }

    /**
     * @param int $maxLength
     * @return string
     */
    public function excerpt(int $maxLength = 300)
    {
        $content = strip_tags($this->currentLangData('content'));
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
        $categoryID = is_object($this->category) ? $this->category->id : $this->category;
        $categoryID = $categoryID !== null ? $categoryID : -1;

        $this->createdAt = new \DateTime();
        $this->createdBy = getLoggedFrameworkUser() != null ? getLoggedFrameworkUser()->id : 1;
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
        $categoryID = is_object($this->category) ? $this->category->id : $this->category;
        $categoryID = $categoryID !== null ? $categoryID : -1;

        if (!$noDateUpdate) {
            $this->modifiedBy = getLoggedFrameworkUser()->id;
            $this->updatedAt = new \DateTime();
        }
        return parent::update();
    }

    /**
     * Asigna datos a una propiedad para múltiples idiomas.
     *
     * Este método itera sobre un arreglo de idiomas y llama a setLangData para cada idioma,
     * permitiendo la asignación de datos específicos para cada uno.
     * Se asegura de que solo se asignen datos si el idioma está presente en el arreglo de datos.
     *
     * @param string $name El nombre de la propiedad a la que se asignarán los datos.
     * @param array<string,mixed> $data Un arreglo asociativo donde la clave es el idioma y el valor es el dato a asignar.
     * @param string[] $langs Un arreglo de idiomas para los cuales se asignarán los datos.
     */
    public function addDataManyLangs(string $name, array $data, array $langs)
    {
        foreach ($langs as $lang) {
            if (is_string($lang) && array_key_exists($lang, $data)) {
                $this->setLangData($lang, $name, $data[$lang]);
            }
        }
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
     *  - categoryName
     *  - statusText
     *  - isActiveByDate
     *  - activeStatus
     *  - activeText
     *  - startDateFormat
     *  - endDateFormat
     *  - endDateExtention
     *  - endDateExtentionFormat
     *  - draft
     *  - baseLang
     * @return string[]
     */
    protected static function fieldsToSelect(string $formatDate = null)
    {

        $formatDate = $formatDate ?? get_default_format_date(null, true);
        $mapper = (new NewsMapper);
        $model = $mapper->getModel();
        $table = $model->getTable();

        $tableView = self::VIEW_ACTIVE_DATE;
        $tableCategory = NewsCategoryMapper::TABLE;
        $currentLang = Config::get_lang();

        $categoryNameCurrentLang = NewsCategoryMapper::fieldCurrentLangForSQL('name');
        $categoryNameSubQuery = "SELECT $categoryNameCurrentLang FROM {$tableCategory} WHERE {$tableCategory}.id = {$table}.category";

        $statusesJSON = json_encode((object) self::statuses(), \JSON_UNESCAPED_UNICODE);

        $isActiveByDate = "(SELECT COUNT({$tableView}.id) > 0 FROM {$tableView} WHERE {$tableView}.id = {$table}.id)";

        $active = self::ACTIVE;
        $inactive = self::INACTIVE;

        $endDateExtention = "DATE_ADD({$table}.endDate, INTERVAL 15 DAY)";
        $fields = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
            "({$categoryNameSubQuery}) AS categoryName",
            "JSON_UNQUOTE(JSON_EXTRACT('{$statusesJSON}', CONCAT('$.', {$table}.status))) AS statusText",
            "{$isActiveByDate} AS isActiveByDate",
            "(SELECT IF(isActiveByDate, {$active}, {$inactive})) AS activeStatus",
            "(SELECT JSON_UNQUOTE(JSON_EXTRACT('{$statusesJSON}', CONCAT('$.', activeStatus)))) AS activeText",
            "IF({$table}.startDate IS NOT NULL, DATE_FORMAT({$table}.startDate, '{$formatDate}'), '-') AS startDateFormat",
            "IF({$table}.endDate IS NOT NULL, DATE_FORMAT({$table}.endDate, '{$formatDate}'), '-') AS endDateFormat",
            "{$endDateExtention} AS endDateExtention",
            "IF({$endDateExtention} IS NOT NULL, DATE_FORMAT({$endDateExtention}, '{$formatDate}'), '-') AS endDateExtentionFormat",
            "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.draft')) AS draft",
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
     * @param \stdClass|NewsMapper|int $elementOrID
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

        if ($elementOrID instanceof NewsMapper && $elementOrID->id !== null) {

            $uniqid = $elementOrID->preferSlug !== null ? $elementOrID->preferSlug : self::getEncryptIDForSlug($elementOrID->id);
            $title = StringManipulate::friendlyURLString($lang === null ? $elementOrID->currentLangData('newsTitle') : $elementOrID->getLangData($lang, 'newsTitle'));

            $slug = "{$title}-{$uniqid}";

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
        $slug = explode('-', $slug);
        $slug = is_array($slug) && count($slug) > 1 ? $slug[count($slug) - 1] : null;
        $slug = $slug !== null ? BaseHashEncryption::decrypt(strtr($slug, '._', '-_'), self::TABLE) : null;
        $slug = $slug !== null ? explode('-', $slug) : null;
        $slugID = is_array($slug) && count($slug) === 2 ? $slug[0] : null;
        $slugID = Validator::isInteger($slugID) ? (int) $slugID : null;
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
     * @param int $categoryID
     * @param bool $onlyActives
     * @return int
     */
    public static function countByCategory(int $categoryID, bool $onlyActives = true)
    {
        $model = self::model();
        $statusActive = self::ACTIVE;
        $selectFields = [
            'COUNT(id) AS total',
        ];
        $model->select($selectFields);
        if ($onlyActives) {
            $model->where("category = {$categoryID} AND status = {$statusActive}");
        } else {
            $model->where("category = {$categoryID}");
        }
        $model->execute();
        $result = $model->result();
        $result = !empty($result) ? (int) $result[0]->total : 0;
        return $result;
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
     * @return NewsMapper|null
     */
    public static function objectToMapper(\stdClass $element)
    {

        $element = (array) $element;
        $mapper = new NewsMapper;
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
            'draft' => 0,
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
                if ($mapper->preferSlug === null && $mapper->newsTitle !== null) {
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
        return (new NewsMapper)->getModel();
    }
}
