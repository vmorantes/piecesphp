<?php

/**
 * NewsReadedMapper.php
 */

namespace News\Mappers;

use App\Model\UsersModel;
use News\NewsLang;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;

/**
 * NewsReadedMapper.
 *
 * @package     News\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 * @property int|null $id
 * @property int|NewsMapper $news
 * @property int|UsersModel $readerUser
 * @property \stdClass|string|null $meta
 */
class NewsReadedMapper extends EntityMapperExtensible
{

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'news' => [
            'type' => 'int',
            'reference_table' => NewsMapper::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'id',
            'mapper' => NewsMapper::class,
            'null' => true,
        ],
        'readerUser' => [
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
            'default' => null,
        ],
    ];

    const TABLE = 'news_readed_relationship';
    const LANG_GROUP = NewsLang::LANG_GROUP;
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
     * @inheritDoc
     */
    public function save()
    {
        $readerUser = $this->readerUser instanceof UsersModel ? $this->readerUser->id : $this->readerUser;
        $news = $this->news instanceof NewsMapper ? $this->news->id : $this->news;
        $saveResult = true;
        if (!self::exists($readerUser, $news)) {
            $saveResult = parent::save();
        }
        return $saveResult;
    }

    /**
     * @inheritDoc
     */
    public function update()
    {
        return true;
    }

    /**
     * @param int $readerUserID
     * @param int $newsID
     * @return bool
     */
    public static function addRecord(int $readerUserID, int $newsID)
    {
        $mapper = new self;
        $mapper->readerUser = $readerUserID;
        $mapper->news = $newsID;
        return $mapper->save();
    }

    /**
     * @return string[]
     */
    protected static function fieldsToSelect(string $formatDate = null)
    {
        $mapper = (new NewsReadedMapper);
        $model = $mapper->getModel();
        $table = $model->getTable();
        $fields = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
            "{$table}.meta",
        ];
        $fieldsNames = array_keys($mapper->getFields());
        foreach ($fieldsNames as $field) {
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
    public static function exists(int $readerUserID, int $newsID)
    {
        $model = self::model();

        $where = [
            "news = {$newsID}",
            "AND readerUser = {$readerUserID}",
        ];

        $where = trim(implode(' ', $where));

        $model->select()->where($where);

        $model->execute();

        $result = $model->result();

        return !empty($result);
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
     * @return NewsReadedMapper|null
     */
    public static function objectToMapper(\stdClass $element)
    {

        $element = (array) $element;
        $mapper = new NewsReadedMapper;
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

        return $allFilled ? $mapper : null;

    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new NewsReadedMapper)->getModel();
    }
}
