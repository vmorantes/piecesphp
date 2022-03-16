<?php

/**
 * NewsletterSuscriberMapper.php
 */

namespace Newsletter\Mappers;

use App\Model\UsersModel;
use Newsletter\NewsletterLang;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;

/**
 * NewsletterSuscriberMapper.
 *
 * @package     Newsletter\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 * @property int|null $id
 * @property string $name
 * @property string $email
 * @property int $acceptUpdates
 * @property string|\DateTime $createdAt
 * @property string|\DateTime $updatedAt
 * @property \stdClass|string|null $meta
 * @property \stdClass|null $langData
 */
class NewsletterSuscriberMapper extends EntityMapperExtensible
{

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'name' => [
            'type' => 'text',
            'null' => false,
            'default' => self::UNSPECIFIED_VALUE,
        ],
        'email' => [
            'null' => false,
            'type' => 'text',
            'default' => self::UNSPECIFIED_VALUE,
        ],
        'acceptUpdates' => [
            'type' => 'int',
            'default' => self::ACCEPT_UPDATES_NO,
            'null' => false,
        ],
        'createdAt' => [
            'type' => 'datetime',
            'default' => 'timestamp',
            'null' => false,
        ],
        'updatedAt' => [
            'type' => 'datetime',
            'null' => true,
        ],
        'meta' => [
            'type' => 'json',
            'null' => true,
            'default' => null,
        ],
    ];

    const ACCEPT_UPDATES_YES = 1;
    const ACCEPT_UPDATES_NO = 0;
    const UNSPECIFIED_VALUE = 'Sin especificar';

    const CAN_DELETE_ALL = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];

    const TABLE = 'newsletter_sucribers';
    const LANG_GROUP = NewsletterLang::LANG_GROUP;
    const ORDER_BY_PREFERENCE = [
        '`id` DESC',
        '`name` ASC',
        '`email` ASC',
        '`acceptUpdates` DESC',
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
        parent::__construct($value, $fieldCompare);
    }

    /**
     * @return string
     */
    public function nameDisplay()
    {
        $value = $this->name;
        if ($value == self::UNSPECIFIED_VALUE) {
            $value = __(self::LANG_GROUP, $value);
        }
        return $value;

    }

    /**
     * @return string
     */
    public function emailDisplay()
    {
        $value = $this->email;
        return $value;
    }

    /**
     * @return string
     */
    public function acceptUpdatesDisplay()
    {
        $value = $this->acceptUpdates == self::ACCEPT_UPDATES_YES ? __(self::LANG_GROUP, 'Sí') : __(self::LANG_GROUP, 'No');
        return $value;
    }

    /**
     * @return string
     */
    public function createdAtDisplay()
    {
        $value = $this->createdAt->format('d-m-Y');
        return $value;
    }

    /**
     * @return string
     */
    public function updatedAtDisplay()
    {
        $value = $this->updatedAt !== null ? $this->updatedAt->format('d-m-Y') : __(self::LANG_GROUP, 'Sin modificar');
        return $value;
    }

    /**
     * @return bool
     */
    public function acceptUpdates()
    {
        return $this->acceptUpdates == self::ACCEPT_UPDATES_YES;
    }

    /**
     * @param bool $updateOnExists
     * @inheritDoc
     */
    public function save(bool $updateOnExists = false)
    {
        $exists = self::existByEmail($this->email);
        $saveResult = false;

        if (!$exists) {
            $this->createdAt = new \DateTime();
            $saveResult = parent::save();
        } else {

            if ($updateOnExists) {

                return $this->update();

            } else {
                throw new \Exception(strReplaceTemplate(__(self::LANG_GROUP, 'El email "{EMAIL}" ya existe'), [
                    '{EMAIL}' => $this->email,
                ]));
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
            $this->updatedAt = new \DateTime();
        }
        return parent::update();
    }

    /**
     * @return string[]
     */
    public static function fieldsToSelect()
    {

        $mapper = (new NewsletterSuscriberMapper);
        $model = $mapper->getModel();
        $table = $model->getTable();

        $acceptValue = self::ACCEPT_UPDATES_YES;
        $yes = __(self::LANG_GROUP, 'Sí');
        $no = __(self::LANG_GROUP, 'No');

        $fields = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
            "IF({$table}.acceptUpdates = {$acceptValue}, '{$yes}', '{$no}') AS acceptUpdatesDisplay",
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
     * @param string $email
     * @return bool
     */
    public static function existByEmail(string $email)
    {
        $model = self::model();

        $where = [
            "email = '$email'",
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
     * @return static|null
     */
    public static function objectToMapper(\stdClass $element)
    {

        $element = (array) $element;
        $mapper = new NewsletterSuscriberMapper;
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

        return $allFilled ? $mapper : null;

    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new NewsletterSuscriberMapper)->getModel();
    }
}
