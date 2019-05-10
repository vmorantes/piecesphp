<?php

/**
 * CountryMapper.php
 */

namespace App\Locations\Mappers;

use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\BaseModel;

/**
 * StateMapper.
 *
 * Mapper de estados
 *
 * @package     App\Locations\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @version     v.1.0
 * @copyright   Copyright (c) 2019
 * @property int $id
 * @property int|CountryMapper $country
 * @property string $name
 * @property int $active
 */
class StateMapper extends BaseEntityMapper
{
    const PREFIX_TABLE = 'locations_';
    const TABLE = 'states';

    const ACTIVE = 1;
    const INACTIVE = 0;

    const STATUS = [
        self::ACTIVE => 'Activo',
        self::INACTIVE => 'Inactivo',
    ];

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'country' => [
            'type' => 'int',
            'reference_table' => CountryMapper::PREFIX_TABLE . CountryMapper::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'name',
            'mapper' => CountryMapper::class,
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
    public function __construct(int $value = null, string $field_compare = 'primary_key')
    {
        parent::__construct($value, $field_compare);
    }

    /**
     * getByCountry
     *
     * @param int $country_id
     * @param bool $as_mapper
     * @return array|static[]
     */
    public static function getByCountry(int $country_id, bool $as_mapper = false)
    {

        $query = self::model()->select();

        $query->where([
            'country' => $country_id,
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
     * @param int $country_id
     * @return array|static[]
     */
    public static function getByName(string $name, bool $as_mapper = false, int $country_id = null)
    {

        $query = self::model()->select();

        $where = [];

        $where['name'] = trim($name);

        if ($country_id !== null) {
            $where['country'] = $country_id;
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
     * @param int $country_id
     * @param int $ignore_id
     * @return bool
     */
    public static function isDuplicate(string $name, int $country_id, int $ignore_id)
    {
        $model = self::model();

        $where = trim(implode(' ', [
            "name = '$name' AND ",
            "country = $country_id AND ",
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
