<?php

/**
 * PointMapper.php
 */

namespace App\Locations\Mappers;

use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\Database\ActiveRecordModel;

/**
 * PointMapper.
 *
 * Mapper de puntos
 *
 * @package     App\Locations\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 * @property int $id
 * @property int|CityMapper $city
 * @property string $address
 * @property string $name
 * @property float $latitude
 * @property float $longitude
 * @property int $active
 */
class PointMapper extends BaseEntityMapper
{
    const PREFIX_TABLE = 'locations_';
    const TABLE = 'points';

    const ACTIVE = 1;
    const INACTIVE = 0;

    const STATUS = [
        self::ACTIVE => 'Activa',
        self::INACTIVE => 'Inactiva',
    ];

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'city' => [
            'type' => 'int',
            'reference_table' => CityMapper::PREFIX_TABLE . CityMapper::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'name',
            'mapper' => CityMapper::class,
        ],
        'name' => [
            'type' => 'varchar',
        ],
        'address' => [
            'type' => 'text',
        ],
        'latitude' => [
            'type' => 'float',
        ],
        'longitude' => [
            'type' => 'float',
        ],
        'active' => [
            'type' => 'int',
            'default' => self::ACTIVE,
        ],
    ];

    /**
     * $table
     *
     * @var string
     */
    protected $table = self::PREFIX_TABLE . self::TABLE;

    /**
     * __construct
     *
     * @param int $value
     * @param string $field_compare
     * @return static
     */
    public function __construct(int $value = null, string $field_compare = 'id')
    {
        parent::__construct($value, $field_compare);
    }

    /**
     * getByState
     *
     * @param int $city_id
     * @param bool $as_mapper
     * @return array|static[]
     */
    public static function getByCity(int $city_id, bool $as_mapper = false)
    {

        $query = self::model()->select();

        $query->where([
            'city' => $city_id,
        ]);

        $result = $query->result();

        if ($as_mapper) {
            $result = array_map(function ($i) {
                return new static($i->id);
            }, $result);
        }

        return $result;
    }

    /**
     * all
     *
     * @param bool $as_mapper
     *
     * @return static[]|array
     */
    public static function all(bool $as_mapper = false)
    {
        $model = self::model();

        $model->select()->execute();

        $result = $model->result();

        if ($as_mapper) {
            $result = array_map(function ($e) {
                return new static($e->id);
            }, $result);
        }

        return $result;
    }

    /**
     * allForSelect
     *
     * @param string $defaultLabel
     * @param string $defaultValue
     * @return array
     */
    public static function allForSelect(string $defaultLabel = '', string $defaultValue = '')
    {
        $defaultLabel = strlen($defaultLabel) > 0 ? $defaultLabel : __('locationBackend', 'Localidades');
        $options = [];
        $options[$defaultValue] = $defaultLabel;

        array_map(function ($e) use (&$options) {
            $options[$e->id] = $e->name;
        }, self::all());

        return $options;
    }

    /**
     * getBy
     *
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
            $result = new static($result->id);
        }

        return $result;
    }

    /**
     * getByID
     *
     * @param int $id
     * @param bool $as_mapper
     * @return static|array|null
     */
    public static function getByID(int $id, bool $as_mapper = false)
    {
        $model = self::model();

        $where = trim(implode(' ', [
            "id = $id",
        ]));

        $model->select()->where($where)->execute();

        $result = $model->result();

        if ($as_mapper) {
            $result = array_map(function ($e) {
                return new static($e->id);
            }, $result);
        }

        return count($result) > 0 ? $result[0] : null;
    }

    /**
     * getByName
     *
     * @param string $name
     * @param bool $as_mapper
     * @param int $city_id
     * @return array|static[]
     */
    public static function getByName(string $name, bool $as_mapper = false, int $city_id = null)
    {

        $query = self::model()->select();

        $where = [];

        $where['name'] = trim($name);

        if ($city_id !== null) {
            $where['city'] = $city_id;
        }

        $query->where($where);

        $query->execute();

        $result = $query->result();

        if ($as_mapper) {
            $result = array_map(function ($i) {
                return new static($i->id);
            }, $result);
        }

        return $result;
    }

    /**
     * isDuplicate
     *
     * @param string $name
     * @param int $city_id
     * @param int $ignore_id
     * @return bool
     */
    public static function isDuplicate(string $name, int $city_id, int $ignore_id)
    {
        $model = self::model();
        $name = \stripslashes($name);
        $name = \addslashes($name);

        $where = trim(implode(' ', [
            "name = '$name' AND ",
            "city = $city_id AND ",
            "id != $ignore_id",
        ]));

        $model->select()->where($where)->execute();

        $result = $model->result();

        return count($result) > 0;
    }

    /**
     * model
     *
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new static )->getModel();
    }

}
