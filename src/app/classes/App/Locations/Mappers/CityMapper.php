<?php

/**
 * CityMapper.php
 */

namespace App\Locations\Mappers;

use PiecesPHP\Core\BaseEntityMapper;

/**
 * CityMapper.
 *
 * Mapper de estados
 *
 * @package     App\Locations\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @version     v.1.0
 * @copyright   Copyright (c) 2019
 * @property int $id
 * @property int|StateMapper $state
 * @property string $name
 * @property int $active
 */
class CityMapper extends BaseEntityMapper
{
    const PREFIX_TABLE = 'locations_';
    const TABLE = 'cities';

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
        'state' => [
            'type' => 'int',
            'reference_table' => StateMapper::PREFIX_TABLE . StateMapper::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'name',
            'mapper' => StateMapper::class,
        ],
        'name' => [
            'type' => 'varchar',
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
     * @param int $state_id
     * @param bool $as_mapper
     * @return array|static[]
     */
    public static function getByState(int $state_id, bool $as_mapper = false)
    {

        $query = self::model()->select();

        $query->where([
            'state' => $state_id,
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
     * getByName
     *
     * @param string $name
     * @param bool $as_mapper
     * @param int $state_id
     * @return array|static[]
     */
    public static function getByName(string $name, bool $as_mapper = false, int $state_id = null)
    {

        $query = self::model()->select();

        $where = [];

        $where['name'] = trim($name);

        if ($state_id !== null) {
            $where['state'] = $state_id;
        }

        $query->where($where);

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
     * @param int $state_id
     * @param int $ignore_id
     * @return bool
     */
    public static function isDuplicate(string $name, int $state_id, int $ignore_id)
    {
        $model = self::model();

        $where = trim(implode(' ', [
            "name = '$name' AND ",
            "state = $state_id AND ",
            "id != $ignore_id",
        ]));

        $model->select()->where($where)->execute();

        $result = $model->result();

        return count($result) > 0;
    }

    /**
     * model
     *
     * @return BaseModel
     */
    public static function model()
    {
        return (new static )->getModel();
    }

}
