<?php

/**
 * UserProfileMapper.php
 */

namespace PiecesPHP\UserSystem\Profile;

use App\Locations\Mappers\CityMapper;
use App\Locations\Mappers\CountryMapper;
use App\Model\UsersModel;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use PiecesPHP\Core\Database\Meta\MetaProperty;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\UserSystem\Profile\SubMappers\InterestResearchAreasMapper;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * UserProfileMapper.
 *
 * @package     PiecesPHP\UserSystem\Profile
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 * @property int|null $id
 * @property string|null $preferSlug Es un token usado para acceso individual sin exponer el ID
 * @property string|null $jobPosition
 * @property string|null $phoneCode
 * @property string|null $phoneNumber
 * @property string|null $nationality
 * @property string|null $linkedinLink
 * @property string|null $websiteLink
 * @property int|CountryMapper|null $country
 * @property int|CityMapper|null $city
 * @property double|null $latitude
 * @property double|null $longitude
 * @property int|UsersModel $belongsTo
 * @property string|\DateTime $createdAt
 * @property string|\DateTime $updatedAt
 * @property int|UsersModel|null $createdBy
 * @property int|UsersModel $modifiedBy
 * @property \stdClass|string|null $meta
 * @property string $baseLang
 * @property \stdClass|null $langData
 * @property int[]|InterestResearchAreasMapper[]|null $interestResearhAreas
 * @property string[] $affiliatedInstitutions
 */
