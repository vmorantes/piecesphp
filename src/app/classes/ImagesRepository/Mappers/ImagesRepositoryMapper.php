<?php

/**
 * ImagesRepositoryMapper.php
 */

namespace ImagesRepository\Mappers;

use App\Locations\Mappers\CityMapper;
use App\Model\UsersModel;
use ImagesRepository\Controllers\ImagesRepositoryController;
use ImagesRepository\ImagesRepositoryLang;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use PiecesPHP\Core\Database\Meta\MetaProperty;

/**
 * ImagesRepositoryMapper.
 *
 * @package     ImagesRepository\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 * @property int|null $id
 * @property string|null $preferSlug Es un token usado para acceso individual sin exponer el ID
 * @property int|CityMapper $city
 * @property string $author
 * @property string $description
 * @property string $image
 * @property string $authorization
 * @property string $folder
 * @property string $resolution
 * @property float $size
 * @property \stdClass|null $coordinates
 * @property string|\DateTime $captureDate
 * @property string|\DateTime $createdAt
 * @property string|\DateTime|null $updatedAt
 * @property int|UsersModel $createdBy
 * @property int|UsersModel|null $modifiedBy
 * @property \stdClass|string|null $meta
 * @property \stdClass|null $langData
 */
class ImagesRepositoryMapper extends EntityMapperExtensible
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
        'city' => [
            'type' => 'int',
            'reference_table' => CityMapper::PREFIX_TABLE . CityMapper::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'name',
            'mapper' => CityMapper::class,
        ],
        'author' => [
            'type' => 'text',
        ],
        'description' => [
            'type' => 'text',
            'default' => '',
        ],
        'image' => [
            'type' => 'text',
        ],
        'authorization' => [
            'type' => 'text',
            'null' => true,
            'default' => null,
        ],
        'folder' => [
            'type' => 'text',
        ],
        'resolution' => [
            'type' => 'text',
            'null' => false,
        ],
        'size' => [
            'type' => 'float',
        ],
        'coordinates' => [
            'type' => 'json',
            'null' => true,
        ],
        'captureDate' => [
            'type' => 'datetime',
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

    const TABLE = 'image_repository_images';
    const VIEW_NAME = 'image_repository_images_view';
    const LANG_GROUP = ImagesRepositoryLang::LANG_GROUP;
    const ORDER_BY_PREFERENCE = [
        '`id` DESC',
        '`name` ASC',
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
        'description',
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
     * @return string Una cadena vacía en caso de no estar definido
     */
    public function imageFullPath()
    {
        return $this->image !== null ? basepath($this->image) : '';
    }

    /**
     * @return bool
     */
    public function imageExists()
    {
        return mb_strlen($this->imageFullPath()) > 0 ? file_exists($this->imageFullPath()) : false;
    }

    /**
     * @return bool
     */
    public function imageRemove()
    {
        $removed = $this->imageExists() ? unlink($this->imageFullPath()) : true;
        if (!$removed) {
            throw new \Exception(__(self::LANG_GROUP, 'La imagen no pudo ser removida, intente más tarde o contacte con el soporte.'));
        }
        return $removed;
    }

    /**
     * @return string Una cadena vacía en caso de no estar definido
     */
    public function authorizationFullPath()
    {
        return $this->authorization !== null ? basepath($this->authorization) : '';
    }

    /**
     * @return bool
     */
    public function authorizationExists()
    {
        return mb_strlen($this->authorizationFullPath()) > 0 ? file_exists($this->authorizationFullPath()) : false;
    }

    /**
     * @return bool
     */
    public function authorizationRemove()
    {
        $removed = $this->authorizationExists() ? unlink($this->authorizationFullPath()) : true;
        if (!$removed) {
            throw new \Exception(__(self::LANG_GROUP, 'El consentimiento no pudo ser removida, intente más tarde o contacte con el soporte.'));
        }
        return $removed;
    }

    /**
     * @return bool
     */
    public function folderRemove()
    {
        $pcsUploadDir = get_config('upload_dir');
        $folder = append_to_url(append_to_url($pcsUploadDir, ImagesRepositoryController::UPLOAD_DIR), $this->folder);
        $removed = @rmdir($folder);
        return $removed;
    }

    /**
     * @return string
     */
    public function author()
    {
        return $this->author;
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
    public function captureDateFormat(string $format = null, array $replaceTemplate = [])
    {
        $captureDate = is_object($this->captureDate) ? $this->captureDate : new \DateTime($this->captureDate);
        $format = is_string($format) ? $format : get_default_format_date();
        $formated = localeDateFormat($format, $captureDate, $replaceTemplate);
        return $formated;
    }

    /**
     * @param string $format
     * @param array $replaceTemplate Para remplazar contenido dentro del formato, el array debe ser ['VALOR_A_REEMPLAZAR' => 'VALOR_DE_REEMPLAZO']
     * @return string
     */
    public function createdAtFormat(string $format = null, array $replaceTemplate = [])
    {
        $createdAt = is_object($this->createdAt) ? $this->createdAt : new \DateTime($this->createdAt);
        $format = is_string($format) ? $format : get_default_format_date();
        $formated = localeDateFormat($format, $createdAt, $replaceTemplate);
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
                $url = ImagesRepositoryController::routeName('single', ['slug' => $this->getSlug($lang)]);
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

        $this->createdAt = new \DateTime();
        $this->createdBy = getLoggedFrameworkUser()->id;
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
        if (!$noDateUpdate) {
            $this->modifiedBy = getLoggedFrameworkUser()->id;
            $this->updatedAt = new \DateTime();
        }
        return parent::update();
    }

    /**
     * @param string $nullString
     * @param bool $asString
     * @return \stdClass|string|null
     * Valores posibles:
     * - Un objeto con las propiedades tipo float lng y lat
     * - Un string con el formato lng, lat
     * - Si es nulo devuelve NULL o el valor provisto en $nullString
     */
    public function getCoordinates(string $nullString = null, bool $asString = false)
    {

        $result = $nullString;
        $coordinates = $this->coordinates;

        if ($coordinates !== null) {

            if ($asString) {
                $result = "{$coordinates->lng}, {$coordinates->lat}";
            } else {
                $result = $coordinates;
            }

        }

        return $result;
    }

    /**
     * @return string
     */
    public function getPreviewImagePath()
    {
        return ImagesRepositoryController::routeName('image-preview', [
            'id' => $this->id,
        ]);
    }

    /**
     * @param bool $withExtension
     * @return string
     */
    public function getFriendlyImageName(bool $withExtension = true)
    {
        $firstSegment = 'IMG';
        $idPadding = str_pad((string) (!is_null($this->id) ? $this->id : -1), 5, '0', \STR_PAD_LEFT);
        $extension = $this->imageExtension();
        $name = "{$firstSegment}_{$idPadding}";
        if ($withExtension) {
            $name .= ".{$extension}";
        }
        return $name;
    }

    /**
     * @param string $mode
     * @param int $modeValue
     * @return string
     */
    public function getImagePublicURL(string $mode = null, int $modeValue = null)
    {
        $imagePath = ImagesRepositoryController::routeName('image-friendly', [
            'name' => $this->getFriendlyImageName(),
        ]);

        $params = [];

        if ($mode !== null) {
            $params[] = "mode={$mode}";
            if ($modeValue !== null) {
                $params[] = "modeValue={$modeValue}";
            }
        }

        if (!empty($params)) {
            $imagePath .= "?" . implode('&', $params);
        }

        return rtrim($imagePath, '/');
    }

    /**
     * @param bool $withExtension
     * @return string
     */
    public function getFriendlyAuthorizationName(bool $withExtension = true)
    {
        $firtsSegment = 'AUT';
        $idPadding = str_pad((string) (!is_null($this->id) ? $this->id : -1), 5, '0', \STR_PAD_LEFT);
        $extension = $this->authorizationExtension();
        $name = "{$firtsSegment}_{$idPadding}";
        if ($withExtension) {
            $name .= ".{$extension}";
        }
        return $name;
    }

    /**
     * @return string
     */
    public function getAuthorizationPublicURL()
    {
        $authorizationPath = '';

        if ($this->hasAuthorization()) {
            $authorizationPath = ImagesRepositoryController::routeName('authorization-friendly', [
                'name' => $this->getFriendlyAuthorizationName(),
            ]);
        }

        return rtrim($authorizationPath, '/');
    }

    /**
     * @param bool $withExtension
     * @return string|null
     */
    public function authorizationName(bool $withExtension = true)
    {
        $name = null;
        if ($this->hasAuthorization()) {
            $extension = $this->authorizationExtension();
            $name = basename($this->authorization);
            if (!$withExtension) {
                $name = str_replace(".{$extension}", '', $name);
            }
        }
        return $name;
    }

    /**
     * @return string|null
     */
    public function authorizationExtension()
    {
        $extension = null;
        if ($this->hasAuthorization()) {
            $extension = mb_strtolower(pathinfo($this->authorization, \PATHINFO_EXTENSION));
        }
        return $extension;
    }

    /**
     * @return string|null
     */
    public function authorizationIconByExtension()
    {
        $icon = 'file outline';
        if ($this->hasAuthorization()) {

            $extension = $this->authorizationExtension();
            $pdf = ['pdf'];
            $excel = ['xlsx', 'xlx', 'xls'];
            $word = ['doc', 'docx'];

            if (in_array($extension, $pdf)) {
                $icon = 'file outline pdf';
            } elseif (in_array($extension, $excel)) {
                $icon = 'file outline excel';
            } elseif (in_array($extension, $word)) {
                $icon = 'file outline word';
            }

        }
        return $icon;
    }

    /**
     * @param bool $withExtension
     * @return string
     */
    public function imageName(bool $withExtension = true)
    {
        $extension = $this->imageExtension();
        $name = basename($this->image);
        if (!$withExtension) {
            $name = str_replace(".{$extension}", '', $name);
        }
        return $name;
    }

    /**
     * @return string
     */
    public function imageExtension()
    {
        $extension = mb_strtolower(pathinfo($this->image, \PATHINFO_EXTENSION));
        return $extension;
    }

    /**
     * @return bool
     */
    public function hasAuthorization()
    {
        return is_string($this->authorization) && mb_strlen(trim($this->authorization)) > 0;
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

        $mapper = new ImagesRepositoryMapper;

        $defaultLang = Config::get_default_lang();
        $currentLang = Config::get_lang();

        $table = self::TABLE;
        $view = self::VIEW_NAME;

        $fields = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
            "(SELECT {$view}.imageYear FROM {$view} WHERE {$view}.id = {$table}.id) AS imageYear",
            "(SELECT lc.name FROM locations_cities AS lc WHERE lc.id = {$table}.city) AS cityName",
            "(SELECT lc.state FROM locations_cities AS lc WHERE lc.id = {$table}.city) AS stateID",
            "(SELECT ls.name FROM locations_states AS ls WHERE ls.id = stateID) AS stateName",
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
     * @param \stdClass|ImagesRepositoryMapper|int $elementOrID
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

        if ($elementOrID instanceof ImagesRepositoryMapper && $elementOrID->id !== null) {

            $uniqid = $elementOrID->preferSlug !== null ? $elementOrID->preferSlug : self::getEncryptIDForSlug($elementOrID->id);
            $title = 'photo';

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
     * @return int
     */
    public static function countAll()
    {
        $model = self::model();
        $model->select("COUNT(id) AS total");

        $model->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];
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
     * @param int $userID
     * @return int[]
     */
    public static function getYears()
    {
        $model = self::model();

        $imagesView = self::VIEW_NAME;
        $model->setTable($imagesView);
        $model->select("imageYear");
        $model->groupBy('imageYear');
        $model->orderBy('imageYear DESC');

        $model->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        $result = array_map(function ($e) {
            return (int) $e->imageYear;
        }, $result);

        return $result;
    }

    /**
     * @param int $userID
     * @return int[]
     */
    public static function getStates()
    {
        $model = self::model();

        $imagesView = self::VIEW_NAME;
        $model->setTable($imagesView);
        $model->select("stateID, stateName");
        $model->groupBy('stateID');
        $model->orderBy('stateID DESC');

        $model->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];
        $resultToReturn = [];

        foreach ($result as $element) {
            $resultToReturn[(int) $element->stateID] = $element->stateName;
        }

        return $resultToReturn;
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
     * Devuelve el mapeador desde un objeto
     *
     * @param \stdClass $element
     * @return ImagesRepositoryMapper|null
     */
    public static function objectToMapper(\stdClass $element)
    {

        $element = (array) $element;
        $mapper = new ImagesRepositoryMapper;
        $fieldsFilleds = [];
        $fields = array_merge(array_keys($mapper->fields), array_keys($mapper->getMetaProperties()));

        $defaultPropertiesValues = [
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
                if ($mapper->preferSlug === null && $mapper->id !== null) {
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
        return (new ImagesRepositoryMapper)->getModel();
    }
}
