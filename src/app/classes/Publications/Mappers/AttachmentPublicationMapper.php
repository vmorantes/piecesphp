<?php

/**
 * AttachmentPublicationMapper.php
 */

namespace Publications\Mappers;

use App\Model\UsersModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use Publications\Mappers\PublicationMapper;
use Publications\PublicationsLang;

/**
 * AttachmentPublicationMapper.
 *
 * @package     Publications\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 * @property int|null $id
 * @property int|PublicationMapper $publication
 * @property string $attachmentName
 * @property string $fileLocation
 * @property string $lang
 * @property string $folder
 * @property string|\DateTime $createdAt
 * @property string|\DateTime $updatedAt
 * @property int|UsersModel $createdBy
 * @property int|UsersModel|null $modifiedBy
 * @property int $status
 * @property \stdClass|string|null $meta
 */
class AttachmentPublicationMapper extends EntityMapperExtensible
{
    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'publication' => [
            'type' => 'int',
            'reference_table' => PublicationMapper::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'id',
            'mapper' => PublicationMapper::class,
        ],
        'attachmentName' => [
            'type' => 'text',
        ],
        'fileLocation' => [
            'type' => 'text',
        ],
        'lang' => [
            'type' => 'text',
        ],
        'folder' => [
            'type' => 'text',
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
            'default' => self::STATUS_ACTIVE,
        ],
        'meta' => [
            'type' => 'json',
            'null' => true,
            'dafault' => null,
        ],
    ];

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    const CAN_DELETE_ALL = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN_GRAL,
    ];

    const TABLE = 'publications_attachments';
    const LANG_GROUP = PublicationsLang::LANG_GROUP;
    const ORDER_BY_PREFERENCE = [
        '`id` ASC',
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
        parent::__construct($value, $fieldCompare);
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
        $formated = $this->updatedAt instanceof \DateTime  ? localeDateFormat($format, $this->updatedAt, $replaceTemplate) : null;
        return $formated;
    }

    /**
     * @return bool
     */
    public function fileExists()
    {

        $hasFile = $this->id !== null && mb_strlen($this->fileLocation) > 2;

        if ($hasFile) {
            $filePath = basepath($this->fileLocation);
            return file_exists($filePath);
        } else {
            return false;
        }

    }

    /**
     * @return string|null
     */
    public function getExtension()
    {

        $hasFile = $this->fileExists();

        if ($hasFile) {
            $filePath = basepath($this->fileLocation);
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            return $extension;
        } else {
            return null;
        }

    }

    /**
     * @return string|null
     */
    public function getMimeType()
    {

        $hasFile = $this->fileExists();

        if ($hasFile) {
            $fileInformation = finfo_open(FILEINFO_MIME_TYPE);
            $filePath = basepath($this->fileLocation);
            $mimeType = finfo_file($fileInformation, $filePath);
            return $mimeType;
        } else {
            return null;
        }

    }

    /**
     * @return string|null
     */
    public function fileIsImage()
    {

        $result = false;

        if ($this->fileExists()) {
            $result = strpos($this->getMimeType(), 'image/') !== false;
        }

        return $result;

    }

    /**
     * @inheritDoc
     */
    public function save()
    {

        $this->createdAt = new \DateTime();
        if ($this->createdBy === null) {
            $this->createdBy = getLoggedFrameworkUser()->id;
        }
        $saveResult = parent::save();

        if ($saveResult) {
            $idInserted = $this->getInsertIDOnSave();
            $this->id = $idInserted;
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
     * Campos:
     * - idPadding
     * @return string[]
     */
    protected static function fieldsToSelect()
    {

        $mapper = new AttachmentPublicationMapper;
        $model = $mapper->getModel();
        $table = $model->getTable();

        $fields = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
            "{$table}.meta",
        ];

        $allFields = array_keys($mapper->getFields());

        foreach ($allFields as $field) {
            $fields[] = "{$table}.{$field}";
        }

        return $fields;

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
     * @param mixed $value
     * @param bool $asMapper
     * @param bool $currentLang
     * @param string $lang
     *
     * @return static[]|array
     */
    public static function allBy(string $column, $value, bool $asMapper = false, bool $currentLang = false, string $lang = null)
    {
        $model = self::model();

        $where = [
            $column => $value,
        ];

        if ($currentLang) {
            $where['lang'] = Config::get_lang();
        }
        if ($lang !== null) {
            $where['lang'] = $lang;
        }

        $model->select()->where($where)->execute();

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
     * @param int $publicationID
     * @param int $attachmentID
     * @param string $lang
     * @param boolean $asMapper
     * @return static|object|null
     */
    public static function getExactAttachment(int $publicationID, int $attachmentID, string $lang, bool $asMapper = false)
    {
        $model = self::model();

        $where = [
            'publication' => $publicationID,
            'id' => $attachmentID,
            'lang' => $lang,
        ];

        $model->select()->where($where);

        $model->execute();

        $result = $model->result();

        $result = !empty($result) ? $result[0] : null;

        if (!is_null($result) && $asMapper) {
            $result = new AttachmentPublicationMapper($result->id);
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

            $statusActive = self::STATUS_ACTIVE;
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
     * @param int $publicationID
     * @param string $lang
     * @param int $ignoreID
     * @return bool
     */
    public static function existsByPublication(int $publicationID, string $lang = null, int $ignoreID = null)
    {

        $ignoreID = $ignoreID !== null ? $ignoreID : -1;
        $model = self::model();

        $where = [
            "publication = {$publicationID} AND",
            "id != {$ignoreID}",
        ];

        if ($lang !== null) {
            $where[] = "AND `lang` = '{$lang}'";
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
     * @return AttachmentPublicationMapper|null
     */
    public static function objectToMapper(\stdClass $element)
    {

        $element = (array) $element;
        $mapper = new AttachmentPublicationMapper;
        $fieldsFilleds = [];
        $fields = array_merge(array_keys($mapper->fields), array_keys($mapper->getMetaProperties()));

        $defaultPropertiesValues = [
        ];

        foreach ($defaultPropertiesValues as $defaultProperty => $defaultPropertyValue) {
            if (!array_key_exists($defaultProperty, $element)) {
                $element[$defaultProperty] = $defaultPropertyValue;
            }
        }

        foreach ($element as $property => $value) {

            if (in_array($property, $fields)) {

                if ($property == 'meta') {

                    $value = $value instanceof \stdClass  ? $value : @json_decode($value);

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
        return (new AttachmentPublicationMapper)->getModel();
    }
}