class UserProfileMapper extends EntityMapperExtensible
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
        'jobPosition' => [
            'type' => 'text',
            'null' => true,
        ],
        'phoneCode' => [
            'type' => 'text',
            'null' => true,
        ],
        'phoneNumber' => [
            'type' => 'text',
            'null' => true,
        ],
        'nationality' => [
            'type' => 'text',
            'null' => true,
        ],
        'linkedinLink' => [
            'type' => 'text',
            'null' => true,
        ],
        'websiteLink' => [
            'type' => 'text',
            'null' => true,
        ],
        'country' => [
            'type' => 'int',
            'reference_table' => CountryMapper::PREFIX_TABLE . CountryMapper::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'id',
            'mapper' => CountryMapper::class,
            'null' => true,
        ],
        'city' => [
            'type' => 'int',
            'reference_table' => CityMapper::PREFIX_TABLE . CityMapper::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'id',
            'mapper' => CityMapper::class,
            'null' => true,
        ],
        'latitude' => [
            'type' => 'double',
            'null' => true,
        ],
        'longitude' => [
            'type' => 'double',
            'null' => true,
        ],
        'belongsTo' => [
            'type' => 'int',
            'reference_table' => UsersModel::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'id',
            'mapper' => UsersModel::class,
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

    const TABLE = 'user_system_profile';
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
    protected $translatableProperties = [];

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
        $this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_ARRAY_MAPPER, null, true, InterestResearchAreasMapper::class, 'id'), 'interestResearhAreas');
        $this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_ARRAY, [], false), 'affiliatedInstitutions');
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
     * Verifica si el perfil del usuario asociado a este mapper está completo.
     *
     * Basado en profileIsComplete
     *
     * @return bool true si el perfil está completo, false de lo contrario.
     */
    public function isComplete()
    {
        $belongsTo = is_object($this->belongsTo) ? $this->belongsTo->id : $this->belongsTo;
        return self::profileIsComplete($belongsTo);
    }

    /**
     * Obtiene el número de teléfono del perfil.
     *
     * Concatena el código de país y el número de teléfono para formar un número de teléfono completo.
     *
     * @return string El número de teléfono completo.
     */
    public function getPhone()
    {
        $phone = "";
        $phoneCode = $this->currentLangData('phoneCode');
        $phoneNumber = $this->currentLangData('phoneNumber');
        if (is_string($phoneCode) && is_string($phoneNumber)) {
            $phone = "{$phoneCode} {$phoneNumber}";
        }
        return trim($phone);
    }

    /**
     * Obtiene el enlace al sitio web del perfil.
     *
     * Retorna el enlace al sitio web del perfil si está disponible.
     *
     * @return string El enlace al sitio web del perfil.
     */
    public function getWebsiteLink()
    {
        $websiteLink = "";
        $websiteLinkData = $this->currentLangData('websiteLink');
        if (is_string($websiteLinkData)) {
            $websiteLink = $websiteLinkData;
        }
        return trim($websiteLink);
    }

    /**
     * Obtiene el enlace a LinkedIn del perfil.
     *
     * Retorna el enlace a LinkedIn del perfil si está disponible.
     *
     * @return string El enlace a LinkedIn del perfil.
     */
    public function getLinkedinLink()
    {
        $linkedinLink = "";
        $linkedinLinkData = $this->currentLangData('linkedinLink');
        if (is_string($linkedinLinkData)) {
            $linkedinLink = $linkedinLinkData;
        }
        return trim($linkedinLink);
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        $belongsTo = is_object($this->belongsTo) ? $this->belongsTo->id : $this->belongsTo;
        $belongsTo = $belongsTo !== null ? $belongsTo : -1;

        if (!self::existsByUser($belongsTo, -1)) {

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

        } else {
            return $this->update();
        }

    }

    /**
     * @param bool $noDateUpdate
     * @inheritDoc
     */
    public function update(bool $noDateUpdate = false)
    {
        $belongsTo = is_object($this->belongsTo) ? $this->belongsTo->id : $this->belongsTo;
        $belongsTo = $belongsTo !== null ? $belongsTo : -1;
        if (self::existsByUser($belongsTo)) {
            if (!$noDateUpdate) {
                $currentUser = UserDataPackage::getConfigCurrentUser(); //Importante para no generar recursividad con UserDataPackage
                $this->modifiedBy = $currentUser !== null ? $currentUser->id : 1;
                $this->updatedAt = new \DateTime();
            }
            return parent::update();
        } else {
            return false;
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
     *  - organizationID
     *  - idPadding
     *  - userStatus
     *  - username
     *  - email
     *  - type
     *  - fullname
     *  - names
     *  - lastNames
     *  - countryName
     *  - cityName
     *  - fullLocation
     *  - interestResearhAreasNames
     *  - interestResearhAreasIDsNames
     *  - interestResearhAreasColorsNames
     *  - baseLang
     * @return string[]
     */
    protected static function fieldsToSelect(string $formatDate = null, string $locationSeparator = ' - ')
    {

        $formatDate = $formatDate ?? get_default_format_date(null, true);
        $mapper = (new UserProfileMapper);
        $model = $mapper->getModel();
        $table = $model->getTable();
        $tableInterestResearchAreas = InterestResearchAreasMapper::TABLE;
        $tableCountry = CountryMapper::PREFIX_TABLE . CountryMapper::TABLE;
        $tableCity = CityMapper::PREFIX_TABLE . CityMapper::TABLE;

        //Ubicación
        $countryName = "(SELECT {$tableCountry}.name FROM {$tableCountry} WHERE {$tableCountry}.id = {$table}.country)";
        $cityName = "(SELECT {$tableCity}.name FROM {$tableCity} WHERE {$tableCity}.id = {$table}.city)";

        //Áreas de investigación
        $researchAreas = "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.interestResearhAreas'))";
        $areaNameCurrentLang = InterestResearchAreasMapper::fieldCurrentLangForSQL('areaName');
        $researchAreasNameSubQuery = "SELECT GROUP_CONCAT($areaNameCurrentLang SEPARATOR ', ') FROM {$tableInterestResearchAreas} WHERE JSON_CONTAINS({$researchAreas}, {$tableInterestResearchAreas}.id)";
        $researchAreasNameAndIDSubQuery = "SELECT GROUP_CONCAT(CONCAT({$tableInterestResearchAreas}.id, ':', $areaNameCurrentLang) SEPARATOR ', ') FROM {$tableInterestResearchAreas} WHERE JSON_CONTAINS({$researchAreas}, {$tableInterestResearchAreas}.id)";
        $researchAreasNameAndColorSubQuery = "SELECT GROUP_CONCAT(CONCAT(JSON_UNQUOTE(JSON_EXTRACT({$tableInterestResearchAreas}.meta, '$.color')), ':', $areaNameCurrentLang) SEPARATOR '|@|') FROM {$tableInterestResearchAreas} WHERE JSON_CONTAINS({$researchAreas}, {$tableInterestResearchAreas}.id)";

        //Usuario
        $tableUser = UsersModel::TABLE;
        $firstnameSegment = "TRIM({$tableUser}.firstname)";
        $secondNameSegment = "IF({$tableUser}.secondname IS NOT NULL, CONCAT(' ', {$tableUser}.secondname), '')";
        $names = "TRIM(CONCAT({$firstnameSegment}, {$secondNameSegment}))";
        $firstLastNameSegment = "TRIM({$tableUser}.first_lastname)";
        $secondLastNameSegment = "IF({$tableUser}.second_lastname IS NOT NULL, CONCAT(' ', {$tableUser}.second_lastname), '')";
        $lastNames = "TRIM(CONCAT({$firstLastNameSegment}, {$secondLastNameSegment}))";
        $fullName = "TRIM(CONCAT({$names}, ' ', {$lastNames}))";

        $currentLang = Config::get_lang();

        $fields = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
            "(SELECT {$tableUser}.organization FROM {$tableUser} WHERE {$tableUser}.id = {$table}.belongsTo) AS organizationID",
            "(SELECT {$tableUser}.status FROM {$tableUser} WHERE {$tableUser}.id = {$table}.belongsTo) AS userStatus",
            "(SELECT {$tableUser}.username FROM {$tableUser} WHERE {$tableUser}.id = {$table}.belongsTo) AS username",
            "(SELECT {$tableUser}.email FROM {$tableUser} WHERE {$tableUser}.id = {$table}.belongsTo) AS email",
            "(SELECT {$tableUser}.type FROM {$tableUser} WHERE {$tableUser}.id = {$table}.belongsTo) AS type",
            "(SELECT {$fullName} FROM {$tableUser} WHERE {$tableUser}.id = {$table}.belongsTo) AS fullname",
            "(SELECT {$names} FROM {$tableUser} WHERE {$tableUser}.id = {$table}.belongsTo) AS names",
            "(SELECT {$lastNames} FROM {$tableUser} WHERE {$tableUser}.id = {$table}.belongsTo) AS lastNames",
            "{$countryName} AS countryName",
            "{$cityName} AS cityName",
            "CONCAT((SELECT countryName), '{$locationSeparator}', (SELECT cityName)) AS fullLocation",
            "({$researchAreasNameSubQuery}) AS interestResearhAreasNames",
            "({$researchAreasNameAndIDSubQuery}) AS interestResearhAreasIDsNames",
            "({$researchAreasNameAndColorSubQuery}) AS interestResearhAreasColorsNames",
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
     * Verifica si el perfil de un usuario está completo.
     *
     * Un perfil se considera completo si tiene todos los campos requeridos llenos.
     * Los campos requeridos son: jobPosition, nationality, country, city, latitude, longitude e interestResearhAreas.
     *
     * @param int $userID El ID del usuario.
     * @return bool true si el perfil está completo, false de lo contrario.
     */
    public static function profileIsComplete(int $userID)
    {
        /**
         * @var UserProfileMapper|null
         */
        $mapper = self::getBy($userID, 'belongsTo', true);
        $complete = false;

        if ($mapper !== null) {

            $complete = true;
            $requiredProperties = [
                'jobPosition' => fn($e) => is_string($e) && mb_strlen(trim($e)) > 1,
                'nationality' => fn($e) => is_string($e) && mb_strlen(trim($e)) > 1,
                'country' => fn($e) => Validator::isInteger($e),
                'city' => fn($e) => Validator::isInteger($e),
                'latitude' => fn($e) => Validator::isDouble($e),
                'longitude' => fn($e) => Validator::isDouble($e),
                'interestResearhAreas' => fn($e) => is_array($e) && !empty($e),
            ];

            foreach ($requiredProperties as $requiredProperty => $validator) {
                $valid = ($validator)($mapper->$requiredProperty);
                $complete = $complete && $valid;
            }

        }
        return $complete;
    }

    /**
     * @param int $userID
     * @return UserProfileMapper|null
     */
    public static function getProfile(int $userID)
    {
        $userRecord = UsersModel::getUsersByIDs([$userID]);
        $userRecord = !empty($userRecord) ? $userRecord[0] : null;

        if ($userRecord !== null) {
            $model = self::model();
            $where = [
                "belongsTo" => $userID,
            ];
            $model->select()->where($where);
            $model->execute();
            $result = $model->result();
            $result = !empty($result) ? $result[0]->id : null;
            $mapper = new UserProfileMapper($result);
            if ($mapper->id === null) {
                $mapper = new UserProfileMapper();
                $mapper->belongsTo = $userID;
                $mapper->save();
            }
            return $mapper->id !== null ? $mapper : null;
        } else {
            return null;
        }

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
     * @param int $userID
     * @param int $ignoreID
     * @return bool
     */
    public static function existsByUser(string $userID, int $ignoreID = null)
    {

        $ignoreID = $ignoreID !== null ? $ignoreID : -1;
        $model = self::model();

        $where = [
            "belongsTo = {$userID} AND",
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
     * @return UserProfileMapper|null
     */
    public static function objectToMapper(\stdClass $element)
    {

        $element = (array) $element;
        $mapper = new UserProfileMapper;
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
            'interestResearhAreas' => null,
            'affiliatedInstitutions' => [],
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
        return (new UserProfileMapper)->getModel();
    }
}
