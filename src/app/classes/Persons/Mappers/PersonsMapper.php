<?php

/**
 * PersonsMapper.php
 */

namespace Persons\Mappers;

use App\Model\UsersModel;
use App\Presentations\Exceptions\DuplicateException;
use Persons\Controllers\PersonsController;
use Persons\PersonsLang;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use PiecesPHP\Core\Database\Meta\MetaProperty;

/**
 * PersonsMapper.
 *
 * @package     Persons\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 * @property int|null $id
 * @property string|null $preferSlug
 * @property string $documentType
 * @property string $documentNumber
 * @property string $personName1
 * @property string $personName2
 * @property string $personLastName1
 * @property string $personLastName2
 * @property string $folder
 * @property int $status
 * @property string|\DateTime $createdAt
 * @property string|\DateTime|null $updatedAt
 * @property int|UsersModel $createdBy
 * @property int|UsersModel|null $modifiedBy
 * @property \stdClass|string|null $meta
 * @property \stdClass|null $langData
 */
class PersonsMapper extends EntityMapperExtensible
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
        'documentType' => [
            'type' => 'text',
        ],
        'documentNumber' => [
            'type' => 'text',
        ],
        'personName1' => [
            'type' => 'text',
        ],
        'personName2' => [
            'type' => 'text',
        ],
        'personLastName1' => [
            'type' => 'text',
        ],
        'personLastName2' => [
            'type' => 'text',
        ],
        'folder' => [
            'type' => 'text',
        ],
        'status' => [
            'type' => 'int',
            'default' => self::STATUS_ACTIVE,
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

    const CAN_VIEW_ALL = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];

    const CAN_ADD_ALL = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];

    const CAN_EDIT_ALL = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];

    const CAN_DELETE_ALL = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;
    const STATUS = [
        self::STATUS_ACTIVE => 'Activo',
        self::STATUS_ACTIVE => 'Inactivo',
    ];

    const TABLE = 'persons';
    const LANG_GROUP = PersonsLang::LANG_GROUP;
    const ORDER_BY_PREFERENCE = [
        '`id` DESC',
        '`fullname` ASC',
    ];

    const ID_DOCUMENT_TYPE_CC = 1;
    const ID_DOCUMENT_TYPE_CE = 2;
    const ID_DOCUMENT_TYPE_NIT = 3;
    const ID_DOCUMENT_TYPE_NUIP = 4;
    const ID_DOCUMENT_TYPE_TI = 5;
    const ID_DOCUMENT_TYPE_RN = 6;

    const ID_DOCUMENT_TYPES = [
        self::ID_DOCUMENT_TYPE_CC => 'Cédula de ciudadanía',
        self::ID_DOCUMENT_TYPE_CE => 'Cédula de extranjería',
        self::ID_DOCUMENT_TYPE_NIT => 'Número de identificación tributaria (NIT)',
        self::ID_DOCUMENT_TYPE_NUIP => 'Número único de identificación personal (NUIP)',
        self::ID_DOCUMENT_TYPE_TI => 'Tarjeta de identidad',
        self::ID_DOCUMENT_TYPE_RN => 'Registro de nacimiento',
    ];

    const ID_DOCUMENT_TYPES_SHORT = [
        self::ID_DOCUMENT_TYPE_CC => 'CC',
        self::ID_DOCUMENT_TYPE_CE => 'CE',
        self::ID_DOCUMENT_TYPE_NIT => 'NIT',
        self::ID_DOCUMENT_TYPE_NUIP => 'NUIP',
        self::ID_DOCUMENT_TYPE_TI => 'TI',
        self::ID_DOCUMENT_TYPE_RN => 'RN',
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
     * @return bool
     */
    public function folderRemove()
    {
        $pcsUploadDir = get_config('upload_dir');
        $folder = append_to_url(append_to_url($pcsUploadDir, PersonsController::UPLOAD_DIR), $this->folder);
        $removed = @rmdir($folder);
        return $removed;
    }

    /**
     * @return string
     */
    public function fullname()
    {
        $fullname = [
            $this->personName1,
            $this->personName2,
            $this->personLastName1,
            $this->personLastName2,
        ];

        $fullname = implode(' ', array_filter($fullname, function ($e) {
            return is_string($e) && mb_strlen(trim($e)) > 0;
        }));

        return $fullname;
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
        if (self::existsByDocument($this->documentType, $this->documentNumber, -1)) {
            throw new DuplicateException(__(self::LANG_GROUP, "Ya existe una persona con el documento '{$this->documentNumber}' (" . self::idDocumentTypes()[$this->documentType] . ")."));
        }

        $this->createdAt = new \DateTime();
        $this->createdBy = get_config('current_user')->id;
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
        if (self::existsByDocument($this->documentType, $this->documentNumber, $this->id)) {
            throw new DuplicateException(__(self::LANG_GROUP, "Ya existe una persona con el documento '{$this->documentNumber}' (" . self::idDocumentTypes()[$this->documentType] . ")."));
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
     *  - names
     *  - lastNames
     *  - fullname
     *  - documentTypeText
     *  - documentShortTypeText
     *  - documentShortAndNumber
     * @return string[]
     */
    public static function fieldsToSelect()
    {

        $mapper = new PersonsMapper;

        $defaultLang = Config::get_default_lang();
        $currentLang = Config::get_lang();

        $table = self::TABLE;

        $documentTypesJSON = json_encode((object) self::idDocumentTypes(), \JSON_UNESCAPED_UNICODE);
        $documentShortTypesJSON = json_encode((object) self::idDocumentShortTypes(), \JSON_UNESCAPED_UNICODE);

        $names = "TRIM(CONCAT({$table}.personName1, ' ', {$table}.personName2))";
        $lastNames = "TRIM(CONCAT({$table}.personLastName1, ' ', {$table}.personLastName2))";
        $fullname = "TRIM(CONCAT({$names}, ' ', {$lastNames}))";

        $fields = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
            "{$names} AS names",
            "{$lastNames} AS lastNames",
            "{$fullname} AS fullname",
            "JSON_UNQUOTE(JSON_EXTRACT('{$documentTypesJSON}', CONCAT('$.', {$table}.documentType))) AS documentTypeText",
            "JSON_UNQUOTE(JSON_EXTRACT('{$documentShortTypesJSON}', CONCAT('$.', {$table}.documentType))) AS documentShortTypeText",
            "CONCAT(JSON_UNQUOTE(JSON_EXTRACT('{$documentShortTypesJSON}', CONCAT('$.', {$table}.documentType))), '-', {$table}.documentNumber) AS documentShortAndNumber",
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
     * @param \stdClass|PersonsMapper|int $elementOrID
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

        if ($elementOrID instanceof PersonsMapper && $elementOrID->id !== null) {

            $uniqid = $elementOrID->preferSlug !== null ? $elementOrID->preferSlug : self::getEncryptIDForSlug($elementOrID->id);
            $title = 'person';

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
    public static function idDocumentTypes()
    {
        $options = [];

        foreach (self::ID_DOCUMENT_TYPES as $value => $text) {
            $options[$value] = __(self::LANG_GROUP, $text);
        }

        return $options;
    }

    /**
     * @return array
     */
    public static function idDocumentShortTypes()
    {
        $options = [];

        foreach (self::ID_DOCUMENT_TYPES_SHORT as $value => $text) {
            $options[$value] = __(self::LANG_GROUP, $text);
        }

        return $options;
    }

    /**
     * Un array listo para ser usado en array_to_html_options
     * @param string $defaultLabel
     * @param string $defaultValue
     * @return array
     */
    public static function idDocumentTypesForSelect(string $defaultLabel = '', string $defaultValue = '')
    {
        $defaultLabel = strlen($defaultLabel) > 0 ? $defaultLabel : __(self::LANG_GROUP, 'Tipo de documento');
        $options = [];
        $options[$defaultValue] = $defaultLabel;

        foreach (self::idDocumentTypes() as $value => $text) {
            $options[$value] = $text;
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
     * @param int $id
     * @return \stdClass|null
     */
    public static function getByID(int $id)
    {
        $model = self::model();
        $model->select();
        $model->where("id = {$id}");
        $model->execute();
        $result = $model->result();
        return !empty($result) ? $result[0] : null;
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
     * @param string $documentType
     * @param string $documentNumber
     * @param int $ignoreID
     * @param bool $onlyActives
     * @return bool
     */
    public static function existsByDocument(string $documentType, string $documentNumber, int $ignoreID = null, bool $onlyActives = true)
    {

        $ignoreID = $ignoreID !== null ? $ignoreID : -1;
        $model = self::model();

        $documentNumber = escapeString($documentNumber);
        $statusActive = self::STATUS_ACTIVE;

        $where = [
            "documentType = '{$documentType}' AND",
            "documentNumber = '{$documentNumber}' AND",
            "id != {$ignoreID}",
        ];

        if ($onlyActives) {
            $where[] = "AND status = {$statusActive}";
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
     * @return PersonsMapper|null
     */
    public static function objectToMapper(\stdClass $element)
    {

        $element = (array) $element;
        $mapper = new PersonsMapper;
        $fieldsFilleds = [];
        $fields = array_merge(array_keys($mapper->fields), array_keys($mapper->getMetaProperties()));

        $defaultPropertiesValues = [];

        foreach ($defaultPropertiesValues as $defaultProperty => $defaultPropertyValue) {
            if (!array_key_exists($defaultProperty, $element)) {
                $element[$defaultProperty] = $defaultPropertyValue;
            }
        }

        $defaultMetaPropertiesValues = [
            'langData' => [],
        ];

        foreach ($element as $property => $value) {

            if (in_array($property, $fields)) {

                if ($property == 'meta') {

                    $value = $value instanceof \stdClass ? $value : @json_decode($value);

                    foreach ($defaultMetaPropertiesValues as $defaultMetaProperty => $defaultMetaPropertyValue) {
                        if (!property_exists($value, $defaultMetaProperty)) {
                            $value->$defaultMetaProperty = $defaultMetaPropertyValue;
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
                if ($mapper->preferSlug === null && $mapper->personName1 !== null) {
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
        return (new PersonsMapper)->getModel();
    }
}
