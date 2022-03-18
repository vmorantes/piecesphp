<?php

/**
 * PublicationMapper.php
 */

namespace Publications\Mappers;

use App\Model\UsersModel;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use PiecesPHP\Core\Database\Meta\MetaProperty;
use PiecesPHP\Core\StringManipulate;
use Publications\Controllers\PublicationsPublicController;
use Publications\Exceptions\DuplicateException;
use Publications\PublicationsLang;

/**
 * PublicationMapper.
 *
 * @package     Publications\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 * @property int|null $id
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
 * @property string|\DateTime $publicDate
 * @property string|\DateTime|null $startDate
 * @property string|\DateTime|null $endDate
 * @property string|\DateTime $createdAt
 * @property string|\DateTime $updatedAt
 * @property int|UsersModel $createdBy
 * @property int|UsersModel|null $modifiedBy
 * @property int $status
 * @property int $featured
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
        'publicDate' => [
            'type' => 'datetime',
            'default' => 'timestamp',
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
        'featured' => [
            'type' => 'int',
            'default' => self::UNFEATURED,
        ],
        'meta' => [
            'type' => 'json',
            'null' => true,
            'dafault' => null,
        ],
    ];

    const ACTIVE = 1;
    const INACTIVE = 0;
    const DRAFT = 2;

    const STATUSES = [
        self::ACTIVE => 'Activo',
        self::INACTIVE => 'Inactivo',
        self::DRAFT => 'Borrador',
    ];

    const VISIBILITY_VISIBLE = 1;
    const VISIBILITY_DRAFT = 0;
    const VISIBILITY_SCHEDULED = 2;
    const VISIBILITY_INACTIVE = 3;

    const VISIBILITIES = [
        self::VISIBILITY_VISIBLE => 'Publicado',
        self::VISIBILITY_DRAFT => 'Borrador',
        self::VISIBILITY_SCHEDULED => 'Programado',
        self::VISIBILITY_INACTIVE => 'Desactivado',
    ];

    const VISIBILITIES_COLORS = [
        self::VISIBILITY_VISIBLE => 'green',
        self::VISIBILITY_DRAFT => 'orange',
        self::VISIBILITY_SCHEDULED => 'blue',
        self::VISIBILITY_INACTIVE => 'red',
    ];

    const FEATURED = 1;
    const UNFEATURED = 0;

    const CAN_DELETE_ALL = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];

    const CAN_VIEW_DRAFT = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
        UsersModel::TYPE_USER_GENERAL,
    ];

    const TABLE = 'publications_elements';
    const VIEW_ACTIVE_DATE = 'publications_active_date_elements';
    const LANG_GROUP = PublicationsLang::LANG_GROUP;
    const ORDER_BY_PREFERENCE = [
        '`featured` DESC',
        '`publicDate` DESC',
        '`title` ASC',
        '`category` ASC',
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
        'seoDescription',
        'mainImage',
        'thumbImage',
        'ogImage',
    ];

    /**
     * $table
     *
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
        $content = strip_tags($this->currentLangData('content'));
        $contentLength = mb_strlen($content);
        return $contentLength <= $maxLength ? $content : substr($content, 0, ($maxLength >= 6 ? $maxLength - 3 : $maxLength)) . '...';
    }

    /**
     * @param bool $onlyCurrentLang
     * @param bool $asMapper
     * @return AttachmentPublicationMapper[]
     */
    public function getAttachments(bool $onlyCurrentLang = false, bool $asMapper = false)
    {
        $attachments = AttachmentPublicationMapper::allBy('publication', $this->id, $asMapper, $onlyCurrentLang);
        return $attachments;
    }

    /**
     * @return string
     */
    public function authorFullName()
    {
        $author = $this->author;

        if (!is_object($author)) {
            $this->author = new UsersModel($this->author);
        }

        return $this->author->getFullName();
    }

    /**
     * @return string
     */
    public function createdByFullName()
    {
        $createdBy = $this->createdBy;

        if (!is_object($createdBy)) {
            $this->createdBy = new UsersModel($this->createdBy);
        }

        return $this->createdBy->getFullName();
    }

    /**
     * @return string|null
     */
    public function modifiedByFullName()
    {
        $modifiedBy = $this->modifiedBy;

        if (!is_object($modifiedBy) && $modifiedBy !== null) {
            $this->modifiedBy = new UsersModel($this->modifiedBy);
        }

        return $modifiedBy !== null ? $this->modifiedBy->getFullName() : null;
    }

    /**
     * @return static
     */
    public function addVisit()
    {
        if ($this->id !== null) {
            $this->visits += 1;
            $this->update(true);
        }
        return $this;
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
     * @return bool
     */
    public function isFeatured()
    {
        return $this->featured == self::FEATURED;
    }

    /**
     * @return bool
     */
    public function isDraft()
    {
        return $this->status == self::DRAFT;
    }

    /**
     * @param string $format
     * @param array $replaceTemplate Para remplazar contenido dentro del formato, el array debe ser ['VALOR_A_REEMPLAZAR' => 'VALOR_DE_REEMPLAZO']
     * @return string
     */
    public function publicDateFormat(string $format = null, array $replaceTemplate = [])
    {
        $format = is_string($format) ? $format : get_default_format_date();
        $formated = localeDateFormat($format, $this->publicDate, $replaceTemplate);
        return $formated;
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
        $formated = $this->updatedAt instanceof \DateTime ? localeDateFormat($format, $this->updatedAt, $replaceTemplate) : null;
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
        $formated = $this->startDate instanceof \DateTime ? localeDateFormat($format, $this->startDate, $replaceTemplate) : null;
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
        $formated = $this->endDate instanceof \DateTime ? localeDateFormat($format, $this->endDate, $replaceTemplate) : null;
        return $formated;
    }

    /**
     * @return string[]
     */
    public function getURLAlternatives()
    {

        $currentLang = Config::get_lang();
        $allowedLangs = Config::get_allowed_langs();
        $urls = [];

        foreach ($allowedLangs as $lang) {
            $existOnLang = $this->getLangData($lang, 'title', false, null) !== null;
            if ($existOnLang && $lang != $currentLang) {
                $url = PublicationsPublicController::routeName('single', ['slug' => $this->getSlug($lang)]);
                $url = convert_lang_url($url, $currentLang, $lang);
                $urls[$lang] = $url;
            }
        }

        return $urls;

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
        if (self::existsByTitle($this->title, is_object($this->category) ? $this->category->id : $this->category, -1)) {
            throw new DuplicateException(__(self::LANG_GROUP, 'Ya existe la publicación.'));
        }

        $this->createdAt = new \DateTime();
        $this->createdBy = get_config('current_user')->id;
        $saveResult = parent::save();

        if ($saveResult) {
            $idInserted = $this->getInsertIDOnSave();
            $this->id = $idInserted;
            $this->preferSlug = self::getEncryptIDForSlug($idInserted);
            $this->update(true);
        }

        return $saveResult;

    }

    /**
     * @param bool $noDateUpdate
     * @inheritDoc
     */
    public function update(bool $noDateUpdate = false)
    {
        if (self::existsByTitle($this->title, is_object($this->category) ? $this->category->id : $this->category, $this->id)) {
            throw new DuplicateException(__(self::LANG_GROUP, 'Ya existe la publicación.'));
        }
        if (!$noDateUpdate) {
            $this->modifiedBy = get_config('current_user')->id;
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
     *  - categoryName
     *  - authorUser
     *  - featuredDisplay
     *  - statusText
     *  - isActiveByDate
     *  - visibility
     *  - visibilityText
     * @return string[]
     */
    public static function fieldsToSelect()
    {

        $mapper = (new PublicationMapper);
        $model = $mapper->getModel();
        $table = $model->getTable();

        $tableView = self::VIEW_ACTIVE_DATE;
        $tableCategory = PublicationCategoryMapper::TABLE;
        $tableUser = UsersModel::TABLE;

        $defaultLang = Config::get_default_lang();
        $currentLang = Config::get_lang();

        $categoryNameCurrentLang = PublicationCategoryMapper::fieldCurrentLangForSQL('name');
        $categoryNameSubQuery = "SELECT $categoryNameCurrentLang FROM {$tableCategory} WHERE {$tableCategory}.id = {$table}.category";

        $statusesJSON = json_encode((object) self::statuses(), \JSON_UNESCAPED_UNICODE);
        $visibilitiesJSON = json_encode((object) self::visibilities(), \JSON_UNESCAPED_UNICODE);

        $yesText = __(self::LANG_GROUP, 'Sí');
        $noText = __(self::LANG_GROUP, 'No');

        $isActiveByDate = "(SELECT COUNT({$tableView}.id) > 0 FROM {$tableView} WHERE {$tableView}.id = {$table}.id)";

        $statusActive = self::ACTIVE;
        $statusInactive = self::INACTIVE;
        $visibilitiyVisible = self::VISIBILITY_VISIBLE;
        $visibilitiyInvisible = self::VISIBILITY_DRAFT;
        $visibilitiySchedule = self::VISIBILITY_SCHEDULED;
        $visibilitiyInactive = self::VISIBILITY_INACTIVE;

        $visibilityConditions = "IF(
            {$isActiveByDate},
            IF(
                {$statusActive} = {$table}.status,
                {$visibilitiyVisible},
                {$visibilitiyInvisible}
            ),
            IF(
                {$statusActive} = {$table}.status,
                {$visibilitiySchedule},
                {$visibilitiyInvisible}
            )
        )";
        $visibilityConditions = "IF({$statusInactive} = {$table}.status, {$visibilitiyInactive}, {$visibilityConditions})";

        $fields = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
            "({$categoryNameSubQuery}) AS categoryName",
            "(SELECT {$tableUser}.username FROM {$tableUser} WHERE {$tableUser}.id = {$table}.author) AS authorUser",
            "IF({$table}.featured, '{$yesText}', '{$noText}') AS featuredDisplay",
            "JSON_UNQUOTE(JSON_EXTRACT('{$statusesJSON}', CONCAT('$.', {$table}.status))) AS statusText",
            "{$isActiveByDate} AS isActiveByDate",
            "$visibilityConditions AS visibility",
            "JSON_UNQUOTE(JSON_EXTRACT('{$visibilitiesJSON}', CONCAT('$.', $visibilityConditions))) AS visibilityText",
            "{$table}.meta",
        ];

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

        if ($defaultLang == $currentLang || !self::jsonExtractExistsMySQL()) {
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
     * Devuelve el nombre amigable del elemento
     *
     * @param \stdClass|PublicationMapper|int $elementOrID
     * @param string $lang
     * @return string
     */
    public static function elementFriendlySlug($elementOrID, string $lang = null)
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
            $title = StringManipulate::friendlyURLString($lang === null ? $elementOrID->currentLangData('title') : $elementOrID->getLangData($lang, 'title'));

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
     * @return array
     */
    public static function visibilities()
    {
        $options = [];
        foreach (self::VISIBILITIES as $value => $text) {
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
     *
     * @return \stdClass|static|null
     */
    public static function lastModifiedElement(bool $asMapper = false)
    {
        $table = self::TABLE;
        $model = self::model();

        $selectFields = [];

        $model->select($selectFields);

        $model->orderBy("{$table}.updatedAt DESC, {$table}.createdAt DESC");

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
     * Verifica si existe algún registro igual
     *
     * @param string $title
     * @param int $categoryID
     * @param int $ignoreID
     * @param bool $onlyActives
     * @return bool
     */
    public static function existsByTitle(string $title, int $categoryID, int $ignoreID = -1, bool $onlyActives = true)
    {

        $model = self::model();

        $title = escapeString($title);
        $statusInactive = self::INACTIVE;

        $where = [
            "title = '{$title}' AND",
            "category = {$categoryID} AND",
            "id != {$ignoreID}",
        ];

        if ($onlyActives) {
            $where[] = "AND status != {$statusInactive}";
        }

        $model->select()->where(implode(' ', $where));

        $model->execute();

        $result = $model->result();

        return !empty($result);

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
        $mapper = new PublicationMapper;
        $fieldsFilleds = [];
        $fields = array_merge(array_keys($mapper->fields), array_keys($mapper->getMetaProperties()));

        $defaultPropertiesValues = [
            'featured' => self::UNFEATURED,
        ];

        foreach ($defaultPropertiesValues as $defaultProperty => $defaultPropertyValue) {
            if (!array_key_exists($defaultProperty, $element)) {
                $element[$defaultProperty] = $defaultPropertyValue;
            }
        }

        $defaultMetaPropertiesValues = [];

        foreach ($element as $property => $value) {

            if (in_array($property, $fields)) {

                if ($property == 'meta') {

                    $value = $value instanceof \stdClass ? $value : @json_decode($value);

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
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new PublicationMapper)->getModel();
    }
}
